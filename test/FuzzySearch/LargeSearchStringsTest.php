<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class LargeSearchStringsTest extends TestCase
{
    public function testFindsNoMatchesWhenStringIsLargerThan32Characters(): void
    {
        $list = [
            [
                'text' => 'pizza',
            ],
            [
                'text' => 'feast',
            ],
            [
                'text' => 'where in the world is carmen san diego',
            ],
        ];

        $fuse = new Fuse($list, [
            'shouldSort' => true,
            'includeScore' => true,
            'threshold' => 0.6,
            'keys' => ['text'],
        ]);

        $result = $fuse->search('where exctly is carmen in the world san diego');

        // We get the properly ordered results
        $this->assertCount(1, $result);
        $this->assertSame($list[2]['text'], $result[0]['item']['text']);
    }

    public function testMatchesWithVeryLongPatterns(): void
    {
        $fuse = new Fuse(
            [
                [
                    'text' => 'pizza',
                ],
                [
                    'text' => 'feast',
                ],
                [
                    'text' => 'where in the world is carmen san diego',
                ],
            ],
            [
                'shouldSort' => true,
                'includeScore' => true,
                'threshold' => 0.6,
                'keys' => ['text'],
            ],
        );

        $patterns = [];
        for ($i = 0; $i < 66; ++$i) {
            $patterns[] = str_repeat('w', $i);
        }

        $this->assertEmpty($fuse->search($patterns[32]));
        $this->assertEmpty($fuse->search($patterns[33]));
        $this->assertEmpty($fuse->search($patterns[34]));
        $this->assertEmpty($fuse->search($patterns[64]));
        $this->assertEmpty($fuse->search($patterns[65]));
    }

    public function testWithHyphens(): void
    {
        $searchText = 'leverage-streams-to';

        $fuse = new Fuse(
            [
                [
                    'name' => 'Streaming Service',
                    'description' => 'Leverage-streams-to-ingest, analyze, monitor.',
                    'tag' => 'Free',
                ],
            ],
            [
                'distance' => 1000,
                'includeScore' => true,
                'includeMatches' => true,
                'keys' => ['name', 'tag', 'description'],
                'minMatchCharLength' => floor(mb_strlen($searchText) * 0.6),
                'shouldSort' => false,
            ],
        );

        $results = $fuse->search($searchText);

        $this->assertEquals(
            [
                [
                    'indices' => [[0, 18]],
                    'key' => 'description',
                    'value' => 'Leverage-streams-to-ingest, analyze, monitor.',
                ],
            ],
            $results[0]['matches'],
        );
    }

    public function testWithSpaces(): void
    {
        $searchText = 'leverage streams to';

        $fuse = new Fuse(
            [
                [
                    'name' => 'Streaming Service',
                    'description' => 'Leverage streams to ingest, analyze, monitor.',
                    'tag' => 'Free',
                ],
            ],
            [
                'distance' => 1000,
                'includeScore' => true,
                'includeMatches' => true,
                'keys' => ['name', 'tag', 'description'],
                'minMatchCharLength' => floor(mb_strlen($searchText) * 0.6),
                'shouldSort' => false,
            ],
        );

        $results = $fuse->search($searchText);

        $this->assertEquals(
            [
                [
                    'indices' => [[0, 18]],
                    'key' => 'description',
                    'value' => 'Leverage streams to ingest, analyze, monitor.',
                ],
            ],
            $results[0]['matches'],
        );
    }
}
