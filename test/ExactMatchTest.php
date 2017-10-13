<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

// Weighted Search
class ExactSearchTest extends TestCase
{
    protected static $books;

    // Weighted search with exact match
    public function testExactWeightedMatch()
    {
        $books = [[
          'title' => 'John Smith',
          'author' => 'Steve Pearson',
        ], [
          'title' => 'The life of Jane',
          'author' => 'John Smith',
        ]];

        // When searching for the term "John Smith" with author weighted higher
        $fuse = new Fuse($books, [
            'keys' => [[
                'name' => 'title',
                'weight' => 0.3
            ], [
                'name' => 'author',
                'weight' => 0.7
            ]]
        ]);
        $result = $fuse->search('John Smith');

        // We get the value [ 'title' => 'The life of Jane', 'author' => 'John Smith' ]
        $this->assertEquals('The life of Jane', $result[0]['title']);
        $this->assertEquals('John Smith', $result[0]['author']);


        // When searching for the term "John Smith" with title weighted higher
        $fuse = new Fuse($books, [
            'keys' => [[
                'name' => 'title',
                'weight' => 0.7
            ], [
                'name' => 'author',
                'weight' => 0.3
            ]]
        ]);
        $result = $fuse->search('John Smith');

        // We get the value [ 'title' => 'John Smith', 'author' => 'Steve Pearson' ]
        $this->assertEquals('John Smith', $result[0]['title']);
        $this->assertEquals('Steve Pearson', $result[0]['author']);
    }

    // Weighted search with exact match in arrays
    public function testExactWeightedMatchArray()
    {
        $books = [ [
          'title' => 'Jackson',
          'author' => 'Steve Pearson',
          'tags' => ['Kevin Wong', 'Victoria Adam', 'John Smith']
        ], [
          'title' => 'The life of Jane',
          'author' => 'John Smith',
          'tags' => ['Jane', 'Jackson', 'Sam']
        ]];

        // When searching for the term "Jackson", with tags weighted higher and string inside tags getting exact match
        $fuse = new Fuse($books, [
            'keys' => [[
                'name' => 'tags',
                'weight' => 0.7
            ], [
                'name' => 'title',
                'weight' => 0.3
            ]]
        ]);
        $result = $fuse->search('Jackson');

        // We get the value [ 'title' => 'The life of Jane', 'tags' => ['Jane', 'Jackson', 'Sam'] ]
        $this->assertEquals('The life of Jane', $result[0]['title']);
        $this->assertEquals(['Jane', 'Jackson', 'Sam'], $result[0]['tags']);


        // When searching for the term "Jackson", with title weighted higher and string inside getting exact match
        $fuse = new Fuse($books, [
            'keys' => [[
                'name' => 'tags',
                'weight' => 0.3
            ], [
                'name' => 'title',
                'weight' => 0.7
            ]]
        ]);
        $result = $fuse->search('Jackson');

        // We get the value [ 'title' => 'The life of Jane', 'tags' => [ 'Kevin Wong', ... ]
        $this->assertEquals('Jackson', $result[0]['title']);
        $this->assertEquals(['Kevin Wong', 'Victoria Adam', 'John Smith'], $result[0]['tags']);
    }
}
