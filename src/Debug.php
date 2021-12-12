<?php

namespace Squirrel\Debug;

/**
 * Debug functionality: create exceptions, sanitize data and function arguments
 */
final class Debug
{
    /**
     * Create exception with correct backtrace while ignoring some classes/interfaces ($ignoreClasses)
     *
     * @param class-string $exceptionClass
     * @param class-string|list<class-string> $ignoreClasses Classes and interfaces to ignore in backtrace
     * @param string|list<string> $ignoreNamespaces Namespaces to ignore
     */
    public static function createException(
        string $exceptionClass,
        string $message,
        string|array $ignoreClasses = [],
        string|array $ignoreNamespaces = [],
        ?\Throwable $previousException = null,
    ): \Throwable {
        // Make sure the provided exception class inherits from Throwable or replace it with Exception
        if (!\in_array(\Throwable::class, self::getClassInterfaces($exceptionClass), true)) {
            $exceptionClass = \Exception::class;
        }

        // If we have no OriginException child class, we assume the default Exception class constructor is used
        if (
            !\in_array(OriginException::class, self::getClassParents($exceptionClass), true)
            && $exceptionClass !== OriginException::class
        ) {
            /**
             * @var \Throwable $exception At this point we know that $exceptionClass inherits from \Throwable for sure
             */
            $exception = new $exceptionClass(
                \str_replace("\n", ' ', $message),
                ( isset($previousException) ? $previousException->getCode() : 0 ),
                $previousException,
            );
        } else {
            $ignoreClassesArray = self::convertToArray($ignoreClasses);
            // Ignore this class as we are doing another internal call to findOrigin below
            $ignoreClassesArray[] = self::class;

            $origin = self::findOrigin(
                ignoreClasses: $ignoreClassesArray,
                ignoreNamespaces: $ignoreNamespaces,
            );

            /**
             * @var OriginException $exception At this point we know that $exceptionClass inherits from OriginException for sure
             */
            $exception = new $exceptionClass(
                originCall: $origin->getCall(),
                originFile: $origin->getFile(),
                originLine: $origin->getLine(),
                message: \str_replace("\n", ' ', $message),
                code: (isset($previousException) ? $previousException->getCode() : 0),
                previous: $previousException,
            );
        }

        return $exception;
    }

    /**
     * Find origin of current position in code by backtracing and ignoring some classes/namespaces
     *
     * @param class-string|list<class-string> $ignoreClasses Classes and interfaces to ignore in backtrace
     * @param string|list<string> $ignoreNamespaces Namespaces to ignore
     */
    public static function findOrigin(
        string|array $ignoreClasses = [],
        string|array $ignoreNamespaces = [],
    ): Origin {
        $ignoreClassesArray = self::convertToArray($ignoreClasses);
        $ignoreNamespacesArray = self::convertToArray($ignoreNamespaces);

        $ignoreClassesArray = \array_filter($ignoreClassesArray, [Debug::class, 'isNotEmptyString']);
        $ignoreNamespacesArray = \array_filter($ignoreNamespacesArray, [Debug::class, 'isNotEmptyString']);

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

            if (self::isIgnoredClass($backtrace['class'], $ignoreClassesArray)) {
                $lastInstance = $backtrace;
                continue;
            }

            if (self::isIgnoredNamespace($backtrace['class'], $ignoreNamespacesArray)) {
                $lastInstance = $backtrace;
                continue;
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

        return new Origin(
            call: $shownClass . ($lastInstance['type'] ?? '') . ($lastInstance['function'] ?? '') . '(' . self::sanitizeArguments($lastInstance['args'] ?? []) . ')',
            file: $lastInstance['file'] ?? '',
            line: $lastInstance['line'] ?? 0,
        );
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

    /**
     * @template T of string|class-string
     * @psalm-param T|list<T> $list
     * @psalm-return list<T>
     */
    private static function convertToArray(string|array $list): array
    {
        if (\is_string($list)) {
            $list = [$list];
        }

        return $list;
    }

    private static function isNotEmptyString(string $s): bool
    {
        if (\strlen($s) === 0) {
            return false;
        }

        return true;
    }

    /**
     * @param class-string $class
     * @return array<string, class-string>
     */
    private static function getClassParents(string $class): array
    {
        $classParents = \class_parents($class);

        // @codeCoverageIgnoreStart
        if ($classParents === false) {
            $classParents = [];
        }
        // @codeCoverageIgnoreEnd

        return $classParents;
    }

    /**
     * @param class-string $class
     * @return array<string, string>
     */
    private static function getClassInterfaces(string $class): array
    {
        $classImplements = \class_implements($class);

        // @codeCoverageIgnoreStart
        if ($classImplements === false) {
            $classImplements = [];
        }
        // @codeCoverageIgnoreEnd

        return $classImplements;
    }

    /**
     * @param class-string $class
     * @return array<string, string>
     */
    private static function getClasses(string $class): array
    {
        return \array_merge(self::getClassInterfaces($class), self::getClassParents($class));
    }

    /**
     * @param class-string $backtraceClass
     * @param class-string[] $ignoreClasses
     */
    private static function isIgnoredClass(
        string $backtraceClass,
        array $ignoreClasses,
    ): bool {
        $possibleClasses = self::getClasses($backtraceClass);

        foreach ($ignoreClasses as $ignoreClass) {
            if (
                \in_array($ignoreClass, $possibleClasses, true) ||
                $ignoreClass === $backtraceClass
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param class-string $backtraceClass
     * @param string[] $ignoreNamespaces
     */
    private static function isIgnoredNamespace(
        string $backtraceClass,
        array $ignoreNamespaces,
    ): bool {
        foreach ($ignoreNamespaces as $ignoreNamespace) {
            if (
                \str_starts_with($backtraceClass, $ignoreNamespace)
            ) {
                return true;
            }
        }

        return false;
    }
}
