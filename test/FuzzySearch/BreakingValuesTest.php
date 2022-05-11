<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class BreakingValuesTest extends TestCase
{
    public function testBooleansAreStillProcessed(): void
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

    public function testObjectValuesAreIgnored(): void
    {
        $fuse = new Fuse(
            [
                [
                    'a' => 'hello',
                ],
                [
                    'a' => [],
                ],
            ],
            [
                'keys' => ['a'],
            ],
        );

        $result = $fuse->search('hello');

        $this->assertCount(1, $result);
    }
}
