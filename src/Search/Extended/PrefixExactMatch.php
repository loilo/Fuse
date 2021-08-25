<?php

namespace Fuse\Search\Extended;

// Token: ^file
// Match type: prefix-exact-match
// Description: Items that start with `file`

class PrefixExactMatch extends BaseMatch
{
    public static string $type = 'prefix-exact';
    protected static string $singleRegex = '/^\\^(.*)$/';
    protected static string $multiRegex = '/^\\^"(.*)"$/';

    public function search(string $text): array
    {
        $patternLength = mb_strlen($this->pattern);

        $isMatch = mb_substr($text, 0, $patternLength) === $this->pattern;

        return [
            'isMatch' => $isMatch,
            'score' => $isMatch ? 0 : 1,
            'indices' => [0, $patternLength - 1],
        ];
    }
}
