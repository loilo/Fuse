<?php

namespace Fuse\Tools;

const SPACE = '/[^ ]+/';

class Norm
{
    private array $cache = [];
    private int $mantissa;

    public function __construct(int $mantissa = 3)
    {
        $this->mantissa = $mantissa;
    }

    public function get(string $value): float
    {
        $numTokens = preg_match_all(SPACE, $value);

        if (isset($this->cache[$numTokens])) {
            return $this->cache[$numTokens];
        }

        $norm = 1 / sqrt($numTokens);

        // In place of `toFixed(mantissa)`, for faster computation
        $n = round($norm, $this->mantissa);
        $this->cache[$numTokens] = $n;

        return $n;
    }

    public function clear(): void
    {
        $this->cache = [];
    }
}
