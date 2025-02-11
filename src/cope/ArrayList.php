<?php

namespace Cope;

use OutOfBoundsException;

/**
 * A sequential list
 */
class ArrayList extends ArrayBase
{
    public function add($item): void {
        $this->array[] = $item;
    }

    /**
     * Add all elements from the supplied list.
     * @param ArrayList|array $list
     * @return void
     */
    public function addAll($list): void {
        foreach ($list as $item) {
            $this->array[] = $item;
        }
    }

    public function get(int $index, $default = null) {
        return $this->array[$index] ?? $default;
    }

    public function set(int $index, $value) {
        if ($index < 0 || $index >= count($this->array)) {
            throw new OutOfBoundsException("Index '$index' does not exist in array list.");
        }
        $previous = $this->array[$index];
        $this->array[$index] = $value;
        return $previous;
    }
}