<?php

namespace markhuot\voyage;

use markhuot\voyage\base\AuditorInterface;
use markhuot\voyage\base\Collection;
use markhuot\voyage\base\Frame;
use markhuot\voyage\base\TransformerInterface;
use markhuot\voyage\base\Trip;
use markhuot\voyage\output\PhpStreamWrapper;
use markhuot\voyage\output\StreamInterface;
use markhuot\voyage\phases\DefaultPhase;
use function markhuot\voyage\helpers\throw_if;
use function markhuot\voyage\helpers\throw_unless;

class Voyage
{
    public function __construct(
        public array $collections=[],
        protected ?AuditorInterface $auditor=null,
        protected ?StreamInterface $stream=null,
        protected bool $devMode = false,
        protected int $concurrency = 10,
    ) {
        $this->stream ??= new PhpStreamWrapper;
    }

    public function auditor(AuditorInterface $auditor): Voyage
    {
        $this->auditor = $auditor;

        return $this;
    }

    public function getAuditor(): ?AuditorInterface
    {
        return $this->auditor;
    }

    public function devMode(bool $devMode): self
    {
        $this->devMode = $devMode;

        return $this;
    }

    public function getDevMode(): bool
    {
        return $this->devMode;
    }

    public function stream(StreamInterface $stream): self
    {
        $this->stream = $stream;

        return $this;
    }

    public function getStream(): ?StreamInterface
    {
        return $this->stream;
    }

    public function concurrency(int $concurrency): self
    {
        $this->concurrency = $concurrency;

        return $this;
    }

    public function getConcurrency(): int
    {
        return $this->concurrency;
    }

    public function setCollections(array $collections): self
    {
        $this->collections = $collections;

        return $this;
    }

    public function addCollection(Collection $collection): self
    {
        $this->collections[] = $collection;

        return $this;
    }

    public function getCollections(): array
    {
        return $this->collections;
    }

    public function start(?Collection $collection=null, ?array $matrix=null, ...$args): self {
        $collections = $collection ? [$collection] : $this->collections;

        foreach ($collections as $collection) {
            foreach ($matrix ? [$matrix] : $collection->getMatrixCombinations() as $combo) {
                (new Trip($this))->start(...[$collection, $combo, ...$args]);
            }
        }

        return $this;
    }
}
