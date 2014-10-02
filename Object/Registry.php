<?php
namespace Lemon\RestBundle\Object;

class Registry
{
    protected $classes = array();

    /**
     * @param string $name
     * @param string $class
     */
    public function addClass($name, $class)
    {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf("Invalid class \"%s\"", $class));
        }
        $this->classes[$name] = $class;
    }

    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * @param string $name
     * @return string
     */
    public function getClass($name)
    {
        if (!$this->hasClass($name)) {
            throw new \InvalidArgumentException(sprintf("Invalid resource \"%s\"", $name));
        }
        return $this->classes[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasClass($name)
    {
        return array_key_exists($name, $this->classes);
    }
}
