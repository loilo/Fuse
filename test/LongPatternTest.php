<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

// List of books - searching for long pattern length > 32
class LongPatternTest extends TestCase
{
    protected static $fuse;

    public static function setUpBeforeClass(): void
    {
        static::$fuse = new Fuse([
          [
            'text' => 'pizza'
          ],
          [
            'text' => 'feast'
          ],
          [
            'text' => 'super+large+much+unique+36+very+wow+'
          ]
        ], [
          'include' => [
            'score',
            'matches'
          ],
          'shouldSort' => true,
          'threshold' => 0.5,
          'location' => 0,
          'distance' => 0,
          'maxPatternLength' => 50,
          'minMatchCharLength' => 4,
          'keys' => [
            'text'
          ]
        ]);
    }

    public function testFindDeliciousPizza()
    {
        $result = static::$fuse->search('pizza');

        $this->assertNotEmpty($result);
        $this->assertSame('pizza', $result[0]['text']);
    }

    public function testFindsPizzaWhenClumsy()
    {
        $result = static::$fuse->search('pizze');

        $this->assertNotEmpty($result);
        $this->assertSame('pizza', $result[0]['text']);
    }

    public function testFindsNoMatchesAt31CharPattern()
    {
        $result = static::$fuse->search('this-string-is-exactly-31-chars');

        $this->assertEmpty($result);
    }

    public function testFindsNoMatchesAt32CharPattern()
    {
        $result = static::$fuse->search('this-string-is-exactly-32-chars-');

        $this->assertEmpty($result);
    }

    public function testFindsNoMatchesAtMoreThan32CharsPattern()
    {
        $result = static::$fuse->search('this-string-is-more-than-32-chars');

        $this->assertEmpty($result);
    }

    public function testFindsExactMatchesAtMoreThan32CharsPattern()
    {
        $result = static::$fuse->search('super+large+much+unique+36+very+wow+');

        $this->assertNotEmpty($result);
        $this->assertSame('super+large+much+unique+36+very+wow+', $result[0]['text']);
    }
}
