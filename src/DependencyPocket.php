<?php

/**
 * A new form of dependency injection :
 * Manage the class dependencies without the need to inject all
 * dependencies via the constructor which makes it difficult
 * and messy to extend a class with many dependencies.
 *
 * So with the help of this pocket you can :
 * - eliminate the big number of dependencies in the constructor
 * - and still manage (add/replace) the dependencies from the subclasses
 * - easy mock and replace the dependencies for testes
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
    public function has($name, $checkValue = false)
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
     * define a new dependency or array of dependencies of a given type
     *
     * @param  string|array $name  name or array of [name => type] pairs
     * @param  string|object|null $type  the required type, can be
     *                              (primitive type, class/interface name, object instance, null or '')
     *                              set to null or '' to allow any type.
     *
     * @return $this
     *
     * @throws \InvalidArgumentException  if $name is empty
     * @throws \Exception  if dependency already exists
     */
    public function define($name, $type = null)
    {
        if (is_array($name)) {
            foreach ($name as $dName => $dType) {
                if (is_int($dName)) {
                    $dName = $dType;
                    $dType = null;
                }

                $this->defineDependency($dName, $dType);
            }
        } else {
            $this->defineDependency($name, $type);
        }

        return $this;
    }

    /**
     * define a new dependency of a given type
     *
     * @param  string $name
     * @param  string|object|null $type  the required type
     *
     * @throws \InvalidArgumentException  if $name is empty
     * @throws \Exception  if dependency already exists
     */
    protected function defineDependency($name, $type = null)
    {
        if (trim($name) === '') {
            throw new InvalidArgumentException("Dependency should has a name!");
        }

        if ($this->has($name)) {
            throw new Exception("Dependency [$name] already exists!");
        }

        $type = is_object($type) ? get_class($type) : (string) $type;
        $this->pocket[$name] = ['type' => $type, 'value' => null];
    }

    /**
     * set values for a dependency or a list of dependencies
     *
     * @param  string|array $name  name of dependency or [name => value] pairs
     * @param  mixed  $value
     *
     * @return $this
     *
     * @throws \Exception  if dependency not found
     * @throws \InvalidArgumentException  if dependency type mismatch
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $dName => $dValue) {
                $this->setDependency($dName, $dValue);
            }
        } else {
            $this->setDependency($name, $value);
        }

        return $this;
    }

    /**
     * set dependency value
     *
     * @param  string $name
     * @param  mixed  $value
     *
     * @throws \Exception  if dependency not found
     * @throws \InvalidArgumentException  if dependency type mismatch
     */
    protected function setDependency($name, $value)
    {
        if (! $this->has($name)) {
            throw new Exception("Dependency [$name] not found, If it's a new one, define it first via 'define'");
        }

        // check that the given dependency is of the required type
        if (null !== $value && $requiredType = $this->pocket[$name]['type']) {
            $givenType = strtolower(gettype($value));

            if ($requiredType != $givenType) {
                if (! is_object($value)) {
                    throw new InvalidArgumentException("Dependency [$name] must be of type [$requiredType], Variable of type [$givenType] was given");
                }

                if (! $value instanceof $requiredType) {
                    $class = get_class($value);
                    throw new InvalidArgumentException("Dependency [$name] must be of type [$requiredType], Object of type [$class] was given");
                }
            }
        }

        // set dependency value
        $this->pocket[$name]['value'] = $value;
        return $this;
    }

    /**
     * get dependency value or list of dependencies as an array
     *
     * @param  string|array|null $name  dep name or list of required dependencies, omit it to get all
     *
     * @return mixed|array
     *
     * @throws \Exception  if dependency not found
     */
    public function get($name = null)
    {
        $name = $name ?: array_keys($this->pocket);
        $dependencies = [];

        if (is_array($name)) {
            foreach ($name as $dName) {
                $dependencies[$dName] = $this->getDependency($dName);
            }
        } else {
            return $this->getDependency($name);
        }

        return $dependencies;
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
    protected function getDependency($name)
    {
        if (! $this->has($name)) {
            throw new Exception("Dependency [$name] not found, If it's a new one, define it first via 'define'");
        }

        return $this->pocket[$name]['value'];
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
    public function getType($name)
    {
        if (! $this->has($name)) {
            throw new Exception("Dependency [$name] not found, If it's a new one, define it first via 'define'");
        }

        return $this->pocket[$name]['type'];
    }

    /**
     * propert overload : get dependency if found
     *
     * @param  string $name
     *
     * @return mixed
     *
     * @throws \Exception  if dependency name not found
     */
    public function __get($name)
    {
        if ($this->has($name)) {
            return $this->get($name);
        } else {
            throw new \Exception("Undefined property: [$name]");
        }
    }
}
