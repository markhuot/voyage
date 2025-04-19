<?php

namespace markhuot\voyage\transformers;

use Illuminate\Support\Collection;
use markhuot\voyage\base\Frame;
use markhuot\voyage\base\Transformer;

class CopyTransformer extends Transformer
{
    public function __construct(
        protected ?array $mappings=null
    ) {
    }

    public function getMappings(): Collection
    {
        return collect($this->mappings);
    }

    /**
     * @param Frame<mixed> $source
     * @param Frame<mixed> $destination
     * @return void
     */
    public function transform(Frame $source, Frame $destination, array $matrix): void
    {
        if ($this->mappings === null) {
            $destination->data = $source->data;
            return;
        }

        foreach ($this->mappings as $mapping) {
            // check if the mapping has a matrix defined. If it does check that the mapping's matrix
            // matches the current matrix
            foreach ($mapping['matrix'] ?? [] as $key => $value) {
                if (! isset($matrix[$key]) || $matrix[$key] !== $value) {
                    continue 2;
                }
            }

            $data = data_get($source->data, $mapping['from']);
            foreach ($mapping['fieldTransformers'] ?? []  as $transformer) {
                $data = (new $transformer)($data);
            }
            data_set($destination->data, $mapping['to'], $data);
        }
    }
}
