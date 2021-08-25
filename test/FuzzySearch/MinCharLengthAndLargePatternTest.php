<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class MinCharLengthAndLargePatternTest extends TestCase
{
    private Fuse $fuse;

    public function setUp(): void
    {
        $this->fuse = new Fuse(
            [
                'Apple pie is a tasty treat that is always best made by mom! But we love store bought too.',
                'Banana splits are what you want from DQ on a hot day.  But a parfait is even better.',
                'Orange sorbet is just a strange yet satisfying snack.  ' .
                'Chocolate seems to be more of a favourite though.',
            ],
            [
                'includeMatches' => true,
                'findAllMatches' => true,
                'includeScore' => true,
                'minMatchCharLength' => 20,
                'threshold' => 0.6,
                'distance' => 30,
            ],
        );
    }

    public function testWhenSearchingForTheTermAmericanAsApplePieIsOddTreatmentOfSomethingMadeByMom(): void
    {
        $result = $this->fuse->search(
            'American as apple pie is odd treatment of something made by mom',
        );

        // We get exactly 1 result
        $this->assertCount(1, $result);

        // Which corresponds to the first item in the list, with no matches
        $this->assertSame(0, $result[0]['refIndex']);
        $this->assertCount(1, $result[0]['matches']);
    }
}
