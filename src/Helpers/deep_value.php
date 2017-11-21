<?php namespace Fuse\Helpers;

function deep_value($assoc, $path, &$list = [])
{
    if (!$path) {
        // If there's no path left, we've gotten to the object we care about.
        $list[] = $assoc;
    } else {
        $dotIndex = mb_strpos($path, '.');
        $firstSegment = $path;
        $remaining = null;

        if ($dotIndex !== false) {
            $firstSegment = mb_substr($path, 0, $dotIndex);
            $remaining = mb_substr($path, $dotIndex + 1);
        }

        $value = isset($assoc[$firstSegment])
            ? $assoc[$firstSegment]
            : null;

        if (!is_null($value)) {
            if (!$remaining && (is_string($value) || is_int($value) || is_float($value))) {
                $list[] = (string) $value;
            } elseif (is_list($value)) {
                // Search each item in the array.
                for ($i = 0, $len = sizeof($value); $i < $len; $i++) {
                    deep_value($value[$i], $remaining, $list);
                }
            } elseif ($remaining) {
                // An associative array. Recurse further.
                deep_value($value, $remaining, $list);
            }
        }
    }

    return $list;
}
