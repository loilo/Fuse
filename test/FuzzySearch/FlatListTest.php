<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class FlatListTest extends TestCase
{
    private Fuse $fuse;

    public function setUp(): void
    {
        $this->fuse = new Fuse(['Apple', 'Orange', 'Banana']);
    }

    public function testWhenSearchingForTheTermApple(): void
    {
        $result = $this->fuse->search('Apple');

        // we get a list of exactly 1 item
        $this->assertCount(1, $result);

        // whose value is the index 0, representing ["Apple"]
        $this->assertSame(0, $result[0]['refIndex']);
    }

    public function testWhenPerformingAFuzzySearchForTheTermRan(): void
    {
        $result = $this->fuse->search('ran');

        // we get a list of containing 2 items
        $this->assertCount(2, $result);

        // whose values represent the indices of ["Orange", "Banana"]
        $this->assertSame(1, $result[0]['refIndex']);
        $this->assertSame(2, $result[1]['refIndex']);
    }

    public function testWhenPerformingAFuzzySearchForTheTermNan(): void
    {
        $result = $this->fuse->search('nan');

        // we get a list of containing 2 items
        $this->assertCount(2, $result);

        // whose values represent the indices of ["Banana", "Orange"]
        $this->assertSame(2, $result[0]['refIndex']);
        $this->assertSame(1, $result[1]['refIndex']);
    }

    public function testWhenPerformingAFuzzySearchForTheTermNanWithALimitOf1Result(): void
    {
        $result = $this->fuse->search('nan', ['limit' => 1]);

        // we get a list of containing 1 item: [2]
        $this->assertCount(1, $result);

        // whose values represent the indices of ["Banana", "Orange"]
        $this->assertSame(2, $result[0]['refIndex']);
    }
}
