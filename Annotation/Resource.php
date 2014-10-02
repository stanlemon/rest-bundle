<?php
namespace Lemon\RestBundle\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Resource
{
    /**
     * @var string
     */
    public $name;
}
