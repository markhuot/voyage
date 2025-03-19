<?php

namespace markhuot\voyage\base;

use markhuot\voyage\actions\ParseOrderedMatrixCombinations;

use function markhuot\voyage\helpers\throw_if;

class Collection
{
    /**
     * @param array<string, mixed> $matrix 
     * @param array<TransformerInterface> $transformers 
     */
    public function __construct(
        protected ?string $name,
        protected SourceConnectionInterface $source,
        protected DestinationConnectionInterface $destination,
        protected array $matrix = ['phase' => ['default']],
        protected ?string $handle = null,
        protected array $transformers = [],
    ) {
        $this->handle = $handle ?? $this->deriveHandle();
    }
    
    public function getName(): ?string
    {
        return $this->name;
    }
    
    public function setName(string $name): self
    {
        $this->name = $name;
        $this->handle = $this->deriveHandle();

        return $this;
    }

    protected function deriveHandle(): ?string
    {
        if (empty($this->name)) {
            return null;
        }

        $handle = preg_replace('/[^a-z0-9]/i', '-', $this->name);
        throw_if(! $handle, 'Invalid handle derived from name: ' . $this->name);

        return strtolower($handle);
    }

    public function getSource(): SourceConnectionInterface
    {
        return $this->source;
    }

    public function setDestination(DestinationConnectionInterface $destination): self
    {
        $this->destination = $destination;

        return $this;
    }

    public function getDestination(): DestinationConnectionInterface
    {
        return $this->destination;
    }

    public function setHandle(string $handle): self
    {
        $this->handle = $handle;

        return $this;
    }
    
    public function getHandle(): string
    {
        return $this->handle;
    }

    public function setMatrix(array $matrix): self
    {
        $this->matrix = $matrix;

        return $this;
    }

    public function getMatrix(): array
    {
        return $this->matrix;
    }

    /**
     * Takes the matrix array and returns an ordered list of combinations to run.
     * 
     * For example, given the matrix: [
     *   'phase' => ['default', 'relations' => 'depends_on:phase=default'],
     *   'locale' => ['en', 'de']
     * ]
     * 
     * We would expect to process the following combinations (in order):
     *   - phase=default&locale=en
     *   - phase=default&locale=de
     *   - phase=relations&locale=en
     *   - phase=relations&locale=de
     */
    public function getMatrixCombinations(): array
    {
        return (new ParseOrderedMatrixCombinations())($this->matrix);

        // $combinations = [[]];
        // foreach ($this->matrix as $key => $values) {
        //     $newCombinations = [];
        //     foreach ($combinations as $combination) {
        //         foreach ($values as $valueKey => $value) {
        //             $newCombinations[] = array_merge($combination, [$key => is_numeric($valueKey) ? $value : $valueKey]);
        //         }
        //     }
        //     $combinations = $newCombinations;
        // }

        // return $combinations;
    }

    public function setTransformers(array $transformers): self
    {
        $this->transformers = $transformers;

        return $this;
    }

    public function transform(Frame $source, Frame $destination): void
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->shouldTransform($source)) {
                $transformer->transform($source, $destination);
            }
        }
    }
}
