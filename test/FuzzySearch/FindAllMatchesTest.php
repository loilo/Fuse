<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class FindAllMatchesTest extends TestCase
{
    private Fuse $fuse;

    public function setUp(): void
    {
        $this->fuse = new Fuse(
            ['t te tes test tes te t'],
            [
                'includeMatches' => true,
                'findAllMatches' => true,
            ],
        );
    }

    public function testWhenSearchingForTheTermTest(): void
    {
        $result = $this->fuse->search('test');

        // We get a match containing 7 indices
        $this->assertCount(7, $result[0]['matches'][0]['indices']);

        // and the first index is a single character
        $this->assertSame(0, $result[0]['matches'][0]['indices'][0][0]);
        $this->assertSame(0, $result[0]['matches'][0]['indices'][0][1]);
    }
}
