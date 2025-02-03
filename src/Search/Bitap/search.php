<?php

namespace Fuse\Search\Bitap;

use Fuse\Exception\PatternLengthTooLargeException;
use Fuse\Search\Bitap\Constants;

use function Fuse\Search\Bitap\computeScore;
use function Fuse\Search\Bitap\convertMaskToIndices;
use function Fuse\Core\config;

/**
 * @return (array|bool|float|int|mixed)[]
 */
function search(string $text, string $pattern, array $patternAlphabet, array $options = []): array
{
    $location = $options['location'] ?? config('location');
    $distance = $options['distance'] ?? config('distance');
    $threshold = $options['threshold'] ?? config('threshold');
    $findAllMatches = $options['findAllMatches'] ?? config('findAllMatches');
    $minMatchCharLength = $options['minMatchCharLength'] ?? config('minMatchCharLength');
    $includeMatches = $options['includeMatches'] ?? config('includeMatches');
    $ignoreLocation = $options['ignoreLocation'] ?? config('ignoreLocation');

    if (mb_strlen($pattern) > Constants::MAX_BITS) {
        throw new PatternLengthTooLargeException(Constants::MAX_BITS);
    }

    $patternLen = mb_strlen($pattern);

    // Set starting location at beginning text and initialize the alphabet.
    $textLen = mb_strlen($text);

    // Handle the case when location > text.length
    $expectedLocation = max(0, min($location, $textLen));

    // Highest score beyond which we give up.
    $currentThreshold = $threshold;

    // Is there a nearby exact match? (speedup)
    $bestLocation = $expectedLocation;

    // Performance: only computer matches when the minMatchCharLength > 1
    // OR if `includeMatches` is true.
    $computeMatches = $minMatchCharLength > 1 || $includeMatches;

    // A mask of the matches, used for building the indices
    $matchMask = $computeMatches ? [$textLen] : [];

    // Get all exact matches, here for speed up
    while (($index = mb_strpos($text, $pattern, $bestLocation)) !== false) {
        $score = computeScore($pattern, [
            'currentLocation' => $index,
            'expectedLocation' => $expectedLocation,
            'distance' => $distance,
            'ignoreLocation' => $ignoreLocation,
        ]);

        $currentThreshold = min($score, $currentThreshold);
        $bestLocation = $index + $patternLen;

        if ($computeMatches) {
            $i = 0;
            while ($i < $patternLen) {
                $matchMask[$index + $i] = 1;
                $i += 1;
            }
        }
    }

    // Reset the best location
    $bestLocation = -1;

    $lastBitArr = [];
    $finalScore = 1;
    $binMax = $patternLen + $textLen;

    $mask = 1 << $patternLen - 1;

    for ($i = 0; $i < $patternLen; $i += 1) {
        // Scan for the best match; each iteration allows for one more error.
        // Run a binary search to determine how far from the match location we can stray
        // at this error level.
        $binMin = 0;
        $binMid = $binMax;

        while ($binMin < $binMid) {
            $score = computeScore($pattern, [
                'errors' => $i,
                'currentLocation' => $expectedLocation + $binMid,
                'expectedLocation' => $expectedLocation,
                'distance' => $distance,
                'ignoreLocation' => $ignoreLocation,
            ]);

            if ($score <= $currentThreshold) {
                $binMin = $binMid;
            } else {
                $binMax = $binMid;
            }

            $binMid = floor(($binMax - $binMin) / 2 + $binMin);
        }

        // Use the result from this iteration as the maximum for the next.
        $binMax = $binMid;

        $start = max(1, $expectedLocation - $binMid + 1);
        $finish = $findAllMatches
            ? $textLen
            : min($expectedLocation + $binMid, $textLen) + $patternLen;

        // Initialize the bit array
        $bitArr = [$finish + 2];

        $bitArr[$finish + 1] = (1 << $i) - 1;

        for ($j = $finish; $j >= $start; $j -= 1) {
            $currentLocation = $j - 1;
            $charMatch = $patternAlphabet[mb_substr($text, $currentLocation, 1)] ?? null;

            if ($computeMatches) {
                // Speed up: quick bool to int conversion (i.e, `charMatch ? 1 : 0`)
                $matchMask[$currentLocation] = (int) $charMatch;
            }

            // First pass: exact match
            $bitArr[$j] = ((($bitArr[$j + 1] ?? 0) << 1) | 1) & $charMatch;

            // Subsequent passes: fuzzy match
            if ($i) {
                $bitArr[$j] |=
                    ((($lastBitArr[$j + 1] ?? 0) | ($lastBitArr[$j] ?? 0)) << 1) |
                    1 |
                    ($lastBitArr[$j + 1] ?? 0);
            }

            if ($bitArr[$j] & $mask) {
                $finalScore = computeScore($pattern, [
                    'errors' => $i,
                    'currentLocation' => $currentLocation,
                    'expectedLocation' => $expectedLocation,
                    'distance' => $distance,
                    'ignoreLocation' => $ignoreLocation,
                ]);

                // This match will almost certainly be better than any existing match.
                // But check anyway.
                if ($finalScore <= $currentThreshold) {
                    // Indeed it is
                    $currentThreshold = $finalScore;
                    $bestLocation = $currentLocation;

                    // Already passed `loc`, downhill from here on in.
                    if ($bestLocation <= $expectedLocation) {
                        break;
                    }

                    // When passing `bestLocation`, don't exceed our current distance from `expectedLocation`.
                    $start = max(1, 2 * $expectedLocation - $bestLocation);
                }
            }
        }

        // No hope for a (better) match at greater error levels.
        $score = computeScore($pattern, [
            'errors' => $i + 1,
            'currentLocation' => $expectedLocation,
            'expectedLocation' => $expectedLocation,
            'distance' => $distance,
            'ignoreLocation' => $ignoreLocation,
        ]);

        if ($score > $currentThreshold) {
            break;
        }

        $lastBitArr = $bitArr;
    }

    $result = [
        'isMatch' => $bestLocation >= 0,
        'score' => max(0.001, $finalScore),
    ];

    if ($computeMatches) {
        $indices = convertMaskToIndices($matchMask, $minMatchCharLength);
        if (sizeof($indices) === 0) {
            $result['isMatch'] = false;
        } elseif ($includeMatches) {
            $result['indices'] = $indices;
        }
    }

    return $result;
}
