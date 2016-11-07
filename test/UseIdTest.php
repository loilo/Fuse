<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class UseIdTest extends TestCase {
	protected static $fuse;

	public static function setUpBeforeClass() {
		static::$fuse = new Fuse([[
			'ISBN' => '0765348276',
			'title' => "Old Man's War",
			'author' => 'John Scalzi'
		], [
			'ISBN' => '0312696957',
			'title' => 'The Lock Artist',
			'author' => 'Steve Hamilton'
		]], [
			'keys' => ['title', 'author'],
			'id' => 'ISBN'
		]);
	}

	// When searching for the term "Stve"...
	public function testSearchStve() {
		$result = static::$fuse->search('Stve');

		// ...we get a list containing 1 item...
		$this->assertEquals(sizeof($result), 1);

		// ...whose value is the ISBN of the book
		$this->assertEquals($result[0], '0312696957');
	}
}