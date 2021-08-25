<?php

namespace Fuse\Exception;

class MissingKeyPropertyException extends \Exception
{
    public function __construct(string $property)
    {
        parent::__construct(sprintf('Missing %s property in key', $property));
    }
}
