<?php

namespace Squirrel\Debug;

class OriginException extends \Exception
{
    /** @var string File in which the exception was thrown */
    private string $exceptionFile = '';

    /** @var int Line on which the exception was thrown */
    private int $exceptionLine = 0;

    public function __construct(
        /** @var string Original call which lead to the exception */
        private string $originCall,
        /** @var string File in which the problem originated */
        private string $originFile,
        /** @var int Line on which the problem originated */
        private int $originLine,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);

        $this->exceptionFile = $this->getFile();
        $this->exceptionLine = $this->getLine();

        // Replace the exception file and line to point to the more accurate origin
        $this->file = $originFile;
        $this->line = $originLine;
    }

    public function getOriginCall(): string
    {
        return $this->originCall;
    }

    public function getOriginFile(): string
    {
        return $this->originFile;
    }

    public function getOriginLine(): int
    {
        return $this->originLine;
    }

    public function getExceptionFile(): string
    {
        return $this->exceptionFile;
    }

    public function getExceptionLine(): int
    {
        return $this->exceptionLine;
    }
}
