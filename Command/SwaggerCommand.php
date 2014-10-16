<?php
namespace Lemon\RestBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SwaggerCommand extends ContainerAwareCommand
{
    protected $input;
    protected $output;

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

        foreach ($objectRegistry->getClasses() as $alias => $class) {
            $main['apis'][] = array(
                //"description" => $class,
                "path" => "/{$alias}",
            );

            $reflection = new \ReflectionClass($class);

            $this->models = array();

            $this->makeModel($class);

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
                                "nickname" => "get" . ucfirst($alias),
                                "type" => $reflection->getShortName(),
                                "produces" => array(
                                    "application/json",
                                    "application/xml",
                                ),
                            ),
                            array(
                                "method" => "DELETE",
                                "nickname" => "delete" . ucfirst($alias),
                                "type" => "void",
                            ),
                            array(
                                "method" => "PUT",
                                "nickname" => "put" . ucfirst($alias),
                                "type" => $reflection->getShortName(),
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
                                "nickname" => "patch" . ucfirst($alias),
                                "type" => $reflection->getShortName(),
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
                        "path" => $router->generate('lemon_rest_post', array('resource' => $alias)),
                        "operations" => array(
                            array(
                                "method" => "POST",
                                "nickname" => "post" . ucfirst($alias),
                                "type" => $reflection->getShortName(),
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
                                "nickname" => "search" . ucfirst($alias),
                                "type" => "array",
                                "items" => array(
                                    "\$ref" => $reflection->getShortName(),
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

        file_put_contents("../api.json", json_encode($main, $options));

        foreach ($objects as $name => $object) {
            file_put_contents("../api/{$name}", json_encode($object, $options));
        }
    }

    protected $models = array();

    protected function makeModel($class)
    {
        $reflection = new \ReflectionClass($class);

        if (isset($this->models[$reflection->getShortName()])) {
            return;
        }

        $this->models[$reflection->getShortName()] = array();

        /** @var \Metadata\MetadataFactory $metadataFactory */
        $metadataFactory = $this->getContainer()->get('jms_serializer.metadata_factory');

        $validTypes = array(
            "integer", "long", "float", "double", "string", "byte", "boolean", "date", "dateTime"
        );

        $model = array(
            "id" => $reflection->getShortName(),
            "properties" => array(),
        );

        $classMetadata = $metadataFactory->getMetadataForClass($class);

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
                    $this->makeModel($propertyMetadata->type['params'][0]['name']);

                    $complex = new \ReflectionClass($propertyMetadata->type['params'][0]['name']);

                    $model['properties'][$propertyMetadata->name] = array(
                        "type" => 'array',
                        "items" => array(
                            "\$ref" => $complex->getShortName(),
                        ),
                    );
                } else {
                    $this->makeModel($propertyMetadata->type['name']);

                    $complex = new \ReflectionClass($propertyMetadata->type['name']);

                    $model['properties'][$propertyMetadata->name] = array(
                        "\$ref" => $complex->getShortName(),
                    );
                }
            }
        }

        $this->models[$reflection->getShortName()] = $model;
    }
}
