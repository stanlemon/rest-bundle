<?php
namespace Lemon\RestBundle\DependencyInjection\Compiler;

use Lemon\RestBundle\Annotation\Resource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class RegisterResourcePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');
        $reader   = $container->get('annotation_reader');
        $registry = $container->getDefinition('lemon.rest.object_registry');

        foreach ($bundles as $name => $bundle) {
            $reflection = new \ReflectionClass($bundle);

            $baseNamespace = $reflection->getNamespaceName() . '\Entity\\';
            $dir = dirname($reflection->getFileName()) . '/Entity';

            if (is_dir($dir)) {
                $finder = new Finder();

                $iterator = $finder
                    ->files()
                    ->name('*.php')
                    ->size('>= 1K')
                    ->in($dir)
                ;

                /** @var SplFileInfo $file */
                foreach ($iterator as $file) {
                    // Translate the directory path from our starting namespace forward
                    $expandedNamespace = substr($file->getPath(), strlen($dir) + 1);

                    // If we are in a sub namespace add a trailing separation
                    $expandedNamespace = $expandedNamespace == false ? '' : $expandedNamespace . '\\';

                    $className = $baseNamespace . $expandedNamespace .  $file->getBasename('.php');

                    if (class_exists($className)) {
                        $reflectionClass = new \ReflectionClass($className);

                        foreach ($reader->getClassAnnotations($reflectionClass) as $annotation) {
                            if ($annotation instanceof Resource) {
                                $name = $annotation->name ?: lcfirst($reflectionClass->getShortName());

                                $registry->addMethodCall('addClass', [$name, $className]);
                            }
                        }
                    }
                }
            }
        }
    }
}
