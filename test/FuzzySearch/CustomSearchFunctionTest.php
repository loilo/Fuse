<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Fuse\Fuse;

class CustomSearchFunctionTest extends TestCase
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
            ],
            [
                'keys' => ['title', 'author.firstName'],
                'getFn' => function ($obj) {
                    if (!$obj) {
                        return null;
                    }
                    $obj = $obj['author']['lastName'];
                    return $obj;
                },
            ],
        );
    }

    public function testWhenSearchingForTheTermHmlt(): void
    {
        $result = $this->fuse->search('Hmlt');

        // we get a list containing at least 1 item
        $this->assertCount(1, $result);

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

    public function testWhenSearchingForTheTermStve(): void
    {
        $result = $this->fuse->search('Stve');

        // we get a list of exactly 0 items
        $this->assertCount(0, $result);
    }
}
