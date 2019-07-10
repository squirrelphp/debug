<?php

namespace Squirrel\Debug\Tests\ExceptionTestClasses;

use Squirrel\Debug\Debug;
use Squirrel\Debug\OriginException;

class SomeClass
{
    public function someFunction()
    {
        return \array_map(function () {
            throw Debug::createException(OriginException::class, [SomeClass::class], 'Something went wrong!', null);
        }, ['dada','mumu']);
    }
}
