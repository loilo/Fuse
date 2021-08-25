<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class SearchLocationTest extends TestCase
{
    private Fuse $fuse;

    public function setUp(): void
    {
        $this->fuse = new Fuse(
            [
                [
                    'name' => 'Hello World',
                ],
            ],
            [
                'keys' => ['name'],
                'includeScore' => true,
                'includeMatches' => true,
            ],
        );
    }

    public function testWhenSearchingForTheTermWor(): void
    {
        $result = $this->fuse->search('wor');

        // We get a list whose indices are found
        $this->assertSame([4, 4], $result[0]['matches'][0]['indices'][0]);
        $this->assertSame([6, 8], $result[0]['matches'][0]['indices'][1]);

        // with original text values
        $this->assertSame('Hello World', $result[0]['matches'][0]['value']);
    }
}
