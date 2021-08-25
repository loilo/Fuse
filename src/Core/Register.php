<?php

namespace Fuse\Core;

use Fuse\Exception\IncorrectSearcherTypeException;
use Fuse\Search\Bitap\BitapSearch;
use Fuse\Search\SearchInterface;

class Register
{
    /**
     * @var string[]
     */
    private static array $registeredSearchers = [];

    /**
     * @param string ...$searchers
     *
     * @return void
     */
    public static function register(...$searchers): void
    {
        foreach ($searchers as $searcher) {
            if (!in_array(SearchInterface::class, class_implements($searcher), true)) {
                throw new IncorrectSearcherTypeException();
            }
        }

        static::$registeredSearchers = array_values(
            array_unique(array_merge(static::$registeredSearchers, $searchers)),
        );
    }

    public static function createSearcher(string $pattern, array $options): SearchInterface
    {
        for ($i = 0, $len = sizeof(static::$registeredSearchers); $i < $len; $i += 1) {
            $searcherClass = static::$registeredSearchers[$i];
            if ($searcherClass::condition($pattern, $options)) {
                /**
                 * @var SearchInterface
                 */
                $searcher = new $searcherClass($pattern, $options);
                return $searcher;
            }
        }

        return new BitapSearch($pattern, $options);
    }
}
