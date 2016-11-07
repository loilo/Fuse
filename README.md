# Fuse

*Fuzzy search for PHP based on Bitap algorithm*

This is a PHP port of the awesome [Fuse.js](https://github.com/krisk/fuse) package and (as of their version 2.5.0) provides 100% API compatibility.

For an approximate demonstration of what this library can do, check out their [demo & usage](http://fusejs.io/)

- [Installation](#installation)
- [Usage](#usage)
- [Options](#options)
- [Methods](#methods)
- [Weighted Search](#weighted-search)
- [Contributing](#contributing)

## Installation

This package is available via Composer. To add it to your project, just run:

`composer require loilo/fuse dev-master`

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
      [author] => Array
        (
          [firstName] => Steve
          [lastName] => Hamilton
        )
    )
  [1] => Array
    (
      [title] => HTML5
      [author] => Array
        (
          [firstName] => Remy
          [lastName] => Sharp
        )
    )
)
*/
```

## Options

**keys** (*type*: `array`)

List of properties that will be searched. This also supports nested properties:

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

**include** (*type*: `array`, *default*: `[]`)

An array of values that should be included from the searcher's output. When this array contains elements, each result in the list will be of the form `[ "item" => ..., "include1" => ..., "include2" => ... ]`. Values you can include are `score`, `matches`. For example:

```php
[ "include" => [ "score", "matches" ] ]
```

---

**shouldSort** (*type*: `bool`, *default*: `true`)

Whether to sort the result list, by score.

---

**searchFn** (*type*: `\Fuse\Searcher`, *default*: `\Fuse\BitapSearcher::class`)

The search class to use. It is required to implement the `\Fuse\Searcher` interface.

---

**getFn** (*type*: `function`, *default*: `\Fuse\Fuse::defaultValueGetter`)

The get function to use when fetching an object's properties. The default will search nested paths like *foo.bar.baz*.

```php
/*
@param $obj  The object or associative array being searched
@param $path The path to the target property
*/

// example using an object with a `getter` method
'getFn' => function ($obj, $path) {
  return $obj->get($path);
}
```
---

**sortFn** (*type*: `function`, *default*: `\Fuse\Fuse::defaultScoreSort`)

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

## Methods

**`search($pattern)`**

```php
/*
@param {string} $pattern The pattern string to fuzzy search on.
@return {array} A list of all search matches.
*/
```

Searches for all the items whose keys (fuzzy) match the pattern.

**`set(/*list*/)`**

```php
/*
@param {array} $list
@return {array} The newly set list
*/
```

Sets a new list of data for Fuse to match against.

## Weighted Search

In some cases you may want certain keys to be weighted differently:

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

Where `0 < weight <= 1`.

## Contributing

Before submitting a pull request, please add relevant [unit tests](https://phpunit.de/) to the `test` folder.