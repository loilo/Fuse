<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class OptionsTest extends TestCase
{
    protected static $fuse;

    // Searching with default options
    public function testDefaultOptions()
    {
        static::$fuse = new Fuse([
            't te tes test tes te t'
        ], [
            'includeMatches' => true
        ]);

        $result = static::$fuse->search('test');

        // We get a match containing 4 indices
        $this->assertCount(4, $result[0]['matches'][0]['indices']);

        // The first index is a single character
        $this->assertEquals(0, $result[0]['matches'][0]['indices'][0][0], 0);
        $this->assertEquals(0, $result[0]['matches'][0]['indices'][0][1]);

        // When the seach pattern is longer than maxPatternLength and contains RegExp special characters, it does not throw
        static::$fuse->search('searching with a sufficiently long string sprinkled with ([ )] *+^$ etc.');
    }

    // Searching with findAllMatches options
    public function testFindAllMatches()
    {
        static::$fuse = new Fuse([
            't te tes test tes te t'
        ], [
            'includeMatches' => true,
            'findAllMatches' => true
        ]);

        $result = static::$fuse->search('test');

        // We get a match containing 7 indices
        $this->assertCount(7, $result[0]['matches'][0]['indices']);

        // The first index is a single character
        $this->assertEquals(0, $result[0]['matches'][0]['indices'][0][0]);
        $this->assertEquals(0, $result[0]['matches'][0]['indices'][0][1]);
    }

    // Searching with minMatchCharLength options
    public function testMinMatchCharLength()
    {
        static::$fuse = new Fuse([
            't te tes test tes te t'
        ], [
            'includeMatches' => true,
            'minMatchCharLength' => 2
        ]);

        // When searching for the term "test"...
        $result = static::$fuse->search('test');

        // ...we get a match containing 3 indices.
        $this->assertCount(3, $result[0]['matches'][0]['indices']);

        // The first index is a single character.
        $this->assertEquals(2, $result[0]['matches'][0]['indices'][0][0]);
        $this->assertEquals(3, $result[0]['matches'][0]['indices'][0][1]);


        // When searching for a string shorter than minMatchCharLength...
        $result = static::$fuse->search('t');

        // ...we get a result with no matches included.
        $this->assertCount(1, $result);
        $this->assertCount(0, $result[0]['matches']);
    }
}
