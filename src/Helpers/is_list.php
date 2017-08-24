<?php namespace Fuse\Helpers;

function is_list($var)
{
    return is_array($var) && !array_diff_key($var, array_keys(array_keys($var)));
}
