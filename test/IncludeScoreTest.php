<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class IncludeScoreTest extends TestCase
{
    protected static $fuse;

    public static function setUpBeforeClass()
    {
        static::$fuse = new Fuse([
            'Apple',
            'Orange',
            'Banana'
        ], [
            'includeScore' => true
        ]);
    }

    // When searching for the term "Apple"...
    public function testSearchApple()
    {
        $result = static::$fuse->search('Apple');

        // ...we get a list of containing 1 item, which is an exact match...
        $this->assertCount(1, $result);

        // ...whose value and score exist
        $this->assertEquals(0, $result[0]['item']);
        $this->assertEquals(0, $result[0]['score']);
    }

    // When performing a fuzzy search for the term "ran"...
    public function testSearchRan()
    {
        $result = static::$fuse->search('ran');

        // ...we get a list of containing 2 items...
        $this->assertCount(2, $result);

        // ...whose items represent the indices, and have non-zero scores
        $this->assertEquals(1, $result[0]['item']);
        $this->assertEquals(2, $result[1]['item']);
        $this->assertNotEquals(0, $result[0]['score']);
        $this->assertNotEquals(0, $result[1]['score']);
    }
}
