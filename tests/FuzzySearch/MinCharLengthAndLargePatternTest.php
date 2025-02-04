<?php

declare(strict_types=1);

use Fuse\Fuse;

it(
    'searches for the term "American as apple pie is odd treatment of something made by mom"',
    function () {
        $fuse = new Fuse(
            [
                'Apple pie is a tasty treat that is always best made by mom! But we love store bought too.',
                'Banana splits are what you want from DQ on a hot day.  But a parfait is even better.',
                'Orange sorbet is just a strange yet satisfying snack.  ' .
                'Chocolate seems to be more of a favourite though.',
            ],
            [
                'includeMatches' => true,
                'findAllMatches' => true,
                'includeScore' => true,
                'minMatchCharLength' => 20,
                'threshold' => 0.6,
                'distance' => 30,
            ],
        );

        $result = $fuse->search('American as apple pie is odd treatment of something made by mom');

        expect($result)->toHaveCount(1);
        expect($result[0]['refIndex'])->toBe(0);
        expect($result[0]['matches'])->toHaveCount(1);
    },
);
