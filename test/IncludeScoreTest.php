<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class IncludeScoreTest extends TestCase {
	protected static $fuse;

	public static function setUpBeforeClass() {
		static::$fuse = new Fuse([
			'Apple',
			'Orange',
			'Banana'
		], [
			'include' => ['score']
		]);
	}

	// When searching for the term "Apple"...
	public function testSearchApple() {
		$result = static::$fuse->search('Apple');

		// ...we get a list of containing 1 item, which is an exact match...
		$this->assertEquals(sizeof($result), 1);

		// ...whose value and score exist
		$this->assertEquals($result[0]['item'], 0);
		$this->assertEquals($result[0]['score'], 0);
	}

	// When performing a fuzzy search for the term "ran"...
	public function testSearchRan() {
		$result = static::$fuse->search('ran');

		// ...we get a list of containing 2 items...
		$this->assertEquals(sizeof($result), 2);

		// ...whose items represent the indices, and have non-zero scores
		$this->assertEquals($result[0]['item'], 1);
		$this->assertEquals($result[1]['item'], 2);
		$this->assertTrue($result[0]['score'] !== 0);
		$this->assertTrue($result[1]['score'] !== 0);
	}
}