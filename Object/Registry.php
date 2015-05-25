<?php
namespace Lemon\RestBundle\Object;

class Registry
{
    /**
     * @var Definition[]
     */
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

    /**
     * @return Definition[]
     */
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
     * @param $className
     * @return Definition
     */
    public function getByClass($className)
    {
        foreach ($this->classes as $definition) {
            if ($definition->getClass() == $className) {
                return $definition;
            }
        }

        throw new \InvalidArgumentException(sprintf(
            "Invalid class \"%s\"",
            $className
        ));

    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->classes);
    }

    /**
     * @param $className
     * @return bool
     */
    public function hasClass($className)
    {
        foreach ($this->classes as $definition) {
            if ($definition->getClass() == $className) {
                return true;
            }
        }
        return false;
    }
}
