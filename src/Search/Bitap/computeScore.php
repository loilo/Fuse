<?php

namespace Fuse\Search\Bitap;

/**
 * @param string $pattern
 * @param int $errors
 * @param int $currentLocation
 * @param int $expectedLocation
 * @param int $distance
 * @param bool $ignoreLocation
 * @return float
 */
function computeScore(
    $pattern,
    $errors,
    $currentLocation,
    $expectedLocation,
    $distance,
    $ignoreLocation
) {
    $accuracy = $errors / mb_strlen($pattern);

    if ($ignoreLocation) {
        return $accuracy;
    }

    $proximity = abs($expectedLocation - $currentLocation);

    if ($distance === 0) {
        // Dodge divide by zero error.
        return $proximity === 0 ? 1.0 : $accuracy;
    }

    return $accuracy + $proximity / $distance;
}
