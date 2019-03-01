<?php namespace Fuse;

use Fuse\Bitap\Bitap;
use function Fuse\Helpers\deep_value;
use function Fuse\Helpers\is_list;

class Fuse
{
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
            'getFn' => '\Fuse\Helpers\deep_value',
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

        $search = $this->innerSearch($searchers['tokenSearchers'], $searchers['fullSearcher']);

        $this->computeScore($search['weights'], $search['results']);

        if ($this->options['shouldSort']) {
            $this->sort($search['results']);
        }

        if (is_int($opts['limit'])) {
            $search['results'] = array_slice($search['results'], 0, $opts['limit']);
        }

        return $this->format($search['results']);
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

            return [
                'weights' => null,
                'results' => $results
            ];
        }

        // Otherwise, the first item is an Object (hopefully), and thus the searching
        // is done on the values of the keys of each item.
        $weights = [];
        for ($i = 0, $len = sizeof($list); $i < $len; $i++) {
            $item = $list[$i];
            // Iterate over every key
            for ($j = 0, $keysLen = sizeof($this->options['keys']); $j < $keysLen; $j++) {
                $key = $this->options['keys'][$j];
                if (!is_string($key)) {
                    $weights[$key['name']] = [
                        'weight' => (1 - $key['weight']) ?: 1
                    ];
                    if ($key['weight'] <= 0 || $key['weight'] > 1) {
                        throw new \LogicException('Key weight has to be > 0 and <= 1');
                    }
                    $key = $key['name'];
                } else {
                    $weights[$key] = [
                        'weight' => 1
                    ];
                }

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

        return [
            'weights' => $weights,
            'results' => $results
        ];
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

        // Check if the texvaluet can be searched
        if (is_null($query['value'])) {
            return;
        }

        $exists = false;
        $averageScore = -1;
        $numTextMatches = 0;

        if (is_string($query['value'])) {
            $this->log("\nKey: " . ($query['key'] === '' ? '-' : $query['key']));

            $mainSearchResult = $fullSearcher->search($query['value']);
            $this->log('Full text: "' . $query['value'] . '", score: ' . $mainSearchResult['score']);

            if ($this->options['tokenize']) {
                $words = mb_split($this->options['tokenSeparator'], $query['value']);
                $scores = [];

                for ($i = 0; $i < sizeof($tokenSearchers); $i++) {
                    $tokenSearcher = $tokenSearchers[$i];

                    $this->log("\nPattern: \"{$tokenSearcher->pattern}\"");

                    // $tokenScores = []
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
                    $averageScore = $scores[0];
                    for ($i = 1; $i < $scoresLen; $i++) {
                        $averageScore += $scores[$i];
                    }
                    $averageScore = $averageScore / $scoresLen;
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
                // Check if the item already exists in our results
                if (isset($resultMap[$query['index']])) {
                    $existingResult = &$resultMap[$query['index']];
                } else {
                    $existingResult = null;
                }

                if (!is_null($existingResult)) {
                    // Use the lowest score
                    // existingResult.score, bitapResult.score
                    $existingResult['output'][] = [
                        'key' => $query['key'],
                        'arrayIndex' => $query['arrayIndex'],
                        'value' => $query['value'],
                        'score' => $finalScore,
                        'matchedIndices' => $mainSearchResult['matchedIndices']
                    ];
                } else {
                    // Add it to the raw result list
                    $resultMap[$query['index']] = [
                        'item' => $query['record'],
                        'output' => [[
                            'key' => $query['key'],
                            'arrayIndex' => $query['arrayIndex'],
                            'value' => $query['value'],
                            'score' => $finalScore,
                            'matchedIndices' => $mainSearchResult['matchedIndices']
                        ]]
                    ];

                    $results[] = &$resultMap[$query['index']];
                }
            }
        } elseif (is_list($query['value'])) {
            for ($i = 0, $len = sizeof($query['value']); $i < $len; $i++) {
                $this->analyze(
                    [
                        'key' => $query['key'],
                        'arrayIndex' => $i,
                        'value' => $query['value'][$i],
                        'record' => $query['record'],
                        'index' => $query['index']
                    ],
                    $resultMap,
                    $results,
                    $tokenSearchers,
                    $fullSearcher
                );
            }
        }
    }

    protected function computeScore($weights, &$results)
    {
        $this->log("\n\nComputing score:\n");

        for ($i = 0, $len = sizeof($results); $i < $len; $i++) {
            $result = &$results[$i];
            $output = $result['output'];
            $scoreLen = sizeof($output);

            $currScore = 1;
            $bestScore = 1;

            for ($j = 0; $j < $scoreLen; $j++) {
                $weight = $weights
                    ? $weights[$output[$j]['key']]['weight']
                    : 1;
                $score = $weight === 1
                    ? $output[$j]['score']
                    : ($output[$j]['score'] ?: 0.001);
                $nScore = $score * $weight;

                if ($weight !== 1) {
                    $bestScore = min($bestScore, $nScore);
                } else {
                    $output[$j]['nScore'] = $nScore;
                    $currScore *= $nScore;
                }
            }

            $result['score'] = $bestScore == 1
                ? $currScore
                : $bestScore;

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
