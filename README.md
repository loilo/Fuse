# Fuse
[![Travis](https://img.shields.io/travis/Loilo/Fuse.svg?label=unix&logo=travis)](https://travis-ci.org/Loilo/Fuse) [![AppVeyor](https://img.shields.io/appveyor/ci/Loilo/Fuse.svg?label=windows&logo=appveyor)](https://ci.appveyor.com/project/Loilo/fuse) [![Packagist](https://img.shields.io/packagist/v/loilo/fuse.svg)](https://packagist.org/packages/loilo/fuse)

*A fuzzy search library for PHP based on the [Bitap](https://en.wikipedia.org/wiki/Bitap_algorithm) algorithm*

This is a PHP port of the awesome [Fuse.js](https://github.com/krisk/fuse) project and provides 100% API compatibility.

> Latest compatible Fuse.js version: 3.4.2

---

Maintaining Fuse does take a significant amount of time. Not primarily for me, but for [Kiro](https://github.com/krisk), who maintains the JavaScript library Fuse is ported from and does all the heavy lifting.

If you get value out of this library and want it to improve, please consider [supporting Fuse.js](https://github.com/krisk/Fuse#supporting-fusejs).

---

For an approximate demonstration of what this library can do, check out their [demo & usage](http://fusejs.io/).

- [Installation](#installation)
- [Usage](#usage)
- [Options](#options)
- [Methods](#methods)
- [Weighted Search](#weighted-search)
- [Contributing](#contributing)


## Installation

This package is available via Composer. To add it to your project, just run:

```bash
composer require loilo/fuse
```

> **Note:** Be aware that this package has the same major and minor version as the Fuse.js original. However, the patch version numbers may differ since this repository may need additional fixes from time to time.

## Usage

```php
<?php
require_once 'vendor/autoload.php';

$fuse = new \Fuse\Fuse([
  [
    "title" => "Old Man's War",
    "author" => "John Scalzi"
  ],
  [
    "title" => "The Lock Artist",
    "author" => "Steve Hamilton"
  ],
  [
    "title" => "HTML5",
    "author" => "Remy Sharp"
  ],
  [
    "title" => "Right Ho Jeeves",
    "author" => "P.D Woodhouse"
  ],
], [
  "keys" => [ "title", "author" ],
]);

$fuse->search('hamil');

/*
Array
(
  [0] => Array
    (
      [title] => The Lock Artist
      [author] => Steve Hamilton
    )
  [1] => Array
    (
      [title] => HTML5
      [author] => Remy Sharp
    )
)
*/
```


## Options

**keys** (*type*: `array`)

List of properties that will be searched. This supports nested properties, weighted search, searching in arrays of strings and associative arrays etc:

```php
$books = [
  [
    "title" => "Old Man's War",
    "author" => [
      "firstName" => "John",
      "lastName" => "Scalzi"
    ]
  ]
];
$fuse = new \Fuse\Fuse($books, [
  "keys" => [ "title", "author.firstName" ]
]);
```

---

**id** (*type*: `string`)

The name of the identifier property. If specified, the returned result will be a list of the items' identifiers, otherwise it will be a list of the items.

---

**caseSensitive** (*type*: `bool`, *default*: `false`)

Indicates whether comparisons should be case sensitive.

---

**includeScore** (*type*: `bool`, *default*: `false`)

Whether the score should be included in the result set. A score of `0` indicates a perfect match, while a score of `1` indicates a complete mismatch.

---

**includeMatches** (*type*: `bool`, *default*: `false`)

Whether the matches should be included in the result set. When true, each record in the result set will include the indices of the matched characters: `"indices" => [ $start, $end ]`. These can consequently be used for highlighting purposes.

---

**shouldSort** (*type*: `bool`, *default*: `true`)

Whether to sort the result list, by score.

---

**getFn** (*type*: `function`, *default*: `\Fuse\Helpers\deep_value`)

The get function to use when fetching an associative array's properties. The default will search nested paths like `foo.bar.baz`.

```php
/*
 * @param {array|object} $data The object or associative array being searched
 * @param {string}       $path The path to the target property
 */

'getFn' => function ($data, $path) {
    // Example using a ->get() method on objects and simple index access on arrays
    return is_object($data)
        ? $data->get($path)
        : $data[$path];
}
```
---

**sortFn** (*type*: `function`, *default*: sort by score)

The function that is used for sorting the result list.

---

**location** (*type*: `int`, *default*: `0`)

Determines approximately where in the text is the pattern expected to be found.

---

**threshold** (*type*: `float`, *default*: `0.6`)

At what point does the match algorithm give up. A threshold of `0.0` requires a perfect match (of both letters and location), a threshold of `1.0` would match anything.

---

**distance** (*type*: `int`, *default*: `100`)

Determines how close the match must be to the fuzzy location (specified by `location`). An exact letter match which is `distance` characters away from the fuzzy location would score as a complete mismatch. A `distance` of `0` requires the match be at the exact `location` specified, a `distance` of `1000` would require a perfect match to be within 800 characters of the `location` to be found using a `threshold` of `0.8`.

---

**maxPatternLength** (*type*: `int`, *default*: `32`)

The maximum length of the search pattern. The longer the pattern, the more intensive the search operation will be.  Whenever the pattern exceeds the `maxPatternLength`, an error will be thrown.  Why is this important? Read [this](http://en.wikipedia.org/wiki/Word_(computer_architecture)#Word_size_choice).

---

**verbose** (*type*: `bool`, *default*: `false`)

Will print out steps. Useful for debugging.

---

**tokenize** (*type*: `bool`, *default*: `false`)

When true, the search algorithm will search individual words **and** the full string, computing the final score as a function of both. Note that when `tokenize` is `true`, the `threshold`, `distance`, and `location` are inconsequential for individual tokens.

---

**tokenSeparator** (*type*: `string`, *default*: `/ +/g`)

A regular expression string used to separate words of the search pattern when searching. Only applicable when `tokenize` is `true`.

---

**matchAllTokens** (*type*: `bool`, *default*: `false`)

When `true`, the result set will only include records that match all tokens. Will only work if `tokenize` is also true.

---

**findAllMatches** (*type*: `bool`, *default*: `false`)

When `true`, the matching function will continue to the end of a search pattern even if a perfect match has already been located in the string.

---

**minMatchCharLength** (*type*: `int`, *default*: `1`)

When set to include matches, only those whose length exceeds this value will be returned. (For instance, if you want to ignore single character index returns, set to `2`)


## Methods

The following methods are available on a `Fuse\Fuse` instance:

---

**`search($pattern)`**

```php
/*
@param {string} $pattern The pattern string to fuzzy search on.
@return {array} A list of all search matches.
*/
```

Searches for all the items whose keys (fuzzy) match the pattern.

---

**`setCollection($list)`**

```php
/*
@param {array}  $list The new data to use
@return {array}       The provided $list
*/
```

Sets a new list of data for Fuse to match against.


## Weighted Search

In some cases you may want certain keys to be weighted differently for more accurate results. You may provide each key with a custom `weight` (where `0 < weight <= 1`):

```php
$fuse = new \Fuse\Fuse($books, [
  "keys" => [
    [
      "name" => "title",
      "weight" => 0.3
    ],
    [
      "name" => "author",
      "weight" => 0.7
    ]
  ]
]);
```


## Contributing

Before submitting a pull request, please add relevant [unit tests](https://phpunit.de/) to the `test` folder.

Please note that I'm striving for feature parity with the original Fuse.js and therefore won't add own features beyond bug fixes.
