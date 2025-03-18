<?php

use markhuot\voyage\auditors\Sqlite;
use markhuot\voyage\base\Collection;
use markhuot\voyage\base\Frame;
use markhuot\voyage\base\FrameManager;
use markhuot\voyage\base\SourceConnectionInterface;
use markhuot\voyage\connections\Connection;
use markhuot\voyage\connections\DestinationConnection;
use markhuot\voyage\transformers\CopyTransformer;
use markhuot\voyage\Voyage;

it('stores checksums', function () {
    ($voyage = (new Voyage(
        auditor: $auditor = new Sqlite(),
    )))
        ->addCollection($blog = new Collection(
            name: 'Blog',
            source: new class extends Connection implements SourceConnectionInterface {
                public function walk(FrameManager $frameManager, ?array $sourceKeys): Generator {
                    yield $frameManager->firstOrCreate(0);
                }
            },
            destination: new class extends DestinationConnection{
                public function upsert(Frame $frame): void {
                    $frame->destinationKey ??= (string)random_int(1, 1000000);
                    $frame->lastImport = new \DateTime;
                }
            },
            transformers: [
                new CopyTransformer(),
            ]
        ))
        ->start($blog);

    $data = $auditor->fetchFrameData(['collection' => 'blog', 'sourceKey' => 0]);
    expect($data)->checksum->not->toBeNull();
})->only();
