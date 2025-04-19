<?php

namespace markhuot\voyage\base;

use Generator;

interface TransformerInterface
{
    /**
     * @param Frame<mixed> $source
     */
    public function shouldTransform(Frame $source, array $matrix): bool;

    /**
     * @param Frame<mixed> $source
     * @param Frame<mixed> $destination
     */
    public function transform(Frame $source, Frame $destination, array $matrix): void;
}
