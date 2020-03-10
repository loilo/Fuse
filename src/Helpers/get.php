<?php namespace Fuse\Helpers;

function get($obj, $path)
{
    $list = [];

    $get = function ($obj, $path) use (&$list, &$get) {
        if (!$path) {
            // If there's no $path left, we've gotten to the $object we care about.
            $list[] = $obj;
        } else {
            $dotIndex = strpos($path, '.');

            $key = $path;
            $remaining = null;

            if ($dotIndex !== false) {
                $key = substr($path, 0, $dotIndex);
                $remaining = substr($path, $dotIndex + 1);
            }

            $value = isset($obj[$key]) ? $obj[$key] : null;

            if (!is_null($value)) {
                if (!$remaining && (is_string($value) || is_float($value) || is_int($value))) {
                    $list[] = (string) ($value);
                } elseif (is_list($value)) {
                    // Search each item in the array.
                    foreach ($value as $item) {
                        $get($item, $remaining);
                    }
                } elseif ($remaining) {
                    // An $object. Recurse further.
                    $get($value, $remaining);
                }
            }
        }
    };

    $get($obj, $path);

    return $list;
}