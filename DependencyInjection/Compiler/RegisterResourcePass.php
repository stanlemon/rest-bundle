<?php
namespace Lemon\RestBundle\DependencyInjection\Compiler;

use Lemon\RestBundle\Annotation\Resource;
use Doctrine\Common\Inflector\Inflector;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class RegisterResourcePass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');
        $reader   = $container->get('annotation_reader');
        $registry = $container->getDefinition('lemon_rest.object_registry');

        foreach ($bundles as $name => $bundle) {
            $reflection = new \ReflectionClass($bundle);

            $baseNamespace = $reflection->getNamespaceName() . '\Entity\\';
            $dir = dirname($reflection->getFileName()) . '/Entity';

            if (is_dir($dir)) {
                $finder = new Finder();

                $iterator = $finder
                    ->files()
                    ->name('*.php')
                    ->in($dir)
                ;

                /** @var SplFileInfo $file */
                foreach ($iterator as $file) {
                    // Translate the directory path from our starting namespace forward
                    $expandedNamespace = substr($file->getPath(), strlen($dir) + 1);

                    // If we are in a sub namespace add a trailing separation
                    $expandedNamespace = $expandedNamespace === false ? '' : $expandedNamespace . '\\';

                    $className = $baseNamespace . $expandedNamespace .  $file->getBasename('.php');

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

                                $registry->addMethodCall('addClass', array($definition));
                            }
                        }
                    }
                }
            }
        }
    }
}
