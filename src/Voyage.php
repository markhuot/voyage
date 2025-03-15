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
        protected int $processes = 10,
    ) {
        $this->stream ??= new PhpStreamWrapper;
    }

    public function auditor(AuditorInterface $auditor): Voyage
    {
        $this->auditor = $auditor;

        return $this;
    }

    public function devMode(bool $devMode): self
    {
        $this->devMode = $devMode;

        return $this;
    }

    public function stream(StreamInterface $stream): self
    {
        $this->stream = $stream;

        return $this;
    }

    public function addCollection(Collection $collection): self
    {
        $this->collections[] = $collection;

        return $this;
    }

    public function start(...$args): void {
        (new Trip(
            concurrency: $this->processes,
            stream: $this->stream,
        ))->start(...$args);
    }
}
