<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use Fuse\Fuse;

class TestObject
{
    private $array = [];

    public function __construct($array)
    {
        $this->array = !empty($array) ? $array : [];
    }

    function get($key)
    {
        return isset($this->array[$key]) ? $this->array[$key] : null;
    }
}

class ObjectValuesTest extends TestCase
{
    public function testObjectsAreProcessed(): void
    {
        $fuse = new Fuse(
            [
                new TestObject(["name" => "foo"]),
                new TestObject(["name" => "bar"]),
            ],
            [
                'keys' => [
                    [
                        'name' => 'name',
                        'getFn' => function ($document) {
                            return $document->get('name');
                        }
                    ],
                ],
            ],
        );

        $result = $fuse->search('foo');

        $this->assertCount(1, $result);
    }
}
