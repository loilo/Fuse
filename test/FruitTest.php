<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';

class FruitTest extends TestCase {
	protected static $fuse;

	public static function setUpBeforeClass() {
		$fruits = ['Apple', 'Orange', 'Banana'];
		static::$fuse = new \Fuse\Fuse($fruits);
	}

	// Searching for "Apple" we expect...
	public function testSearchApple() {
		$result = static::$fuse->search('Apple');

		// one result...
		$this->assertEquals(sizeof($result), 1);

		// ...whose value is the index 0, representing ["Apple"]
		$this->assertEquals($result[0], 0);
	}

	// Searching for "ran" we expect...
	public function testFuzzySearchRan() {
		$result = static::$fuse->search('ran');

		// list of containing 2 items: [1, 2]
		$this->assertEquals(sizeof($result), 2);

		// ...whose values represent the indices of ["Orange", "Banana"]
		$this->assertEquals($result[0], 1);
		$this->assertEquals($result[1], 2);
	}

	// Searching for "nan" we expect...
	public function testFuzzySearchNan() {
		$result = static::$fuse->search('nan');

		// list of containing 2 items: [2, 1]
		$this->assertEquals(sizeof($result), 2);

		// ...whose values represent the indices of ["Banana", "Orange"]
		$this->assertEquals($result[0], 2);
		$this->assertEquals($result[1], 1);
	}
}