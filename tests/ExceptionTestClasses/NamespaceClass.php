<?php

namespace Squirrel\Debug\Tests\ExceptionTestClasses;

use Squirrel\Debug\Debug;
use Squirrel\Debug\OriginException;

class NamespaceClass
{
    public function someFunction()
    {
        throw Debug::createException(
            OriginException::class,
            'Something went wrong!',
            ignoreNamespaces: 'Squirrel\\Debug\\Tests\\Exception',
        );
    }
}
