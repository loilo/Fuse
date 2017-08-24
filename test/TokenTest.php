<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

// Weighted Search
class TokenTest extends TestCase
{
    protected static $items;

    public static function setUpBeforeClass()
    {
        static::$items = [
            'AustralianSuper - Corporate Division',
            'Aon Master Trust - Corporate Super',
            'Promina Corporate Superannuation Fund',
            'Workforce Superannuation Corporate',
            'IGT (Australia) Pty Ltd Superannuation Fund'
        ];
    }

    // When searching for the term "Australia"
    public function testSearchAustralia()
    {
        $fuse = new Fuse(static::$items, [ 'tokenize' => true ]);

        $result = $fuse->search('Australia');

        // we get a list containing 2 items
        $this->assertCount(2, $result);

        // whose items represent the indices of "AustralianSuper - Corporate Division" and "IGT (Australia) Pty Ltd Superannuation Fund"
        $this->assertContains(0, $result);
        $this->assertContains(4, $result);
    }

    // When searching for the term "corporate"
    public function testSearchCorporate()
    {
        $fuse = new Fuse(static::$items, [ 'tokenize' => true ]);

        $result = $fuse->search('corporate');

        // we get a list containing 4 items
        $this->assertCount(4, $result);

        // whose items represent the indices of "AustralianSuper - Corporate Division" and "IGT (Australia) Pty Ltd Superannuation Fund"
        $this->assertContains(0, $result);
        $this->assertContains(1, $result);
        $this->assertContains(2, $result);
        $this->assertContains(3, $result);
    }

    // When searching for the term "Australia corporate" with "matchAllTokens" set to false
    public function testSearchAustraliaCorporate()
    {
        $fuse = new Fuse(static::$items, [
            'tokenize' => true,
            'matchAllTokens' => false
        ]);

        $result = $fuse->search('Australia corporate');

        // we get a list containing 5 items
        $this->assertCount(5, $result);

        // whose items represent the indices of "AustralianSuper - Corporate Division", "Aon Master Trust - Corporate Super", "Promina Corporate Superannuation Fund", "Workforce Superannuation Corporate" and "IGT (Australia) Pty Ltd Superannuation Fund"
        $this->assertContains(0, $result);
        $this->assertContains(1, $result);
        $this->assertContains(2, $result);
        $this->assertContains(3, $result);
        $this->assertContains(4, $result);
    }

    // When searching for the term "Australia corporate" with "matchAllTokens" set to true
    public function testSearchAustraliaCorporateAll()
    {
        $fuse = new Fuse(static::$items, [
            'tokenize' => true,
            'matchAllTokens' => true
        ]);

        $result = $fuse->search('Australia corporate');

        // we get a list containing 1 item
        $this->assertCount(1, $result);

        // whose item represents the index of "AustralianSuper - Corporate Division"
        $this->assertContains(0, $result);
    }
}
