<?php

namespace Fuse\Search\Extended;

use Fuse\Search\Bitap\BitapSearch;

use function Fuse\Core\config;

class FuzzyMatch extends BaseMatch
{
    public static string $type = 'fuzzy';
    protected static string $singleRegex = '/^(.*)$/';
    protected static string $multiRegex = '/^"(.*)"$/';

    private BitapSearch $bitapSearch;

    public function __construct(string $pattern, array $options = [])
    {
        parent::__construct($pattern, $options);

        $this->bitapSearch = new BitapSearch($pattern, [
            'location' => $options['location'] ?? config('location'),
            'threshold' => $options['threshold'] ?? config('threshold'),
            'distance' => $options['distance'] ?? config('distance'),
            'includeMatches' => $options['includeMatches'] ?? config('includeMatches'),
            'findAllMatches' => $options['findAllMatches'] ?? config('findAllMatches'),
            'minMatchCharLength' => $options['minMatchCharLength'] ?? config('minMatchCharLength'),
            'isCaseSensitive' => $options['isCaseSensitive'] ?? config('isCaseSensitive'),
            'ignoreDiacritics' => $options['ignoreDiacritics'] ?? config('ignoreDiacritics'),
            'ignoreLocation' => $options['ignoreLocation'] ?? config('ignoreLocation'),
        ]);
    }

    public function search(string $text): array
    {
        return $this->bitapSearch->searchIn($text);
    }
}
