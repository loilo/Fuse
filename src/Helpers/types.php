<?php

namespace Fuse\Helpers\Types;

function isArray($value): bool
{
    if (!is_array($value)) {
        return false;
    }

    foreach ($value as $key => $_) {
        if (!is_int($key)) {
            return false;
        }
    }

    return true;
}

function isAssoc($value): bool
{
    return is_array($value) && !isArray($value);
}

function isNumber($value): bool
{
    return is_int($value) || is_float($value);
}

function isBlank(string $value): bool
{
    return mb_strlen(trim($value)) === 0;
}
