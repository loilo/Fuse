<?php

namespace Fuse\Core;

use Fuse\Core\Register;
use Fuse\Exception\LogicalSearchInvalidQueryForKeyException;
use Fuse\Tools\KeyStore;

use function Fuse\Helpers\Types\{isArray, isAssoc};

function isExpression(array $query): bool
{
    return isset($query[LogicalOperator::AND]) || isset($query[LogicalOperator::OR]);
}

function isPath(array $query): bool
{
    return isset($query[KeyType::PATH]);
}

function isLeaf(array $query): bool
{
    return isAssoc($query) && !isExpression($query);
}

/**
 * @return array[][]
 */
function convertToExplicit(array $query): array
{
    return [
        LogicalOperator::AND => array_map(fn($key) => [$key => $query[$key]], array_keys($query)),
    ];
}

function parseNext(array $query, array $options, bool $auto): array
{
    $keys = array_keys($query);

    $isQueryPath = isPath($query);

    if (!$isQueryPath && sizeof($keys) > 1 && !isExpression($query)) {
        return parseNext(convertToExplicit($query), $options, $auto);
    }

    if (isLeaf($query)) {
        $key = $isQueryPath ? $query[KeyType::PATH] : $keys[0];

        $pattern = $isQueryPath ? $query[KeyType::PATTERN] : $query[$key];

        if (!is_string($pattern)) {
            throw new LogicalSearchInvalidQueryForKeyException($key);
        }

        $obj = [
            'keyId' => KeyStore::createKeyId($key),
            'pattern' => $pattern,
        ];

        if ($auto) {
            $obj['searcher'] = Register::createSearcher($pattern, $options);
        }

        return $obj;
    }

    $node = [
        'children' => [],
        'operator' => $keys[0],
    ];

    foreach ($keys as $key) {
        $value = $query[$key];

        if (isArray($value)) {
            foreach ($value as $item) {
                $node['children'][] = parseNext($item, $options, $auto);
            }
        }
    }

    return $node;
}

// When `auto` is `true`, the parse function will infer and initialize and add
// the appropriate `Searcher` instance
function parse(array $query, array $options, array $additionalOptions = []): array
{
    $auto = $additionalOptions['auto'] ?? true;

    if (!isExpression($query)) {
        $query = convertToExplicit($query);
    }

    return parseNext($query, $options, $auto);
}
