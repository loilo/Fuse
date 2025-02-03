<?php

namespace Fuse\Search\Extended;

abstract class BaseMatch
{
    public static string $type = 'base';
    protected static string $singleRegex = '/(.+)/';
    protected static string $multiRegex = '/(.+)/';
    protected string $pattern;

    // @phpstan-ignore constructor.unusedParameter
    public function __construct(string $pattern, array $options = [])
    {
        $this->pattern = $pattern;
    }

    protected static function getMatch(string $pattern, string $exp): ?string
    {
        /** @var non-empty-string $exp */

        preg_match($exp, $pattern, $matches);

        return $matches[1] ?? null;
    }

    public static function isMultiMatch(string $pattern): ?string
    {
        return static::getMatch($pattern, static::$multiRegex);
    }

    public static function isSingleMatch(string $pattern): ?string
    {
        return static::getMatch($pattern, static::$singleRegex);
    }

    abstract public function search(string $text): array;
}
