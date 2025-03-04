<?php
/**
 * StringHelper.php
 *
 * Copyright 2020 SchedulesPlus LLC
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */
namespace Cope;

/**
 * String helpers
 */
class StringHelper {
    
    /**
     * Return true if haystack ends with needle.
     * @param string|null $haystack
     * @param string|null $needle
     * @param boolean $ci true for case insensitive comparison
     * @return boolean
     */
    public static function endsWith(?string $haystack, ?string $needle, $ci = false): bool {
        if(null === $haystack && null === $needle) return true;
        if(null === $haystack) return false;
        if(null === $needle || 0 === ($len = strlen($needle))) return true;
        return (0 === substr_compare($haystack,$needle,-$len,$len,$ci));
    }
    
    /**
     * Return true if haystack begins with needle.
     * @param string $haystack
     * @param string $needle
     * @param bool $case_insensitive
     * @return bool
     */
    public static function startsWith(string $haystack, string $needle, bool $case_insensitive = false): bool {
        $length = strlen($needle);
        return (0 === substr_compare($haystack, $needle, 0, $length, $case_insensitive));
    }
    
    /**
     * Trim white space from a value or array of values.
     * @param string|string[] $value
     * @return string|string[]
     */
    public static function trimAll($value) {
        return is_array($value)
            ? array_map('StringHelper::trimAll', $value)
            : trim($value);
    }
}