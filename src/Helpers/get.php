<?php

namespace Fuse\Helpers;

use function Fuse\Helpers\Types\{isArray, isNumber, toString};

/**
 * @return void
 */
function deepGet($obj, array $path, $index, $arrayIndex, &$list, &$arr)
{
    if (is_null($obj)) {
        return;
    }

    if (!isset($path[$index])) {
        // If there's no path left, we've arrived at the object we care about.
        $list[] = !is_null($arrayIndex)
            ? ['v' => $obj, 'i' => $arrayIndex]
            : $obj;
    } else {
        $key = $path[$index];
        $value = $obj[$key] ?? null;

        if (is_null($value)) {
            return;
        }

        // If we're at the last value in the path, and if it's a string/number/bool,
        // add it to the list
        if (
            $index === sizeof($path) - 1 &&
            (is_string($value) || isNumber($value) || is_bool($value))
        ) {
            $list[] = !is_null($arrayIndex)
                ? ['v' => toString($value), 'i' => $arrayIndex]
                : toString($value);
        } elseif (isArray($value)) {
            $arr = true;

            // Search each item in the array.
            for ($i = 0, $len = sizeof($value); $i < $len; $i += 1) {
                deepGet($value[$i], $path, $index + 1, $i, $list, $arr);
            }
        } else {
            // An object. Recurse further.
            deepGet($value, $path, $index + 1, $arrayIndex, $list, $arr);
        }
    }
}

function get($obj, $path)
{
    $list = [];
    $arr = false;

    // Backwards compatibility (since path used to be a string)
    deepGet($obj, is_string($path) ? explode('.', $path) : $path, 0, null, $list, $arr);

    return $arr ? $list : $list[0] ?? null;
}
