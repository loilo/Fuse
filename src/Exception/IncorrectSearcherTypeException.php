<?php

namespace Fuse\Exception;

use Fuse\Search\SearchInterface;

class IncorrectSearcherTypeException extends \Exception
{
    public function __construct(string $searcher_type)
    {
        parent::__construct(
            sprintf(
                'Incorrect registered searcher type %s, must extend %s',
                $searcher_type,
                SearchInterface::class,
            ),
        );
    }
}
