<?php namespace Fuse\Bitap;

function pattern_alphabet($pattern)
{
    $mask = [];
    $len = mb_strlen($pattern);

    for ($i = 0; $i < $len; $i++) {
        $mask[mb_substr($pattern, $i, 1)] = 0;
    }

    for ($i = 0; $i < $len; $i++) {
        $mask[mb_substr($pattern, $i, 1)] |= 1 << ($len - $i - 1);
    }

    return $mask;
}
