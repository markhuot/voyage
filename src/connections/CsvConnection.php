<?php

namespace markhuot\etl\connections;

use Generator;
use markhuot\etl\base\DestinationConnectionInterface;
use markhuot\etl\base\Frame;
use markhuot\etl\base\SourceConnectionInterface;

class CsvConnection implements SourceConnectionInterface, DestinationConnectionInterface
{
    public function __construct(
        protected string $data,
    ) {
    }

    public function on(string $event, callable $listener): static
    {
        // TODO: Implement on() method.
    }

    public function trigger(string $event, ...$args): void
    {
        // TODO: Implement trigger() method.
    }

    public function prepare(Frame $frame): Frame
    {
        // TODO: Implement prepareFrame() method.
    }

    public function upsert(Frame $frame): void
    {
        // TODO: Implement upsertFrame() method.
    }

    public function close(): void
    {
        // TODO: Implement close() method.
    }

    public function walk(?array $sourceKeys): Generator
    {
        if (($handle = fopen($this->filename, "r")) !== FALSE) {
            $row = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                yield new Frame($data);
            }
            fclose($handle);
        }
    }
}
