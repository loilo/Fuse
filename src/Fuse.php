<?php namespace Fuse;

class Fuse {
    protected $options;
    protected $list;
    protected $pattern;
    protected $results;
    protected $resultMap;
    protected $keyMap;
    protected $tokenSearchers;
    protected $fullSearcher;

    static $VERSION = '1.0.0';

    protected static $defaultOptions = [
        // The name of the identifier property. If specified, the returned result will be a list
        // of the items' dentifiers, otherwise it will be a list of the items.
        'id' => null,

        // Indicates whether comparisons should be case sensitive.

        'caseSensitive' => false,

        // An array of values that should be included from the searcher's output. When this array
        // contains elements, each result in the list will be of the form `{ item => '...', include1 => ..., include2 => ... }`.
        // Values you can include are `score`, `matchedLocations`
        'include' => [],

        // Whether to sort the result list, by score
        'shouldSort' => true,

        // The search class to use
        // Required to follow a certain format, you may implement the \Fuse\Searcher interface
        'searchFn' => BitapSearcher::class,

        // Default sort function
        'sortFn' => ['Fuse\Fuse', 'defaultScoreSort'],

        // The get function to use when fetching an object's properties.
        // The default will search nested paths *ie foo.bar.baz*
        'getFn' => ['Fuse\Fuse', 'defaultValueGetter'],

        // List of properties that will be searched. This also supports nested properties.
        'keys' => [],

        // Will print to the console. Useful for debugging.
        'verbose' => false,

        // When true, the search algorithm will search individual words **and** the full string,
        // computing the final score as a function of both. Note that when `tokenize` is `true`,
        // the `threshold`, `distance`, and `location` are inconsequential for individual tokens.
        'tokenize' => false,

        // When true, the result set will only include records that match all tokens. Will only work
        // if `tokenize` is also true.
        'matchAllTokens' => false,

        // Regex used to separate words when searching. Only applicable when `tokenize` is `true`.
        'tokenSeparator' => '/ +/'
    ];

    protected static function defaultScoreSort ($a, $b) {
        return $a['score'] === $b['score']
            ? 0
            : ($a['score'] < $b['score'] ? -1 : 1);
    }

    protected static function defaultValueGetter ($obj, $path, $list) {
        $remaining = null;

        if (!$path) {
            // If there's no path left, we've gotten to the object we care about.
            $list[] = $obj;
        } else {
            $dotIndex = mb_strpos($path, '.');

            if ($dotIndex !== false) {
                $firstSegment = mb_substr($path, 0, $dotIndex);
                $remaining = mb_substr($path, $dotIndex + 1);
            } else {
                $firstSegment = $path;
            }

            $value = isset($obj[$firstSegment]) ? $obj[$firstSegment] : null;
            if (!is_null($value)) {
                if (!$remaining && (is_string($value) || is_numeric($value))) {
                    $list[] = $value;
                } else if (is_array($value) && !array_diff_key($value, array_keys(array_keys($value)))) {
                    // Search each item in the array.
                    for ($i = 0, $len = sizeof($value); $i < $len; $i++) {
                        $list = static::defaultValueGetter($value[$i], $remaining, $list);
                    }
                } else if ($remaining) {
                    // An object. Recurse further.
                    $list = static::defaultValueGetter($value, $remaining, $list);
                }
            }
        }

        return $list;
    }

    protected function log() {
        $args = func_get_args();
        $output = [];

        foreach ($args as $arg) {
            if (is_string($arg) || is_numeric($arg)) {
                $output[] = $arg;
            } else {
                ob_start();
                var_dump($arg);
                $output[] = ob_get_contents();
                ob_end_clean();
            }
        }

        echo '<pre>' . join(' ', $output) . "\n\n</pre>\n";
    }

    public function __construct ($list, $options = []) {
        $this->list = $list;
        $this->options = $options;

        // Add boolean type options
        for ($i = 0, $keys = ['sort', 'shouldSort', 'tokenize'], $len = sizeof($keys); $i < $len; $i++) {
            $key = $keys[$i];
            $this->options[$key] = array_key_exists($key, $options)
                ? $options[$key]
                : isset(static::$defaultOptions[$key]);
        }

        // Add all other options
        for ($i = 0, $keys = ['searchFn', 'sortFn', 'keys', 'getFn', 'include', 'verbose', 'tokenSeparator'], $len = sizeof($keys); $i < $len; $i++) {
            $key = $keys[$i];
            $this->options[$key] = array_key_exists($key, $options)
                ? $options[$key]
                : static::$defaultOptions[$key];
        }

        // Check if `searchFn` implements the Searcher interface
        if (!in_array(Searcher::class, class_implements($this->options['searchFn']))) {
            throw new \Exception('The class provided as "searchFn" option must implement the \\Fuse\\Searcher interface');
        }
    }

