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

        $classes = $container->get('doctrine')
            ->getEntityManager()
            ->getConfiguration()
            ->getMetadataDriverImpl()
            ->getAllClassNames();

        foreach ($classes as $className) {
            if (!class_exists($className)) {
                continue;
            }

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
