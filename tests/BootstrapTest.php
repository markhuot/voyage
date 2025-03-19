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

it('errors out with devMode', function () {
    $voyage = (new Voyage(
        processes: 4,
        devMode: false,
    ))
        ->addCollection($blog = new Collection(
            name: 'Blog',
            source: new ArrayConnection([['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]]),
            destination: new ArrayConnection([]),
            transformers: [
                new class extends Transformer {
                    public function transform(Frame $source, Frame $destination): void {
                        throw new \Exception('test');
                    }
                }
            ]
        ))
        ->start($blog);

    expect(true)->toBe(true);
})->skip();

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

it('stores matrix', function () {
    ($voyage = voyage())
        ->getCollections()[0]
        ->setMatrix(['phase' => ['alpha', 'beta']]);

    $voyage->start(null, ['phase' => 'alpha'])
        ->start(null, ['phase' => 'beta']);

    $alpha = $voyage->getAuditor()->fetchFrameData([
        'collection' => 'blog',
        'matrix' => 'phase=alpha',
        'sourceKey' => '0',
    ]);
    expect($alpha[0])
        ->matrix->toBe('phase=alpha')
        ->destinationKey->not->toBeNull();

    $beta = $voyage->getAuditor()->fetchFrameData([
        'collection' => 'blog',
        'matrix' => 'phase=beta',
        'sourceKey' => '0',
    ]);
    expect($beta[0])
        ->matrix->toBe('phase=beta')
        ->destinationKey->toBe($alpha[0]['destinationKey']);
});

it('supports multiple matrix levels', function () {
    ($voyage = voyage())
        ->getCollections()[0]
        ->setMatrix([
            'phase' => ['alpha', 'beta'],
            'locale' => ['en', 'de'],
        ]);

    $voyage->start(null, ['phase' => 'alpha', 'locale' => 'en'])
        ->start(null, ['phase' => 'alpha', 'locale' => 'de'])
        ->start(null, ['phase' => 'beta', 'locale' => 'en'])
        ->start(null, ['phase' => 'beta', 'locale' => 'de']);

    $frames = $voyage->getAuditor()->fetchFrameData([
        'collection' => 'blog',
    ]);
    expect($frames)->toHaveCount(4);
    expect($frames[0])
        ->destinationKey->toBe($frames[1]['destinationKey'])
        ->destinationKey->toBe($frames[2]['destinationKey'])
        ->destinationKey->toBe($frames[3]['destinationKey']);

    expect($frames[0])->lastImport->not->toBeNull();
    expect($frames[1])->lastImport->not->toBeNull();
    expect($frames[2])->lastImport->not->toBeNull();
    expect($frames[3])->lastImport->not->toBeNull();
});
