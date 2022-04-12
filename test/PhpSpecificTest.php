<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

/**
 * Tests that only affect the PHP port and are not needed in the original Fuse.js
 */
class PhpSpecificTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testLongMultibyteQuery()
    {
        $fuse = new Fuse(['test']);
        $fuse->search('qüery qüery qüery qüery qüery qüery');
    }
}
