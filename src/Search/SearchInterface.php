<?php

namespace Fuse\Search;

interface SearchInterface
{
    public function searchIn(string $text): array;
}
