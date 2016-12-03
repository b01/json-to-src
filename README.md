## Description
Convert JSON to source code.

This tool takes a JSON file, along with other required input, and on success
will parse the JSON and output (PHP by default) source files. It works
recursively on the JSON, and will parse nested keys where the value is an 
object. In addition, an array that contain objects will produce a class, but
only one since all elements are assumed to be of the same type.

### Features
* Parses JSON into an hash-array to be used in a template engine ([see schema](#array-schema)). 
* Outputs file(s) to a directory of your choice (assuming it is writable).
* Can turn off/on generating unit tests.
* Optional set a namespace.

#### Using the Default PHP template
* Will convert to PHP, turning fields into
  properties with getters and setters (fluent style).
* Optional scalar type hints for PHP 7 Function signatures.
Example:
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
* Bad characters like "$,-" Will automatically be removed from keys in the JSON file.
* A field that is an array and has an object as it first element will result in
  a class being generated.
* All objects found will produce a class file, the key that contained that object will be used as the name.
* Will not source more than one object per key to prevent class name collision.

Here's a rough idea of how it handles JSON types: 

JSON | PHP
---- | ---
null | null
123 | int
1.00 | double (a.k.a float)
"string" | string (no interpolation, ex: 'string')
"Company": {"name": "Kohirens"} | class Company {}
"Clients": [{"id":1}] | class Clients { private $id; getId,setId }
"Company": {} | Will produce no class, since the class would have no properties.

### Installation

#### Composer.json
```json
"require": {
    "kshabazz/json-to-src": "dev-master"
}
```

#### Shell or Command Line
```bash
composer.phar require --dev kshabazz/json-to-src
```