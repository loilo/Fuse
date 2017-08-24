<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

// Set new list on Fuse
class SearchLocationTest extends TestCase
{
    protected static $fuse;

    public static function setUpBeforeClass()
    {
        static::$fuse = new Fuse([[ 'name' => 'Hello World' ]], [
            'keys' => ['name'],
            'includeScore' => true,
            'includeMatches' => true
        ]);
    }

    // When searching for the term "wor"
    public function testSearchWor()
    {
        $result = static::$fuse->search('wor');

        // ...we get a non empty list...
        $this->assertNotEmpty($result);

        // ...whose indices are found...
        $matches = $result[0]['matches'];
        $a = $matches[0]['indices'][0];
        $b = $matches[0]['indices'][1];

        $this->assertEquals([4, 4], $a);
        $this->assertEquals([6, 8], $b);

        // ...with original text value.
        $this->assertEquals('Hello World', $matches[0]['value']);
    }
}
