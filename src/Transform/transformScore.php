<?php

namespace Fuse\Transform;

function transformScore(array &$result, array &$data): void
{
    $data['score'] = $result['score'];
}
