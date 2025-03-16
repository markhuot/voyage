<?php

namespace markhuot\voyage\base;

use Generator;

abstract class Transformer implements TransformerInterface
{
    public function canTransform(Frame $frame): bool
    {
        return true;
    }

    abstract public function transform(Frame $source, Frame $destination): void;
}
