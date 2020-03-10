<?php namespace Fuse;

use Fuse\Bitap\Bitap;
use function Fuse\Helpers\deep_value;
use function Fuse\Helpers\is_list;

class Fuse
{
    protected $keyWeights;
    protected $keyNames;

    public $options;

    public function __construct($list, $options = [])
    {
        $this->options = array_merge([
            'location' => 0,
            'distance' => 100,
            'threshold' => 0.6,
            'maxPatternLength' => 32,
            'caseSensitive' => false,
            'tokenSeparator' => ' +',
            'findAllMatches' => false,
            'minMatchCharLength' => 1,
            'id' => null,
            'keys' => [],
            'shouldSort' => true,
            'getFn' => '\Fuse\Helpers\get',
            'sortFn' => function ($a, $b) {
                if ($a['score'] === $b['score']) {
                    return $a['index'] === $b['index']
                        ? 0
                        : (
                            $a['index'] < $b['index']
                            ? -1
                            : 1
                        );
                } elseif ($a['score'] < $b['score']) {
                    return -1;
                } else {
                    return 1;
                }
            },
            'tokenize' => false,
            'matchAllTokens' => false,
            'includeMatches' => false,
            'includeScore' => false,
            'verbose' => false
        ], $options);
        $this->options['isCaseSensitive'] = $this->options['caseSensitive'];

        $this->setCollection($list);
        $this->processKeys($this->options['keys']);
    }

    protected function processKeys(array $keys)
    {
        $this->keyWeights = [];
        $this->keyNames = [];
    
        // Iterate over every key
        if (sizeof($keys) > 0 && is_string($keys[0])) {
            foreach ($keys as $key) {
                $this->keyWeights[$key] = 1;
                $this->keyNames[] = $key;
            }
        } else {
            $lowest = null;
            $highest = null;
            $weightsTotal = 0;

            foreach ($keys as $key) {
                if (!isset($key['name'])) {
                    throw new \Exception('Missing "name" property in key array');
                }

                $keyName = $key['name'];
                $this->keyNames[] = $keyName;

                if (!isset($key['weight'])) {
                    throw new \Exception('Missing "weight" property in key array');
                }

                $keyWeight = $key['weight'];

                if ($keyWeight < 0 || $keyWeight > 1) {
                    throw new \Exception(
                        '"weight" property in key must bein the range of [0, 1)'
                    );
                }

                if (is_null($highest)) {
                    $highest = $keyWeight;
                } else {
                    $highest = max($highest, $keyWeight);
                }

                if (is_null($lowest)) {
                    $lowest = $keyWeight;
                } else {
                    $lowest = min($lowest, $keyWeight);
                }

                $this->keyWeights[$keyName] = $keyWeight;

                $weightsTotal += $keyWeight;
            }

            if ($weightsTotal > 1) {
                throw new \Exception('Total of weights cannot exceed 1, was ' . $weightsTotal);
            }
        }
    }

    public function setCollection($list)
    {
        $list = array_values($list);
        $this->list = $list;
        return $list;
    }

    public function search($pattern, $opts = [ 'limit' => false ])
    {
        $this->log("---------\nSearch pattern: \"$pattern\"");

        $searchers = $this->prepareSearchers($pattern);

        $results = $this->innerSearch($searchers['tokenSearchers'], $searchers['fullSearcher']);

        $this->computeScore($results);

        if ($this->options['shouldSort']) {
            $this->sort($results);
        }

        if (is_int($opts['limit'])) {
            $results = array_slice($results, 0, $opts['limit']);
        }

        return $this->format($results);
    }

    protected function prepareSearchers($pattern = '')
    {
        $tokenSearchers = [];

        if ($this->options['tokenize']) {
            // Tokenize on the separator
            $tokens = mb_split($this->options['tokenSeparator'], $pattern);

            for ($i = 0, $len = sizeof($tokens); $i < $len; $i++) {
                $tokenSearchers[] = new Bitap($tokens[$i], $this->options);
            }
        }

        $fullSearcher = new Bitap($pattern, $this->options);

        return [
            'tokenSearchers' => $tokenSearchers,
            'fullSearcher' => $fullSearcher
        ];
    }

