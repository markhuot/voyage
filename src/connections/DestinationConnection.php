<?php

namespace markhuot\voyage\connections;

use markhuot\voyage\base\DestinationConnectionInterface;
use markhuot\voyage\base\Frame;

class DestinationConnection extends Connection implements DestinationConnectionInterface {
    public function prepare(Frame $frame): Frame {
        return $frame;
    }

    public function reconnect(): void {
    }

    public function upsert(Frame $frame): void {
    }

    public function close(): void {
    }
}
