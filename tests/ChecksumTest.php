<?php

use markhuot\voyage\auditors\Sqlite;
use markhuot\voyage\base\Collection;
use markhuot\voyage\base\Frame;
use markhuot\voyage\base\FrameManager;
use markhuot\voyage\base\SourceConnectionInterface;
use markhuot\voyage\connections\Connection;
use markhuot\voyage\connections\DestinationConnection;
use markhuot\voyage\transformers\CopyTransformer;

it('stores checksums', function () {
    ($voyage = voyage())->start();

    $data = $voyage->getAuditor()->fetchFrameData(['collection' => 'blog', 'sourceKey' => 0]);
    expect($data[0])->checksum->not->toBeNull();
})->only();

it('does not process unchanged frames', function () {
    // swap destination with a mock so we can assert how many times it is called
    $voyage = voyage();
    $destination = $voyage->getCollections()[0]->getDestination();
    $mock = Mockery::mock($destination)
        ->shouldReceive('upsert')
        ->once()
        ->getMock();
    $voyage->getCollections()[0]->setDestination($mock);
    
    // run once
    $voyage->start();

    $initialData = $voyage->getAuditor()->fetchFrameData(['collection' => 'blog', 'sourceKey' => 0]);
    expect($initialData[0])->checksum->not->toBeNull();

    // run a second time
    $voyage->start();

    $newData = $voyage->getAuditor()->fetchFrameData(['collection' => 'blog', 'sourceKey' => 0]);
    expect($newData[0])->checksum->toBe($initialData[0]['checksum']);
    expect($newData[0])->lastImport->toBe($initialData[0]['lastImport']);
})->only();
