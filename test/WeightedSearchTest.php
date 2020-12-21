<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

// Weighted Search
class WeightedSearchTest extends TestCase
{
    protected static $books;

    public static function setUpBeforeClass(): void
    {
        static::$books = [[
            'title' => "Old Man's War fiction",
            'author' => 'John X',
            'tags' => ['war']
        ], [
            'title' => 'Right Ho Jeeves',
            'author' => 'P.D. Mans',
            'tags' => ['fiction', 'war']
        ]];
    }

    // When searching for the term "Man", where the author is weighted higher than title
    public function testSearchManAuthor()
    {
        $options = [
            'keys' => [[
                'name' => 'title',
                'weight' => 0.3
            ], [
                'name' => 'author',
                'weight' => 0.7
            ]]
        ];

        $fuse = new Fuse(static::$books, $options);

        $result = $fuse->search('Man');

        // We get the value [ 'title' => 'Right Ho Jeeves', 'author' => 'P.D. Mans' ]
        $this->assertEquals('Right Ho Jeeves', $result[0]['title']);
    }

    // When searching for the term "Man", where the title is weighted higher than author
    public function testSearchManTitle()
    {
        $options = [
            'keys' => [[
                'name' => 'title',
                'weight' => 0.7
            ], [
                'name' => 'author',
                'weight' => 0.3
            ]]
        ];

        $fuse = new Fuse(static::$books, $options);

        $result = $fuse->search('Man');

        // We get the value for "John X"
        $this->assertEquals('John X', $result[0]['author']);
    }

    // When searching for the term "war", where tags are weighted higher than all other keys
    public function testSearchWarTag()
    {
        $options = [
            'keys' => [[
                'name' => 'title',
                'weight' => 0.4
            ], [
                'name' => 'author',
                'weight' => 0.1
            ], [
                'name' => 'tags',
                'weight' => 0.5
            ]]
        ];

        $fuse = new Fuse(static::$books, $options);

        $result = $fuse->search('fiction');

        // We get the value for "P.D. Mans"
        $this->assertEquals('P.D. Mans', $result[0]['author']);
    }
}
