<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class RecurseArrayObjectsTest extends TestCase
{
    protected static $fuse;

    public static function setUpBeforeClass()
    {
        static::$fuse = new Fuse([
            [
                'ISBN' => '0765348276',
                'title' => "Old Man's War",
                'author' => [
                    'name' => 'John Scalzi',
                    'tags' => [[
                        'value' => 'American'
                    ]]
                ]
            ], [
                'ISBN' => '0312696957',
                'title' => 'The Lock Artist',
                'author' => [
                    'name' => 'Steve Hamilton',
                    'tags' => [[
                        'value' => 'American'
                    ]]
                ]
            ], [
                'ISBN' => '0321784421',
                'title' => 'HTML5',
                'author' => [
                    'name' => 'Remy Sharp',
                    'tags' => [[
                        'value' => 'British'
                    ]]
                ]
            ]
        ], [
            'keys' => ['author.tags.value'],
            'id' => 'ISBN',
            'threshold' => 0
        ]);
    }

    // When searching for the author tag "British"...
    public function testSearchBritish()
    {
        $result = static::$fuse->search('British');

        // ...we get a list containing 1 item...
        $this->assertCount(1, $result);

        // ...whose value is the ISBN of the book.
        $this->assertEquals('0321784421', $result[0]);
    }
}
