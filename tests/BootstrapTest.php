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
});

it('audits frames', function () {
    ($voyage = (new Voyage(
        concurrency: 4,
        auditor: new Sqlite(),
    )))
        ->addCollection($blog = new Collection(
            name: 'Blog',
            source: new ArrayConnection([['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]]),
            destination: new ArrayConnection([]),
            transformers: [
                new CopyTransformer(),
            ]
        ))
        ->start($blog);

    $frame = new Frame(
        collection: 'blog',
        sourceKey: '1',
    );
    $voyage->getAuditor()->hydrateFrame($frame);
    expect($frame)
        ->matrix->toBeNull()
        ->sourceKey->toBe('1')
        ->destinationKey->toBe('1');
});

it('stores matrix', function () {
    ($voyage = (new Voyage(
        auditor: $auditor = new Sqlite(),
    )))
        ->addCollection($blog = new Collection(
            name: 'Blog',
            matrix: ['phase' => ['alpha', 'beta']],
            source: new class extends Connection implements SourceConnectionInterface {
                public function walk(FrameManager $frameManager, ?array $sourceKeys): Generator {
                    yield $frameManager->firstOrCreate(0);
                }
            },
            destination: new class extends DestinationConnection{
                public function upsert(Frame $frame): void {
                    $frame->destinationKey ??= (string)random_int(1, 1000000);
                }
            },
            transformers: [
                new CopyTransformer(),
            ]
        ))
        ->start($blog, ['phase' => 'alpha'])
        ->start($blog, ['phase' => 'beta']);

    $alpha = $auditor->fetchFrameData([
        'collection' => 'blog',
        'matrix' => 'phase=alpha',
        'sourceKey' => '0',
    ]);
    expect($alpha[0])
        ->matrix->toBe('phase=alpha')
        ->destinationKey->not->toBeNull();

    $beta = $auditor->fetchFrameData([
        'collection' => 'blog',
        'matrix' => 'phase=beta',
        'sourceKey' => '0',
    ]);
    expect($beta[0])
        ->matrix->toBe('phase=beta')
        ->destinationKey->toBe($alpha[0]['destinationKey']);
});

it('supports multiple matrix levels', function () {
    ($voyage = (new Voyage(
        auditor: $auditor = new Sqlite(),
    )))
        ->addCollection($blog = new Collection(
            name: 'Blog',
            matrix: [
                'phase' => ['alpha', 'beta'],
                'locale' => ['en', 'de'],
            ],
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
        ->start($blog, ['phase' => 'alpha', 'locale' => 'en'])
        ->start($blog, ['phase' => 'alpha', 'locale' => 'de'])
        ->start($blog, ['phase' => 'beta', 'locale' => 'en'])
        ->start($blog, ['phase' => 'beta', 'locale' => 'de']);

    $frames = $auditor->fetchFrameData([
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
