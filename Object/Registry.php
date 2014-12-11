<?php
namespace Lemon\RestBundle\Object;

class Registry
{
    protected $classes = array();

    /**
     * @param Definition $definition
     */
    public function add(Definition $definition)
    {
        if (!class_exists($definition->getClass())) {
            throw new \InvalidArgumentException(sprintf(
                "Invalid class \"%s\"",
                $definition->getClass()
            ));
        }
        $this->classes[$definition->getName()] = $definition;
    }

    public function all()
    {
        return $this->classes;
    }

    /**
     * @param string $name
     * @return Definition
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(sprintf("Invalid resource \"%s\"", $name));
        }
        return $this->classes[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->classes);
    }
}
