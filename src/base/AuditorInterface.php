<?php

namespace markhuot\voyage\base;

use stdClass;
use Throwable;

interface AuditorInterface
{
    public function fetchFrameData(array $condition): ?array;

    public function hydrateFrame(Frame $frame): bool;

    public function persistFrame(Frame $frame): void;
}
