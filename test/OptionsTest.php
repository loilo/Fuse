<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class OptionsTest extends TestCase {
	protected static $fuse;

	// Searching with default options
	public function testDefaultOptions() {
		static::$fuse = new Fuse([
			't te tes test tes te t'
		], [
			'include' => ['matches']
		]);

		$result = static::$fuse->search('test');

		// We get a match containing 4 indices
		$this->assertEquals(sizeof($result[0]['matches'][0]['indices']), 4);

		// The first index is a single character
		$this->assertEquals($result[0]['matches'][0]['indices'][0][0], 0);
		$this->assertEquals($result[0]['matches'][0]['indices'][0][1], 0);
	}

	// Searching with findAllMatches options
	public function testFindAllMatches() {
		static::$fuse = new Fuse([
			't te tes test tes te t'
		], [
			'include' => ['matches'],
			'findAllMatches' => true
		]);

		$result = static::$fuse->search('test');

		// We get a match containing 7 indices
		$this->assertEquals(sizeof($result[0]['matches'][0]['indices']), 7);

		// The first index is a single character
		$this->assertEquals($result[0]['matches'][0]['indices'][0][0], 0);
		$this->assertEquals($result[0]['matches'][0]['indices'][0][1], 0);
	}

	// Searching with minMatchCharLength options
	public function testMinMatchCharLength() {
		static::$fuse = new Fuse([
			't te tes test tes te t'
		], [
			'include' => ['matches'],
			'minMatchCharLength' => 2
		]);

		$result = static::$fuse->search('test');

		// We get a match containing 3 indices
		$this->assertEquals(sizeof($result[0]['matches'][0]['indices']), 3);

		// The first index is a single character
		$this->assertEquals($result[0]['matches'][0]['indices'][0][0], 2);
		$this->assertEquals($result[0]['matches'][0]['indices'][0][1], 3);
	}
}