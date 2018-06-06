<?php namespace Fuse\Bitap;

class Bitap
{
    // Public properties to preserve compatibility with Fuse.js
    public $options;
    public $pattern;
    public $patternAlphabet;

    public function __construct($pattern, $options = [])
    {
        $this->options = array_merge([
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
            // Indicates whether comparisons should be case sensitive.
            'isCaseSensitive' => false,
            // Regex used to separate words when searching. Only applicable when `tokenize` is `true`.
            'tokenSeparator' => '/ +/',
            // When true, the algorithm continues searching to the end of the input even if a perfect
            // match is found before the end of the same input.
            'findAllMatches' => false,
            // Minimum number of characters that must be matched before a result is considered a match
            'minMatchCharLength' => 1
        ], $options);

        $this->pattern = $this->options['isCaseSensitive']
            ? $pattern
            : mb_strtolower($pattern);

        if (mb_strlen($this->pattern) <= $this->options['maxPatternLength']) {
            $this->patternAlphabet = pattern_alphabet($this->pattern);
        }
    }

    public function search($text)
    {
        if (!$this->options['isCaseSensitive']) {
            $text = mb_strtolower($text);
        }

        // Exact match
        if ($this->pattern === $text) {
            return [
                'isMatch' => true,
                'score' => 0,
                'matchedIndices' => [[0, mb_strlen($text) - 1]]
            ];
        }

        // When pattern length is greater than the machine word length, just do a a regex comparison
        if (mb_strlen($this->pattern) > $this->options['maxPatternLength']) {
            return regex_search($text, $this->pattern, $this->options['tokenSeparator']);
        }

        // Otherwise, use Bitap algorithm
        return search($text, $this->pattern, $this->patternAlphabet, [
            'location' => $this->options['location'],
            'distance' => $this->options['distance'],
            'threshold' => $this->options['threshold'],
            'findAllMatches' => $this->options['findAllMatches'],
            'minMatchCharLength' => $this->options['minMatchCharLength']
        ]);
    }
}
