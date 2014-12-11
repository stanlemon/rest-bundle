<?php
namespace Lemon\RestBundle\Object;

class Definition
{
    protected $name;
    
    protected $class;
    
    /**
     * @var bool
     */
    protected $search = true;

    /**
     * @var bool
     */
    protected $create = true;

    /**
     * @var bool
     */
    protected $update = true;

    /**
     * @var bool
     */
    protected $delete = true;
    
    /**
     * @var bool
     */
    protected $partialUpdate = true;

    public function __construct(
        $name,
        $class,
        $search = true,
        $create = true,
        $update = true,
        $delete = true,
        $partialUpdate = true
    ) {
        $this->name = $name;
        $this->class = $class;
        $this->search = $search;
        $this->create = $create;
        $this->update = $update;
        $this->delete = $delete;
        $this->partialUpdate = $partialUpdate;
    }

    public function getName()
    {
        return $this->name;
    }
    
    public function getClass()
    {
        return $this->class;
    }
    
    public function canSearch()
    {
        return $this->search;
    }
    
    public function canCreate()
    {
        return $this->create;
    }
    
    public function canUpdate()
    {
        return $this->update;
    }
    
    public function canDelete()
    {
        return $this->delete;
    }
    
    public function canPartialUpdate()
    {
        return $this->partialUpdate;
    }
}
