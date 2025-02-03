<?php

use Fuse\Fuse;

beforeEach(function () {
    $this->setupFuse = function ($itemList = null, $overwriteOptions = []) {
        $list = $itemList ?? ['Apple', 'Orange', 'Banana'];
        $options = array_merge([], $overwriteOptions);

        return new Fuse($list, $options);
    };
});

describe('Flat list of strings: ["Apple", "Orange", "Banana"]', function () {
    beforeEach(function () {
        $this->fuse = ($this->setupFuse)();
    });

    describe('When searching for the term "Apple"', function () {
        beforeEach(function () {
            $this->result = $this->fuse->search('Apple');
        });

        test('we get a list of exactly 1 item', function () {
            expect($this->result)->toHaveCount(1);
        });

        test('whose value is the index 0, representing ["Apple"]', function () {
            expect($this->result[0]['refIndex'])->toBe(0);
        });
    });

    describe('When performing a fuzzy search for the term "ran"', function () {
        beforeEach(function () {
            $this->result = $this->fuse->search('ran');
        });

        test('we get a list of containing 2 items', function () {
            expect($this->result)->toHaveCount(2);
        });

        test('whose values represent the indices of ["Orange", "Banana"]', function () {
            expect($this->result[0]['refIndex'])->toBe(1);
            expect($this->result[1]['refIndex'])->toBe(2);
        });
    });

    describe('When performing a fuzzy search for the term "nan"', function () {
        beforeEach(function () {
            $this->result = $this->fuse->search('nan');
        });

        test('we get a list of containing 2 items', function () {
            expect($this->result)->toHaveCount(2);
        });

        test('whose values represent the indices of ["Banana", "Orange"]', function () {
            expect($this->result[0]['refIndex'])->toBe(2);
            expect($this->result[1]['refIndex'])->toBe(1);
        });
    });

    describe(
        'When performing a fuzzy search for the term "nan" with a limit of 1 result',
        function () {
            beforeEach(function () {
                $this->result = $this->fuse->search('nan', ['limit' => 1]);
            });

            test('we get a list of containing 1 item: [2]', function () {
                expect($this->result)->toHaveCount(1);
            });

            test('whose values represent the indices of ["Banana", "Orange"]', function () {
                expect($this->result[0]['refIndex'])->toBe(2);
            });
        },
    );
});

$customBookList = [
    [
        'title' => "Old Man's War",
        'author' => ['firstName' => 'John', 'lastName' => 'Scalzi'],
    ],
    [
        'title' => 'The Lock Artist',
        'author' => ['firstName' => 'Steve', 'lastName' => 'Hamilton'],
    ],
    ['title' => 'HTML5'],
    [
        'title' => 'A History of England',
        'author' => ['firstName' => 1066, 'lastName' => 'Hastings'],
    ],
];

describe('Deep key search, with ["title", "author.firstName"]', function () use ($customBookList) {
    beforeEach(function () use ($customBookList) {
        $this->fuse = ($this->setupFuse)($customBookList, [
            'keys' => ['title', 'author.firstName'],
        ]);
    });

    describe('When searching for the term "Stve"', function () {
        beforeEach(function () {
            $this->result = $this->fuse->search('Stve');
        });

        it('we get a list containing at least 1 item', function () {
            expect(count($this->result))->toBeGreaterThanOrEqual(1);
        });

        it('and the first item has the matching key/value pairs', function () {
            expect($this->result[0]['item']['title'])->toEqual('The Lock Artist');
            expect($this->result[0]['item']['author']['firstName'])->toEqual('Steve');
            expect($this->result[0]['item']['author']['lastName'])->toEqual('Hamilton');
        });
    });

    describe('When searching for the term "106"', function () {
        beforeEach(function () {
            $this->result = $this->fuse->search('106');
        });

        it('we get a list of exactly 1 item', function () {
            expect(count($this->result))->toEqual(1);
        });

        it('whose value matches', function () {
            expect($this->result[0]['item']['title'])->toEqual('A History of England');
            expect($this->result[0]['item']['author']['firstName'])->toEqual(1066);
            expect($this->result[0]['item']['author']['lastName'])->toEqual('Hastings');
        });
    });
});
