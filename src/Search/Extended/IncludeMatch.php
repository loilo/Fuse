<?php

namespace Fuse\Search\Extended;

// Token: 'file
// Match type: include-match
// Description: Items that include `file`

class IncludeMatch extends BaseMatch
{
    public static string $type = 'include';
    protected static string $singleRegex = '/^\'(.*)$/';
    protected static string $multiRegex = '/^\'"(.*)"$/';

    public function search(string $text): array
    {
        $location = 0;

        $indices = [];
        $patternLen = mb_strlen($this->pattern);

        // Get all exact matches
        while (($index = mb_strpos($text, $this->pattern, $location)) !== false) {
            $location = $index + $patternLen;
            $indices[] = [$index, $location - 1];
        }

        $isMatch = sizeof($indices) > 0;

        return [
            'isMatch' => $isMatch,
            'score' => $isMatch ? 0 : 1,
            'indices' => $indices,
        ];
    }
}
