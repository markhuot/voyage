<?php

namespace markhuot\voyage\base;

use Generator;

interface SourceConnectionInterface extends ConnectionInterface
{
    /**
     * @return Generator<Frame<mixed>>
     */
    public function walk(?array $sourceKeys): Generator;
}
