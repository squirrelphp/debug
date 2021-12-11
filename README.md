Squirrel Debug
==============

[![Build Status](https://img.shields.io/travis/com/squirrelphp/debug.svg)](https://travis-ci.com/squirrelphp/debug) [![Test Coverage](https://api.codeclimate.com/v1/badges/24a5dad790d20148e10a/test_coverage)](https://codeclimate.com/github/squirrelphp/debug/test_coverage) ![PHPStan](https://img.shields.io/badge/style-level%208-success.svg?style=flat-round&label=phpstan) [![Packagist Version](https://img.shields.io/packagist/v/squirrelphp/debug.svg?style=flat-round)](https://packagist.org/packages/squirrelphp/debug) [![PHP Version](https://img.shields.io/packagist/php-v/squirrelphp/debug.svg)](https://packagist.org/packages/squirrelphp/debug) [![Software License](https://img.shields.io/badge/license-MIT-success.svg?style=flat-round)](LICENSE)

Provides an exception base class (OriginException) and backtracing logic to enable libraries to generate exceptions about the true origin of an exception (as opposed to where it was thrown) even if it is not know in what context the library is being used. Also includes an easy way of dumping debug data and function arguments in a shortened form. This is a helper library created for the other squirrel libraries, but it can be useful in any library or application.

Installation
------------

    composer require squirrelphp/debug

OriginException
---------------

When using libraries and abstractions it often is not relevant where an exception was thrown, but where in the application the relevant call was made to the library/component that then causes the exception. This library enables libraries to go through the backtrace, capture the place where the library was called, and to point out the specific function call that caused a problem by throwing an OriginException with the origin call and location.

`Squirrel\Debug\Debug::createException` provides this functionality by specifying what exception should be created and what classes/interfaces/namespaces in the backtrace should be ignored and considered as part of the library (and therefore not the true cause of the exception).

Sanitize data and arguments
---------------------------

Dumping some form of debug data is a common requirement, but often is more troublesome than expected. When dumping data with objects in modern frameworks, the resulting dump can be so big that it exceeds the PHP memory limit and makes problems harder to spot rather than easier. Also, binary data in the dump can make it impossible to see anything useful.

`Squirrel\Debug\Debug::sanitizeData` and `Squirrel\Debug\Debug::sanitizeArguments` create small dumps without including object data: they only include the class name for objects, they show binary data as hex strings, and they show the resource type for resources. Arrays are shown in PHP array notation, so the output is easy to read for PHP developers. All other data types are shown fully (string, int, float, bool, null).

`Squirrel\Debug\Debug::sanitizeArguments` is used internally by `Squirrel\Debug\Debug::createException` to show the origin call that lead to an exception, which is why the functionality was added to this library.