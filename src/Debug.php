<?php

namespace Squirrel\Debug;

/**
 * Debug functionality: create exception, sanitize data
 */
class Debug
{
    /**
     * Create exception with correct backtrace while ignoring some classes/interfaces ($backtraceClasses)
     *
     * @param string $exceptionClass
     * @psalm-param class-string $exceptionClass
     * @param string|array $backtraceClasses
     * @psalm-param class-string|list<class-string> $backtraceClasses
     * @param string $message
     * @param \Throwable|null $previousException
     * @return \Throwable
     */
    public static function createException(
        string $exceptionClass,
        $backtraceClasses,
        string $message,
        ?\Throwable $previousException = null
    ): \Throwable {
        // Convert backtrace class to an array if it is a string
        if (\is_string($backtraceClasses)) {
            $backtraceClasses = [$backtraceClasses];
        }

        $assignedBacktraceClass = '';

        // Get backtrace to find out where the query error originated
        $backtraceList = \debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);

        // Where the DBInterface was called
        $lastInstance = null;

        // Go through backtrace and find the topmost caller
        foreach ($backtraceList as $backtrace) {
            // We are only going through classes - this is necessary because of
            // helper functions like array_map, which otherwise come up in the backtrace
            if (!isset($backtrace['class'])) {
                continue;
            }

            // Replace backtrace instance if we find a valid class insance
            foreach ($backtraceClasses as $backtraceClass) {
                $classImplements = \class_implements($backtrace['class']);
                $classParents = \class_parents($backtrace['class']);

                // @codeCoverageIgnoreStart
                if ($classImplements === false || $classParents === false) {
                    continue;
                }
                // @codeCoverageIgnoreEnd

                // Check if the class or interface we are looking for is implemented or used
                // by the current backtrace class
                if (
                    \in_array($backtraceClass, $classImplements, true) ||
                    \in_array($backtraceClass, $classParents, true) ||
                    $backtraceClass === $backtrace['class']
                ) {
                    $lastInstance = $backtrace;
                    $assignedBacktraceClass = $backtraceClass;
                }
            }

            // We reached the first non-DBInterface backtrace - we are at the top
            if ($lastInstance !== null) {
                if ($lastInstance !== $backtrace) {
                    break;
                }
            }
        }

        // Shorten the backtrace class to just the class name without namespace
        $parts = \explode('\\', $assignedBacktraceClass);
        $shownClass = \array_pop($parts);

        $classImplements = \class_implements($exceptionClass);
        $classParents = \class_parents($exceptionClass);

        // @codeCoverageIgnoreStart
        if ($classImplements === false) {
            $classImplements = [];
        }

        if ($classParents === false) {
            $classParents = [];
        }
        // @codeCoverageIgnoreEnd

        // Make sure the provided exception class inherits from Throwable, otherwise replace it with Exception
        if (!\in_array(\Throwable::class, $classImplements, true)) {
            $exceptionClass = \Exception::class;
        }

        // If we have no OriginException child class, we assume the default Exception class constructor is used
        if (!\in_array(OriginException::class, $classParents, true) && $exceptionClass !== OriginException::class) {
            /**
             * @var \Throwable $exception At this point we know that $exceptionClass inherits from \Throwable for sure
             */
            $exception = new $exceptionClass(
                \str_replace("\n", ' ', $message),
                ( isset($previousException) ? $previousException->getCode() : 0 ),
                $previousException
            );

            return $exception;
        }

        /**
         * @var OriginException $exception At this point we know that $exceptionClass inherits from OriginException for sure
         */
        $exception = new $exceptionClass(
            $shownClass . ( $lastInstance['type'] ?? '' ) . ( $lastInstance['function'] ?? '' ) .
            '(' . self::sanitizeArguments($lastInstance['args'] ?? []) . ')',
            $lastInstance['file'] ?? '',
            $lastInstance['line'] ?? 0,
            \str_replace("\n", ' ', $message),
            ( isset($previousException) ? $previousException->getCode() : 0 ),
            $previousException
        );

        return $exception;
    }

    /**
     * Sanitize function arguments for showing what caused an exception
     */
    public static function sanitizeArguments(array $args): string
    {
        $result = array();

        // Go through all arguments and prepare them for output
        foreach ($args as $key => $value) {
            $result[] = \is_int($key) ? self::sanitizeData($value) : "'" . $key . "' => " . self::sanitizeData($value);
        }

        return \implode(', ', $result);
    }

    /**
     * Convert debug data into a sanitized string which can be shown in a log or on screen
     *
     * @param mixed $data
     */
    public static function sanitizeData($data): string
    {
        // Convert object to class name
        if (\is_object($data)) {
            return 'object(' . (new \ReflectionClass($data))->getName() . ')';
        }

        // Convert resource to its type name
        if (\is_resource($data)) {
            return 'resource(' . \get_resource_type($data) . ')';
        }

        // Convert boolean to integer
        if (\is_bool($data)) {
            return \strtolower(\var_export($data, true));
        }

        // All other non-array values are fine
        if (!\is_array($data)) {
            // If parts of the string are not UTF8 we assume it to be a binary string
            if (!\mb_check_encoding($data, 'UTF-8')) {
                return '0x' . \bin2hex($data);
            }

            return \str_replace("\n", '', \var_export($data, true));
        }

        $result = [];

        // Go through all values in the array and process them recursively
        foreach ($data as $key => $value) {
            $formattedValue = self::sanitizeData($value);
            $result[] = \is_int($key) ? $key . " => " . $formattedValue : "'" . $key . "' => " . $formattedValue;
        }

        return '[' . \implode(', ', $result) . ']';
    }
}
