<?php

namespace markhuot\voyage\transformers;

use markhuot\voyage\base\Frame;
use markhuot\voyage\base\Transformer;

class CopyTransformer extends Transformer
{
    public function __construct(
        protected ?array $mappings=null
    ) {
    }

    /**
     * @param Frame<mixed> $source
     * @param Frame<mixed> $destination
     * @return void
     */
    public function transform(Frame $source, Frame $destination): void
    {
        if ($this->mappings === null) {
            $destination->data = $source->data;
            return;
        }

        foreach ($this->mappings as $mapping) {
            $data = data_get($source->data, $mapping['from']);
            foreach ($mapping['fieldTransformers'] ?? []  as $transformer) {
                $data = (new $transformer)($data);
            }
            data_set($destination->data, $mapping['to'], $data);
        }
    }
}
