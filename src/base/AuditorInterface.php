<?php

namespace markhuot\voyage\base;

use stdClass;
use Throwable;

interface AuditorInterface
{
    /**
     * @param array<string, mixed> $condition 
     * @return array<string, mixed>|null
     */
    public function fetchFrameData(array $condition): ?array;

    /**
     * @param array<string, mixed> $condition
     */
    public function fetchFrameCount(array $condition): int;

    /**
     * @param Frame<mixed> $frame 
     */
    public function hydrateFrame(Frame $frame): bool;
    
    /**
     * @param Frame<mixed> $frame 
     */
    public function persistFrame(Frame $frame): void;
}
