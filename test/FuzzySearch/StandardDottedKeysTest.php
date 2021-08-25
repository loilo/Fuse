<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class StandardDottedKeysTest extends TestCase
{
    private array $list = [
        [
            'title' => 'HTML5',
            'author' => [
                'firstName' => 'Remy',
                'lastName' => 'Sharp',
            ],
        ],
        [
            'title' => 'Angels & Demons',
            'author' => [
                'firstName' => 'rmy',
                'lastName' => 'Brown',
            ],
        ],
    ];

    public function testWeGetMathes(): void
    {
        $fuse = new Fuse($this->list, [
            'keys' => ['title', ['author', 'firstName']],
            'includeMatches' => true,
            'includeScore' => true,
        ]);

        $result = $fuse->search('remy');

        $this->assertCount(2, $result);
    }

    public function testWeGetAResultWithNoMatches(): void
    {
        $fuse = new Fuse(
            [
                [
                    'title' => 'HTML5',
                    'author' => [
                        'first.name' => 'Remy',
                        'last.name' => 'Sharp',
                    ],
                ],
                [
                    'title' => 'Angels & Demons',
                    'author' => [
                        'first.name' => 'rmy',
                        'last.name' => 'Brown',
                    ],
                ],
            ],
            [
                'keys' => ['title', ['author', 'first.name']],
                'includeMatches' => true,
                'includeScore' => true,
            ],
        );

        $result = $fuse->search('remy');

        $this->assertCount(2, $result);
    }

    public function testKeysWithWeights(): void
    {
        $fuse = new Fuse($this->list, [
            'keys' => [
                [
                    'name' => 'title',
                ],
                [
                    'name' => ['author', 'firstName'],
                ],
            ],
            'includeMatches' => true,
            'includeScore' => true,
        ]);

        $result = $fuse->search('remy');

        $this->assertCount(2, $result);
    }
}
