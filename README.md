## Description
Convert JSON to PHP source code.

This tool takes a JSON file, along with other required input, and on success
will produce PHP files. It works recursively, and will parse nested keys where
the value is an object. In addition, an array that contain objects will produce
a class, but only one since all elements are assumed to be of the same type.

### Features
* Turns fields into properties with getters and setters (fluent style).
* Outputs PHP file(s) to a directory of your choice (assuming it is writeable).
* Can turn off/on generating PHPUnit tests.
* Optional namespace.
* Scalar type hints for PHP 7 Function signatures.
  ```php
  /**
   * @var int
   */
  private $client;

  /**
   * Get ClientId.
   *
   * @param int $ClientId
   * @return $this;
   */
  public function setClientId(int $ClientId)
  {
      $this->ClientId = $ClientId; 
  }
  ```

### Side-effect

Some things to make note of when using this tool include:
* Bad characters like "$,-" Will automatically be removed keys in the JSON file.
* A field that is an array and has an object as it first element will result in
  a class being generated.
* All objects found will produce a class file, the key will be used as the name.

Here's a rough idea of how it handles JSON types: 

JSON | PHP
---- | ---
null | null
123 | int
1.00 | double (a.k.a float)
"string" | string (no interpolation, ex: 'string')
"Company": {} | class Company {}
"Clients": [{"id":1}] | class Clients { private $id; getId,setId }

### Installation

#### Composer.json
```json
"require": {
    "kshabazz/json-to-phpsrc": "dev-master"
}
```

#### Shell or Command Line
```bash
composer.phar require --dev kshabazz/json-to-phpsrc
```