    /**
     * Sets a new list for Fuse to match against.
     * @param {Array} list
     * @return {Array} The newly set list
     * @public
     */
    public function set ($list) {
        $this->list = $list;
        return $list;
    }

    public function search ($pattern) {
        if ($this->options['verbose']) $this->log('Search term:', $pattern);

        $this->pattern = $pattern;
        $this->results = [];
        $this->resultMap = [];
        $this->keyMap = null;

        $this->prepareSearchers();
        $this->startSearch();
        $this->computeScore();
        $this->sort();

        $output = $this->format();

        return $output;
    }

    protected function prepareSearchers () {
        $pattern = $this->pattern;
        $searchFn = $this->options['searchFn'];
        $tokens = preg_split($this->options['tokenSeparator'], $pattern);

        $i = 0;
        $len = sizeof($tokens);

        if ($this->options['tokenize']) {
            $this->tokenSearchers = [];
            for (; $i < $len; $i++) {
                $this->tokenSearchers[] = new $searchFn($tokens[$i], $this->options);
            }
        }
        $this->fullSearcher = new $searchFn($pattern, $this->options);
    }

    protected function startSearch () {
        $getFn = $this->options['getFn'];
        $list = $this->list;
        $listLen = sizeof($list);
        $keys = $this->options['keys'];
        $keysLen = sizeof($keys);
        $item = null;

        // Check the first item in the list, if it's a string, then we assume
        // that every item in the list is also a string, and thus it's a flattened array.
        if (is_string($list[0])) {
            // Iterate over every item
            for ($i = 0; $i < $listLen; $i++) {
                $this->analyze('', $list[$i], $i, $i);
            }
        } else {
            $this->keyMap = [];

            // Otherwise, the first item is an array or object (hopefully), and thus the searching
            // is done on the values of the keys of each item.
            // Iterate over every item
            for ($i = 0; $i < $listLen; $i++) {
                $item = $list[$i];

                // Iterate over every key
                for ($j = 0; $j < $keysLen; $j++) {
                    $key = $keys[$j];
                    if (!is_string($key)) {
                        $weight = (1 - $key['weight']) ?: 1;
                        $this->keyMap[$key['name']] = [
                            'weight' => $weight
                        ];
                        if ($key['weight'] <= 0 || $key['weight'] > 1) {
                            throw new \Exception('Key weight has to be > 0 and <= 1');
                        }
                        $key = $key['name'];
                    } else {
                        $this->keyMap[$key] = [
                            'weight' => 1
                        ];
                    }
                    $this->analyze($key, $getFn($item, $key, []), $item, $i);
                }
            }
        }
    }

    protected function analyze ($key, $text, $entity, $index) {
        $exists = false;
        $averageScore = null;

        if (!isset($text) || $text == null) {
            return;
        }

        $scores = [];

        $numTextMatches = 0;

        if (is_string($text)) {
            $words = preg_split($this->options['tokenSeparator'], $text);

            if ($this->options['verbose']) $this->log("---------\nKey:", $key);

            if ($this->options['tokenize']) {
                for ($i = 0; $i < sizeof($this->tokenSearchers); $i++) {
                    $tokenSearcher = $this->tokenSearchers[$i];

                    if ($this->options['verbose']) $this->log('Pattern:', $tokenSearcher->getPattern());

                    $termScores = [];
                    $hasMatchInText = false;

                    for ($j = 0; $j < sizeof($words); $j++) {
                        $word = $words[$j];
                        $tokenSearchResult = $tokenSearcher->search($word);

                        $obj = [];

                        if ($tokenSearchResult['isMatch']) {
                            $obj[$word] = $tokenSearchResult['score'];
                            $exists = true;
                            $hasMatchInText = true;
                            $scores[] = $tokenSearchResult['score'];
                        } else {
                            $obj[$word] = 1;
                            if (!isset($this->options['matchAllTokens']) || !$this->options['matchAllTokens']) {
                                $scores[] = 1;
                            }
                        }
                        $termScores[] = $obj;
                    }

                    if ($hasMatchInText) {
                        $numTextMatches++;
                    }

                    if ($this->options['verbose']) $this->log('Token scores:', $termScores);
                }

                $averageScore = $scores[0];
                $scoresLen = sizeof($scores);
                for($i = 1; $i < $scoresLen; $i++) {
                    $averageScore += $scores[$i];
                }
                $averageScore /= $scoresLen;

                if ($this->options['verbose']) $this->log('Token score average:', $averageScore);
            }

            $mainSearchResult = $this->fullSearcher->search($text);
            if ($this->options['verbose']) $this->log('Full text score:', $mainSearchResult['score']);

            $finalScore = $mainSearchResult['score'];
            if (!is_null($averageScore)) {
                $finalScore = ($finalScore + $averageScore) / 2;
            }

            if ($this->options['verbose']) $this->log('Score average:', $finalScore);

            $checkTextMatches = ((isset($this->options['tokenize']) && $this->options['tokenize']) && (isset($this->options['matchAllTokens']) && $this->options['matchAllTokens']))
                ? $numTextMatches >= sizeof($this->tokenSearchers)
                : true;

            if ($this->options['verbose']) $this->log('Check Matches', $checkTextMatches);

            // If a match is found, add the item to <rawResults>, including its score
            if (($exists || $mainSearchResult['isMatch']) && $checkTextMatches) {
                // Check if the item already exists in our results
                $existingResult = isset($this->resultMap[$index])
                    ? $this->resultMap[$index]
                    : null;

                if ($existingResult) {
                    // Use the lowest score
                    // $existingResult['score'], $bitapResult['score']
                    $existingResult['output'][] = [
                        'key' => $key,
                        'score' => $finalScore,
                        'matchedIndices' => $mainSearchResult['matchedIndices']
                    ];
                } else {
                    // Add it to the raw result list
                    $this->resultMap[$index] = [
                        'item' => $entity,
                        'output' => [
                            [
                                'key' => $key,
                                'score' => $finalScore,
                                'matchedIndices' => $mainSearchResult['matchedIndices']
                            ]
                        ]
                    ];

                    $this->results[] = $this->resultMap[$index];
                }
            }
        } elseif (is_array($text)) {
            for ($i = 0; $i < sizeof($text); $i++) {
                $this->analyze($key, $text[$i], $entity, $index);
            }
        }
    }

