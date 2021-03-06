<?php

namespace Squirrel\Strings\Tests;

use Squirrel\Debug\Debug;
use Squirrel\Debug\OriginException;
use Squirrel\Debug\Tests\ExceptionTestClasses\NoExceptionClass;
use Squirrel\Debug\Tests\ExceptionTestClasses\SomeClass;

class DebugTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateException()
    {
        $debugClassPath = \preg_replace('#/tests/DebugTest.php$#si', '/src/Debug.php', __FILE__);

        $someRepository = new SomeClass();

        try {
            $someRepository->someFunction();

            $this->assertFalse(true);
        } catch (OriginException $e) {
            $this->assertEquals('Something went wrong!', $e->getMessage());
            $this->assertEquals(__FILE__, $e->getOriginFile());
            $this->assertEquals(__LINE__ - 6, $e->getOriginLine());
            $this->assertEquals(__FILE__, $e->getFile());
            $this->assertEquals(__LINE__ - 8, $e->getLine());
            $this->assertEquals($debugClassPath, $e->getExceptionFile());
            $this->assertEquals(118, $e->getExceptionLine());
            $this->assertEquals('SomeClass->someFunction()', $e->getOriginCall());
        }
    }

    public function testInvalidExceptionClass()
    {
        $exception = Debug::createException(NoExceptionClass::class, [], 'Something went wrong!');

        $this->assertEquals(\Exception::class, \get_class($exception));
    }

    public function testBaseExceptionClass()
    {
        $exception = Debug::createException(OriginException::class, [], 'Something went wrong!');

        $this->assertEquals(OriginException::class, \get_class($exception));
    }

    public function testBaseExceptionClassStringBacktraceClasses()
    {
        $exception = Debug::createException(OriginException::class, '', 'Something went wrong!');

        $this->assertEquals(OriginException::class, \get_class($exception));
    }

    public function testBinaryData()
    {
        $sanitizedData = Debug::sanitizeData(\md5('dada', true));

        $this->assertEquals('0x' . \bin2hex(\md5('dada', true)), $sanitizedData);
    }

    public function testBoolDataTrue()
    {
        $sanitizedData = Debug::sanitizeData(true);

        $this->assertEquals('true', $sanitizedData);
    }

    public function testBoolDataFalse()
    {
        $sanitizedData = Debug::sanitizeData(false);

        $this->assertEquals('false', $sanitizedData);
    }

    public function testObjectData()
    {
        $sanitizedData = Debug::sanitizeData(new SomeClass());

        $this->assertEquals('object(' . SomeClass::class . ')', $sanitizedData);
    }

    public function testArrayData()
    {
        $sanitizedData = Debug::sanitizeData([
            'dada',
            'mumu' => 'haha',
            5444,
            [
                'ohno' => 'yes',
                'maybe',
            ],
        ]);

        $this->assertEquals("[0 => 'dada', 'mumu' => 'haha', 1 => 5444, 2 => ['ohno' => 'yes', 0 => 'maybe']]", $sanitizedData);
    }

    public function testResourceData()
    {
        $sanitizedData = Debug::sanitizeData(\fopen("php://memory", "r"));

        $this->assertEquals("resource(stream)", $sanitizedData);
    }

    public function testSanitizeArguments()
    {
        $sanitizedData = Debug::sanitizeArguments([
            'hello',
            [
                'my',
                'weird' => 'friend',
            ],
            56
        ]);

        $this->assertEquals("'hello', [0 => 'my', 'weird' => 'friend'], 56", $sanitizedData);
    }
}
