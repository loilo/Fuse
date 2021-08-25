<?php

namespace Fuse\Search\Extended;

// Token: !fire
// Match type: inverse-exact-match
// Description: Items that do not include `fire`

class InverseExactMatch extends BaseMatch
{
    public static string $type = 'inverse-exact';
    protected static string $singleRegex = '/^!(.*)$/';
    protected static string $multiRegex = '/^!"(.*)"$/';

    public function search(string $text): array
    {
        $index = mb_strpos($text, $this->pattern);
        $isMatch = $index === false;

        return [
            'isMatch' => $isMatch,
            'score' => $isMatch ? 0 : 1,
            'indices' => [0, mb_strlen($text) - 1],
        ];
    }
}
