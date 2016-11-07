<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

// Set new list on Fuse
class SetListTest extends TestCase {
	protected static $fuse;

	public static function setUpBeforeClass() {
		$fruits = ['Apple', 'Orange', 'Banana'];
		$vegetables = ['Onion', 'Lettuce', 'Broccoli'];

		static::$fuse = new Fuse($fruits);
		static::$fuse->set($vegetables);
	}

	// When searching for the term "Lettuce"...
	public function testSearchLettuce() {
		$result = static::$fuse->search('Lettuce');

		// ...we get a list containing 1 item, which is an exact match...
		$this->assertEquals(sizeof($result), 1);

		// ...whose value is the the index 1, representing ['Lettuce']
		$this->assertEquals($result[0], 1);
	}
}