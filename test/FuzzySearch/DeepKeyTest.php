<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Fuse\Fuse;

class DeepKeyTest extends TestCase
{
    use ArraySubsetAsserts;

    private Fuse $fuse;

    public function setUp(): void
    {
        $this->fuse = new Fuse(
            [
                [
                    'title' => 'Old Man\'s War',
                    'author' => [
                        'firstName' => 'John',
                        'lastName' => 'Scalzi',
                    ],
                ],
                [
                    'title' => 'The Lock Artist',
                    'author' => [
                        'firstName' => 'Steve',
                        'lastName' => 'Hamilton',
                    ],
                ],
                [
                    'title' => 'HTML5',
                ],
                [
                    'title' => 'A History of England',
                    'author' => [
                        'firstName' => 1066,
                        'lastName' => 'Hastings',
                    ],
                ],
            ],
            [
                'keys' => ['title', 'author.firstName'],
            ],
        );
    }

    public function testWhenSearchingForTheTermStve(): void
    {
        $result = $this->fuse->search('Stve');

        // we get a list containing at least 1 item
        $this->assertGreaterThanOrEqual(1, sizeof($result));

        // and the first item has the matching key/value pairs
        $this->assertArraySubset(
            [
                'title' => 'The Lock Artist',
                'author' => [
                    'firstName' => 'Steve',
                    'lastName' => 'Hamilton',
                ],
            ],
            $result[0]['item'],
        );
    }

    public function testWhenSearchingForTheTerm106(): void
    {
        $result = $this->fuse->search('106');

        // we get a list of exactly 1 item
        $this->assertCount(1, $result);

        // whose value matches
        $this->assertArraySubset(
            [
                'title' => 'A History of England',
                'author' => [
                    'firstName' => 1066,
                    'lastName' => 'Hastings',
                ],
            ],
            $result[0]['item'],
        );
    }
}