    protected function innerSearch($tokenSearchers = [], $fullSearcher = null)
    {
        $list = $this->list;
        $resultMap = [];
        $results = [];

        // Check the first item in the list, if it's a string, then we assume
        // that every item in the list is also a string, and thus it's a flattened array.
        if (is_string($list[0])) {
            // Iterate over every item
            for ($i = 0, $len = sizeof($list); $i < $len; $i++) {
                $this->analyze(
                    [
                        'key' => '',
                        'value' => $list[$i],
                        'record' => $i,
                        'index' => $i
                    ],
                    $resultMap,
                    $results,
                    $tokenSearchers,
                    $fullSearcher
                );
            }

            return $results;
        }

        // Otherwise, the first item is an Object (hopefully), and thus the searching
        // is done on the values of the keys of each item.
        foreach ($list as $i => $item) {
            // Iterate over every key
            foreach ($this->keyNames as $key) {
                $this->analyze(
                    [
                        'key' => $key,
                        'value' => $this->options['getFn']($item, $key),
                        'record' => $item,
                        'index' => $i
                    ],
                    $resultMap,
                    $results,
                    $tokenSearchers,
                    $fullSearcher
                );
            }
        }

        return $results;
    }

    protected function analyze($query = [], &$resultMap = [], &$results = [], &$tokenSearchers = [], &$fullSearcher = null)
    {
        $query = array_merge([
            'key' => null,
            'arrayIndex' => -1,
            'value' => null,
            'record' => null,
            'index' => null
        ], $query);

        $key = $query['key'];

        $search = function ($arrayIndex, $value, $record, $index) use ($key, $fullSearcher, $tokenSearchers, &$resultMap, &$search, &$results) {
            // Check if the text value can be searched
            if (is_null($value)) {
                return;
            }

            $exists = false;
            $averageScore = -1;
            $numTextMatches = 0;

            if (is_string($value)) {
                $this->log("\nKey: " . ($key === '' ? '--' : $key));

                $mainSearchResult = $fullSearcher->search($value);
                $this->log('Full text: "' . $value . '", score: ' . $mainSearchResult['score']);

                // TODO: revisit this to take into account term frequency
                if ($this->options['tokenize']) {
                    $words = mb_split($this->options['tokenSeparator'], $value);
                    $scores = [];

                    foreach ($tokenSearchers as $tokenSearcher) {
                        $this->log("\nPattern: \"{$tokenSearcher->pattern}\"");

                        // $tokenScores = []
                        $hasMatchInText = false;

                        foreach ($words as $word) {
                            $tokenSearchResult = $tokenSearcher->search($word);
                            $obj = [];
                            if ($tokenSearchResult['isMatch']) {
                                $obj[$word] = $tokenSearchResult['score'];
                                $exists = true;
                                $hasMatchInText = true;
                                $scores[] = $tokenSearchResult['score'];
                            } else {
                                $obj[$word] = 1;
                                if (!$this->options['matchAllTokens']) {
                                    $scores[] = 1;
                                }
                            }
                            $this->log('Token: "' . $word . '", score: ' . $obj[$word]);
                            // tokenScores.push(obj)
                        }

                        if ($hasMatchInText) {
                            $numTextMatches += 1;
                        }
                    }

                    $scoresLen = sizeof($scores);

                    if ($scoresLen > 0) {
                        $averageScore = array_sum($scores) / $scoresLen;
                    } else {
                        $averageScore = -1;
                    }

                    $this->log('Token score average: ', $averageScore);
                }

                $finalScore = $mainSearchResult['score'];
                if ($averageScore > -1) {
                    $finalScore = ($finalScore + $averageScore) / 2;
                }

                $this->log('Score average: ', $finalScore);

                $checkTextMatches = ($this->options['tokenize'] && $this->options['matchAllTokens'])
                    ? $numTextMatches >= sizeof($tokenSearchers)
                    : true;

                $this->log("\nCheck Matches: ", $checkTextMatches);

                // If a match is found, add the item to <rawResults>, including its score
                if (($exists || $mainSearchResult['isMatch']) && $checkTextMatches) {
                    $_searchResult = [
                        'key' => $key,
                        'arrayIndex' => $arrayIndex,
                        'value' => $value,
                        'score' => $finalScore
                    ];
                    
                    if ($this->options['includeMatches']) {
                        $_searchResult['matchedIndices'] = $mainSearchResult['matchedIndices'];
                    }

                    // Check if the item already exists in our results
                    if (isset($resultMap[$index])) {
                        $existingResult = &$resultMap[$index];
                    } else {
                        $existingResult = null;
                    }

                    if (!is_null($existingResult)) {
                        // Use the lowest score
                        // existingResult.score, bitapResult.score
                        $existingResult['output'][] = $_searchResult;
                    } else {
                        $resultMap[$index] = [
                            'item' => $record,
                            'output' => [$_searchResult]
                        ];

                        $results[] = &$resultMap[$index];
                    }
                }
            } elseif (is_list($value)) {
                for ($i = 0; $i < sizeof($value); $i++) {
                    $search($i, $value[$i], $record, $index);
                }
            }
        };

        $search($query['arrayIndex'], $query['value'], $query['record'], $query['index']);
    }

