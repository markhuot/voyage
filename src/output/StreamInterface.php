<?php

namespace markhuot\voyage\output;

interface StreamInterface
{
    public function info(string $message, string $verbosity='v'): void;

    public function debug(string $message, string $verbosity='vvv'): void;

    public function error(string $message, string $verbosity='v'): void;

    public function close(): void;
}
