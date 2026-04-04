<?php

declare(strict_types=1);

namespace Fuse\Test;

use Fuse\Fuse;
use Fuse\Search\SearchInterface;

function booksFixture(): array
{
    return [
        ['title' => 'The Great Gatsby', 'author' => 'F. Scott Fitzgerald'],
        ['title' => 'To Kill a Mockingbird', 'author' => 'Harper Lee'],
        ['title' => '1984', 'author' => 'George Orwell'],
        ['title' => 'Pride and Prejudice', 'author' => 'Jane Austen'],
        ['title' => 'The Catcher in the Rye', 'author' => 'J.D. Salinger'],
        ['title' => 'Lord of the Flies', 'author' => 'William Golding'],
        ['title' => 'Animal Farm', 'author' => 'George Orwell'],
        ['title' => 'Brave New World', 'author' => 'Aldous Huxley'],
        ['title' => 'The Hobbit', 'author' => 'J.R.R. Tolkien'],
        ['title' => 'Fahrenheit 451', 'author' => 'Ray Bradbury'],
    ];
}

function fruitsFixture(): array
{
    return ['apple', 'orange', 'banana', 'pear', 'grape', 'kiwi', 'mango', 'plum'];
}

function field($value, string $key)
{
    return is_array($value) ? $value[$key] : $value->{$key};
}

class OptimizationsAlwaysMatchSearcher implements SearchInterface
{
    public $pattern;

    public function __construct($pattern)
    {
        $this->pattern = $pattern;
    }

    public static function condition($pattern, array $options): bool
    {
        return $options['useAlwaysMatch'] ?? false;
    }

    public function searchIn($value): array
    {
        return [
            'isMatch' => true,
            'score' => 0.5,
            'indices' => [[0, 0]],
        ];
    }
}

describe('Search with limit', function () {
    test('limit=3 returns exactly 3 results', function () {
        $fuse = new Fuse(fruitsFixture());

        $results = $fuse->search('an', ['limit' => 3]);

        expect($results)->toHaveCount(3);
    });

    test('limit larger than result count returns all matches', function () {
        $fuse = new Fuse(fruitsFixture());

        $results = $fuse->search('apple', ['limit' => 100]);

        expect(count($results))->toBeGreaterThan(0);
        expect(count($results))->toBeLessThanOrEqual(count(fruitsFixture()));
    });

    test('limit=1 returns best match', function () {
        $fuse = new Fuse(fruitsFixture());

        $all = $fuse->search('orange');
        $limited = $fuse->search('orange', ['limit' => 1]);

        expect($limited)->toHaveCount(1);
        expect(field($limited[0], 'refIndex'))->toBe(field($all[0], 'refIndex'));
    });

    test('limit results match top-N of unlimited results', function () {
        $fuse = new Fuse(fruitsFixture());

        $all = $fuse->search('an');
        $limited = $fuse->search('an', ['limit' => 3]);

        expect($limited)->toHaveCount(3);

        for ($i = 0; $i < 3; $i++) {
            expect(field($limited[$i], 'refIndex'))->toBe(field($all[$i], 'refIndex'));
        }
    });
});

describe('Search with limit on object list', function () {
    test('limit=2 returns 2 results', function () {
        $fuse = new Fuse(booksFixture(), [
            'keys' => ['title', 'author'],
            'includeScore' => true,
        ]);

        $results = $fuse->search('the', ['limit' => 2]);

        expect($results)->toHaveCount(2);
    });

    test('limit results have same scores as unlimited top-N', function () {
        $fuse = new Fuse(booksFixture(), [
            'keys' => ['title', 'author'],
            'includeScore' => true,
        ]);

        $all = $fuse->search('George');
        $limited = $fuse->search('George', ['limit' => 2]);

        expect($limited)->toHaveCount(2);

        for ($i = 0; $i < count($limited); $i++) {
            expect(field($limited[$i], 'score'))
                ->toEqualWithDelta(field($all[$i], 'score'), 1e-10);
        }
    });

    test('limit=5 with includeMatches', function () {
        $fuse = new Fuse(booksFixture(), [
            'keys' => ['title', 'author'],
            'includeMatches' => true,
        ]);

        $results = $fuse->search('the', ['limit' => 5]);

        expect(count($results))->toBeLessThanOrEqual(5);

        foreach ($results as $result) {
            $matches = field($result, 'matches');

            expect($matches)->toBeArray();
            expect(count($matches))->toBeGreaterThan(0);
        }
    });
});

