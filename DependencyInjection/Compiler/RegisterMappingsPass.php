<?php
namespace Lemon\RestBundle\DependencyInjection\Compiler;

use Doctrine\Common\Inflector\Inflector;
use Lemon\RestBundle\Annotation\Resource;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

class RegisterMappingsPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $mappings = $container->getParameter('lemon_rest_mappings');

        foreach ($mappings as $mapping) {
            if (isset($mapping['dir']) && isset($mapping['prefix'])) {
                $this->mapDirectory($container, $mapping['dir'], $mapping['prefix']);
            } elseif (isset($mapping['class']) && isset($mapping['name'])) {
                $this->mapClass($container, $mapping['class'], $mapping['name']);
            } else {
                throw new \RuntimeException("Invalid mapping configuration!");
            }
        }
    }

    protected function mapDirectory(ContainerBuilder $container, $dir, $prefix)
    {
        if (!is_dir($dir)) {
            throw new \RuntimeException(sprintf("Invalid directory %s", $dir));
        }

        $reader   = $container->get('annotation_reader');
        $registry = $container->getDefinition('lemon_rest.object_registry');

        $finder = new Finder();

        $iterator = $finder
            ->files()
            ->name('*.php')
            ->in($dir)
        ;

        if (substr($prefix, -1, 1) !== "\\") {
            $prefix .= "\\";
        }

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            // Translate the directory path from our starting namespace forward
            $expandedNamespace = substr($file->getPath(), strlen($dir) + 1);

            // If we are in a sub namespace add a trailing separation
            $expandedNamespace = $expandedNamespace === false ? '' : $expandedNamespace . '\\';

            $className = $prefix . $expandedNamespace .  $file->getBasename('.php');

            if (class_exists($className)) {
                $reflectionClass = new \ReflectionClass($className);

                foreach ($reader->getClassAnnotations($reflectionClass) as $annotation) {
                    if ($annotation instanceof Resource) {
                        $name = $annotation->name ?: Inflector::pluralize(
                            lcfirst($reflectionClass->getShortName())
                        );

                        $definition = new Definition('Lemon\RestBundle\Object\Definition', array(
                            $name,
                            $className,
                            $annotation->search,
                            $annotation->create,
                            $annotation->update,
                            $annotation->delete,
                            $annotation->partialUpdate
                        ));

                        $container->setDefinition('lemon_rest.object_resources.' . $name, $definition);

                        $registry->addMethodCall('add', array($definition));
                    }
                }
            }
        }
    }

    protected function mapClass(ContainerBuilder$container, $class, $name)
    {
        // These are explicit name -> class mappings
        if (!class_exists($class)) {
            throw new \RuntimeException(sprintf("Class \"%s\" does not exist", $class));
        }

        $definition = new Definition('Lemon\RestBundle\Object\Definition', array($name, $class));

        $container->setDefinition('lemon_rest.object_resources.' . $name, $definition);

        $registry = $container->getDefinition('lemon_rest.object_registry');
        $registry->addMethodCall('add', array($definition));
    }
}
