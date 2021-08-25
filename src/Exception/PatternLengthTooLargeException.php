<?php

namespace Fuse\Exception;

class PatternLengthTooLargeException extends \Exception
{
    public function __construct(int $maxLength)
    {
        parent::__construct(sprintf('Pattern length exceeds max of %s', $maxLength));
    }
}
