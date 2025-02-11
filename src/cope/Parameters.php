<?php

namespace Cope;

class Parameters extends ArrayMap {
    /**
     * Returns the identified parameter or null if the key does not exist.
     * A delimiter can be used to specify a path to the value.
     * @param string $name
     * @param mixed $default
     * @param $delimiter
     * @return array|mixed|mixed|null
     */
    public function get(string $name, $default = null, string $delimiter = '.') {
        if (strpos($name, $delimiter) !== false) {
            return parent::get($name, $default);
        }
        $value = $this->array;
        $key = strtok($name, $delimiter);
        while($key !== false) {
            if(isset($value[$key])) {
                $value = $value[$key];
            } else {
                return $default;
            }
            $key = strtok($delimiter);
        }
        return $value;
    }
}
