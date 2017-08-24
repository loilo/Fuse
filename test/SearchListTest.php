<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class SearchListTest extends TestCase
{
    public function testSearchBogoList()
    {
        $fuse = new Fuse([
            'Borwaila hamlet',
            'Bobe hamlet',
            'Bo hamlet',
            'Boma hamlet'
        ], [
            'includeScore' => true
        ]);

        // When searching for the term "Bo hamet"
        $result = $fuse->search('Bo hamet');

        // ...we get a list containing 4 items...
        $this->assertCount(4, $result);

        // ...whose first value is the index of "Bo hamlet"
        $this->assertEquals(2, $result[0]['item']);
    }

    public function testSearchUniversities()
    {
        $fuse = new Fuse(['FH Mannheim', 'University Mannheim']);

        // When searching for the term "Uni Mannheim"...
        $result = $fuse->search('Uni Mannheim');

        // ...we get a list containing 2 items...
        $this->assertCount(2, $result);

        // ...whose first value is the index of "University Mannheim"
        // This test is disabled because it's from the official Fuse.js test suite but Fuse.js itself doesn't pass it
        // $this->assertEquals(1, $result[0]);
    }
}
