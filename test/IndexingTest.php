<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Fuse\Fuse;
use ReflectionObject;

class IndexingTest extends TestCase
{
    use ArraySubsetAsserts;

    private static array $options = [
        'useExtendedSearch' => true,
        'includeMatches' => true,
        'includeScore' => true,
        'threshold' => 0.3,
        'keys' => ['title', 'author.firstName', 'author.lastName'],
    ];
    private static array $books;

    public static function setUpBeforeClass(): void
    {
        static::$books = require __DIR__ . '/fixtures/books.php';
    }

    private function idx(array $results): array
    {
        return array_map(fn(array $result) => $result['refIndex'], $results);
    }

    /**
     * @return array[]
     *
     * @psalm-return array<array{0: mixed, 1: mixed}>
     */
    private function idxMap(Fuse $fuse): array
    {
        return array_map(fn(array $item) => [$item['v'], $item['i']], $fuse->getIndex()->records);
    }

    public function testCreateIndexEnsurePropertiesExist(): void
    {
        $myIndex = Fuse::createIndex(static::$options['keys'], static::$books);

        $this->assertNotNull($myIndex->records);
        $this->assertNotNull($myIndex->keys);
    }

    public function testCreateIndexEnsureKeysCanBeCreatedWithObjects(): void
    {
        $myIndex = Fuse::createIndex(
            [
                [
                    'name' => 'title',
                ],
                [
                    'name' => 'author.firstName',
                ],
            ],
            static::$books,
        );

        $this->assertNotNull($myIndex->records);
        $this->assertNotNull($myIndex->keys);
    }

    public function testCreateIndexEnsureKeysCanBeCreatedWithGetFn(): void
    {
        $myIndex = Fuse::createIndex(
            [
                [
                    'name' => 'title',
                    'getFn' => fn($book) => $book['title'],
                ],
                [
                    'name' => 'author.firstName',
                    'getFn' => fn($book) => $book['author']['firstName'],
                ],
            ],
            static::$books,
        );

        $data = json_decode(json_encode($myIndex), true);
        $this->assertNotNull($data['records']);
        $this->assertNotNull($data['keys']);
    }

    public function testParseIndexEnsureIndexCanBeExportedAndFuseCanBeInitialized(): void
    {
        $myIndex = Fuse::createIndex(static::$options['keys'], static::$books);

        $this->assertSame(sizeof(static::$books), $myIndex->size());

        $data = json_decode(json_encode($myIndex), true);
        $this->assertNotNull($data['records']);
        $this->assertNotNull($data['keys']);

        $parsedIndex = Fuse::parseIndex($data);

        $this->assertSame(sizeof(static::$books), $parsedIndex->size());
    }

    public function testParseIndexSearchWithGetFn(): void
    {
        $fuse = new Fuse(static::$books, [
            'useExtendedSearch' => true,
            'includeMatches' => true,
            'includeScore' => true,
            'threshold' => 0.3,
            'keys' => [
                [
                    'name' => 'bookTitle',
                    'getFn' => fn($book) => $book['title'],
                ],
                [
                    'name' => 'authorName',
                    'getFn' => fn($book) => $book['author']['firstName'],
                ],
            ],
        ]);

        $result = $fuse->search([
            'bookTitle' => 'old man',
        ]);

        $this->assertCount(1, $result);
        $this->assertArraySubset([0], $this->idx($result));
    }

    public function testFuseCanBeInstantiatedWithAnIndex(): void
    {
        $myIndex = Fuse::createIndex(static::$options['keys'], static::$books);
        $fuse = new Fuse(static::$books, static::$options, $myIndex);

        $result = $fuse->search([
            'title' => 'old man',
        ]);

        $this->assertCount(1, $result);
        $this->assertArraySubset([0], $this->idx($result));
        $this->assertArraySubset([[0, 2], [4, 6]], $result[0]['matches'][0]['indices']);
    }

    public function testAddObjectToIndex(): void
    {
        $fuse = new Fuse(static::$books, static::$options);

        $fuse->add([
            'title' => 'book',
            'author' => [
                'firstName' => 'Kiro',
                'lastName' => 'Risk',
            ],
        ]);

        $result = $fuse->search('kiro');

        $this->assertCount(1, $result);
        $this->assertArraySubset([sizeof(static::$books)], $this->idx($result));
    }

    public function testAddStringToIndex(): void
    {
        $fuse = new Fuse(['apple', 'orange'], ['includeScore' => true]);

        $fuse->add('banana');

        $result = $fuse->search('banana');

        $this->assertCount(1, $result);
        $this->assertArraySubset([2], $this->idx($result));
    }

    public function testRemoveStringFromTheIndex(): void
    {
        $fuse = new Fuse(['apple', 'orange', 'banana', 'pear']);

        $this->assertSame(4, $fuse->getIndex()->size());
        $this->assertArraySubset(
            [['apple', 0], ['orange', 1], ['banana', 2], ['pear', 3]],
            $this->idxMap($fuse),
        );

        $fuse->removeAt(1);

        $this->assertSame(3, $fuse->getIndex()->size());
        $this->assertArraySubset([['apple', 0], ['banana', 1], ['pear', 2]], $this->idxMap($fuse));

        $results = $fuse->remove(fn($doc) => $doc === 'banana' || $doc === 'pear');

        $this->assertCount(2, $results);
        $this->assertSame(1, $fuse->getIndex()->size());

        $fuseReflection = new ReflectionObject($fuse);
        $docsProperty = $fuseReflection->getProperty('docs');
        $docsProperty->setAccessible(true);

        $this->assertCount(1, $docsProperty->getValue($fuse));
    }
}
