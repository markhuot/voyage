<?php

namespace markhuot\voyage\base;

class Collection
{
    public function __construct(
        protected ?string $name,
        protected SourceConnectionInterface $source,
        protected DestinationConnectionInterface $destination,
        protected ?string $handle = null,
        protected array $transformers = [],
    ) {
        $this->handle = $handle ?? preg_replace('/[^a-z0-9]/', '-', $name);
    }

    public function getSource(): SourceConnectionInterface
    {
        return $this->source;
    }

    public function getDestination(): DestinationConnectionInterface
    {
        return $this->destination;
    }

    public function transform(Frame $source, Frame $destination, array $matrix): void
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->shouldTransform($source, $matrix)) {
                $transformer->transform($source, $destination);
            }
        }
    }
}
