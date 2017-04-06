<?php namespace Fuse;

/**
 * Adapted from "Diff, Match and Patch", by Google
 *
 *   http://code.google.com/p/google-diff-match-patch/
 *
 * Modified by: Kirollos Risk <kirollos@gmail.com>
 * Ported to PHP by: Florian Reuschel <florian@loilo.de>
 * -----------------------------------------------
 * Licensed under the Apache License, Version 2.0 (the "License")
 * you may not use this file except in compliance with the License.
 */
class BitapSearcher implements Searcher {
    protected $options;
    protected $pattern;
    protected $patternLen;
    protected $matchMask;
    protected $patternAlphabet;
    protected static $defaultOptions = [
        // Approximately where in the text is the pattern expected to be found?
        'location' => 0,

        // Determines how close the match must be to the fuzzy location (specified above).
        // An exact letter match which is 'distance' characters away from the fuzzy location
        // would score as a complete mismatch. A distance of '0' requires the match be at
        // the exact location specified, a threshold of '1000' would require a perfect match
        // to be within 800 characters of the fuzzy location to be found using a 0.8 threshold.
        'distance' => 100,

        // At what point does the match algorithm give up. A threshold of '0.0' requires a perfect match
        // (of both letters and location), a threshold of '1.0' would match anything.
        'threshold' => 0.6,

        // Machine word size
        'maxPatternLength' => 32,

        // Minimum number of characters that must be matched before a result is considered a match
        'minMatchCharLength' => 1,

        // When true, the algorithm continues searching to the end of the input even if a perfect
        // match is found before the end of the same input.
        'findAllMatches' => false
    ];

    public function __construct ($pattern, $options = []) {
        $this->options = array_merge(static::$defaultOptions, $options);

        $this->pattern = isset($options['caseSensitive']) && $options['caseSensitive']
            ? $pattern
            : mb_strtolower($pattern);
        $this->patternLen = mb_strlen($pattern);

        if ($this->patternLen <= $this->options['maxPatternLength']) {
            $this->matchmask = 1 << ($this->patternLen - 1);
            $this->patternAlphabet = $this->calculatePatternAlphabet();
        }
    }

    /**
     * Get the search pattern
     * @return {string} The search pattern
     */
    public function getPattern() {

    }

    /**
     * Initialize the alphabet for the Bitap algorithm.
     * @return {Object} Hash of character locations.
     * @private
     */
    protected function calculatePatternAlphabet () {
        $mask = [];
        $i = 0;

        for ($i = 0; $i < $this->patternLen; $i++) {
            $mask[mb_substr($this->pattern, $i, 1)] = 0;
        }

        for ($i = 0; $i < $this->patternLen; $i++) {
            $mask[mb_substr($this->pattern, $i, 1)] |= 1 << (mb_strlen($this->pattern) - $i - 1);
        }

        return $mask;
    }

    /**
     * Compute and return the score for a match with `e` errors and `x` location.
     * @param {number} errors Number of errors in match.
     * @param {number} location Location of match.
     * @return {number} Overall score for match (0.0 = good, 1.0 = bad).
     * @private
     */
    protected function bitapScore ($errors, $location) {
        $accuracy = $errors / $this->patternLen;
        $proximity = abs($this->options['location'] - $location);

        if (!$this->options['distance']) {
            // Dodge divide by zero error.
            return $proximity ? 1.0 : $accuracy;
        }
        return $accuracy + ($proximity / $this->options['distance']);
    }

