<?php namespace Fuse\Bitap;

function matched_indices($matchmask = [], $minMatchCharLength = 1)
{
    $matchedIndices = [];
    $start = -1;
    $end = -1;
    $i = 0;

    for ($len = sizeof($matchmask); $i < $len; $i++) {
        $match = $matchmask[$i];
        if ($match && $start === -1) {
            $start = $i;
        } elseif (!$match && $start !== -1) {
            $end = $i - 1;
            if (($end - $start) + 1 >= $minMatchCharLength) {
                $matchedIndices[] = [ $start, $end ];
            }
            $start = -1;
        }
    }

    // (i-1 - start) + 1 => i - start
    if (isset($matchmask[$i - 1]) && $matchmask[$i - 1] && ($i - $start) >= $minMatchCharLength) {
        $matchedIndices[] = [ $start, $i - 1 ];
    }

    return $matchedIndices;
}
