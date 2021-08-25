<?php

declare(strict_types=1);

namespace Fuse\Test;

use PHPUnit\Framework\TestCase;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Fuse\Exception\InvalidKeyWeightValueException;
use Fuse\Exception\MissingKeyPropertyException;
use Fuse\Fuse;

class WeightedSearchTest extends TestCase
{
    use ArraySubsetAsserts;

    private function createFuse(array $options = []): Fuse
    {
        return new Fuse(
            [
                [
                    'title' => 'Old Man\'s War fiction',
                    'author' => 'John X',
                    'tags' => ['war'],
                ],
                [
                    'title' => 'Right Ho Jeeves',
                    'author' => 'P.D. Mans',
                    'tags' => ['fiction', 'war'],
                ],
                [
                    'title' => 'The life of Jane',
                    'author' => 'John Smith',
                    'tags' => ['john', 'smith'],
                ],
                [
                    'title' => 'John Smith',
                    'author' => 'Steve Pearson',
                    'tags' => ['steve', 'pearson'],
                ],
            ],
            $options,
        );
    }

    public function testInvalidKeyEntries(): void
    {
        $this->expectException(InvalidKeyWeightValueException::class);

        $this->createFuse([
            'keys' => [
                [
                    'name' => 'title',
                    'weight' => -10,
                ],
                [
                    'name' => 'author',
                    'weight' => 0.7,
                ],
            ],
        ]);
    }

    public function testMissingKeyProperties(): void
    {
        $this->expectException(MissingKeyPropertyException::class);

        $this->createFuse([
            'keys' => [
                [
                    'weight' => 10,
                ],
                [
                    'name' => 'author',
                    'weight' => 0.7,
                ],
            ],
        ]);
    }

    public function testWhenSearchingForTheTermJohnSmithWithAuthorWeightedHigher(): void
    {
        $fuse = $this->createFuse([
            'keys' => [
                [
                    'name' => 'title',
                    'weight' => 0.3,
                ],
                [
                    'name' => 'author',
                    'weight' => 0.7,
                ],
            ],
        ]);

        $result = $fuse->search('John Smith');

        // We get the the exactly matching object
        $this->assertArraySubset(
            [
                'item' => [
                    'title' => 'The life of Jane',
                    'author' => 'John Smith',
                    'tags' => ['john', 'smith'],
                ],
                'refIndex' => 2,
            ],
            $result[0],
        );
    }

    public function testWhenSearchingForTheTermJohnSmithWithAuthorWeightedHigherWithMixedKeyTypes(): void
    {
        $fuse = $this->createFuse([
            'keys' => [
                'title',
                [
                    'name' => 'author',
                    'weight' => 2,
                ],
            ],
        ]);

        $result = $fuse->search('John Smith');

        // We get the the exactly matching object
        $this->assertArraySubset(
            [
                'item' => [
                    'title' => 'The life of Jane',
                    'author' => 'John Smith',
                    'tags' => ['john', 'smith'],
                ],
                'refIndex' => 2,
            ],
            $result[0],
        );
    }

    public function testWhenSearchingForTheTermJohnSmithWithAuthorWeightedHigherWithMixedKeyTypes2(): void
    {
        // Throws when key does not have a name property
        $this->expectException(MissingKeyPropertyException::class);

        $this->createFuse([
            'keys' => [
                'title',
                [
                    'weight' => 2,
                ],
            ],
        ]);
    }

    public function testWhenSearchingForTheTermJohnSmithWithTitleWeightedHigher(): void
    {
        $fuse = $this->createFuse([
            'keys' => [
                [
                    'name' => 'title',
                    'weight' => 0.7,
                ],
                [
                    'name' => 'author',
                    'weight' => 0.3,
                ],
            ],
        ]);

        $result = $fuse->search('John Smith');

        // We get the the exactly matching object
        $this->assertArraySubset(
            [
                'item' => [
                    'title' => 'John Smith',
                    'author' => 'Steve Pearson',
                    'tags' => ['steve', 'pearson'],
                ],
                'refIndex' => 3,
            ],
            $result[0],
        );
    }

    public function testWhenSearchingForTheTermManWhereTheAuthorIsWeightedHigherThanTitle(): void
    {
        $fuse = $this->createFuse([
            'keys' => [
                [
                    'name' => 'title',
                    'weight' => 0.3,
                ],
                [
                    'name' => 'author',
                    'weight' => 0.7,
                ],
            ],
        ]);

        $result = $fuse->search('Man');

        // We get the the exactly matching object
        $this->assertArraySubset(
            [
                'item' => [
                    'title' => 'Right Ho Jeeves',
                    'author' => 'P.D. Mans',
                    'tags' => ['fiction', 'war'],
                ],
                'refIndex' => 1,
            ],
            $result[0],
        );
    }

    public function testWhenSearchingForTheTermManWhereTheTitleIsWeightedHigherThanAuthor(): void
    {
        $fuse = $this->createFuse([
            'keys' => [
                [
                    'name' => 'title',
                    'weight' => 0.7,
                ],
                [
                    'name' => 'author',
                    'weight' => 0.3,
                ],
            ],
        ]);

        $result = $fuse->search('Man');

        // We get the the exactly matching object
        $this->assertArraySubset(
            [
                'item' => [
                    'title' => 'Old Man\'s War fiction',
                    'author' => 'John X',
                    'tags' => ['war'],
                ],
                'refIndex' => 0,
            ],
            $result[0],
        );
    }

    public function testWhenSearchingForTheTermWarWhereTagsAreWeightedHigherThanAllOtherKeys(): void
    {
        $fuse = $this->createFuse([
            'keys' => [
                [
                    'name' => 'title',
                    'weight' => 0.4,
                ],
                [
                    'name' => 'author',
                    'weight' => 0.1,
                ],
                [
                    'name' => 'tags',
                    'weight' => 0.5,
                ],
            ],
        ]);

        $result = $fuse->search('War');

        // We get the the exactly matching object
        $this->assertArraySubset(
            [
                'item' => [
                    'title' => 'Old Man\'s War fiction',
                    'author' => 'John X',
                    'tags' => ['war'],
                ],
                'refIndex' => 0,
            ],
            $result[0],
        );
    }
}
