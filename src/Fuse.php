<?php

namespace Fuse;

use Fuse\Core\LogicalOperator;
use Fuse\Core\Register;
use Fuse\Search\Extended\ExtendedSearch;
use Fuse\Search\SearchInterface;
use Fuse\Tools\FuseIndex;
use Fuse\Tools\KeyStore;
use Fuse\Tools\MaxHeap;

use function Fuse\Core\{computeScore, computeScoreSingle};
use function Fuse\Core\config;
use function Fuse\Core\format;
use function Fuse\Core\parse;
use function Fuse\Helpers\Types\{isArray, isNumber};

class Fuse
{
    public static function createIndex(array $keys, array $docs, array $options = [])
    {
        return FuseIndex::create($keys, $docs, $options);
    }

    public static function parseIndex(array $data, array $options = [])
    {
        return FuseIndex::parse($data, $options);
    }

    public static function config(...$args)
    {
        return config(...$args);
    }

    /**
     * @param string ...$plugins
     */
    public static function use(...$plugins): void
    {
        foreach ($plugins as $plugin) {
            Register::register($plugin);
        }
    }

    private KeyStore $keyStore;
    private ?string $lastQuery = null;
    private ?SearchInterface $lastSearcher = null;
    private array $options;
    private array $docs;
    private FuseIndex $myIndex;

    public function __construct($docs, array $options = [], ?FuseIndex $index = null)
    {
        $this->options = array_merge((array) config(), $options);

        // No need to enforce an extra environment variable for
        // extended search in a PHP world
        if ($this->options['useExtendedSearch']) {
            Register::register(ExtendedSearch::class);
        }

        $this->keyStore = new KeyStore($this->options['keys']);

        $this->setCollection($docs, $index);
    }

    private function getSearcher(string $query)
    {
        if ($this->lastQuery === $query) {
            return $this->lastSearcher;
        }

        $searcher = Register::createSearcher($query, $this->options);
        $this->lastQuery = $query;
        $this->lastSearcher = $searcher;
        
        return $searcher;
    }

    public function getCollection(): array
    {
        return $this->docs;
    }

    public function setCollection(array $docs, ?FuseIndex $index = null): void
    {
        $this->docs = $docs;

        $this->myIndex =
            $index ??
            FuseIndex::create($this->options['keys'], $this->docs, [
                'getFn' => $this->options['getFn'],
                'fieldNormWeight' => $this->options['fieldNormWeight'],
            ]);
    }

    public function add($doc): void
    {
        if (is_null($doc)) {
            return;
        }

        $this->docs[] = $doc;
        $this->myIndex->add($doc);
    }

    public function remove(?callable $predicate = null): array
    {
        $predicate = is_null($predicate) ? fn($doc, $i): bool => false : $predicate;

        $results = [];
        $indicesToRemove = [];

        for ($i = 0, $len = sizeof($this->docs); $i < $len; $i += 1) {
            $doc = $this->docs[$i];

            if ($predicate($doc, $i)) {
                $results[] = $doc;
                $indicesToRemove[] = $i;
            }
        }

        if (!empty($indicesToRemove)) {
            // Remove from docs in reverse to preserve indices
            for ($i = sizeof($indicesToRemove) - 1; $i >= 0; $i -= 1) {
                array_splice($this->docs, $indicesToRemove[$i], 1);
            }
        }

        $this->myIndex->removeAll($indicesToRemove);

        return $results;
    }

    public function removeAt(int $idx): void
    {
        array_splice($this->docs, $idx, 1);
        $this->myIndex->removeAt($idx);
    }

    public function getIndex(): FuseIndex
    {
        return $this->myIndex;
    }

    public function search($query, array $options = []): array
    {
        $limit = $options['limit'] ?? -1;

        $useHeap = isNumber($limit) && $limit > 0 && is_string($query);

        if ($useHeap) {
            $heap = new MaxHeap($limit);

            if (is_string($this->docs[0] ?? null)) {
                $this->searchStringList($query, [
                    'heap' => $heap,
                    'ignoreFieldNorm' => $this->options['ignoreFieldNorm'],
                ]);
            } else {
                $this->searchObjectList($query, [
                    'heap' => $heap,
                    'ignoreFieldNorm' => $this->options['ignoreFieldNorm'],
                ]);
            }

            $results = $heap->extractSorted($this->options['sortFn']);
        } else {
            $results = is_string($query)
                ? (is_string($this->docs[0] ?? null)
                    ? $this->searchStringList($query)
                    : $this->searchObjectList($query))
                : $this->searchLogical($query);
    
            computeScore($results, [
                'ignoreFieldNorm' => $this->options['ignoreFieldNorm'],
            ]);
    
            if ($this->options['shouldSort']) {
                usort($results, $this->options['sortFn']);
            }
    
            if (isNumber($limit) && $limit > -1) {
                $results = array_slice($results, 0, $limit);
            }
        }

        return format($results, $this->docs, [
            'includeMatches' => $this->options['includeMatches'],
            'includeScore' => $this->options['includeScore'],
        ]);
    }

