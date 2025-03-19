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

it('gets matrix combos', function () {
    $voyage = voyage();
    $collection = $voyage->getCollections()[0]->setMatrix([
        'phase' => ['default', 'relations' => 'depends_on=phase=default'],
        'locale' => ['en', 'de'],
    ]);

    expect($collection->getMatrixCombinations())->toBe([
        ['phase' => 'default', 'locale' => 'en'],
        ['phase' => 'default', 'locale' => 'de'],
        ['phase' => 'relations', 'locale' => 'en'],
        ['phase' => 'relations', 'locale' => 'de'],
    ]);
});

it('tracks matrix dependencies', function () {
    $voyage = voyage();
    $collection = $voyage->getCollections()[0]->setMatrix([
        'phase' => ['default', 'relations' => 'depends_on=phase=default'],
        'locale' => ['en', 'de'],
    ]);
    $voyage->setCollections([
        $collection,
        (clone $collection)->setName('News'),
    ]);
    $voyage->start();

    $blogFrames = $voyage->getAuditor()->fetchFrameData([
        'collection' => 'blog',
    ]);
    dd($blogFrames);
    $newsFrames = $voyage->getAuditor()->fetchFrameData([
        'collection' => 'news',
    ]);

    expect($blogFrames)->toHaveCount(4);
    expect($newsFrames)->toHaveCount(4);
})->only();
