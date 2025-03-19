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

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function getMatrix(): array
    {
        return $this->matrix;
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
