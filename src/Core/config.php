<?php

namespace Fuse\Core;

use Fuse\Exception\InvalidConfigKeyException;

function config(...$args)
{
    static $config = null;
    if (is_null($config)) {
        $config = (object) [
            // Basic options
            'isCaseSensitive' => false,
            'ignoreDiacritics' => false,
            'includeScore' => false,
            'keys' => [],
            'shouldSort' => true,
            'sortFn' => '\Fuse\Helpers\sort',

            // Match options
            'includeMatches' => false,
            'findAllMatches' => false,
            'minMatchCharLength' => 1,

            // Fuzzy options
            'location' => 0,
            'threshold' => 0.6,
            'distance' => 100,

            // Advanced options
            'useExtendedSearch' => false,
            'getFn' => '\Fuse\Helpers\get',
            'ignoreLocation' => false,
            'ignoreFieldNorm' => false,
            'fieldNormWeight' => 1,
        ];
    }

    switch (sizeof($args)) {
        case 0:
            return $config;

        case 1:
            if (is_array($args[0])) {
                foreach ($args as $key => $value) {
                    config($key, $value);
                }
            } else {
                return $config->{$args[0]};
            }
            break;

        case 2:
            [$key, $value] = $args;

            if (!isset($config->{$key})) {
                throw new InvalidConfigKeyException($key);
            }

            $config->$key = $value;
            break;
    }
}
