<?php

namespace Fuse\Search\Bitap;

use function Fuse\Core\config;

function convertMaskToIndices(array $matchmask = [], ?int $minMatchCharLength = null): array
{
    $minMatchCharLength = $minMatchCharLength ?? config('minMatchCharLength');

    $indices = [];
    $start = -1;
    $end = -1;
    $i = 0;

    for ($len = sizeof($matchmask); $i < $len; $i += 1) {
        $match = $matchmask[$i] ?? null;
        if ($match && $start === -1) {
            $start = $i;
        } elseif (!$match && $start !== -1) {
            $end = $i - 1;
            if ($end - $start + 1 >= $minMatchCharLength) {
                $indices[] = [$start, $end];
            }
            $start = -1;
        }
    }

    // (i-1 - start) + 1 => i - start
    if (($matchmask[$i - 1] ?? false) && $i - $start >= $minMatchCharLength) {
        $indices[] = [$start, $i - 1];
    }

    return $indices;
}
