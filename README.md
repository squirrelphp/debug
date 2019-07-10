Squirrel Debug
==============

[![Build Status](https://img.shields.io/travis/com/squirrelphp/debug.svg)](https://travis-ci.com/squirrelphp/debug) [![Test Coverage](https://api.codeclimate.com/v1/badges/24a5dad790d20148e10a/test_coverage)](https://codeclimate.com/github/squirrelphp/debug/test_coverage) ![PHPStan](https://img.shields.io/badge/style-level%207-success.svg?style=flat-round&label=phpstan) [![Packagist Version](https://img.shields.io/packagist/v/squirrelphp/debug.svg?style=flat-round)](https://packagist.org/packages/squirrelphp/debug)  [![PHP Version](https://img.shields.io/packagist/php-v/squirrelphp/debug.svg)](https://packagist.org/packages/squirrelphp/debug) [![Software License](https://img.shields.io/badge/license-MIT-success.svg?style=flat-round)](LICENSE)

- Create exception with the origin call, ignoring some classes/interfaces when backtracking, so the application code "at fault" is easier to localize and problems are easy to understand
- Sanitize function arguments and data to be able to include it as debug data in logs, to give a general idea what data caused a problem and what kind of objects were involved, without including too large data structures

OriginException and this class serves as basis for the other squirrel components, to make debugging less of a hassle and provide the data needed to identify problems in applications.