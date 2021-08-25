<?php

namespace Fuse\Exception;

class InvalidConfigKeyException extends \Exception
{
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Invalid config key: %s', $key));
    }
}
