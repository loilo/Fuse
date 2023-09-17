<?php

namespace Fuse\Tools;

const SPACE = '/[^ ]+/';

class Norm
{
    private array $cache = [];
    private float $weight;
    private int $mantissa;

    public function __construct(float $weight = 1, int $mantissa = 3)
    {
        $this->weight = $weight;
        $this->mantissa = $mantissa;
    }

    public function get(string $value): float
    {
        $numTokens = preg_match_all(SPACE, $value);

        if (isset($this->cache[$numTokens])) {
            return $this->cache[$numTokens];
        }

        // Default function is 1/sqrt(x), weight makes that variable
        $norm = 1 / pow($numTokens, 0.5 * $this->weight);

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
