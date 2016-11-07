<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class RecurseArraysTest extends TestCase {
	protected static $fuse;

	public static function setUpBeforeClass() {
		static::$fuse = new Fuse([[
			'ISBN' => '0765348276',
			'title' => "Old Man's War",
			'author' => 'John Scalzi',
			'tags' => ['fiction']
		], [
			'ISBN' => '0312696957',
			'title' => 'The Lock Artist',
			'author' => 'Steve Hamilton',
			'tags' => ['fiction']
		], [
			'ISBN' => '0321784421',
			'title' => 'HTML5',
			'author' => 'Remy Sharp',
			'tags' => ['nonfiction']
		]], [
			'keys' => ['tags'],
			'id' => 'ISBN',
			'threshold' => 0
		]);
	}

	// When searching for the tag "nonfiction"...
	public function testSearchNonfiction() {
		$result = static::$fuse->search('nonfiction');

		// ...we get a list containing 1 item...
		$this->assertEquals(sizeof($result), 1);

		// ...whose value is the ISBN of the book...
		$this->assertEquals($result[0], '0321784421');
	}
}