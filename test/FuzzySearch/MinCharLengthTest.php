<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class MinCharLengthTest extends TestCase
{
    private Fuse $fuse;

    public function setUp(): void
    {
        $this->fuse = new Fuse(
            ['t te tes test tes te t'],
            [
                'includeMatches' => true,
                'minMatchCharLength' => 2,
            ],
        );
    }

    public function testWhenSearchingForTheTermTest(): void
    {
        $result = $this->fuse->search('test');

        // We get a match containing 3 indices
        $this->assertCount(3, $result[0]['matches'][0]['indices']);

        // and the first index is a single character
        $this->assertSame(2, $result[0]['matches'][0]['indices'][0][0]);
        $this->assertSame(3, $result[0]['matches'][0]['indices'][0][1]);
    }

    public function testWhenSearchingForAStringShorterThanMinMatchCharLength(): void
    {
        $result = $this->fuse->search('t');

        // We get no results
        $this->assertEmpty($result);
    }

    public function testMainFunctionality(): void
    {
        $fuse = new Fuse(
            [
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
                        'firstName' => 'Dan',
                        'lastName' => 'Brown',
                    ],
                ],
            ],
            [
                'keys' => ['title', 'author.firstName'],
                'includeMatches' => true,
                'includeScore' => true,
                'minMatchCharLength' => 3,
            ],
        );

        $result = $fuse->search('remy');

        // We get a result with no matches
        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]['matches']);
    }
}
