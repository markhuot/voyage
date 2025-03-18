<?php

namespace markhuot\voyage\base;

use stdClass;
use Throwable;

interface AuditorInterface
{
    public function hydrateFrame(Frame $frame): void;

    public function persistFrame(Frame $frame): void;
}
