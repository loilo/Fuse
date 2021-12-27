<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class ScoringTest extends TestCase
{
    private static array $defaultList = [
        'Stove',
        'My good friend Steve from college'
    ];
    private static array $defaultOptions = [];
    
    private static function setupFuse($itemList = null, $overwriteOptions = [])
    {
        $list = $itemList ?? static::$defaultList;
        $options = array_merge(static::$defaultOptions, $overwriteOptions);
      
        return new Fuse($list, $options);
    }

    public function testIgnoreFieldNormOff()
    {
        $fuse = $this::setupFuse();
        $result = $fuse->search('Steve');

        // we get a list of containing 2 items
        $this->assertCount(2, $result);

        // whose values represent the indices of ["Stove", "My good friend Steve from college"]
        $this->assertSame(0, $result[0]['refIndex']);
        $this->assertSame(1, $result[1]['refIndex']);
    }

    public function testIgnoreFieldNormOffAndFieldNormWeightDecreased()
    {
        $fuse = $this::setupFuse(null, [ 'fieldNormWeight' => 0.15 ]);
        $result = $fuse->search('Steve');

        // we get a list of containing 2 items
        $this->assertCount(2, $result);

        // whose values represent the indices of ["My good friend Steve from college", "Stove"]
        $this->assertSame(1, $result[0]['refIndex']);
        $this->assertSame(0, $result[1]['refIndex']);
    }
}
