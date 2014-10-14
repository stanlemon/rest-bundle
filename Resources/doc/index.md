Setting up the bundle
=====================

 1. Add LemonRestBundle to your dependencies:

        // composer.json

        {
           // ...
           "require": {
               // ...
               "stanlemon/rest-bundle": "dev-master@dev"
           }
        }

 2. Use Composer to download and install LemonRestBundle:

        $ php composer.phar update stanlemon/rest-bundle

 3. Register the bundle in your application:

        // app/AppKernel.php

        class AppKernel extends Kernel
        {
            // ...
            public function registerBundles()
            {
                $bundles = array(
                    // ...
                    new Lemon\RestBundle\LemonRestBundle()
                );
            }
        }

 4. Add routing to your routing.yaml
 
        lemon_rest:
            resource: "@LemonRestBundle/Resources/config/routing.yml"
            prefix:   /api

Adding support for your Doctrine entities
=====================

There are currently three ways you can add entities to be used as REST resources.

  1. Use the annotation

        // src/Lemon/TestBundle/Entity/Person.php
        
        namespace Lemon\TestBundle\Entity;;
        
        use Doctrine\ORM\Mapping as ORM;
        use Lemon\RestBundle\Annotation as Rest;
        use Symfony\Component\Validator\Constraints as Assert;
        use JMS\Serializer\Annotation as Serializer;
        
        /**
         * @ORM\Table()
         * @ORM\Entity()
         * @Rest\Resource(name="person")
         */
        class Person
        {
            /**
             * @ORM\Column(name="id", type="integer", nullable=false)
             * @ORM\Id
             * @ORM\GeneratedValue(strategy="IDENTITY")
             */
            public $id;
        
            /**
             * @ORM\Column(name="name", type="string", length=255, nullable=false)
             * @Assert\NotBlank()
             */
            public $name;
        }
        
  2. Use the object registry, retrieve the _lemon_rest.object_registry_ service from the dependency injection container and then
  
        $objectRegistry->addClass('person', 'Lemon\TestBundle\Entity\Person');

  3. Add explicit configuration to your app
   
        lemon_rest:
            mappings:
                - { name: post, class: Lemon\RestDemoBundle\Entity\Post }
                - { name: comment, class: Lemon\RestDemoBundle\Entity\Comment }
                - { name: tag, class: Lemon\RestDemoBundle\Entity\Tag }

    The 'name' refers to the resource, specifically the portion of the endpoint uri that refers to the object. The 'class' should be the fully qualified namespace path of the Doctrine Entity you wish to add to the object registry.

Running the tests
=====================
After installing dependencies with composer (including require-dev) simply Run phpunit

        ./vendor/bin/phpunit -c ./phpunit.xml

The _RestControllerTest_ is a functional test that show cases many of the ways which this bundle can be used.
        
Serialization & Deserialization
=====================

You can custom the serialize/deserialize process of your entities using [JMS Serializer](http://jmsyst.com/libs/serializer), please reference the documentation for specifics, such as accessor methods and exclusions.

        use JMS\Serializer\Annotation as Serializer;
        
        class Person
        {
            /**
             * @Serializer\Exclude()
             */
            public $ssn;
        }

Validation
=====================

The REST bundle uses the [Symfony Validation](http://symfony.com/doc/current/book/validation.html) component to validate entities on _POST_ and _PUT_ operations.  This means that you can easily add validation rules to your REST api by simply annotating your entity (or through yaml/xml configuration).

        use Symfony\Component\Validator\Constraints as Assert;
        
        class Author
        {
            /**
             * @Assert\NotBlank()
             */
            public $name;
        }

Events
=====================

There are several points at which you can tie into the bundle, the following events are available using the event dispatcher

    - lemon_rest.event.pre_search
    - lemon_rest.event.post_search
    - lemon_rest.event.pre_create
    - lemon_rest.event.post_create
    - lemon_rest.event.pre_retrieve
    - lemon_rest.event.post_retrieve
    - lemon_rest.event.pre_update
    - lemon_rest.event.post_update
    - lemon_rest.event.pre_delete
    - lemon_rest.event.post_delete

You can register event listeners and subscribers simply by tagging your service definitions

    lemon_rest.event_listener
    lemon_rest.event_subscriber

Envelopes
=====================

The bundle uses an _Envelope_ object to return the final payload to the serializer. This envelope can be customized so long as it implements the _Lemon\RestBundle\Object\Envelope_ interface.  A default envelope is provided, as well as an envelope that flattens the search results output, this is particularly helpful when using a framework like Restangular. Envelopes are a good way to customize the bundle's output to cater to the needs of your particular consuming client.

To switch to the _FlattenedEnvelope_ (or any custom envelope of your choosing) you would add the following in your app config

    lemon_rest:
        envelope: Lemon\RestBundle\Object\Envelope\FlattenedEnvelope

