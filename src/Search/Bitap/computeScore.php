<?php

namespace Fuse\Search\Bitap;

use function Fuse\Core\config;

function computeScore(string $pattern, array $options = [])
{
    $errors = $options['errors'] ?? 0;
    $currentLocation = $options['currentLocation'] ?? 0;
    $expectedLocation = $options['expectedLocation'] ?? 0;
    $distance = $options['distance'] ?? config('distance');
    $ignoreLocation = $options['ignoreLocation'] ?? config('ignoreLocation');

    $accuracy = $errors / mb_strlen($pattern);

    if ($ignoreLocation) {
        return $accuracy;
    }

    $proximity = abs($expectedLocation - $currentLocation);

    if (!$distance) {
        // Dodge divide by zero error.
        return $proximity ? 1.0 : $accuracy;
    }

    return $accuracy + $proximity / $distance;
}
