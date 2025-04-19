<?php

namespace markhuot\voyage\base;

use Generator;

abstract class Transformer implements TransformerInterface
{
    public function shouldTransform(Frame $frame, array $matrix): bool
    {
        return true;
    }

    abstract public function transform(Frame $source, Frame $destination, array $matrix): void;
}
