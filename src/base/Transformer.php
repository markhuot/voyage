<?php

namespace markhuot\voyage\base;

use Generator;
use markhuot\voyage\phases\DefaultPhase;

abstract class Transformer implements TransformerInterface, DefaultPhase
{
    public function getPhase(): string
    {
        return 'default';
    }

    public function canTransform(Frame $frame): bool
    {
        return true;
    }

    abstract public function transform(Frame $source, Frame $destination): void;
}
