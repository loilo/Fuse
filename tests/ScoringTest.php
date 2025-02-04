<?php

declare(strict_types=1);

use Fuse\Fuse;

beforeEach(function () {
    $this->defaultList = ['Stove', 'My good friend Steve from college'];
    $this->defaultOptions = [];
    $this->setupFuse = function ($itemList = null, $overwriteOptions = []) {
        $list = $itemList ?? $this->defaultList;
        $options = array_merge($this->defaultOptions, $overwriteOptions);

        return new Fuse($list, $options);
    };
});

it('should return the correct indices when field norm is off', function () {
    $fuse = ($this->setupFuse)();
    $result = $fuse->search('Steve');

    expect($result)->toHaveCount(2);
    expect($result[0]['refIndex'])->toBe(0);
    expect($result[1]['refIndex'])->toBe(1);
});

it('should return the correct indices when field norm weight is decreased', function () {
    $fuse = ($this->setupFuse)(null, ['fieldNormWeight' => 0.15]);
    $result = $fuse->search('Steve');

    expect($result)->toHaveCount(2);
    expect($result[0]['refIndex'])->toBe(1);
    expect($result[1]['refIndex'])->toBe(0);
});
