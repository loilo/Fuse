<?php

declare(strict_types=1);

namespace Fuse\Test;

use Fuse\Fuse;

it('when searching for the term "test"', function () {
    $fuse = new Fuse(
        ['t te tes test tes te t'],
        [
            'includeMatches' => true,
            'findAllMatches' => true,
        ],
    );

    $result = $fuse->search('test');

    // We get a match containing 7 indices
    expect($result[0]['matches'][0]['indices'])->toHaveCount(7);

    // and the first index is a single character
    expect($result[0]['matches'][0]['indices'][0][0])->toBe(0);
    expect($result[0]['matches'][0]['indices'][0][1])->toBe(0);
});

describe('Searching with pattern larger than MAX_BITS should not produce duplicate indices', function () {
    $list = [['title' => '/images/payment/select-payment-icon.png']];
    $fuse = new Fuse($list, [
        'includeMatches' => true,
        'keys' => ['title'],
    ]);

    test('indices are deduplicated and merged when query exceeds 32 chars', function () use ($fuse) {
        $result = $fuse->search('images-payment-select-payment-icon');

        expect($result)->toHaveCount(1);

        $indices = $result[0]['matches'][0]['indices'];

        for ($i = 1; $i < sizeof($indices); $i++) {
            expect($indices[$i][0])->toBeGreaterThan($indices[$i - 1][1]);
        }
    });
});
