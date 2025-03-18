<?php

use markhuot\voyage\auditors\Sqlite;
use markhuot\voyage\base\Collection;
use markhuot\voyage\base\Frame;
use markhuot\voyage\base\Transformer;
use markhuot\voyage\connections\ArrayConnection;
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

it('gets parent frame', function () {
    ($voyage = (new Voyage(
        auditor: new Sqlite(),
    )))
        ->addCollection($blog = new Collection(
            name: 'Blog',
            source: new ArrayConnection([['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]]),
            destination: new ArrayConnection([]),
            matrix: ['phase' => ['alpha', 'beta']],
            transformers: [
                new CopyTransformer(),
            ]
        ))
        ->start($blog, ['phase' => 'alpha'])
        ->start($blog, ['phase' => 'beta']);

    $frame = new Frame(
        collection: 'blog',
        sourceKey: '1',
    );
    $voyage->getAuditor()->hydrateFrame($frame);
    expect($frame->matrix)->toBe('phase=alpha');
})->only();
