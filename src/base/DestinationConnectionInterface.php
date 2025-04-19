<?php

namespace markhuot\voyage\base;

use Generator;
use markhuot\voyage\Voyage;

interface DestinationConnectionInterface extends ConnectionInterface
{
    /**
     * @param Frame<mixed> $frame
     * @return Frame<mixed>
     */
    public function prepare(Frame $frame): Frame;

    /**
     * Provides the connection space to re-connect to any sockets after the PHP process forks
     */
    public function reconnect(): void;

    /**
     * Upsert the frame data in to the destination persistent storage
     *
     * @param Frame<mixed> $frame
     */
    public function upsert(Frame $frame, Collection $collection, Voyage $voyage): void;

    public function close(): void;
}
