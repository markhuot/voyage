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

it('bootstraps', function () {
    $voyage = (new Voyage(
        concurrency: 4,
    ))
        ->addCollection($blog = new Collection(
            name: 'Blog',
            source: new ArrayConnection([['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]]),
            destination: new ArrayConnection([]),
        ))
        ->start($blog);

    expect(true)->toBe(true);
});

it('continues without devMode', function () {
    ($voyage = voyage())
        ->getCollections()[0]
        ->setTransformers([new class extends Transformer {
            public function transform(Frame $source, Frame $destination): void {
                throw new \Exception('This is a test exception');
            }
        }]);
    $voyage->start();

    $data = $voyage->getAuditor()->fetchFrameData(['collection' => 'blog', 'sourceKey' => 0]);
    expect($data[0])->lastError->not->toBeNull();
});

it('stops on exception in devMode', function () {
    $this->expectException(\Exception::class, 'This is a test exception');

    ($voyage = voyage())
        ->devMode(true)
        ->getCollections()[0]
        ->setTransformers([new class extends Transformer {
            public function transform(Frame $source, Frame $destination): void {
                throw new \Exception('This is a test exception');
            }
        }]);
    $voyage->start();
});
