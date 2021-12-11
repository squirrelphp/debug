<?php

namespace Squirrel\Debug;

/**
 * Debug functionality: create exception, sanitize data
 */
class Debug
{
    /**
     * Create exception with correct backtrace while ignoring some classes/interfaces ($ignoreClasses)
     *
     * @param class-string $exceptionClass
     * @param class-string|list<class-string> $ignoreClasses Classes and interfaces to ignore in backtrace
     * @param string|string[] $ignoreNamespaces Namespaces to ignore
     */
    public static function createException(
        string $exceptionClass,
        string $message,
        string|array $ignoreClasses = [],
        string|array $ignoreNamespaces = [],
        ?\Throwable $previousException = null,
    ): \Throwable {
        if (\is_string($ignoreClasses)) {
            $ignoreClasses = [$ignoreClasses];
        }

        if (\is_string($ignoreNamespaces)) {
            $ignoreNamespaces = [$ignoreNamespaces];
        }

        $removeEmptyStrings = function (string $s): bool {
            if (\strlen($s) === 0) {
                return false;
            }

            return true;
        };

        $ignoreClasses = \array_filter($ignoreClasses, $removeEmptyStrings);
        $ignoreNamespaces = \array_filter($ignoreNamespaces, $removeEmptyStrings);

        // Get backtrace to find out where the query error originated
        $backtraceList = \debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);

        // Where the relevant method call was made
        $lastInstance = null;

        // Go through backtrace and find the topmost caller
        foreach ($backtraceList as $backtrace) {
            // We are only going through classes - this is necessary because of
            // helper functions like array_map, which otherwise come up in the backtrace
            if (!isset($backtrace['class'])) {
                continue;
            }

            $lastInstance ??= $backtrace;

            $classImplements = \class_implements($backtrace['class']);
            $classParents = \class_parents($backtrace['class']);

            // @codeCoverageIgnoreStart
            if ($classImplements === false) {
                $classImplements = [];
            }

            if ($classParents === false) {
                $classParents = [];
            }
            // @codeCoverageIgnoreEnd

            foreach ($ignoreClasses as $ignoreClass) {
                // Check if the class or interface we are looking for is implemented or used
                // by the current backtrace class
                if (
                    \in_array($ignoreClass, $classImplements, true) ||
                    \in_array($ignoreClass, $classParents, true) ||
                    $ignoreClass === $backtrace['class']
                ) {
                    $lastInstance = $backtrace;

                    continue 2;
                }
            }

            foreach ($ignoreNamespaces as $ignoreNamespace) {
                // Check if the backtrace class starts with any ignored namespaces
                if (
                    \str_starts_with($backtrace['class'], $ignoreNamespace)
                ) {
                    $lastInstance = $backtrace;

                    continue 2;
                }
            }

            // We reached the first non-ignored backtrace - we are at the top
            if (
                $lastInstance !== $backtrace
            ) {
                break;
            }
        }

        // Shorten the backtrace class to just the class name without namespace
        $parts = \explode('\\', $lastInstance['class'] ?? '');
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
                $previousException,
            );
        } else {
            /**
             * @var OriginException $exception At this point we know that $exceptionClass inherits from OriginException for sure
             */
            $exception = new $exceptionClass(
                $shownClass . ($lastInstance['type'] ?? '') . ($lastInstance['function'] ?? '') .
                '(' . self::sanitizeArguments($lastInstance['args'] ?? []) . ')',
                $lastInstance['file'] ?? '',
                $lastInstance['line'] ?? 0,
                \str_replace("\n", ' ', $message),
                (isset($previousException) ? $previousException->getCode() : 0),
                $previousException,
            );
        }

        return $exception;
    }

    /**
     * Sanitize function arguments for showing what caused an exception
     */
    public static function sanitizeArguments(array $args): string
    {
        $result = [];

        // Go through all arguments and prepare them for output
        foreach ($args as $key => $value) {
            $result[] = \is_int($key) ? self::sanitizeData($value) : "'" . $key . "' => " . self::sanitizeData($value);
        }

        return \implode(', ', $result);
    }

    /**
     * Convert debug data into a sanitized string which can be shown in a log or on screen
     */
    public static function sanitizeData(mixed $data): string
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
            if (\is_string($data) && !\mb_check_encoding($data, 'UTF-8')) {
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
