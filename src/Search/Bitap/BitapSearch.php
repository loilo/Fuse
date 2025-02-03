<?php

namespace Fuse\Search\Bitap;

use Fuse\Search\Bitap\Constants;
use Fuse\Search\SearchInterface;

use function Fuse\Core\config;
use function Fuse\Helpers\stripDiacritics;
use function Fuse\Search\Bitap\search;
use function Fuse\Search\Bitap\createPatternAlphabet;

class BitapSearch implements SearchInterface
{
    private $chunks = [];
    private $options;
    private $pattern;

    public function __construct(string $pattern, array $options = [])
    {
        $location = $options['location'] ?? config('location');
        $threshold = $options['threshold'] ?? config('threshold');
        $distance = $options['distance'] ?? config('distance');
        $includeMatches = $options['includeMatches'] ?? config('includeMatches');
        $findAllMatches = $options['findAllMatches'] ?? config('findAllMatches');
        $minMatchCharLength = $options['minMatchCharLength'] ?? config('minMatchCharLength');
        $isCaseSensitive = $options['isCaseSensitive'] ?? config('isCaseSensitive');
        $ignoreDiacritics = $options['ignoreDiacritics'] ?? config('ignoreDiacritics');
        $ignoreLocation = $options['ignoreLocation'] ?? config('ignoreLocation');

        $this->options = [
            'location' => $location,
            'threshold' => $threshold,
            'distance' => $distance,
            'includeMatches' => $includeMatches,
            'findAllMatches' => $findAllMatches,
            'minMatchCharLength' => $minMatchCharLength,
            'isCaseSensitive' => $isCaseSensitive,
            'ignoreDiacritics' => $ignoreDiacritics,
            'ignoreLocation' => $ignoreLocation,
        ];

        $pattern = $isCaseSensitive ? $pattern : mb_strtolower($pattern);
        $pattern = $ignoreDiacritics ? stripDiacritics($pattern) : $pattern;
        $this->pattern = $pattern;

        if (mb_strlen($this->pattern) === 0) {
            return;
        }

        $addChunk = function ($pattern, $startIndex): void {
            $this->chunks[] = [
                'pattern' => $pattern,
                'alphabet' => createPatternAlphabet($pattern),
                'startIndex' => $startIndex,
            ];
        };

        $len = mb_strlen($this->pattern);

        if ($len > Constants::MAX_BITS) {
            $i = 0;
            $remainder = $len % Constants::MAX_BITS;
            $end = $len - $remainder;

            while ($i < $end) {
                $addChunk(mb_substr($this->pattern, $i, Constants::MAX_BITS), $i);
                $i += Constants::MAX_BITS;
            }

            if ($remainder) {
                $startIndex = $len - Constants::MAX_BITS;
                $addChunk(mb_substr($this->pattern, $startIndex), $startIndex);
            }
        } else {
            $addChunk($this->pattern, 0);
        }
    }

    public function searchIn(string $text): array
    {
        if (!$this->options['isCaseSensitive']) {
            $text = mb_strtolower($text);
        }

        if ($this->options['ignoreDiacritics']) {
            $text = stripDiacritics($text);
        }

        // Exact match
        if ($this->pattern === $text) {
            $result = [
                'isMatch' => true,
                'score' => 0,
            ];

            if ($this->options['includeMatches']) {
                $result['indices'] = [[0, mb_strlen($text) - 1]];
            }

            return $result;
        }

        // Otherwise, use Bitap algorithm

        $allIndices = [];
        $totalScore = 0;
        $hasMatches = false;

        foreach ($this->chunks as $chunk) {
            $search = search($text, $chunk['pattern'], $chunk['alphabet'], [
                'location' => $this->options['location'] + $chunk['startIndex'],
                'distance' => $this->options['distance'],
                'threshold' => $this->options['threshold'],
                'findAllMatches' => $this->options['findAllMatches'],
                'minMatchCharLength' => $this->options['minMatchCharLength'],
                'includeMatches' => $this->options['includeMatches'],
                'ignoreLocation' => $this->options['ignoreLocation'],
            ]);

            if ($search['isMatch']) {
                $hasMatches = true;
            }

            $totalScore += $search['score'];

            if ($search['isMatch'] && isset($search['indices'])) {
                $allIndices = array_merge($allIndices, $search['indices']);
            }
        }

        $result = [
            'isMatch' => $hasMatches,
            'score' => $hasMatches ? $totalScore / sizeof($this->chunks) : 1,
        ];

        if ($hasMatches && $this->options['includeMatches']) {
            $result['indices'] = $allIndices;
        }

        return $result;
    }
}
