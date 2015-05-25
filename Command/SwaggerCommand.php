<?php
namespace Lemon\RestBundle\Command;

use Lemon\RestBundle\Object\Definition;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SwaggerCommand extends ContainerAwareCommand
{
    protected $input;
    protected $output;
    protected $models = array();

    protected function configure()
    {
        $this
            ->setName('rest:swagger')
            ->setDescription('Generate swagger documentation for your rest api');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        /** @var \Symfony\Component\Routing\Router $router */
        $router = $this->getContainer()->get('router');
        $context = $router->getContext();
        $context->setHost('example.com');
        $context->setScheme('http');

        $main = array(
            "swaggerVersion" => "1.2",
            "basePath" => "/api",
            "apis" => array()
        );
        $objects = array();

        /** @var \Lemon\RestBundle\Object\Registry $objectRegistry */
        $objectRegistry = $this->getContainer()->get('lemon_rest.object_registry');

        foreach ($objectRegistry->all() as $alias => $definition) {
            $main['apis'][] = array(
                //"description" => $class,
                "path" => "/{$alias}",
            );

            $this->models = array();

            $this->makeModel($definition);

            $objects[$alias] = array(
                "swaggerVersion" => "1.2",
                "models" => $this->models,
                "apis" => array(
                    array(
                        "path" => str_replace(
                            '__ID__',
                            '{id}',
                            $router->generate('lemon_rest_get', array('resource' => $alias, 'id' => '__ID__'))
                        ),
                        "operations" => array(
                            array(
                                "method" => "GET",
                                "nickname" => "get" . ucfirst($definition->getName()),
                                "type" => $definition->getName(),
                                "produces" => array(
                                    "application/json",
                                    "application/xml",
                                ),
                            ),
                            array(
                                "method" => "DELETE",
                                "nickname" => "delete" . ucfirst($definition->getName()),
                                "type" => "void",
                            ),
                            array(
                                "method" => "PUT",
                                "nickname" => "put" . ucfirst($definition->getName()),
                                "type" => $definition->getName(),
                                "produces" => array(
                                    "application/json",
                                    "application/xml",
                                ),
                                "consumes" => array(
                                    "application/json",
                                    "application/xml",
                                ),
                            ),
                            array(
                                "method" => "PATCH",
                                "nickname" => "patch" . ucfirst($definition->getName()),
                                "type" => $definition->getName(),
                                "produces" => array(
                                    "application/json",
                                    "application/xml",
                                ),
                                "consumes" => array(
                                    "application/json",
                                    "application/xml",
                                ),
                            ),
                        ),
                    ),
                    array(
                        "path" => $router->generate('lemon_rest_post', array('resource' => $definition->getName())),
                        "operations" => array(
                            array(
                                "method" => "POST",
                                "nickname" => "post" . ucfirst($definition->getName()),
                                "type" => $definition->getName(),
                                "produces" => array(
                                    "application/json",
                                    "application/xml",
                                ),
                                "consumes" => array(
                                    "application/json",
                                    "application/xml",
                                ),
                            ),
                            array(
                                "method" => "GET",
                                "nickname" => "search" . ucfirst($definition->getName()),
                                "type" => "array",
                                "items" => array(
                                    "\$ref" => $definition->getName(),
                                ),
                                "produces" => array(
                                    "application/json",
                                    "application/xml",
                                ),
                            ),
                        ),
                    ),
                ),
            );
        }

        $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES; // | JSON_UNESCAPED_UNICODE;

        file_put_contents(getcwd() . DIRECTORY_SEPARATOR ."api.json", json_encode($main, $options));

        foreach ($objects as $name => $object) {
            file_put_contents(getcwd() . DIRECTORY_SEPARATOR . "api/{$name}", json_encode($object, $options));
        }
    }

    protected function makeModel(Definition $definition)
    {
        /** @var \Lemon\RestBundle\Object\Registry $objectRegistry */
        $objectRegistry = $this->getContainer()->get('lemon_rest.object_registry');

        $reflection = $definition->getReflection();

        if (isset($this->models[$definition->getName()])) {
            return;
        }

        $this->models[$definition->getName()] = array();

        /** @var \Metadata\MetadataFactory $metadataFactory */
        $metadataFactory = $this->getContainer()->get('jms_serializer.metadata_factory');

        $validTypes = array(
            "integer", "long", "float", "double", "string", "byte", "boolean", "date", "dateTime"
        );

        $model = array(
            "id" => $reflection->getShortName(),
            "properties" => array(),
        );

        $classMetadata = $metadataFactory->getMetadataForClass($definition->getClass());

        foreach ($classMetadata->propertyMetadata as $propertyMetadata) {
            if ($propertyMetadata->type == null) {
                $type = "void";
            } elseif ($propertyMetadata->type['name'] == 'DateTime') {
                $type = 'dateTime';
            } elseif (in_array($propertyMetadata->type['name'], $validTypes)) {
                $type = $propertyMetadata->type['name'];
            } else{
                $type = null;
            }

            if ($type) {
                $model['properties'][$propertyMetadata->name] = array(
                    "type" => $type,
                );
            } else {
                if (!empty($propertyMetadata->type['params'])) {
                    $name = $propertyMetadata->type['params'][0]['name'];
                    $def = $objectRegistry->getByClass($name);
                    $this->makeModel($def);

                    $complex = new \ReflectionClass($propertyMetadata->type['params'][0]['name']);

                    $model['properties'][$propertyMetadata->name] = array(
                        "type" => 'array',
                        "items" => array(
                            "\$ref" => $complex->getShortName(),
                        ),
                    );
                } else {
                    $name = $propertyMetadata->type['name'];

                    if ($objectRegistry->hasClass($name)) {
                        $def = $objectRegistry->getByClass($name);
                        $this->makeModel($def);
                    }

                    $complex = new \ReflectionClass($propertyMetadata->type['name']);

                    $model['properties'][$propertyMetadata->name] = array(
                        "\$ref" => $complex->getShortName(),
                    );
                }
            }
        }

        $this->models[$definition->getName()] = $model;
    }
}
