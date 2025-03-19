<?php

namespace markhuot\voyage\base;

class Collection
{
    public function __construct(
        protected ?string $name,
        protected SourceConnectionInterface $source,
        protected DestinationConnectionInterface $destination,
        protected array $matrix = [],
        protected ?string $handle = null,
        protected array $transformers = [],
    ) {
        $this->handle = $handle ?? strtolower(preg_replace('/[^a-z0-9]/i', '-', $name));
    }
    
    public function getName(): ?string
    {
        return $this->name;
    }
    
    public function setName(string $name): self
    {
        $this->name = $name;
        $this->handle = strtolower(preg_replace('/[^a-z0-9]/i', '-', $name));

        return $this;
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

    public function getMatrixCombinations(): array
    {
        $combinations = [[]];
        foreach ($this->matrix as $key => $values) {
            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach ($values as $valueKey => $value) {
                    $newCombinations[] = array_merge($combination, [$key => is_numeric($valueKey) ? $value : $valueKey]);
                }
            }
            $combinations = $newCombinations;
        }

        return $combinations;
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
