<?php

namespace Fuse\Core;

use function Fuse\Core\config;

// Practical scoring function
function computeScore(&$results, $options = []): void
{
    $ignoreFieldNorm = $options['ignoreFieldNorm'] ?? config('ignoreFieldNorm');

    foreach ($results as &$result) {
        $totalScore = 1;

        foreach ($result['matches'] as $match) {
            $weight = $match['key']['weight'] ?? null;

            $totalScore *= pow(
                $match['score'] === 0 && $weight ? PHP_FLOAT_EPSILON : $match['score'],
                ($weight ?: 1) * ($ignoreFieldNorm ? 1 : $match['norm']),
            );
        }

        $result['score'] = $totalScore;
    }
}
