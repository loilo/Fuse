<?php

declare(strict_types=1);

namespace Fuse\Test;

use Fuse\Fuse;

it('searches for the term Apple and returns the correct result', function () {
    $fuse = new Fuse(['Apple', 'Orange', 'Banana'], ['includeScore' => true]);

    $result = $fuse->search('Apple');

    expect($result)->toHaveCount(1);
    expect($result[0]['refIndex'])->toBe(0);
    expect($result[0]['score'])->toEqual(0);
});

it('performs a fuzzy search for the term Ran and returns the correct results', function () {
    $fuse = new Fuse(['Apple', 'Orange', 'Banana'], ['includeScore' => true]);

    $result = $fuse->search('ran');

    expect($result)->toHaveCount(2);
    expect($result[0]['refIndex'])->toBe(1);
    expect($result[0]['score'])->not->toEqual(0);
    expect($result[1]['refIndex'])->toBe(2);
    expect($result[1]['score'])->not->toEqual(0);
});
