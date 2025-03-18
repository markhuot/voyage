<?php

namespace markhuot\voyage\base;

use DateTime;
use RuntimeException;
use Throwable;

/**
 * @template T
 */
class Frame
{
    /**
     * @param T $data
     */
    public function __construct(
        public mixed $data=null,
        public string $collection='default',
        public string $matrix='',
        public string|int|null $sourceKey=null,
        public string|int|null $destinationKey=null,
        public string|null $checksum=null,
        public Throwable|null $exception=null,
        public DateTime|null $lastError=null,
        public DateTime|null $lastImport=null,
    ) {
    }

    /**
     * @return Frame<T>
     */
    public function setData(mixed $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function matchesChecksum(): bool
    {
        if (empty($this->checksum)) {
            return false;
        }

        return $this->checksum === $this->getDerivedChecksum();
    }

    public function getDerivedChecksum(): string
    {
        $json = json_encode($this->data);
        if (! $json) {
            throw new RuntimeException('Could not create json from frame data. Frame ' . $this->collection . ' ' . $this->sourceKey);
        }

        return md5($json);
    }
}
