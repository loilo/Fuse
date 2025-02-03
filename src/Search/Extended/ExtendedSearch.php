<?php

namespace Fuse\Search\Extended;

use Fuse\Search\Extended\FuzzyMatch;
use Fuse\Search\Extended\IncludeMatch;
use Fuse\Search\SearchInterface;

use function Fuse\Core\config;
use function Fuse\Helpers\stripDiacritics;
use function Fuse\Search\Extended\parseQuery;

/**
 * Command-like searching
 * ======================
 *
 * Given multiple search terms delimited by spaces.e.g. `^jscript .python$ ruby !java`,
 * search in a given text.
 *
 * Search syntax:
 *
 * | Token       | Match type                 | Description                            |
 * | ----------- | -------------------------- | -------------------------------------- |
 * | `jscript`   | fuzzy-match                | Items that fuzzy match `jscript`       |
 * | `=scheme`   | exact-match                | Items that are `scheme`                |
 * | `'python`   | include-match              | Items that include `python`            |
 * | `!ruby`     | inverse-exact-match        | Items that do not include `ruby`       |
 * | `^java`     | prefix-exact-match         | Items that start with `java`           |
 * | `!^earlang` | inverse-prefix-exact-match | Items that do not start with `earlang` |
 * | `.js$`      | suffix-exact-match         | Items that end with `.js`              |
 * | `!.go$`     | inverse-suffix-exact-match | Items that do not end with `.go`       |
 *
 * A single pipe character acts as an OR operator. For example, the following
 * query matches entries that start with `core` and end with either`go`, `rb`,
 * or`py`.
 *
 * ```
 * ^core go$ | rb$ | py$
 * ```
 */

class ExtendedSearch implements SearchInterface
{
    private string $pattern;
    private array $query;
    private array $options;

    public function __construct(string $pattern, array $options = [])
    {
        $isCaseSensitive = $options['isCaseSensitive'] ?? config('isCaseSensitive');
        $ignoreDiacritics = $options['ignoreDiacritics'] ?? config('ignoreDiacritics');

        $this->options = [
            'isCaseSensitive' => $isCaseSensitive,
            'ignoreDiacritics' => $ignoreDiacritics,
            'includeMatches' => $options['includeMatches'] ?? config('includeMatches'),
            'minMatchCharLength' => $options['minMatchCharLength'] ?? config('minMatchCharLength'),
            'findAllMatches' => $options['findAllMatches'] ?? config('findAllMatches'),
            'ignoreLocation' => $options['ignoreLocation'] ?? config('ignoreLocation'),
            'location' => $options['location'] ?? config('location'),
            'threshold' => $options['threshold'] ?? config('threshold'),
            'distance' => $options['distance'] ?? config('distance'),
        ];

        $pattern = $isCaseSensitive ? $pattern : mb_strtolower($pattern);
        $pattern = $ignoreDiacritics ? stripDiacritics($pattern) : $pattern;
        $this->pattern = $pattern;
        $this->query = parseQuery($this->pattern, $this->options);
    }

    public static function condition($_, array $options): bool
    {
        return $options['useExtendedSearch'];
    }

    public function searchIn(string $text): array
    {
        $query = $this->query;

        if (!$query) {
            return [
                'isMatch' => false,
                'score' => 1,
            ];
        }

        // These extended matchers can return an array of matches, as opposed
        // to a single match
        $multiMatchSet = [FuzzyMatch::$type, IncludeMatch::$type];

        $text = $this->options['isCaseSensitive'] ? $text : mb_strtolower($text);
        $text = $this->options['ignoreDiacritics'] ? stripDiacritics($text) : $text;

        $numMatches = 0;
        $allIndices = [];
        $totalScore = 0;

        // ORs
        for ($i = 0, $qLen = sizeof($query); $i < $qLen; $i += 1) {
            $searchers = $query[$i];

            // Reset indices
            $allIndices = [];
            $numMatches = 0;

            // ANDs
            for ($j = 0, $pLen = sizeof($searchers); $j < $pLen; $j += 1) {
                $searcher = $searchers[$j];
                $searchResult = $searcher->search($text);

                if ($searchResult['isMatch']) {
                    $numMatches += 1;
                    $totalScore += $searchResult['score'];
                    if ($this->options['includeMatches'] ?? false) {
                        $type = $searcher::$type;

                        if (in_array($type, $multiMatchSet, true)) {
                            $allIndices = array_merge($allIndices, $searchResult['indices']);
                        } else {
                            $allIndices[] = $searchResult['indices'];
                        }
                    }
                } else {
                    $totalScore = 0;
                    $numMatches = 0;
                    $allIndices = [];
                    break;
                }
            }

            // OR condition, so if TRUE, return
            if ($numMatches) {
                $result = [
                    'isMatch' => true,
                    'score' => $totalScore / $numMatches,
                ];

                if ($this->options['includeMatches'] ?? false) {
                    $result['indices'] = $allIndices;
                }

                return $result;
            }
        }

        // Nothing was matched
        return [
            'isMatch' => false,
            'score' => 1,
        ];
    }
}
