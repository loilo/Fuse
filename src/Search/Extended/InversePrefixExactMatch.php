<?php

namespace Fuse\Search\Extended;

// Token: !^fire
// Match type: inverse-prefix-exact-match
// Description: Items that do not start with `fire`

class InversePrefixExactMatch extends BaseMatch
{
    public static string $type = 'inverse-prefix-exact';
    protected static string $singleRegex = '/^!\\^(.*)$/';
    protected static string $multiRegex = '/^!\\^"(.*)"$/';

    public function search(string $text): array
    {
        $isMatch = mb_substr($text, 0, mb_strlen($this->pattern)) !== $this->pattern;

        return [
            'isMatch' => $isMatch,
            'score' => $isMatch ? 0 : 1,
            'indices' => [0, mb_strlen($text) - 1],
        ];
    }
}
