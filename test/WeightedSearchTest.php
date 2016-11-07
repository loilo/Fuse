<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

// Weighted Search
class WeightedSearchTest extends TestCase {
	protected static $books;

	public static function setUpBeforeClass() {
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
	public function testSearchManAuthor() {
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

		// We get the value { title: "Right Ho Jeeves", author: "P.D. Mans" }
		$this->assertEquals($result[0]['title'], 'Right Ho Jeeves');
	}

	// When searching for the term "Man", where the title is weighted higher than author
	public function testSearchManTitle() {
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
		$this->assertEquals($result[0]['author'], 'John X');
	}

	// When searching for the term "war", where tags are weighted higher than all other keys
	public function testSearchWarTag() {
		$options = [
			'keys' => [[
				'name' => 'tags',
				'weight' => 0.8
			], [
				'name' => 'author',
				'weight' => 0.3
			], [
				'name' => 'title',
				'weight' => 0.2
			]]
		];

		$fuse = new Fuse(static::$books, $options);

		$result = $fuse->search('fiction');

		// We get the value for "P.D. Mans"
		$this->assertEquals($result[0]['author'], 'P.D. Mans');
	}
}