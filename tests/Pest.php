<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

// pest()->extend(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

use markhuot\voyage\auditors\Sqlite;
use markhuot\voyage\base\Collection;
use markhuot\voyage\base\Frame;
use markhuot\voyage\base\FrameManager;
use markhuot\voyage\base\SourceConnectionInterface;
use markhuot\voyage\connections\Connection;
use markhuot\voyage\connections\DestinationConnection;
use markhuot\voyage\Voyage;
use markhuot\voyage\output\MemoryStream;
use markhuot\voyage\transformers\CopyTransformer;

function voyage(...$args) {
    $randonInteger = random_int(1, 1000000);
    rmtree('audits');
    mkdir('audits');

    return (new Voyage(...$args))
        ->auditor(new Sqlite(path: 'audits/audit-' . $randonInteger . '.sqlite'))
        ->stream(new MemoryStream)
        ->addCollection(new Collection(
            name: 'Blog',
            source: new class extends Connection implements SourceConnectionInterface {
                public function walk(FrameManager $frameManager, ?array $sourceKeys): Generator {
                    yield $frameManager->firstOrCreate(0);
                }
            },
            destination: new class extends DestinationConnection {
                public function upsert(Frame $frame): void {
                    $frame->destinationKey ??= (string)random_int(1, 1000000);
                    $frame->lastImport = new \DateTime;
                }
            },
            transformers: [
                new CopyTransformer(),
            ]
        ));
}

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function rmtree(string $dir) {

    $files = array_diff(scandir($dir), array('.','..'));
 
     foreach ($files as $file) {
 
       (is_dir("$dir/$file")) ? rmtree("$dir/$file") : unlink("$dir/$file");
 
     }
 
     return rmdir($dir);
 
}
