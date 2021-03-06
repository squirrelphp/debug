<?php

namespace Squirrel\Debug;

class OriginException extends \Exception
{
    /**
     * @var string Original call which lead to the exception
     */
    private $originCall = '';

    /**
     * @var string File in which the problem originated
     */
    private $originFile = '';

    /**
     * @var int Line on which the problem originated
     */
    private $originLine = 0;

    /**
     * @var string File in which the exception was thrown
     */
    private $exceptionFile = '';

    /**
     * @var int Line on which the exception was thrown
     */
    private $exceptionLine = 0;

    /**
     * @param string $originCall Original call which lead to the exception
     * @param string $originFile File in which the problem originated
     * @param int $originLine Line on which the problem originated
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $originCall,
        string $originFile,
        int $originLine,
        $message = "",
        $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->exceptionFile = $this->getFile();
        $this->exceptionLine = $this->getLine();

        $this->originCall = $originCall;
        $this->originFile = $originFile;
        $this->originLine = $originLine;

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
