<?php

namespace markhuot\voyage\base;

use Generator;

interface SourceConnectionInterface extends ConnectionInterface
{
    /**
     * @return Generator<Frame<mixed>>
     */
    public function walk(FrameManager $frameManager, ?array $sourceKeys): Generator;
}
