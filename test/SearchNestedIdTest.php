<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class SearchNestedIdTest extends TestCase {
	protected static $fuse;

	public static function setUpBeforeClass() {
		static::$fuse = new Fuse([[
			'ISBN' => [ 'name' => 'A' ],
			'title' => "Old Man's War",
			'author' => 'John Scalzi'
		], [
			'ISBN' => [ 'name' => 'B' ],
			'title' => 'The Lock Artist',
			'author' => 'Steve Hamilton'
		]], [
			'keys' => ['title', 'author'],
			'id' => 'ISBN.name',
		]);
	}

	// When searching for the author tag "Stve"...
	public function testSearchStve() {
		$result = static::$fuse->search('Stve');

		// ...we get a list containing 1 item...
		$this->assertEquals(sizeof($result), 1);

		// ...whose value is the ISBN of the book
		$this->assertTrue(is_string($result[0]));
		$this->assertEquals($result[0], 'B');
	}
}