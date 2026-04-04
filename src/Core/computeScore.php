<?php

namespace Fuse\Core;

use function Fuse\Core\config;

function computeScoreSingle(&$result, $options = []): void
{
    $ignoreFieldNorm = $options['ignoreFieldNorm'] ?? config('ignoreFieldNorm');
    $totalScore = 1;

    foreach ($result['matches'] as $single_result) {
        $weight = $single_result['key']['weight'] ?? null;
        $totalScore *= pow(
            $single_result['score'] === 0 && $weight ? PHP_FLOAT_EPSILON : $single_result['score'],
            ($weight ?: 1) * ($ignoreFieldNorm ? 1 : $single_result['norm']),
        );
    }

    $result['score'] = $totalScore;
}

// Practical scoring function
function computeScore(&$results, $options = []): void
{
    foreach ($results as &$result) {
        computeScoreSingle($result, $options);
    }
}
