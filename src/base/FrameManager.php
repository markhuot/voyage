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
            schemaVersion: $this->collection->getSchemaVersion()
        );

        if (! $this->voyage->getAuditor()?->hydrateFrame($frame)) {
            $parent = $this->voyage->getAuditor()?->fetchFrameData([
                'collection' => $frame->collection,
                'matrix' => http_build_query($this->getPrimaryFrame()),
                'sourceKey' => $frame->sourceKey,
            ]);

            $frame->destinationKey = $parent[0]['destinationKey'] ?? null;
        }

        return $frame;
    }

    public function getPrimaryFrame()
    {
        return array_map(function ($passes) {
            return array_keys($passes)[0];
        }, $this->collection->getMatrix());
    }
}
