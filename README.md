## Description
Takes a stdClass object and produces PHP source code. Works recursively, so sub objects will be converted to classes as well.

### Features
* Turns fields into properties with getters and setters.
* Outputs PHP file(s) to a directory of your choice (assuming it is writeable).
* Can turn off/on generating PHPUnit tests.
* Optional namespace.

Speicifically it will convert:
* Scalar values into properties. Will automatically remove '$'.
* stdClass objects into classes.

### Installation
```json
"require": {
    "kshabazz/battlenet-d3": "^1.2"
}
```
or
```bash
composer.phar require --dev kshabazz/json-to-phpsrc
```