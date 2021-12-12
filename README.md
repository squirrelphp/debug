Squirrel Debug
==============

[![Build Status](https://img.shields.io/travis/com/squirrelphp/debug.svg)](https://travis-ci.com/squirrelphp/debug) [![Test Coverage](https://api.codeclimate.com/v1/badges/24a5dad790d20148e10a/test_coverage)](https://codeclimate.com/github/squirrelphp/debug/test_coverage) ![PHPStan](https://img.shields.io/badge/style-level%208-success.svg?style=flat-round&label=phpstan) [![Packagist Version](https://img.shields.io/packagist/v/squirrelphp/debug.svg?style=flat-round)](https://packagist.org/packages/squirrelphp/debug) [![PHP Version](https://img.shields.io/packagist/php-v/squirrelphp/debug.svg)](https://packagist.org/packages/squirrelphp/debug) [![Software License](https://img.shields.io/badge/license-MIT-success.svg?style=flat-round)](LICENSE)

Provides backtracing functionality to enable libraries to find the true origin of a problem (as opposed to where it becomes a problem) even if it is not know in what context the library/component is being used, and includes an exception base class for adding the origin data when throwing exceptions. Also provides an easy way of dumping debug data and function arguments in a shortened form. This is a helper library created for the other squirrel libraries, but it can be useful in any library or application.

Installation
------------

    composer require squirrelphp/debug

Finding the origin method call
------------------------------

When using libraries and abstractions it often is not relevant where a problem occurs (leading to an exception or warning), but where in the application the relevant call was made to the library/component that then causes the problem.

`Squirrel\Debug\Debug::findOrigin` goes through the debug backtrace and finds the relevant method call that lead to the current point in the application. You can (and should) provide classes/interfaces and namespaces it should ignore (to go back further and find the method call that preceded it). This method returns an Origin object which includes the file and line of the origin together with the method call (and its arguments).

Creating an OriginException
---------------------------

`Squirrel\Debug\Debug::createException` uses `Squirrel\Debug\Debug::findOrigin` to find the origin of the current problem and then creates an OriginException (which is an exception that includes the additional information about the origin). You should create your own exception classes which extend OriginException so you can then handle specific problems in your application/library.

Sanitize data and arguments
---------------------------

Dumping some form of debug data is a common requirement, but often is more troublesome than expected. When dumping data with objects in modern frameworks, the resulting dump can be so big that it exceeds the PHP memory limit and makes problems harder to spot rather than easier. Also, binary data in the dump can make it impossible to see anything useful.

`Squirrel\Debug\Debug::sanitizeData` and `Squirrel\Debug\Debug::sanitizeArguments` create small dumps without including object data: they only include the class name for objects, they show binary data as hex strings, and they show the resource type for resources. Arrays are shown in PHP array notation, so the output is easy to read for PHP developers. All other data types are shown fully (string, int, float, bool, null).

`Squirrel\Debug\Debug::sanitizeArguments` is used internally by `Squirrel\Debug\Debug::createException` to show the origin call that lead to an exception, which is why the functionality was added to this library.