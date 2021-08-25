<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class SetNewListTest extends TestCase
{
    private Fuse $fuse;

    public function setUp(): void
    {
        $this->fuse = new Fuse([]);
        $this->fuse->setCollection(['Onion', 'Lettuce', 'Broccoli']);
    }

    public function testWhenSearchingForTheTermLettuce(): void
    {
        $result = $this->fuse->search('Lettuce');

        // we get a list of exactly 1 item
        $this->assertCount(1, $result);

        // whose value is the index 0, representing ["Apple"]
        $this->assertSame(1, $result[0]['refIndex']);
    }
}
