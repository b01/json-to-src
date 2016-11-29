## Description
Takes a stdClass object and produces PHP source code. Works recursively, so sub objects will be converted to classes as well.

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

Side-effect to note include:
* Bad characters like "$,-" Will automatically be removed keys in the JSON file.
* A field that is an array and has an object as it first element will result in
  a class being generated.
* All objects found will produce a class file, the key will be used as the name.

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