<?php

use markhuot\voyage\base\Collection;
use markhuot\voyage\base\Frame;
use markhuot\voyage\base\Transformer;
use markhuot\voyage\connections\ArrayConnection;
use markhuot\voyage\transformers\CopyTransformer;
use markhuot\voyage\Voyage;

it('bootstraps', function () {
    $voyage = (new Voyage(
        processes: 4,
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
