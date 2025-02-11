<?php

namespace Cope;

use Iterator;

/**
 * Parameters hold key value pairs and is intended to have behavior similar
 * to that of a JavaScript object (in order to keep with the Express theme).
 */
class ArrayMap extends ArrayBase
{

    /**
     * Magic method to return a Parameter item as a property.
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) {
        return $this->offsetGet($name);
    }

    /**
     * Magic method to set a collection item as a property.
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value) {
        $this->offsetSet($name, $value);
    }

    /**
     * Add all element from the supplied map or array
     * @param $parameters
     * @return void
     */
    public function addAll( $parameters): void {
        foreach ($parameters as $name => $value) {
            $this->array[$name] = $value;
        }
    }

    /**
     * Associates the specified value with the specified key in this map.
     * The newly assigned value is returned.
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function assign(string $name, $value) {
        return ($this->array[$name] = $value);
    }

    public function get(string $name, $default = null) {
        return $this->offsetGet($name) ?? $default;
    }

    /**
     * Returns true if the named value is set for the collection
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool {
        return $this->offsetExists($name);
    }

    public function keys(): array {
        return array_keys($this->array);
    }

    /**
     * Associates the specified value with the specified key in this map.
     * The previous associated value is returned or null if there was none.
     * @param string $name
     * @param $value
     * @return mixed|null
     */
    public function put(string $name, $value) {
        $previous = $this->array[$name] ?? null;
        $this->array[$name] = $value;
        return $previous;
    }

    public function remember(string $name, callable $fn) {
        return $this->array[$name] ?? ($this->array[$name] = $fn());
    }

    /**
     * Delete a parameter
     * @param string $name
     */
    public function remove(string $name): void {
        $this->offsetUnset($name);
    }

    /**
     * Associates the specified value with the specified key in this map.
     * @param string $name
     * @param mixed $value
     * @return void But will make this fluent at php 8
     */
    public function set(string $name, $value): void {
        $this->array[$name] = $value;
    }

    /**
     * Returns the collection as an array
     * @return array
     */
    public function toArray(): array {
        return $this->array;
    }

    public function values(): array {
        return array_values($this->array);
    }
}