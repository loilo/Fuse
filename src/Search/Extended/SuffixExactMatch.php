<?php

namespace Fuse\Search\Extended;

// Token: .file$
// Match type: suffix-exact-match
// Description: Items that end with `.file`

class SuffixExactMatch extends BaseMatch
{
    public static string $type = 'suffix-exact';
    protected static string $singleRegex = '/^(.*)\\$$/';
    protected static string $multiRegex = '/^"(.*)"\\$$/';

    public function search(string $text): array
    {
        $textLength = mb_strlen($text);
        $patternLength = mb_strlen($this->pattern);

        $isMatch = mb_substr($text, -$patternLength) === $this->pattern;

        return [
            'isMatch' => $isMatch,
            'score' => $isMatch ? 0 : 1,
            'indices' => [$textLength - $patternLength, $textLength - 1],
        ];
    }
}
