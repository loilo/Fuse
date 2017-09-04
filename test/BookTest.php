<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class BookTest extends TestCase
{
    protected static $fuse;

    public static function setUpBeforeClass()
    {
        static::$fuse = new Fuse((require __DIR__ . '/fixtures/books.php'), [
            'keys' => ['title', 'author'],
            'tokenize' => true
        ]);
    }

    // Searching for "HTML5" we expect...
    public function testSearchHTML5()
    {
        $result = static::$fuse->search('HTML5');

        // three results...
        $this->assertCount(3, $result);

        // ...of whom the first equals [ 'title' => 'HTML5', 'author' => 'Remy Sharp' ]
        $this->assertEquals([
            'title' => 'HTML5',
            'author' => 'Remy Sharp'
        ], $result[0]);
    }

    // Searching for "Jeeves Woodhouse" we expect...
    public function testSearchJeevesWoodhouse()
    {
        $result = static::$fuse->search('Jeeves Woodhouse');

        // ...six results...
        $this->assertCount(6, $result);

        // ...which are all the books written by "P.D. Jeeves Woodhouse".
        $this->assertEquals([
            [ 'title' => 'Right Ho Jeeves', 'author' => 'P.D. Woodhouse' ],
            [ 'title' => 'Thank You Jeeves', 'author' => 'P.D. Woodhouse' ],
            [ 'title' => 'The Code of the Wooster', 'author' => 'P.D. Woodhouse'],
            [ 'title' => 'The Lock Artist', 'author' => 'Steve Hamilton' ],
            [ 'title' => 'the wooster code', 'author' => 'aa' ],
            [ 'title' => 'The code of the wooster', 'author' => 'aa']
        ], $result);
    }

    // Searching for "brwn" we expect...
    public function testSearchBrwn()
    {
        $result = static::$fuse->search('brwn');

        // at least 3 results...
        $this->assertGreaterThanOrEqual(3, sizeof($result));

        // ...and the first three ones should be all the books by Dan Brown
        $this->assertEquals([ 'title' => 'The DaVinci Code', 'author' => 'Dan Brown' ], $result[0]);
        $this->assertEquals([ 'title' => 'Angels & Demons', 'author' => 'Dan Brown' ], $result[1]);
        $this->assertEquals([ 'title' => 'The Lost Symbol', 'author' => 'Dan Brown' ], $result[2]);
    }

    // Deep key search, with ["title", "author.firstName"]
    public function testDeepKeySearch()
    {
        $books = [[
            'title' => "Old Man's War",
            'author' => [
                'firstName' => 'John',
                'lastName' => 'Scalzi'
            ]
        ], [
            'title' => 'The Lock Artist',
            'author' => [
                'firstName' => 'Steve',
                'lastName' => 'Hamilton'
            ]
        ], [
            'title' => 'HTML5',
        ], [
            'title' => 'A History of England',
            'author' => [
              'firstName' => 1066,
              'lastName' => 'Hastings'
            ]
        ]];

        $options = [ 'keys' => ['title', 'author.firstName'] ];

        $fuse = new Fuse($books, $options);

        // When searching for the term "Stve"...
        $result = $fuse->search('Stve');

        // ...we get a list of containing at least 1 item...
        $this->assertNotEmpty($result);

        // ...whose first value is found
        $this->assertEquals([
          'title' => 'The Lock Artist',
          'author' => [
            'firstName' => 'Steve',
            'lastName' => 'Hamilton'
          ]
        ], $result[0]);

        // When searching for the term "106"...
        $result = $fuse->search('106');

        // ...we get a list of exactly 1 item...
        $this->assertCount(1, $result);

        // ...whose first value is found
        $this->assertEquals([
          'title' => 'A History of England',
          'author' => [
            'firstName' => 1066,
            'lastName' => 'Hastings'
          ]
        ], $result[0]);
    }

    // Custom search function, with ["title", "author.firstName"]
    public function testCustomSearchFunction()
    {
        $books = [[
            'title' => "Old Man's War",
            'author' => [
                'firstName' => 'John',
                'lastName' => 'Scalzi'
            ]
        ], [
            'title' => 'The Lock Artist',
            'author' => [
                'firstName' => 'Steve',
                'lastName' => 'Hamilton'
            ]
        ]];

        $options = [
            'keys' => [ 'title', 'author.firstName' ],
            'getFn' => function ($obj, $path) {
                if (!$obj) {
                    return null;
                }
                $obj = $obj['author']['lastName'];
                return $obj;
            }
        ];

        $fuse = new Fuse($books, $options);

        // When searching for the term "Hmlt"
        $result = $fuse->search('Hmlt');

        // we get a list of containing at least 1 item
        $this->assertNotEmpty($result);

        // ...whose first value is found
        $this->assertEquals([
          'title' => 'The Lock Artist',
          'author' => [
            'firstName' => 'Steve',
            'lastName' => 'Hamilton'
          ]
        ], $result[0]);


        // When searching for the term "Stve"...
        $result = $fuse->search('Stve');

        // ...we get a list with no results
        $this->assertCount(0, $result);
    }
}
