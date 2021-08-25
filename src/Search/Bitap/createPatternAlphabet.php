<?php

namespace Fuse\Search\Bitap;

function createPatternAlphabet(string $pattern): array
{
    $mask = [];

    for ($i = 0, $len = mb_strlen($pattern); $i < $len; $i += 1) {
        $char = mb_substr($pattern, $i, 1);
        $mask[$char] = ($mask[$char] ?? 0) | (1 << $len - $i - 1);
    }

    return $mask;
}