    private function searchStringList(string $query, array $options = []): ?array
    {
        $heap = $options['heap'] ?? null;
        $ignoreFieldNorm = $options['ignoreFieldNorm'] ?? null;

        $searcher = $this->getSearcher($query);
        $results = $heap ? null : [];

        // Iterate over every string in the index
        foreach ($this->myIndex->records as ['v' => $text, 'i' => $idx, 'n' => $norm]) {
            if (is_null($text)) {
                continue;
            }

            $searchInResult = $searcher->searchIn($text);

            if ($searchInResult['isMatch']) {
                $result = [
                    'item' => $text,
                    'idx' => $idx,
                    'matches' => [
                        [
                            'score' => $searchInResult['score'],
                            'value' => $text,
                            'norm' => $norm,
                            'indices' => $searchInResult['indices'] ?? null,
                        ],
                    ],
                ];

                if ($heap) {
                    computeScoreSingle($result, [
                        'ignoreFieldNorm' => $ignoreFieldNorm,
                    ]);

                    if ($heap->shouldInsert($result['score'])) {
                        $heap->insert($result);
                    }
                } else {
                    $results[] = $result;
                }
            }
        }

        return $results;
    }

    private function searchLogical(array $query): array
    {
        $expression = parse($query, $this->options);

        $evaluate = function (array $node, $item, int $idx) use (&$evaluate) {
            if (!isset($node['children'])) {
                $matches = $this->findMatches([
                    'key' => $this->keyStore->get($node['keyId']),
                    'value' => $this->myIndex->getValueForItemAtKeyId($item, $node['keyId']),
                    'searcher' => $node['searcher'],
                ]);

                if (sizeof($matches) > 0) {
                    return [
                        [
                            'idx' => $idx,
                            'item' => $item,
                            'matches' => $matches,
                        ],
                    ];
                }

                return [];
            }

            $res = [];
            for ($i = 0, $len = sizeof($node['children']); $i < $len; $i += 1) {
                $child = $node['children'][$i];
                $result = $evaluate($child, $item, $idx);
                if (sizeof($result) > 0) {
                    array_push($res, ...$result);
                } elseif (isset($node['operator']) && $node['operator'] === LogicalOperator::AND) {
                    return [];
                }
            }
            return $res;
        };

        $records = $this->myIndex->records;
        $resultMap = [];
        $results = [];

        foreach ($records as ['$' => $item, 'i' => $idx]) {
            if (!is_null($item)) {
                $expResults = $evaluate($expression, $item, $idx);

                if (sizeof($expResults) > 0) {
                    // Dedupe when adding
                    if (!isset($resultMap[$idx])) {
                        $resultMap[$idx] = [
                            'idx' => $idx,
                            'item' => $item,
                            'matches' => [],
                        ];

                        $results[] = &$resultMap[$idx];
                    }

                    foreach ($expResults as ['matches' => $matches]) {
                        array_push($resultMap[$idx]['matches'], ...$matches);
                    }
                }
            }
        }

        return $results;
    }

    private function searchObjectList(string $query, array $options = [])
    {
        $heap = $options['heap'] ?? null;
        $ignoreFieldNorm = $options['ignoreFieldNorm'] ?? null;

        $searcher = $this->getSearcher($query);
        $results = $heap ? null : [];

        // List is an array of arrays
        foreach ($this->myIndex->records as ['$' => $item, 'i' => $idx]) {
            if (is_null($item)) {
                continue;
            }

            $matches = [];

            // Iterate over every key (i.e, path), and fetch the value at that key
            foreach ($this->myIndex->keys as $keyIndex => $key) {
                array_push(
                    $matches,
                    ...$this->findMatches([
                        'key' => $key,
                        'value' => $item[$keyIndex] ?? null,
                        'searcher' => $searcher,
                    ]),
                );
            }

            if (!empty($matches)) {
                $result = [
                    'idx' => $idx,
                    'item' => $item,
                    'matches' => $matches,
                ];

                if ($heap) {
                    computeScoreSingle($result, [
                        'ignoreFieldNorm' => $ignoreFieldNorm,
                    ]);

                    if ($heap->shouldInsert($result['score'])) {
                        $heap->insert($result);
                    }
                } else {
                    $results[] = $result;
                }
            }
        }

        return $results;
    }

    private function findMatches($request)
    {
        $value = $request['value'];
        $key = $request['key'];
        $searcher = $request['searcher'];

        if (is_null($value)) {
            return [];
        }

        $matches = [];

        if (isArray($value)) {
            foreach ($value as ['v' => $text, 'i' => $idx, 'n' => $norm]) {
                if (is_null($text)) {
                    return;
                }

                $searchInResult = $searcher->searchIn($text);

                if ($searchInResult['isMatch']) {
                    $matches[] = [
                        'score' => $searchInResult['score'],
                        'key' => $key,
                        'value' => $text,
                        'idx' => $idx,
                        'norm' => $norm,
                        'indices' => $searchInResult['indices'] ?? null,
                    ];
                }
            }
        } else {
            ['v' => $text, 'n' => $norm] = $value;

            $searchInResult = $searcher->searchIn($text);

            if ($searchInResult['isMatch']) {
                $matches[] = [
                    'score' => $searchInResult['score'],
                    'key' => $key,
                    'value' => $text,
                    'norm' => $norm,
                    'indices' => $searchInResult['indices'] ?? null,
                ];
            }
        }

        return $matches;
    }
}
