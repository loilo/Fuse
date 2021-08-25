<?php

namespace Fuse\Exception;

class InvalidKeyWeightValueException extends \Exception
{
    public function __construct(string $key)
    {
        parent::__construct(
            sprintf('Property \'weight\' in key \'$%s\' must be a positive integer', $key),
        );
    }
}
