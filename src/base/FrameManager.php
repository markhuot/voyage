<?php

namespace markhuot\voyage\base;

use markhuot\voyage\Voyage;

class FrameManager {
    public function __construct(
        protected Voyage $voyage,
        protected Collection $collection,
        protected array $matrix
    ) {
    }

    public function firstOrCreate($sourceKey): Frame 
    {
        $frame = new Frame(
            collection: $this->collection->getHandle(),
            matrix: http_build_query($this->matrix),
            sourceKey: $sourceKey,
        );

        $this->voyage->getAuditor()?->hydrateFrame($frame);

        return $frame;
    }
}