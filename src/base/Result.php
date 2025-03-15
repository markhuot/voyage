<?php

namespace markhuot\voyage\base;

use markhuot\voyage\Voyage;

class Result
{
    /**
     * @param array<mixed> $errors
     */
    public function __construct(
        public Voyage $etl,
        public array $errors = [],
    ) {
    }
}
