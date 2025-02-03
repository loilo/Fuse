<?php

declare(strict_types=1);

use Fuse\Fuse;

beforeEach(function () {
    $books = require __DIR__ . '/fixtures/books.php';
    $this->books = $books;
    $this->fuseOptions = [
        'useExtendedSearch' => true,
        'includeMatches' => true,
        'includeScore' => true,
        'threshold' => 0.3,
        'keys' => ['title', 'author.firstName', 'author.lastName'],
    ];

    $this->idx = function (array $results): array {
        return array_map(fn(array $result) => $result['refIndex'], $results);
    };

    $this->idxMap = function (Fuse $fuse): array {
        return array_map(fn(array $item) => [$item['v'], $item['i']], $fuse->getIndex()->records);
    };
});

it('creates index and ensures properties exist', function () {
    $myIndex = Fuse::createIndex($this->fuseOptions['keys'], $this->books);

    expect($myIndex->records)->not->toBeNull();
    expect($myIndex->keys)->not->toBeNull();
});

it('creates index and ensures keys can be created with objects', function () {
    $myIndex = Fuse::createIndex(
        [['name' => 'title'], ['name' => 'author.firstName']],
        $this->books,
    );

    expect($myIndex->records)->not->toBeNull();
    expect($myIndex->keys)->not->toBeNull();
});

it('creates index and ensures keys can be created with getFn', function () {
    $myIndex = Fuse::createIndex(
        [
            ['name' => 'title', 'getFn' => fn($book) => $book['title']],
            ['name' => 'author.firstName', 'getFn' => fn($book) => $book['author']['firstName']],
        ],
        $this->books,
    );

    $data = json_decode(json_encode($myIndex), true);
    expect($data['records'])->not->toBeNull();
    expect($data['keys'])->not->toBeNull();
});

it('parses index, exports it, and initializes Fuse', function () {
    $myIndex = Fuse::createIndex($this->fuseOptions['keys'], $this->books);

    expect($myIndex->size())->toBe(count($this->books));

    $data = json_decode(json_encode($myIndex), true);
    expect($data['records'])->not->toBeNull();
    expect($data['keys'])->not->toBeNull();

    $parsedIndex = Fuse::parseIndex($data);
    expect($parsedIndex->size())->toBe(count($this->books));
});

it('searches parsed index using getFn', function () {
    $fuse = new Fuse($this->books, [
        'useExtendedSearch' => true,
        'includeMatches' => true,
        'includeScore' => true,
        'threshold' => 0.3,
        'keys' => [
            ['name' => 'bookTitle', 'getFn' => fn($book) => $book['title']],
            ['name' => 'authorName', 'getFn' => fn($book) => $book['author']['firstName']],
        ],
    ]);

    $result = $fuse->search(['bookTitle' => 'old man']);

    expect($result)->toHaveCount(1);
    expect(($this->idx)($result))->toContain(0);
});

it('instantiates Fuse with an index', function () {
    $myIndex = Fuse::createIndex($this->fuseOptions['keys'], $this->books);
    $fuse = new Fuse($this->books, $this->fuseOptions, $myIndex);

    $result = $fuse->search(['title' => 'old man']);

    expect($result)->toHaveCount(1);
    expect(($this->idx)($result))->toContain(0);
    expect($result[0]['matches'][0]['indices'])->toContain([0, 2], [4, 6]);
});

it('adds an object to the index', function () {
    $fuse = new Fuse($this->books, $this->fuseOptions);

    $fuse->add(['title' => 'book', 'author' => ['firstName' => 'Kiro', 'lastName' => 'Risk']]);

    $result = $fuse->search('kiro');

    expect($result)->toHaveCount(1);
    expect(($this->idx)($result))->toContain(count($this->books));
});

it('adds a string to the index', function () {
    $fuse = new Fuse(['apple', 'orange'], ['includeScore' => true]);

    $fuse->add('banana');
    $result = $fuse->search('banana');

    expect($result)->toHaveCount(1);
    expect(($this->idx)($result))->toContain(2);
});

it('removes a string from the index', function () {
    $fuse = new Fuse(['apple', 'orange', 'banana', 'pear']);

    expect($fuse->getIndex()->size())->toBe(4);
    expect(($this->idxMap)($fuse))->toContain(
        ['apple', 0],
        ['orange', 1],
        ['banana', 2],
        ['pear', 3],
    );

    $fuse->removeAt(1);

    expect($fuse->getIndex()->size())->toBe(3);
    expect(($this->idxMap)($fuse))->toContain(['apple', 0], ['banana', 1], ['pear', 2]);

    $results = $fuse->remove(fn($doc) => $doc === 'banana' || $doc === 'pear');

    expect($results)->toHaveCount(2);
    expect($fuse->getIndex()->size())->toBe(1);

    $fuseReflection = new ReflectionObject($fuse);
    $docsProperty = $fuseReflection->getProperty('docs');
    $docsProperty->setAccessible(true);

    expect($docsProperty->getValue($fuse))->toHaveCount(1);
});
