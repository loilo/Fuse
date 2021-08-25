<?php

namespace Fuse\Transform;

function transformMatches(array &$result, array &$data): void
{
    $matches = &$result['matches'];
    $data['matches'] = [];

    if (is_null($matches)) {
        return;
    }

    foreach ($matches as &$match) {
        if (is_null($match['indices']) || sizeof($match['indices']) === 0) {
            continue;
        }

        $obj = [
            'indices' => $match['indices'],
            'value' => $match['value'],
        ];

        if (isset($match['key'])) {
            $obj['key'] = $match['key']['src'];
        }

        if (isset($match['idx']) && $match['idx'] > -1) {
            $obj['refIndex'] = $match['idx'];
        }

        $data['matches'][] = $obj;
    }
}
