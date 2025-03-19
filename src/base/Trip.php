<?php

namespace markhuot\voyage\base;

use markhuot\voyage\Voyage;

use function markhuot\voyage\helpers\throw_if;

class Trip
{
    /** @var array<int> */
    protected array $processIds = [];

    /** @var array<int, mixed> */
    protected array $results = [];

    /** @var array<int, Resource> */
    protected array $pipes = [];

    public function __construct(
        protected Voyage $voyage
    ) {
    }

    public function start(
        ?Collection $collection=null,
        array $matrix=[],
        ?array $sourceKeys=null,
    ): void {
        if ($collection === null) {
            $collection = $this->voyage->getCollections()[0];
        }

        // Check that the passed $matrix matches all the keys from the collection's matrix
        foreach ($collection->getMatrix() as $key => $values) {
            throw_if(! isset($matrix[$key]), "Missing matrix key: {$key}");
        }

        $this->voyage->getStream()?->debug('Starting processing collection '.$collection->getName());

        $frameManager = new FrameManager($this->voyage, $collection, $matrix);
        foreach ($collection->getSource()->walk($frameManager, $sourceKeys) as $source) {
            if ($source->matchesChecksum()) {
                $this->voyage->getStream()?->debug("Skipping {$source->sourceKey}, no changes since last import...");
                continue;
            }
            else {
                $this->voyage->getStream()?->debug("Processing {$source->sourceKey}...");
            }

            while (count($this->processIds) >= $this->voyage->getConcurrency()) {
                $this->reapChildren();
            }

            $this->fork(function () use ($collection, $source) {
                try {
                    $collection->getDestination()->reconnect();
                    $destination = $collection->getDestination()->prepare($source);
                    $collection->transform($source, $destination);
                    $collection->getDestination()->upsert($destination);

                    $this->voyage->getAuditor()?->persistFrame($destination);
                    
                    return $destination;
                }
                catch (\Throwable $e) {
                    if ($this->voyage->getDevMode()) {
                        throw $e;
                    }
                    
                    $this->voyage->getStream()?->error("Error processing {$source->sourceKey}: {$e->getMessage()}");

                    $source->lastError = new \DateTime();
                    $this->voyage->getAuditor()?->persistFrame($source);
                }
            });
        }

        while (! empty($this->processIds)) {
            $this->reapChildren();
        }
    }

    protected function fork(callable $callback): void
    {
        if ($this->voyage->getConcurrency() === 1 || ! function_exists('pcntl_fork')) {
            $callback();
            return;
        }

        $pipe = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        throw_if(! $pipe, 'Failed to create a socket pair');

        $pid = pcntl_fork();
        throw_if($pid == -1, 'Could not fork');

        if ($pid === 0) {
            // Child process
            fclose($pipe[0]); // Close parent end
            try {
                $result = $callback();
                fwrite($pipe[1], serialize($result)); // Send result to parent
                fclose($pipe[1]); // Close child end
                exit(0);
            }
            catch (\Throwable $e) {
                fwrite($pipe[1], serialize([
                    'class' => get_class($e),
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'trace' => $e->getTraceAsString(),
                ]));
                fclose($pipe[1]);
                posix_kill(posix_getpid(), SIGUSR1);
            }
        } else {
            // Parent process
            fclose($pipe[1]); // Close child end
            $this->processIds[$pid] = $pid;
            $this->pipes[$pid] = $pipe[0]; // Store parent end for reading
        }
    }

    protected function reapChildren(): void
    {
        foreach ($this->processIds as $pid) {
            $status = 0;
            $result = pcntl_waitpid($pid, $status, WNOHANG);
            if ($result > 0) {
                $this->collectResult($pid);
                unset($this->processIds[$pid]);
                if (! pcntl_wifexited($status)) {
                    $error = $this->results[$pid];
                    throw new \RuntimeException($error['class'].' '.$error['message'], $error['code']);
                }
            }
        }
    }

    protected function collectResult(int $pid): void
    {
        if (isset($this->pipes[$pid])) {
            $data = stream_get_contents($this->pipes[$pid]);
            fclose($this->pipes[$pid]);
            unset($this->pipes[$pid]);

            if ($data !== false) {
                $this->results[$pid] = unserialize($data);
            }
        }
    }
}
