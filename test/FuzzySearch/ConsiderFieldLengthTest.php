<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class ConsiderFieldLengthTest extends TestCase
{
    private array $list = [
        [
            'ISBN' => '0312696957',
            'title' => 'The Lock war Artist nonficon',
            'author' => 'Steve Hamilton',
            'tags' => ['fiction war hello no way'],
        ],
        [
            'ISBN' => '0765348276',
            'title' => 'Old Man\'s War',
            'author' => 'John Scalzi',
            'tags' => ['fiction no'],
        ],
    ];

    public function testTheEntryWithTheShorterFieldLengthAppearsFirst(): void
    {
        $fuse = new Fuse($this->list, [
            'keys' => ['title'],
        ]);

        $result = $fuse->search('war');

        $this->assertCount(2, $result);
        $this->assertSame('0765348276', $result[0]['item']['ISBN']);
        $this->assertSame('0312696957', $result[1]['item']['ISBN']);
    }

    public function testWeightedEntriesStillAreGivenHighPrecedence(): void
    {
        $fuse = new Fuse($this->list, [
            'keys' => [
                [
                    'name' => 'tags',
                    'weight' => 0.8,
                ],
                [
                    'name' => 'title',
                    'weight' => 0.2,
                ],
            ],
        ]);

        $result = $fuse->search('war');

        $this->assertCount(2, $result);
        $this->assertSame('0312696957', $result[0]['item']['ISBN']);
        $this->assertSame('0765348276', $result[1]['item']['ISBN']);
    }
}
