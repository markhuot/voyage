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

    /**
     * Normalize our collection to a standard format regardless of input. This will take the
     * shorthand format of,
     * 
     * ```php
     * [
     *     'phase' => ['default', 'relations' => 'depends_on:phase=default'],
     *     'locale' => ['en', 'de'],
     * ]
     * ```
     * 
     * And convert it to,
     * 
     * ```php
     * [
     *     'phase' => ['default' => null, 'relations' => ['depends_on' => ['phase' => 'default']]],
     *     'country' => ['en' => null, 'de' => null],
     * ]
     * ```
     */
    public function getMatrix(): array
    {
        $normalized = [];

        foreach ($this->matrix as $key => $values) {
            if (!is_array($values)) {
                $values = [$values];
            }

            foreach ($values as $subKey => $value) {
                if (is_string($subKey)) {
                    $normalized[$key][$subKey] = $this->parseDependencyString($value);
                } else {
                    $normalized[$key][$value] = null;
                }
            }
        }
        return $normalized;
    }

    protected function parseDependencyString(string $dependency): array
    {
        [$key, $dependency] = explode(':', $dependency);
        [$dependencyKey, $dependencyValue] = explode('=', $dependency);

        return [$key => [$dependencyKey => $dependencyValue]];
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
