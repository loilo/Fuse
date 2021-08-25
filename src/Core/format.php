<?php

namespace Fuse\Core;

use function Fuse\Core\config;

function format(array $results, array $docs, array $options = []): array
{
    $includeMatches = $options['includeMatches'] ?? config('includeMatches');
    $includeScore = $options['includeScore'] ?? config('includeScore');

    $transformers = [];

    if ($includeMatches) {
        $transformers[] = '\Fuse\Transform\transformMatches';
    }
    if ($includeScore) {
        $transformers[] = '\Fuse\Transform\transformScore';
    }

    return array_map(function (array $result) use ($docs, $transformers) {
        $data = [
            'item' => $docs[$result['idx']],
            'refIndex' => $result['idx'],
        ];

        if (sizeof($transformers) > 0) {
            foreach ($transformers as $transformer) {
                $transformer($result, $data);
            }
        }

        return $data;
    }, $results);
}
