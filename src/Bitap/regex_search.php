<?php namespace Fuse\Bitap;

function regex_search($text, $pattern, $tokenSeparator = ' +')
{
    $regex = '/' . preg_replace('/' . str_replace('/', '\\/', $tokenSeparator) . '/', '|', str_replace('/', '\\/', preg_quote($pattern))) . '/';

    $isMatch = (bool) preg_match($regex, $text, $matches);
    $matchedIndices = [];

    if ($isMatch) {
        for ($i = 0, $matchesLen = sizeof($matches); $i < $matchesLen; $i++) {
            $match = $matches[$i];
            $matchedIndices[] = [ mb_strpos($text, $match), mb_strlen($match) - 1 ];
        }
    }

    return [
        'score' => $isMatch ? 0.5 : 1,
        'isMatch' => $isMatch,
        'matchedIndices' => $matchedIndices
    ];
}
