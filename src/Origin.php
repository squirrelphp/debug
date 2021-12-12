<?php

namespace Squirrel\Debug;

/**
 * @immutable
 */
final class Origin
{
    public function __construct(
        private string $call,
        private string $file,
        private int $line,
    ) {
    }

    public function getCall(): string
    {
        return $this->call;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getLine(): int
    {
        return $this->line;
    }
}