    protected function computeScore () {
        $keyMap = $this->keyMap;
        $results = &$this->results;

        if ($this->options['verbose']) $this->log("Computing score:");

        for ($i = 0; $i < sizeof($results); $i++) {
            $totalScore = 0;
            $output = $results[$i]['output'];
            $scoreLen = sizeof($output);

            $bestScore = 1;

            for ($j = 0; $j < $scoreLen; $j++) {
                $score = $output[$j]['score'];
                $weight = $keyMap ? $keyMap[$output[$j]['key']]['weight'] : 1;

                $nScore = $score * $weight;

                if ($weight !== 1) {
                    $bestScore = min($bestScore, $nScore);
                } else {
                    $totalScore += $nScore;
                    $output[$j]['nScore'] = $nScore;
                }
            }

            if ($bestScore === 1) {
                $results[$i]['score'] = $totalScore / $scoreLen;
            } else {
                $results[$i]['score'] = $bestScore;
            }

            if ($this->options['verbose']) $this->log($results[$i]);
        }
    }

    protected function sort () {
        if ($this->options['shouldSort']) {
            if ($this->options['verbose']) $this->log("Sorting...");
            usort($this->results, $this->options['sortFn']);
        }
    }

    protected function format () {
        $finalOutput = [];
        $results = &$this->results;
        $include = $this->options['include'];

        if ($this->options['verbose']) $this->log("Output:", $results);

        // Helper function, here for speed-up, which replaces the item with its value,
        // if the options specifies it,
        $self = $this;
        $replaceValue = isset($this->options['id'])
            ? function ($index) use (&$results, $self) {
                $results[$index]['item'] = $self->options['getFn']($results[$index]['item'], $self->options['id'], [])[0];
            }
            : function () {};

        $getItemAtIndex = function ($index) use (&$results, &$include, &$replaceValue, &$getItemAtIndex) {
            $record = $results[$index];

            // If `include` has values, put the item in the result
            if (sizeof($include) > 0) {
                $data = [
                    'item' => $record['item']
                ];

                if (in_array('matches', $include)) {
                    $output = $record['output'];
                    $data['matches'] = [];

                    for ($j = 0; $j < sizeof($output); $j++) {
                        $_item = $output[$j];
                        $_result = [
                            'indices' => $_item['matchedIndices']
                        ];

                        if (isset($_item['key'])) {
                            $_result['key'] = $_item['key'];
                        }
                        $data['matches'][] = $_result;
                    }
                }

                if (in_array('score', $include)) {
                    $data['score'] = $results[$index]['score'];
                }

            } else {
                $data = $record['item'];
            }

            return $data;
        };

        // From the results, push into a new array only the item identifier (if specified)
        // of the entire item.  This is because we don't want to return the <results>,
        // since it contains other metadata
        for ($i = 0, $len = sizeof($results); $i < $len; $i++) {
            $replaceValue($i);
            $item = $getItemAtIndex($i);
            $finalOutput[] = $item;
        }

        return $finalOutput;
    }
}
