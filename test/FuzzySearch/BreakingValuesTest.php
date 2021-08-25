<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class BreakingValuesTest extends TestCase
{
    public function testNonStringsAreStillProcessed(): void
    {
        $fuse = new Fuse(
            [
                [
                    'first' => false,
                ],
            ],
            [
                'keys' => [
                    [
                        'name' => 'first',
                    ],
                ],
            ],
        );

        $result = $fuse->search('fa');

        $this->assertCount(1, $result);
    }
}
