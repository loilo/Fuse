<?php

declare(strict_types=1);

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

it('processes objects', function () {
    $fuse = new Fuse(
        [new TestObject(['name' => 'foo']), new TestObject(['name' => 'bar'])],
        [
            'keys' => [
                [
                    'name' => 'name',
                    'getFn' => function ($document) {
                        return $document->get('name');
                    },
                ],
            ],
        ],
    );

    $result = $fuse->search('foo');

    expect($result)->toHaveCount(1);
});
