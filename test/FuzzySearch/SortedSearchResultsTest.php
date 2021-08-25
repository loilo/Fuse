<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class SortedSearchResultsTest extends TestCase
{
    private Fuse $fuse;

    public function setUp(): void
    {
        $this->fuse = new Fuse(
            [
                [
                    'title' => 'Right Ho Jeeves',
                    'author' => [
                        'firstName' => 'P.D',
                        'lastName' => 'Woodhouse',
                    ],
                ],
                [
                    'title' => 'The Code of the Wooster',
                    'author' => [
                        'firstName' => 'P.D',
                        'lastName' => 'Woodhouse',
                    ],
                ],
                [
                    'title' => 'Thank You Jeeves',
                    'author' => [
                        'firstName' => 'P.D',
                        'lastName' => 'Woodhouse',
                    ],
                ],
            ],
            [
                'keys' => ['title', 'author.firstName', 'author.lastName'],
            ],
        );
    }

    public function testWhenSearchingForTheTermWood(): void
    {
        $result = $this->fuse->search('wood');

        // We get the properly ordered results
        $this->assertSame('The Code of the Wooster', $result[0]['item']['title']);
        $this->assertSame('Right Ho Jeeves', $result[1]['item']['title']);
        $this->assertSame('Thank You Jeeves', $result[2]['item']['title']);
    }
}
