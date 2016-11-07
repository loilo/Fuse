<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class BookTest extends TestCase {
	protected static $fuse;

	public static function setUpBeforeClass() {
		static::$fuse = new Fuse((require __DIR__ . '/books.php'), [
			'keys' => ['title', 'author'],
			'tokenize' => true
		]);
	}

	// Searching for "HTML5" we expect...
	public function testSearchHTML5() {
		$result = static::$fuse->search('HTML5');

		// three results...
		$this->assertEquals(sizeof($result), 3);

		// ...of whom the first equals [ 'title' => 'HTML5', 'author' => 'Remy Sharp' ]
		$this->assertEquals($result[0], [ 'title' => 'HTML5', 'author' => 'Remy Sharp' ]);
	}

	// Searching for "Woodhouse" we expect...
	public function testSearchWoodhouse() {
		$result = static::$fuse->search('Woodhouse');

		// six results...
		$this->assertEquals(sizeof($result), 5);

		// ...which are all the books written by "P.D. Woodhouse"
		$this->assertEquals($result, [
			[ 'title' => 'Right Ho Jeeves', 'author' => 'P.D. Woodhouse' ],
			[ 'title' => 'Thank You Jeeves', 'author' => 'P.D. Woodhouse' ],
			[ 'title' => 'the wooster code', 'author' => 'aa' ],
			[ 'title' => 'The code of the wooster', 'author' => 'aa'],
			[ 'title' => 'The Code of the Wooster', 'author' => 'P.D. Woodhouse' ],
		]);
	}

	// Searching for "brwn" we expect...
	public function testSearchBrwn() {
		$result = static::$fuse->search('brwn');

		// at least 3 results...
		$this->assertTrue(sizeof($result) >= 3);

		// ...and the first three ones should be all the books by Dan Brown
		$this->assertEquals($result[0], [ 'title' => 'The DaVinci Code', 'author' => 'Dan Brown' ]);
		$this->assertEquals($result[1], [ 'title' => 'Angels & Demons', 'author' => 'Dan Brown' ]);
		$this->assertEquals($result[2], [ 'title' => 'The Lost Symbol', 'author' => 'Dan Brown' ]);
	}

	// Deep key search, with ["title", "author.firstName"]
	public function testDeepKeySearch() {
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
		]];

		$options = [ 'keys' => ['title', 'author.firstName'] ];

		$fuse = new Fuse($books, $options);

		// When searching for the term "Stve"...
		$result = $fuse->search('Stve');

		// ...we get a list of containing at least 1 item...
		$this->assertTrue(sizeof($result) > 0);

		// ...whose first value is found
		$this->assertEquals($result[0], [
          'title' => 'The Lock Artist',
          'author' => [
            'firstName' => 'Steve',
            'lastName' => 'Hamilton'
          ]
        ]);
	}

	// Custom search function, with ["title", "author.firstName"]
	public function testCustomSearchFunction() {
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
		$this->assertTrue(sizeof($result) > 0);

		// ...whose first value is found
		$this->assertEquals($result[0], [
          'title' => 'The Lock Artist',
          'author' => [
            'firstName' => 'Steve',
            'lastName' => 'Hamilton'
          ]
        ]);


		// When searching for the term "Stve"...
		$result = $fuse->search('Stve');

		// ...we get a list with no results
		$this->assertEquals(sizeof($result), 0);
	}
}