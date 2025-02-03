<?php

namespace Fuse\Search\Extended;

// ❗Order is important. DO NOT CHANGE.
const SEARCHERS = [
    ExactMatch::class,
    IncludeMatch::class,
    PrefixExactMatch::class,
    InversePrefixExactMatch::class,
    InverseSuffixExactMatch::class,
    SuffixExactMatch::class,
    InverseExactMatch::class,
    FuzzyMatch::class,
];

// Regex to split by spaces, but keep anything in quotes together
const SPACE_RE = '/ +(?=(?:[^\\"]*\\"[^\\"]*\\")*[^\\"]*$)/';
const OR_TOKEN = '|';

// Return a 2D array representation of the query, for simpler parsing.
// Example:
// "^core go$ | rb$ | py$ xy$" => [["^core", "go$"], ["rb$"], ["py$", "xy$"]]
/**
 * @return (ExactMatch|FuzzyMatch|IncludeMatch|InverseExactMatch|InversePrefixExactMatch|InverseSuffixExactMatch|PrefixExactMatch|SuffixExactMatch)[][]
 */
function parseQuery(string $pattern, array $options = []): array
{
    $patternParts = explode(OR_TOKEN, $pattern);

    return array_map(function (string $item) use ($options) {
        $itemParts = preg_split(SPACE_RE, trim($item));
        $query = array_values(array_filter($itemParts, fn($item) => strlen(trim($item)) > 0));

        $results = [];
        for ($i = 0, $len = sizeof($query); $i < $len; $i += 1) {
            $queryItem = $query[$i];

            // 1. Handle multiple query match (i.e, once that are quoted, like `"hello world"`)
            $found = false;
            $idx = -1;
            while (!$found && ++$idx < sizeof(SEARCHERS)) {
                $searcher = SEARCHERS[$idx];
                $token = $searcher::isMultiMatch($queryItem);

                if ($token) {
                    $results[] = new $searcher($token, $options);
                    $found = true;
                }
            }

            if ($found) {
                continue;
            }

            // 2. Handle single query matches (i.e, once that are *not* quoted)
            $idx = -1;
            while (++$idx < sizeof(SEARCHERS)) {
                $searcher = SEARCHERS[$idx];
                $token = $searcher::isSingleMatch($queryItem);

                if ($token) {
                    $results[] = new $searcher($token, $options);
                    break;
                }
            }
        }

        return $results;
    }, $patternParts);
}
