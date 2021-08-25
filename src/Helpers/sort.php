<?php

namespace Fuse\Helpers;

function sort(array $a, array $b): int
{
    return $a['score'] <=> $b['score'] ?: $a['idx'] <=> $b['idx'];
}
