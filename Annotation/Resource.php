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

    /**
     * @var bool
     */
    public $search = true;

    /**
     * @var bool
     */
    public $create = true;

    /**
     * @var bool
     */
    public $update = true;

    /**
     * @var bool
     */
    public $delete = true;
    
    /**
     * @var bool
     */
    public $partialUpdate = true;
}
