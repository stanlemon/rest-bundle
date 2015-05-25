<?php
namespace Lemon\RestBundle\Tests;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    public function registerBundles()
    {
        return array(
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle(),
            new \JMS\SerializerBundle\JMSSerializerBundle(),
            new \Lemon\RestBundle\LemonRestBundle(),
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        if ($this->getEnvironment() == 'test_mongodb') {
            $loader->load(__DIR__ . '/config/config_mongodb.yml');
        } else {
            $loader->load(__DIR__ . '/config/config.yml');
        }
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir() . '/LemonRestBundle/cache';
    }

    public function getLogDir()
    {
        return sys_get_temp_dir() . '/LemonRestBundle/logs';
    }
}
