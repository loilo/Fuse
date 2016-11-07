<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

// Weighted Search
class TokenTest extends TestCase {
	protected static $items;

	public static function setUpBeforeClass() {
		static::$items = [
			'AustralianSuper - Corporate Division',
			'Aon Master Trust - Corporate Super',
			'Promina Corporate Superannuation Fund',
			'Workforce Superannuation Corporate',
			'IGT (Australia) Pty Ltd Superannuation Fund'
		];
	}

	// When searching for the term "Australia"
	public function testSearchAustralia() {
		$fuse = new Fuse(static::$items, [ 'tokenize' => true ]);

		$result = $fuse->search('Australia');

		// we get a list containing 2 items
		$this->assertEquals(sizeof($result), 2);

		// whose items represent the indices of "AustralianSuper - Corporate Division" and "IGT (Australia) Pty Ltd Superannuation Fund"
		$this->assertTrue(in_array(0, $result));
		$this->assertTrue(in_array(4, $result));
	}

	// When searching for the term "corporate"
	public function testSearchCorporate() {
		$fuse = new Fuse(static::$items, [ 'tokenize' => true ]);

		$result = $fuse->search('corporate');

		// we get a list containing 4 items
		$this->assertEquals(sizeof($result), 4);

		// whose items represent the indices of "AustralianSuper - Corporate Division" and "IGT (Australia) Pty Ltd Superannuation Fund"
		$this->assertTrue(in_array(0, $result));
		$this->assertTrue(in_array(1, $result));
		$this->assertTrue(in_array(2, $result));
		$this->assertTrue(in_array(3, $result));
	}

	// When searching for the term "Australia corporate"
	public function testSearchAustraliaCorporate() {
		$fuse = new Fuse(static::$items, [
			'tokenize' => true,
			'matchAllTokens' => false
		]);

		$result = $fuse->search('Australia corporate');

		// we get a list containing 5 items
		$this->assertEquals(sizeof($result), 5);

		// whose items represent the indices of "AustralianSuper - Corporate Division", "Aon Master Trust - Corporate Super", "Promina Corporate Superannuation Fund", "Workforce Superannuation Corporate" and "IGT (Australia) Pty Ltd Superannuation Fund"
		$this->assertTrue(in_array(0, $result));
		$this->assertTrue(in_array(1, $result));
		$this->assertTrue(in_array(2, $result));
		$this->assertTrue(in_array(3, $result));
		$this->assertTrue(in_array(4, $result));
	}

	// When searching for the term "Australia corporate" with "matchAllTokens" set to true
	public function testSearchAustraliaCorporateAll() {
		$fuse = new Fuse(static::$items, [
			'tokenize' => true,
			'matchAllTokens' => true
		]);

		$result = $fuse->search('Australia corporate');

		// we get a list containing 1 item
		$this->assertEquals(sizeof($result), 1);

		// whose item represents the index of "AustralianSuper - Corporate Division"
		$this->assertTrue(in_array(0, $result));
	}
}