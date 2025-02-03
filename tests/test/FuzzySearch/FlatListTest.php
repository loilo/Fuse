<?php

declare(strict_types=1);

namespace Fuse\Test;

use Fuse\Fuse;

it('searches for the term Apple and returns exactly 1 result', function () {
    $fuse = new Fuse(['Apple', 'Orange', 'Banana']);
    $result = $fuse->search('Apple');

    expect($result)->toHaveCount(1);
    expect($result[0]['refIndex'])->toBe(0);
});

it('performs a fuzzy search for the term ran and returns 2 results', function () {
    $fuse = new Fuse(['Apple', 'Orange', 'Banana']);
    $result = $fuse->search('ran');

    expect($result)->toHaveCount(2);
    expect($result[0]['refIndex'])->toBe(1);
    expect($result[1]['refIndex'])->toBe(2);
});

it('performs a fuzzy search for the term nan and returns 2 results', function () {
    $fuse = new Fuse(['Apple', 'Orange', 'Banana']);
    $result = $fuse->search('nan');

    expect($result)->toHaveCount(2);
    expect($result[0]['refIndex'])->toBe(2);
    expect($result[1]['refIndex'])->toBe(1);
});

it('performs a fuzzy search for the term nan with a limit of 1 result', function () {
    $fuse = new Fuse(['Apple', 'Orange', 'Banana']);
    $result = $fuse->search('nan', ['limit' => 1]);

    expect($result)->toHaveCount(1);
    expect($result[0]['refIndex'])->toBe(2);
});
