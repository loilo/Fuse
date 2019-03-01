<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';

class FruitTest extends TestCase
{
    protected static $fuse;

    public static function setUpBeforeClass()
    {
        $fruits = ['Apple', 'Orange', 'Banana'];
        static::$fuse = new \Fuse\Fuse($fruits);
    }

    // Searching for "Apple" we expect...
    public function testSearchApple()
    {
        $result = static::$fuse->search('Apple');

        // ...one result...
        $this->assertCount(1, $result);

        // ...whose value is the index 0, representing ["Apple"]
        $this->assertEquals(0, $result[0]);
    }

    // Searching for "ran" we expect...
    public function testFuzzySearchRan()
    {
        $result = static::$fuse->search('ran');

        // ...a list containing 2 items: [1, 2]...
        $this->assertCount(2, $result);

        // ...whose values represent the indices of ["Orange", "Banana"]
        $this->assertEquals(1, $result[0]);
        $this->assertEquals(2, $result[1]);
    }

    // Searching for "nan" we expect...
    public function testFuzzySearchNan()
    {
        $result = static::$fuse->search('nan');

        // ...a list containing 2 items: [2, 1]
        $this->assertCount(2, $result);

        // ...whose values represent the indices of ["Banana", "Orange"]
        $this->assertEquals(2, $result[0]);
        $this->assertEquals(1, $result[1]);
    }

    // Searching for "nan" with a limit of 1 we expect...
    public function testFuzzySearchNanLimited()
    {
        $result = static::$fuse->search('nan', [ 'limit' => 1 ]);

        // ...a list containing 1 items
        $this->assertCount(1, $result);

        // ...whose values represent the index of "Banana"
        $this->assertEquals(2, $result[0]);
    }
}
