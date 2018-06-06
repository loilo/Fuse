<?php namespace Fuse\Bitap;

function score($pattern, $options = [])
{
    $options = array_merge([
        'errors' => 0,
        'currentLocation' => 0,
        'expectedLocation' => 0,
        'distance' => 100
    ], $options);

    $accuracy = $options['errors'] / mb_strlen($pattern);
    $proximity = abs($options['expectedLocation'] - $options['currentLocation']);

    if (!$options['distance']) {
        // Dodge divide by zero error.
        return $proximity ? 1.0 : $accuracy;
    }

    return $accuracy + ($proximity / $options['distance']);
}
