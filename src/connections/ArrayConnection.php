<?php

namespace markhuot\voyage\connections;

use Generator;
use markhuot\voyage\base\DestinationConnectionInterface;
use markhuot\voyage\base\SourceConnectionInterface;
use markhuot\voyage\base\Frame;

class ArrayConnection extends Connection implements SourceConnectionInterface, DestinationConnectionInterface
{
    /**
     * @param array<mixed> $array
     */
    public function __construct(
        public array $array = [],
    ) {
    }

    public function walk(?array $sourceKeys): Generator
    {
        foreach ($this->array as $index => $data) {
            yield new Frame(
                $data,
                sourceKey: $index,
            );
        }
    }

    public function reconnect(): void { }

    /**
     * @param Frame<mixed> $frame
     * @return Frame<mixed>
     */
    public function prepare(Frame $frame): Frame
    {
        return (clone $frame)->setData([]);
    }

    /**
     * @param Frame<mixed> $frame
     */
    public function upsert(Frame $frame): void
    {
        if ($frame->sourceKey) {
            $this->array[$frame->sourceKey] = $frame->data;
            $destinationKey = $frame->sourceKey;
        }
        else {
            $this->array[] = $frame->data;
            $destinationKey = count($this->array) - 1;
        }

        $frame->destinationKey = $destinationKey;
        $frame->lastError = null;
        $frame->lastImport = new \DateTime();

        $this->trigger('upsert', [$frame]);
    }

    public function close(): void
    {
        // no-op here since frames are inserted immediately there's no file pointer
        // to close.
    }
}
