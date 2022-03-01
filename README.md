<div align="center">

![The Fuse logo, a violet asterisk, in reference to the Fuse.js logo](fuse.svg)
<br>

</div>

# Fuse

_A fuzzy search library for PHP_

[![Tests](https://badgen.net/github/checks/loilo/Fuse/master)](https://github.com/loilo/Fuse/actions)
[![Packagist](https://badgen.net/packagist/v/loilo/fuse)](https://packagist.org/packages/loilo/fuse)
![PHP Version](https://badgen.net/packagist/php/loilo/fuse)

This is a PHP port of the awesome [Fuse.js](https://github.com/krisk/fuse) project and aims to provide full API compatibility wherever possible.

Check out their [demo](https://fusejs.io/demo.html) and [examples](https://fusejs.io/examples.html) to get a good feel for what this library is capable of.

> Latest compatible Fuse.js version: 6.5.3

---

**Table of Contents:**

-   [Installation](#installation)
-   [Usage](#usage)
-   [Options](#options)
-   [Methods](#methods)
-   [Differences with Fuse.js](#differences-with-fusejs)
-   [Development](#development)

---

## Installation

This package is available via Composer. To add it to your project, just run:

```bash
composer require loilo/fuse
```

Note that at least PHP 7.4 is needed to use Fuse.

## Usage

Here's a simple usage example:

```php
<?php
require_once 'vendor/autoload.php';

$list = [
    [
        'title' => "Old Man's War",
        'author' => 'John Scalzi',
    ],
    [
        'title' => 'The Lock Artist',
        'author' => 'Steve Hamilton',
    ],
    [
        'title' => 'HTML5',
        'author' => 'Remy Sharp',
    ],
    [
        'title' => 'Right Ho Jeeves',
        'author' => 'P.D Woodhouse',
    ],
];

$options = [
    'keys' => ['title', 'author'],
];

$fuse = new \Fuse\Fuse($list, $options);

$fuse->search('hamil');
```

This leads to the following results (where each result's `item` refers to the matched entry itself and `refIndex` provides the item's position in the original `$list`):

```php
[
    [
        'item' => [
            'title' => 'The Lock Artist',
            'author' => 'Steve Hamilton',
        ],
        'refIndex' => 1,
    ],
    [
        'item' => [
            'title' => 'HTML5',
            'author' => 'Remy Sharp',
        ],
        'refIndex' => 2,
    ],
];
```

## Options

Fuse has a lot of options to refine your search:

### Basic Options

#### `isCaseSensitive`

-   Type: `bool`
-   Default: `false`

Indicates whether comparisons should be case sensitive.

---

#### `includeScore`

-   Type: `bool`
-   Default: `false`

Whether the score should be included in the result set. A score of `0` indicates a perfect match, while a score of `1` indicates a complete mismatch.

---

#### `includeMatches`

-   Type: `bool`
-   Default: `false`

Whether the matches should be included in the result set. When `true`, each record in the result set will include the indices of the matched characters. These can consequently be used for highlighting purposes.

---

#### `minMatchCharLength`

-   Type: `int`
-   Default: `1`

Only the matches whose length exceeds this value will be returned. (For instance, if you want to ignore single character matches in the result, set it to `2`).

---

#### `shouldSort`

-   Type: `bool`
-   Default: `true`

Whether to sort the result list, by score.

---

#### `findAllMatches`

-   Type: `bool`
-   Default: `false`

When true, the matching function will continue to the end of a search pattern even if a perfect match has already been located in the string.

---

#### `keys`

-   Type: `array`
-   Default: `[]`

List of keys that will be searched. This supports nested paths, weighted search, searching in arrays of [strings](https://fusejs.io/examples.html#search-string-array) and [objects](https://fusejs.io/examples.html#nested-search).

---

### Fuzzy Matching Options

#### `location`

-   Type: `int`
-   Default: `0`

Determines approximately where in the text is the pattern expected to be found.

---

#### `threshold`

-   Type: `float`
-   Default: `0.6`

At what point does the match algorithm give up. A threshold of `0.0` requires a perfect match (of both letters and location), a threshold of `1.0` would match anything.

---

#### `distance`

-   Type: `int`
-   Default: `100`

Determines how close the match must be to the fuzzy location (specified by `location`). An exact letter match which is `distance` characters away from the fuzzy location would score as a complete mismatch. A `distance` of `0` requires the match be at the exact `location` specified. A distance of `1000` would require a perfect match to be within `800` characters of the `location` to be found using a `threshold` of `0.8`.

---

#### `ignoreLocation`

-   Type: `bool`
-   Default: `false`

When `true`, search will ignore `location` and `distance`, so it won't matter where in the string the pattern appears.

> **Tip:** The default options only search the first 60 characters. This should suffice if it is reasonably expected that the match is within this range. To modify this behavior, set the appropriate combination of `location`, `threshold`, `distance` (or `ignoreLocation`).
>
> To better understand how these options work together, read about [Fuse.js' Scoring Theory](https://fusejs.io/concepts/scoring-theory.html#scoring-theory).

---

### Advanced Options

#### `useExtendedSearch`

-   Type: `bool`
-   Default: `false`

When `true`, it enables the use of unix-like search commands. See [example](https://fusejs.io/examples.html#extended-search).

---

#### `getFn`

-   Type: `callable`
-   Default: [source](src/Helpers/get.php)

The function to use to retrieve an object's value at the provided path. The default will also search nested paths.

---

#### `sortFn`

-   Type: `callable`
-   Default: [source](src/Helpers/sort.php)

The function to use to sort all the results. The default will sort by ascending relevance score, ascending index.

---

#### `ignoreFieldNorm`

-   Type: `bool`
-   Default: `false`

When `true`, the calculation for the relevance score (used for sorting) will ignore the [field-length norm](https://fusejs.io/concepts/scoring-theory.html#fuzziness-score).

---

> **Tip:** The only time it makes sense to set `ignoreFieldNorm` to `true` is when it does not matter how many terms there are, but only that the query term exists.

### `fieldNormWeight`

-   Type: `float`
-   Default: `1`

Determines how much the [field-length norm](https://fusejs.io/concepts/scoring-theory.html#field-length-norm) affects scoring. A value of `0` is equivalent to ignoring the field-length norm. A value of `0.5` will greatly reduce the effect of field-length norm, while a value of `2.0` will greatly increase it.

---

### Global Config

You can access and manipulate default values of all options above via the `config` method:

```php
// Get an associative array of all options listed above
Fuse::config();

// Merge associative array of options into default config
Fuse::config(['shouldSort' => false]);

// Get single default option
Fuse::config('shouldSort');

// Set single default option
Fuse::config('shouldSort', false);
```

## Methods

The following methods are available on each `Fuse\Fuse` instance:

---

### `search`

Searches the entire collection of documents, and returns a list of search results.

```php
public function search(mixed $pattern, ?array $options): array
```

The `$pattern` can be one of:

-   [String](https://fusejs.io/examples.html#search-string-array)
-   [Path](https://fusejs.io/examples.html#nested-search)
-   [Extended query](https://fusejs.io/examples.html#extended-search)
-   [Logical query](https://fusejs.io/api/query.html)

The `$options`:

-   `limit` (type: `int`): Denotes the max number of returned search results.

---

### `setCollection`

Set/replace the entire collection of documents. If no index is provided, one will be generated.

```php
public function setCollection(array $docs, ?\Fuse\Core\FuseIndex $index): void
```

**Example:**

```php
$fruits = ['apple', 'orange'];
$fuse = new Fuse($fruits);

$fuse->setCollection(['banana', 'pear']);
```

### `add`

Adds a doc to the collection and update the index accordingly.

```php
public function add(mixed $doc): void
```

**Example:**

```php
$fruits = ['apple', 'orange'];
$fuse = new Fuse($fruits);

$fuse->add('banana');

sizeof($fruits); // => 3
```

---

### `remove`

Removes all documents from the list which the predicate returns truthy for, and returns an array of the removed docs. The predicate is invoked with two arguments: `($doc, $index)`.

```php
public function remove(?callable $predicate): array
```

**Example:**

```php
$fruits = ['apple', 'orange', 'banana', 'pear'];
$fuse = new Fuse($fruits);

$results = $fuse->remove(fn($doc) => $doc === 'banana' || $doc === 'pear');
sizeof($fuse->getCollection()); // => 2
$results; // => ['banana', 'pear']
```

---

### `removeAt`

Removes the doc at the specified index.

```php
public function removeAt(int $index): void
```

**Example:**

```php
$fruits = ['apple', 'orange', 'banana', 'pear'];
$fuse = new Fuse($fruits);

$fuse->removeAt(1);

$fuse->getCollection(); // => ['apple', 'banana', 'pear']
```

---

### `getIndex`

Returns the generated Fuse index.

```php
public function getIndex(): \Fuse\Core\FuseIndex
```

**Example:**

```php
$fruits = ['apple', 'orange', 'banana', 'pear'];
$fuse = new Fuse($fruits);

$fuse->getIndex()->size(); // => 4
```

## Indexing

The following methods are available on each `Fuse\Fuse` instance:

---

### `Fuse::createIndex`

Pre-generate the index from the list, and pass it directly into the Fuse instance. If the list is (considerably) large, it speeds up instantiation.

```php
public static function createIndex(array $keys, array $docs, array $options = []): \Fuse\Core\FuseIndex
```

**Example:**

```php
$list = [ ... ]; // See the example from the 'Usage' section
$options = [ 'keys' => [ 'title', 'author.firstName' ] ];

// Create the Fuse index
$myIndex = Fuse::createIndex($options['keys'], $list);

// Initialize Fuse with the index
$fuse = new Fuse($list, $options, $myIndex);
```

### `Fuse::parseIndex`

Parses a JSON-serialized Fuse index.

```php
public static function parseIndex(array $data, array $options = []): \Fuse\Core\FuseIndex
```

**Example:**

```php
// (1) When the data is collected

$list = [ ... ]; // See the example from the 'Usage' section
$options = [ 'keys' => [ 'title', 'author.firstName' ] ];

// Create the Fuse index
$myIndex = Fuse::createIndex($options['keys'], $list);

// Serialize and save it
file_put_contents('fuse-index.json', json_encode($myIndex));


// (2) When the search is needed

// Load and deserialize index to an array
$fuseIndex = json_decode(file_get_contents('fuse-index.json'), true);
$myIndex = Fuse::parseIndex($fuseIndex);

// Initialize Fuse with the index
$fuse = new Fuse($list, $options, $myIndex);
```

## Differences with Fuse.js

<!-- prettier-ignore -->
&nbsp; | Fuse.js | PHP Fuse
-|-|-
Get Fuse Version | `Fuse.version` | – |
Access global configuration | [`Fuse.config`](https://fusejs.io/api/config.html) property | [`Fuse::config`](#global-config) method
List modification | Using `fuse.add()` etc. modifies the original list passed to the `new Fuse` constructor. | In PHP, arrays are a primitive data type, which means that your original list is never modified by Fuse. To receive the current list after adding/removing items, the `$fuse->getCollection()` method can be used.

## Development

### Project Scope

Please note that I'm striving for feature parity with Fuse.js and therefore will add neither features nor fixes to the search logic that are not reflected in Fuse.js itself.

If you have any issues with search results that are _not_ obviously bugs in this PHP port, and you happen to know JavaScript, please check if your use case works correctly in the [online demo of Fuse.js](https://fusejs.io/demo.html) as that is the canonical Fuse implementation. If the issue appears there as well, please open an issue [in their repo](https://github.com/krisk/Fuse).

### Setup

> To start development on Fuse, you need git, PHP (≥ 7.4) and Composer.
>
> Since code is formatted using [Prettier](https://prettier.io/), it's also recommended to have Node.js/npm installed as well as using an [editor which supports Prettier](https://prettier.io/docs/en/editors.html) formatting.

Clone the repository and `cd` into it:

```sh
git clone https://github.com/loilo/fuse.git
cd fuse
```

Install Composer dependencies:

```sh
composer install
```

Install npm dependencies (optional but recommended). This is only needed for code formatting as npm dependencies include Prettier plugins used by this project.

```
npm ci
```

### Quality Assurance

There are different kinds of code checks in place for this project. All of these are run when a pull request is submitted but can also be run locally:

<!-- prettier-ignore -->
Command | Purpose | Description
-|-|-
`vendor/bin/phpcs` | check code style | Run [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) to verify that the Fuse source code abides by the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style.
`vendor/bin/psalm` | static analysis | Run [Psalm](https://psalm.dev/) against the codebase to avoid type-related errors and unsafe coding patterns.
`vendor/bin/phpunit` | check program logic | Run all [PHPUnit](https://phpunit.de/) tests from the [`test`](test/) folder.

### Contributing

Before submitting a pull request, please add relevant tests to the [`test`](test/) folder.
