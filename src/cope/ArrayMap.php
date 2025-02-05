<?php

namespace Cope;

/**
 * An object wrapper around an associative array
 */
class ArrayMap extends AbstractArray
{
    /**
     * Create a new collection object
     * @param array|null $array optional array passed by value
     * @param array|null $reference optional array passed by reference
     */
    public function __construct(array $array = null, array &$reference = null) {
        if(isset($array)) {
            $this->array = $array;
        } else if (isset($reference)) {
            $this->array = &$reference;
        } else {
            $this->array = array();
        }
    }

    /**
     * Magic method to return a collection item as a property.
     * @param mixed $name
     * @return mixed
     */
    public function __get($name) {
        return $this->get($name);
    }

    /**
     * Magic method to set a collection item as a property.
     * @param mixed $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        $this->set($name,$value);
    }

    /**
     * Returns true if the named value is set for the collection
     * @param string $name
     * @return boolean
     */
    public function has(string $name): bool {
        return $this->offsetExists($name);
    }

    /**
     * Returns the value for the specified name.
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, $default = null) {
        if(isset($this->array[$name])) {
            return $this->array[$name];
        }
        return $default;
    }

    /**
     * Remove a named value from the collection
     * @param string $name
     */
    public function remove(string $name) {
        $this->offsetUnset($name);
    }

    /**
     * Set a value in the collection
     * @param string $name
     * @param mixed $value
     */
    public function set(string $name,$value) {
        $this->offsetSet($name, $value);
    }
}