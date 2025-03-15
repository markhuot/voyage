<?php

namespace markhuot\voyage;

use markhuot\voyage\base\AuditorInterface;
use markhuot\voyage\base\DestinationConnectionInterface;
use markhuot\voyage\base\SourceConnectionInterface;
use markhuot\voyage\base\Frame;
use markhuot\voyage\base\TransformerInterface;
use markhuot\voyage\output\PhpStreamWrapper;
use markhuot\voyage\output\StreamInterface;
use markhuot\voyage\phases\DefaultPhase;
use Throwable;
use function markhuot\voyage\helpers\throw_unless;

class Voyage
{
    /** @var array<TransformerInterface> */
    public array $transformers = [];

    public function __construct(
        protected ?SourceConnectionInterface $source=null,
        protected ?DestinationConnectionInterface $destination=null,
        protected ?AuditorInterface $auditor=null,
        protected bool $devMode = false,
        protected ?StreamInterface $stream=null,
    ) {
        $this->stream ??= new PhpStreamWrapper;
    }

    public function from(SourceConnectionInterface $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function to(DestinationConnectionInterface $destination): self
    {
        $this->destination = $destination;

        return $this;
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

    public function addTransformer(TransformerInterface $transformer): self
    {
        $this->transformers[] = $transformer;

        return $this;
    }

    public function getAuditor(): ?AuditorInterface
    {
        return $this->auditor;
    }

    public function start(
        bool $auditOnly=false,
        string $phase=DefaultPhase::class,
    ): void {
        if (! $this->source) {
            throw new \RuntimeException('A source must be set before `->start()`ing a voyage.');
        }
        if (! $this->destination) {
            throw new \RuntimeException('A destination must be set before `->start()`ing a voyage.');
        }

        $this->destination->on('upsert', function (array $frames) {
            throw_unless($this->destination, 'The destination must be set.');

            /** @var array<Frame<mixed>> $frames */
            $this->auditor?->trackFrames($frames);
            $this->stream?->info('Sent ' . count($frames) . ' frames to ' . get_class($this->destination));
        });
        $this->destination->on('error', function (array $frames, Throwable $exception) {
            throw_unless($this->destination, 'The destination must be set.');

            /** @var array<Frame<mixed>> $frames */
            $this->auditor?->trackErrorForFrames($frames, $exception);
            $this->stream?->error('Error sending frames to ' . get_class($this->destination) . ': ' . get_class($exception) . ': ' . $exception->getMessage());
            $this->stream?->error(json_encode($frames, JSON_THROW_ON_ERROR));
        });

        foreach ($this->source->walk() as $batch) {
            foreach ($batch as $frame) {
                $frame->phase = $phase;
            }

            $this->auditor?->fetchFrames($batch);

            if ($auditOnly) {
                continue;
            }

            $this->stream?->info('~ Transforming ' . count($batch) . ' frames');
            foreach ($batch as $sourceFrame) {
                $destinationFrame = $this->destination->prepareFrame($sourceFrame);

                if ($this->transform($sourceFrame, $destinationFrame, $phase)) {
                    if (! empty($destinationFrame->destinationKey) && empty($destinationFrame->lastError) && $destinationFrame->matchesChecksum()) {
                        $this->stream?->info('  ⇢ Skipping frame [' . $sourceFrame->collection . ':' . $sourceFrame->sourceKey . '] with no changes', 'vvv');
                    }
                    else {
                        $this->stream?->info('  ↑ Upserting frame [' . $sourceFrame->collection . ':' . $sourceFrame->sourceKey . ']', 'vvv');
                        $this->destination->upsertFrame($destinationFrame);
                    }
                }
            }

            $this->stream?->info('  Completed');
        }

        $this->destination->close();

        $this->stream?->info('! Done');
    }

    /**
     * @param Frame<mixed> $source
     * @param Frame<mixed> $destination
     */
    public function transform(Frame $source, Frame $destination, string $phase): bool
    {
        try {
            foreach ($this->transformers as $transformer) {
                $reflect = new \ReflectionClass($transformer);
                if (! $reflect->implementsInterface($phase)) {
                    continue;
                }

                if ($transformer->canTransform($source)) {
                    $transformer->transform($source, $destination);
                }
            }

            return true;
        }
        catch (Throwable $throwable) {
            $this->auditor?->trackErrorForFrames([$destination], $throwable);

            if ($this->devMode) {
                throw $throwable;
            }

            return false;
        }
    }
}
