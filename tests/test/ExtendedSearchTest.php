<?php

use Fuse\Fuse;

describe('Searching using extended search', function () {
    $list = [
        ['text' => 'hello word'],
        ['text' => 'how are you'],
        ['text' => 'indeed fine hello foo'],
        ['text' => 'I am fine'],
        ['text' => 'smithee'],
        ['text' => 'smith'],
    ];

    $options = [
        'useExtendedSearch' => true,
        'includeMatches' => true,
        'includeScore' => true,
        'threshold' => 0.5,
        'minMatchCharLength' => 4,
        'keys' => ['text'],
    ];
    $fuse = new Fuse($list, $options);

    test('Search: exact-match', function () use ($fuse) {
        $result = $fuse->search('=smith');
        expect($result)->toMatchSnapshot();
    });

    test('Search: include-match', function () use ($fuse) {
        $result = $fuse->search("'hello");
        expect($result)->toMatchSnapshot();
    });

    test('Search: prefix-exact-match', function () use ($fuse) {
        $result = $fuse->search('^hello');
        expect($result)->toMatchSnapshot();
    });

    test('Search: suffix-exact-match', function () use ($fuse) {
        $result = $fuse->search('fine$');
        expect($result)->toMatchSnapshot();
    });

    test('Search: inverse-exact-match', function () use ($fuse) {
        $result = $fuse->search('!indeed');
        expect($result)->toMatchSnapshot();
    });

    test('Search: inverse-prefix-exact-match', function () use ($fuse) {
        $result = $fuse->search('!^hello');
        expect($result)->toMatchSnapshot();
    });

    test('Search: inverse-suffix-exact-match', function () use ($fuse) {
        $result = $fuse->search('!foo$');
        expect($result)->toMatchSnapshot();
    });

    test('Search: all', function () use ($fuse) {
        $result = $fuse->search('!foo$ !^how');
        expect($result)->toMatchSnapshot();
    });

    test('Search: single literal match', function () use ($fuse) {
        $result = $fuse->search('\'"indeed fine"');
        expect($result)->toMatchSnapshot();
    });

    test('Search: literal match with regular match', function () use ($fuse) {
        $result = $fuse->search('\'"indeed fine" foo$ | \'are');
        expect($result)->toMatchSnapshot();
    });

    test('Search: literal match with fuzzy match', function () use ($fuse) {
        $result = $fuse->search('\'"indeed fine" foo$ | helol');
        expect($result)->toMatchSnapshot();
    });
});

describe('ignoreLocation when useExtendedSearch is true', function () {
    $list = [
        [
            'document' =>
                'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum apple.',
        ],
    ];

    test('Search: literal match with fuzzy match', function () use ($list) {
        $options = [
            'threshold' => 0.2,
            'useExtendedSearch' => true,
            'ignoreLocation' => true,
            'keys' => ['document'],
        ];
        $fuse = new Fuse($list, $options);

        $result = $fuse->search('Apple');
        expect($result)->toHaveCount(1);
    });
});

describe('Searching using extended search ignoring diacritics', function () {
    $list = [['text' => 'déjà'], ['text' => 'cafe']];

    $options = [
        'useExtendedSearch' => true,
        'ignoreDiacritics' => true,
        'threshold' => 0,
        'keys' => ['text'],
    ];

    $fuse = new Fuse($list, $options);

    test('Search: query with diacritics, list with diacritics', function () use ($fuse) {
        $result = $fuse->search('déjà');
        expect($result)->toHaveCount(1);
        expect($result[0]['refIndex'])->toBe(0);
    });

    test('Search: query without diacritics, list with diacritics', function () use ($fuse) {
        $result = $fuse->search('deja');
        expect($result)->toHaveCount(1);
        expect($result[0]['refIndex'])->toBe(0);
    });

    test('Search: query with diacritics, list without diacritics', function () use ($fuse) {
        $result = $fuse->search('café');
        expect($result)->toHaveCount(1);
        expect($result[0]['refIndex'])->toBe(1);
    });

    test('Search: query without diacritics, list without diacritics', function () use ($fuse) {
        $result = $fuse->search('cafe');
        expect($result)->toHaveCount(1);
        expect($result[0]['refIndex'])->toBe(1);
    });
});
