<?php

namespace Fuse\Search\Extended;

// Token: 'file
// Match type: exact-match
// Description: Items that are `file`

class ExactMatch extends BaseMatch
{
    public static string $type = 'exact';
    protected static string $singleRegex = '/^=(.*)$/';
    protected static string $multiRegex = '/^="(.*)"$/';

    public function search(string $text): array
    {
        $isMatch = $text === $this->pattern;

        return [
            'isMatch' => $isMatch,
            'score' => $isMatch ? 0 : 1,
            'indices' => [0, mb_strlen($this->pattern) - 1],
        ];
    }
}
