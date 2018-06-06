<?php namespace Fuse\Bitap;

function search($text, $pattern, $patternAlphabet, $options = [])
{
    $options = array_merge([
        'location' => 0,
        'distance' => 100,
        'threshold' => 0.6,
        'findAllMatches' => false,
        'minMatchCharLength' => 1
    ], $options);

    $expectedLocation  = $options['location'];
    // Set starting location at beginning text and initialize the alphabet.
    $textLen = mb_strlen($text);
    // Highest score beyond which we give up.
    $currentThreshold = $options['threshold'];
    // Is there a nearby exact match? (speedup)
    $bestLocation = mb_strpos($text, $pattern, $expectedLocation);

    $patternLen = mb_strlen($pattern);

    // a mask of the matches
    $matchMask = [];
    for ($i = 0; $i < $textLen; $i++) {
        $matchMask[$i] = 0;
    }

    if ($bestLocation !== false) {
        $score = score($pattern, [
            'errors' => 0,
            'currentLocation' => $bestLocation,
            'expectedLocation' => $expectedLocation,
            'distance' => $options['distance']
        ]);
        $currentThreshold = min($score, $currentThreshold);

        // What about in the other direction? (speed up)
        $bestLocation = mb_strrpos($text, $pattern, $expectedLocation + $patternLen);

        if ($bestLocation !== false) {
            $score = score($pattern, [
                'errors' => 0,
                'currentLocation' => $bestLocation,
                'expectedLocation' => $expectedLocation,
                'distance' => $options['distance']
            ]);
            $currentThreshold = min($score, $currentThreshold);
        }
    }

    // Reset the best location
    $bestLocation = -1;

    $lastBitArr = [];
    $finalScore = 1;
    $binMax = $patternLen + $textLen;

    $mask = 1 << ($patternLen - 1);

    for ($i = 0; $i < $patternLen; $i++) {
        // Scan for the best match; each iteration allows for one more error.
        // Run a binary search to determine how far from the match location we can stray
        // at this error level.
        $binMin = 0;
        $binMid = $binMax;

        while ($binMin < $binMid) {
            $score = score($pattern, [
                'errors' => $i,
                'currentLocation' => $expectedLocation + $binMid,
                'expectedLocation' => $expectedLocation,
                'distance' => $options['distance']
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
        $finish = $options['findAllMatches']
            ? $textLen
            : min($expectedLocation + $binMid, $textLen) + $patternLen;

        // Initialize the bit array
        $bitArr = [];

        $bitArr[$finish + 1] = (1 << $i) - 1;

        for ($j = $finish; $j >= $start; $j -= 1) {
            $currentLocation = $j - 1;

            $offset = mb_substr($text, $currentLocation, 1);
            $charMatch = isset($patternAlphabet[$offset])
                ? $patternAlphabet[$offset]
                : null;

            if ($charMatch) {
                $matchMask[$currentLocation] = 1;
            }

            // First pass: exact match
            $bitArr[$j] = (($bitArr[$j + 1] << 1) | 1) & $charMatch;

            // Subsequent passes: fuzzy match
            if ($i !== 0) {
                $bitArr[$j] |= ((($lastBitArr[$j + 1] | $lastBitArr[$j]) << 1) | 1) | $lastBitArr[$j + 1];
            }

            if ($bitArr[$j] & $mask) {
                $finalScore = score($pattern, [
                    'errors' => $i,
                    'currentLocation' => $currentLocation,
                    'expectedLocation' => $expectedLocation,
                    'distance' => $options['distance']
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
        $score = score($pattern, [
            'errors' => $i + 1,
            'currentLocation' => $expectedLocation,
            'expectedLocation' => $expectedLocation,
            'distance' => $options['distance']
        ]);

        if ($score > $currentThreshold) {
            break;
        }

        $lastBitArr = $bitArr;
    }

    // Count exact matches (those with a score of 0) to be "almost" exact
    return [
        'isMatch' => $bestLocation >= 0,
        'score' => $finalScore == 0 ? 0.001 : $finalScore,
        'matchedIndices' => matched_indices($matchMask, $options['minMatchCharLength'])
    ];
}
