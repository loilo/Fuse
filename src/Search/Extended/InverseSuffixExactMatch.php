<?php

namespace Fuse\Search\Extended;

// Token: !.file$
// Match type: inverse-suffix-exact-match
// Description: Items that do not end with `.file`

class InverseSuffixExactMatch extends BaseMatch
{
    public static string $type = 'inverse-suffix-exact';
    protected static string $singleRegex = '/^!(.*)\\$$/';
    protected static string $multiRegex = '/^!"(.*)"\\$$/';

    public function search(string $text): array
    {
        $isMatch = mb_substr($text, -mb_strlen($this->pattern)) !== $this->pattern;

        return [
            'isMatch' => $isMatch,
            'score' => $isMatch ? 0 : 1,
            'indices' => [0, mb_strlen($text) - 1],
        ];
    }
}
