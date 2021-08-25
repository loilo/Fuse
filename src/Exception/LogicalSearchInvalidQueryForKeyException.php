<?php

namespace Fuse\Exception;

class LogicalSearchInvalidQueryForKeyException extends \Exception
{
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Invalid value for key %s', $key));
    }
}
