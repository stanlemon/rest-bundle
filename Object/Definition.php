<?php
namespace Lemon\RestBundle\Object;

class Definition
{
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const OPTIONS = 'OPTIONS';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
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

    public function getOptions($isResource = false)
    {
        $options = array(self::OPTIONS);

        if ($this->canCreate() && !$isResource) {
            $options[] = self::POST;
        }
        if ($this->canUpdate() && $isResource) {
            $options[] = self::PUT;
        }
        if ($this->canDelete() && $isResource) {
            $options[] = self::DELETE;
        }
        if ($this->canSearch() && !$isResource) {
            $options[] = self::GET;
        }
        if ($isResource) {
            $options[] = self::GET;
        }

        return $options;
    }
}
