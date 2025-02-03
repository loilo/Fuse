<?php

declare(strict_types=1);

use Fuse\Fuse;

beforeEach(function () {
    $this->fuse = new Fuse(
        [
            [
                'name' => 'Hello World',
            ],
        ],
        [
            'keys' => ['name'],
            'includeScore' => true,
            'includeMatches' => true,
        ],
    );
});

test('when searching for the term Wor', function () {
    $result = $this->fuse->search('wor');

    // We get a list whose indices are found
    expect($result[0]['matches'][0]['indices'][0])->toBe([4, 4]);
    expect($result[0]['matches'][0]['indices'][1])->toBe([6, 8]);

    // with original text values
    expect($result[0]['matches'][0]['value'])->toBe('Hello World');
});
