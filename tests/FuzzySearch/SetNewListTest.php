<?php

declare(strict_types=1);

use Fuse\Fuse;

beforeEach(function () {
    $this->fuse = new Fuse([]);
    $this->fuse->setCollection(['Onion', 'Lettuce', 'Broccoli']);
});

test('when searching for the term Lettuce', function () {
    $result = $this->fuse->search('Lettuce');

    // we get a list of exactly 1 item
    expect($result)->toHaveCount(1);

    // whose value is the index 0, representing ["Apple"]
    expect($result[0]['refIndex'])->toBe(1);
});
