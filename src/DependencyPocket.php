<?php

/**
 * A Dependency container created to ease dependency management
 * without the need to inject all dependencies via the constructor which
 * makes it difficult and messy to extend a class with many dependencies
 *
 * So with the help of this container you can :
 * - eliminate the big number of dependencies in the constructor
 * - and still manage (add/replace) the dependencies from the subclasses
 * - easy replace and mock the dependencies for testes
 *
 * @author mmghv (Mohamed Gharib)
 * @license MIT
 */

namespace mmghv;

use Exception;
use InvalidArgumentException;

class DependencyPocket
{
    protected $pocket = [];

    /**
     * check whether or not a dependency is defined
     * (and optionally check if it has some value)
     *
     * @param  string  $name
     * @param  boolean $checkValue  set to true to also check if it has a value been set
     *
     * @return boolean
     */
    public function hasDependency($name, $checkValue = false)
    {
        if (! isset($this->pocket[$name])) {
            return false;
        }
        if ($checkValue) {
            return (null !== $this->pocket[$name]['value']);
        } else {
            return true;
        }
    }

    /**
     * define a new dependency of a given type
     *
     * @param  string $name
     * @param  string|object|null $type  the required type, can be
     *                              (primitive type, class/interface name, object instance, null or '')
     *                              set to null or '' to allow any type.
     *
     * @return $this
     *
     * @throws \InvalidArgumentException  if $name is empty
     * @throws \Exception  if dependency already exists
     */
    public function addDependency($name, $type = null)
    {
        if (trim($name) === '') {
            throw new InvalidArgumentException("Dependency should have a name!");
        }

        if ($this->hasDependency($name)) {
            throw new Exception("Dependency [{$name}] already exists!");
        }

        $type = is_object($type) ? get_class($type) : (string) $type;
        $this->pocket[$name] = ['type' => $type, 'value' => null];

        return $this;
    }

    /**
     * define a list of dependencies
     *
     * @param  array $list [name => type] pairs
     *
     * @return $this
     */
    public function addDependencies(array $list)
    {
        foreach ($list as $name => $type) {
            if (is_int($name)) {
                $name = $type;
                $type = null;
            }

            $this->addDependency($name, $type);
        }

        return $this;
    }

    /**
     * set dependency value
     *
     * @param  string $name
     * @param  mixed  $value
     *
     * @return $this
     *
     * @throws \Exception  if dependency not found
     * @throws \InvalidArgumentException  if dependency type mismatch
     */
    public function setDependency($name, $value)
    {
        if (! $this->hasDependency($name)) {
            throw new Exception("Dependency [{$name}] not found, If it's a new one, define it first via 'addDependency'");
        }

        // check that the given dependency is of the required type
        if (null !== $value && $requiredType = $this->pocket[$name]['type']) {
            $givenType = strtolower(gettype($value));

            if ($requiredType != $givenType) {
                if (! is_object($value)) {
                    throw new InvalidArgumentException("Dependency [{$name}] must be of type [{$requiredType}], Variable of type [{$givenType}] was given");
                }

                if (! $value instanceof $requiredType) {
                    $class = get_class($value);
                    throw new InvalidArgumentException("Dependency [{$name}] must be of type [{$requiredType}], Object of type [{$class}] was given");
                }
            }
        }

        // set dependency value
        $this->pocket[$name]['value'] = $value;
        return $this;
    }

    /**
     * set values for a list of dependencies
     *
     * @param  array $list [name => value] pairs
     *
     * @return $this
     */
    public function setDependencies(array $list)
    {
        foreach ($list as $name => $type) {
            $this->setDependency($name, $type);
        }

        return $this;
    }

    /**
     * get dependency value/object
     *
     * @param  string $name
     *
     * @return mixed
     *
     * @throws \Exception  if dependency not found
     */
    public function getDependency($name)
    {
        if (! $this->hasDependency($name)) {
            throw new Exception("Dependency [{$name}] not found, If it's a new one, define it first via 'addDependency'");
        }

        return $this->pocket[$name]['value'];
    }

    /**
     * get list of dependencies as an array
     *
     * @param  array|null $list  list of required dependencies, omit it to get all
     *
     * @return array
     */
    public function getDependencies(array $list = null)
    {
        $dependencies = [];

        foreach (($list ?: array_keys($this->pocket)) as $name) {
            $dependencies[$name] = $this->getDependency($name);
        }

        return $dependencies;
    }

    /**
     * get the registered type of the dependency
     *
     * @param  string $name
     *
     * @return string
     *
     * @throws \Exception  if dependency not found
     */
    public function getDependencyType($name)
    {
        if (! $this->hasDependency($name)) {
            throw new Exception("Dependency [{$name}] not found, If it's a new one, define it first via 'addDependency'");
        }

        return $this->pocket[$name]['type'];
    }
}
