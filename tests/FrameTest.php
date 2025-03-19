<?php

use markhuot\voyage\auditors\Sqlite;
use markhuot\voyage\base\Collection;
use markhuot\voyage\base\Frame;
use markhuot\voyage\base\FrameManager;
use markhuot\voyage\base\SourceConnectionInterface;
use markhuot\voyage\base\Transformer;
use markhuot\voyage\connections\ArrayConnection;
use markhuot\voyage\connections\Connection;
use markhuot\voyage\connections\DestinationConnection;
use markhuot\voyage\transformers\CopyTransformer;
use markhuot\voyage\Voyage;

it('audits frames', function () {
    ($voyage = voyage())
        ->start();

    $data = $voyage->getAuditor()->fetchFrameData([
        'collection' => 'blog',
        'sourceKey' => '0',
    ]);
    expect($data[0])
        ->matrix->toBeEmpty()
        ->sourceKey->toBe('0')
        ->destinationKey->not->toBeNull();
});