    protected function computeScore(&$results)
    {
        $this->log("\n\nComputing score:\n");

        $weights = $this->keyWeights;
        $hasWeights = !empty($weights);

        foreach ($results as &$result) {
            $output = $result['output'];
            $scoreLen = sizeof($output);

            $totalWeightedScore = 1;

            for ($j = 0; $j < $scoreLen; $j++) {
                $item = $output[$j];
                $key = $item['key'];

                $weight = $hasWeights ? $weights[$key] : 1;
                $score = $item['score'] === 0 && $hasWeights && $weights[$key] > 0
                    ? (defined('PHP_FLOAT_EPSILON') ? PHP_FLOAT_EPSILON : 0.00001)
                    : $item['score'];

                $totalWeightedScore *= pow($score, $weight);
            }

            $result['score'] = $totalWeightedScore;

            $this->log($result);
        }
    }

    protected function sort(&$results)
    {
        $this->log("\n\nSorting....");

        $results = array_map(function ($result, $index) {
            $result['index'] = $index;
            return $result;
        }, array_values($results), array_keys($results));

        usort($results, $this->options['sortFn']);

        $results = array_map(function ($result) {
            unset($result['index']);
            return $result;
        }, $results);
    }

    protected function format(&$results)
    {
        $finalOutput = [];

        $this->log("\n\nOutput:\n\n", $results);

        $transformers = [];

        if ($this->options['includeMatches']) {
            $transformers[] = function ($result, &$data) {
                $output = $result['output'];
                $data['matches'] = [];

                for ($i = 0, $len = sizeof($output); $i < $len; $i++) {
                    $item = $output[$i];

                    if (sizeof($item['matchedIndices']) === 0) {
                        continue;
                    }

                    $obj = [
                        'indices' => $item['matchedIndices'],
                        'value' => $item['value']
                    ];
                    if ($item['key']) {
                        $obj['key'] = $item['key'];
                    }
                    if (isset($item['arrayIndex']) && $item['arrayIndex'] > -1) {
                        $obj['arrayIndex'] = $item['arrayIndex'];
                    }
                    $data['matches'][] = $obj;
                }
            };
        }

        if ($this->options['includeScore']) {
            $transformers[] = function ($result, &$data) {
                $data['score'] = $result['score'];
            };
        }

        for ($i = 0, $len = sizeof($results); $i < $len; $i += 1) {
            $result = &$results[$i];

            if ($this->options['id']) {
                $getterResult = $this->options['getFn']($result['item'], $this->options['id']);
                $result['item'] = $getterResult[0];
            }

            if (!sizeof($transformers)) {
                $finalOutput[] = $result['item'];
                continue;
            }

            $data = [
                'item' => $result['item']
            ];

            for ($j = 0, $tlen = sizeof($transformers); $j < $tlen; $j++) {
                $transformers[$j]($result, $data);
            }

            $finalOutput[] = $data;
        }

        return $finalOutput;
    }

    protected function log(...$args)
    {
        if ($this->options['verbose']) {
            echo "\n";
            foreach ($args as $arg) {
                if (is_array($arg) || is_object($arg)) {
                    var_dump($arg);
                } elseif (is_bool($arg)) {
                    echo($arg ? 'true' : 'false');
                } else {
                    echo $arg;
                }
            }
        }
    }
}