    /**
     * Compute and return the result of the search
     * @param {String} text The text to search in
     * @return {Object} Literal containing:
     *                          {Boolean} isMatch Whether the text is a match or not
     *                          {Decimal} score Overall score for the match
     * @public
     */
    public function search ($text) {
        $options = $this->options;
        $lastBitArr = [];

        $text = isset($options['caseSensitive']) && $options['caseSensitive'] ? $text : mb_strtolower($text);

        if ($this->pattern === $text) {
            // Exact match
            return [
                'isMatch' => true,
                'score' => 0,
                'matchedIndices' => [[0, mb_strlen($text) - 1]]
            ];
        }

        // When pattern length is greater than the machine word length, just do a a regex comparison
        if ($this->patternLen > $options['maxPatternLength']) {
            $pattern = '/' . str_replace('/', '\\/', preg_replace($options['tokenSeparator'], '|', $this->pattern)) . '/';
            preg_match($pattern, $text, $matches);
            $isMatched = (bool) sizeof($matches);

            $matchedIndices = null;
            if ($isMatched) {
                $matchedIndices = [];
                for ($i = 0, $matchesLen = sizeof($matches); $i < $matchesLen; $i++) {
                    $match = $matches[$i];
                    $matchedIndices[] = [mb_strpos($text, $match), mb_strlen($match) - 1];
                }
            }

            return [
                'isMatch' => $isMatched,
                // TODO: revisit this score
                'score' => $isMatched ? 0.5 : 1,
                'matchedIndices' => $matchedIndices
            ];
        }

        $findAllMatches = $options['findAllMatches'];
        $location = $options['location'];
        // Set starting location at beginning text and initialize the alphabet.
        $textLen = mb_strlen($text);
        // Highest score beyond which we give up.
        $threshold = $options['threshold'];
        // Is there a nearby exact match? (speedup)
        $bestLoc = mb_strpos($text, $this->pattern, $location);

        // a mask of the matches
        $matchMask = [];
        for ($i = 0; $i < $textLen; $i++) {
            $matchMask[$i] = 0;
        }

        if ($bestLoc !== false) {
            $threshold = min($this->bitapScore(0, $bestLoc), $threshold);
            // What about in the other direction? (speed up)
            $bestLoc = mb_strrpos($text, $this->pattern, $location + $this->patternLen);

            if ($bestLoc !== false) {
                $threshold = min($this->bitapScore(0, $bestLoc), $threshold);
            }
        }

        $bestLoc = -1;
        $score = 1;
        $locations = [];
        $binMax = $this->patternLen + $textLen;

        for ($i = 0; $i < $this->patternLen; $i++) {
            // Scan for the best match; each iteration allows for one more error.
            // Run a binary search to determine how far from the match location we can stray
            // at this error level.
            $binMin = 0;
            $binMid = $binMax;
            while ($binMin < $binMid) {
                if ($this->bitapScore($i, $location + $binMid) <= $threshold) {
                    $binMin = $binMid;
                } else {
                    $binMax = $binMid;
                }
                $binMid = floor(($binMax - $binMin) / 2 + $binMin);
            }

            // Use the result from this iteration as the maximum for the next.
            $binMax = $binMid;
            $start = max(1, $location - $binMid + 1);

            if ($findAllMatches) {
                $finish = $textLen;
            } else {
                $finish = min($location + $binMid, $textLen) + $this->patternLen;
            }

            // Initialize the bit array
            $bitArr = array_fill(0, $finish + 2, null);

            $bitArr[$finish + 1] = (1 << $i) - 1;

            for ($j = $finish; $j >= $start; $j--) {
                $charMatch = mb_strlen($text) >= $j - 2 && isset($this->patternAlphabet[mb_substr($text, $j - 1, 1)])
                    ? $this->patternAlphabet[mb_substr($text, $j - 1, 1)]
                    : null;

                if ($charMatch) {
                    $matchMask[$j - 1] = 1;
                }

                if ($i === 0) {
                    // First pass: exact match.
                    $bitArr[$j] = (($bitArr[$j + 1] << 1) | 1) & $charMatch;
                } else {
                    // Subsequent passes: fuzzy match.
                    $bitArr[$j] = (($bitArr[$j + 1] << 1) | 1) & $charMatch | ((($lastBitArr[$j + 1] | $lastBitArr[$j]) << 1) | 1) | $lastBitArr[$j + 1];
                }
                if ($bitArr[$j] & $this->matchmask) {
                    $score = $this->bitapScore($i, $j - 1);

                    // This match will almost certainly be better than any existing match.
                    // But check anyway.
                    if ($score <= $threshold) {
                        // Indeed it is
                        $threshold = $score;
                        $bestLoc = $j - 1;
                        $locations[] = $bestLoc;

                        if ($bestLoc > $location) {
                            // When passing loc, don't exceed our current distance from loc.
                            $start = max(1, 2 * $location - $bestLoc);
                        } else {
                            // Already passed loc, downhill from here on in.
                            break;
                        }
                    }
                }
            }

            // No hope for a (better) match at greater error levels.
            if ($this->bitapScore($i + 1, $location) > $threshold) {
                break;
            }
            $lastBitArr = $bitArr;
        }

        $matchedIndices = $this->getMatchedIndices($matchMask);

        // Count exact matches (those with a score of 0) to be "almost" exact
        return [
            'isMatch' => $bestLoc >= 0,
            'score' => $score === 0 ? 0.001 : $score,
            'matchedIndices' => $matchedIndices
        ];
    }

    protected function getMatchedIndices ($matchMask) {
        $matchedIndices = [];
        $start = -1;
        $end = -1;
        $i = 0;
        $len = sizeof($matchMask);
        for (; $i < $len; $i++) {
            $match = $matchMask[$i];
            if ($match && $start === -1) {
                $start = $i;
            } else if (!$match && $start !== -1) {
                $end = $i - 1;
                if (($end - $start) + 1 >= $this->options['minMatchCharLength']) {
                    $matchedIndices[] = [$start, $end];
                }
                $start = -1;
            }
        }
        if ($matchMask[$i - 1]) {
            if (($i - 1 - $start) + 1 >= $this->options['minMatchCharLength']) {
                $matchedIndices[] = [$start, $i - 1];
            }
        }
        return $matchedIndices;
    }
}