describe('Batch remove', function () {
    test('remove non-contiguous items', function () {
        $fuse = new Fuse(fruitsFixture());

        $removed = $fuse->remove(fn ($doc, $i) => $i === 0 || $i === 2 || $i === 4);

        expect($removed)->toBe(['apple', 'banana', 'grape']);
        expect($fuse->getIndex()->size())->toBe(5);

        $records = $fuse->getIndex()->records;

        foreach ($records as $idx => $record) {
            expect(field($record, 'i'))->toBe($idx);
        }
    });

    test('remove all items', function () {
        $fuse = new Fuse(fruitsFixture());

        $removed = $fuse->remove(fn () => true);

        expect($removed)->toHaveCount(count(fruitsFixture()));
        expect($fuse->getIndex()->size())->toBe(0);
    });

    test('remove single item via predicate', function () {
        $fuse = new Fuse(fruitsFixture());

        $removed = $fuse->remove(fn ($doc) => $doc === 'kiwi');

        expect($removed)->toBe(['kiwi']);
        expect($fuse->getIndex()->size())->toBe(count(fruitsFixture()) - 1);
    });

    test('search works correctly after batch remove', function () {
        $fuse = new Fuse(fruitsFixture());

        $fuse->remove(fn ($doc) => $doc === 'apple' || $doc === 'orange');

        $results = $fuse->search('banana');

        expect($results)->toHaveCount(1);
        expect(field($results[0], 'item'))->toBe('banana');
    });

    test('batch remove from object list', function () {
        $fuse = new Fuse(booksFixture(), [
            'keys' => ['title', 'author'],
            'threshold' => 0.2,
        ]);

        $before = $fuse->search('Orwell');
        expect(count($before))->toBeGreaterThan(0);

        $removed = $fuse->remove(fn ($doc) => $doc['author'] === 'George Orwell');
        expect($removed)->toHaveCount(2);

        $after = $fuse->search('Orwell');
        expect($after)->toHaveCount(0);
    });
});

describe('Fuse::use()', function () {
    test('registers a custom searcher plugin', function () {
        Fuse::use(OptimizationsAlwaysMatchSearcher::class);

        $fuse = new Fuse(['hello', 'world'], [
            'useAlwaysMatch' => true,
            'includeScore' => true,
        ]);

        $results = $fuse->search('zzz');

        expect($results)->toHaveCount(2);
    });
});

describe('Searcher cache', function () {
    test('repeated searches with same query return consistent results', function () {
        $fuse = new Fuse(fruitsFixture());

        $r1 = $fuse->search('apple');
        $r2 = $fuse->search('apple');

        expect($r1)->toEqual($r2);
    });

    test('different queries return different results', function () {
        $fuse = new Fuse(fruitsFixture());

        $r1 = $fuse->search('apple');
        $r2 = $fuse->search('orange');

        expect($r1)->not->toEqual($r2);
    });

    test('search works correctly after setCollection', function () {
        $fuse = new Fuse(fruitsFixture());

        $r1 = $fuse->search('apple');
        expect(count($r1))->toBeGreaterThan(0);

        $fuse->setCollection(['cat', 'dog', 'bird']);

        $r2 = $fuse->search('apple');
        expect($r2)->toHaveCount(0);

        $r3 = $fuse->search('cat');
        expect(count($r3))->toBeGreaterThan(0);
    });

    test('search works correctly after add', function () {
        $fuse = new Fuse(fruitsFixture());

        $r1 = $fuse->search('watermelon');

        $fuse->add('watermelon');

        $r2 = $fuse->search('watermelon');

        expect(count($r2))->toBeGreaterThan(count($r1));
    });
});
