## Description
Convert JSON to source code.

This tool takes a JSON file, along with other required input, and on success
will parse the JSON and output (PHP by default) source files. It works
recursively on the JSON, and will parse nested keys where the value is an 
object. In addition, an array that contain objects will produce a class, but
only one since all elements are assumed to be of the same type.

### Features
* Parses JSON into an hash-array to be used in a template engine ([see schema](#template-data-schema)). 
* Outputs file(s) to a directory of your choice (assuming it is writable).
* Can turn off/on generating unit tests.
* Optional set a namespace.
* set a callaback to manipulate the output of the source code generated.

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
{
  "require": {
    "kshabazz/json-to-src": "^1.0"
  }
}
```

#### Shell or Command Line
```bash
composer.phar require --dev kshabazz/json-to-src
```

### Template Data Schema
This is the array that is passed to the tempalte engines render method:
```php
$renderData = [
    "className" => Company
    "classProperties" => [
        [
            "name" => company
            "type" => string
            "isCustomType" => 
            "paramType" => string
            "value" => Kohirens
            "arrayType" => 
        ],
        [
            "name" => employees
            "type" => array
            "isCustomType" => 
            "paramType" => array
            "value" => ""
            "arrayType" => employees
        ],
        [
            "name" => location
            "type" => Location
            "isCustomType" => 1
            "paramType" => Location
            "value" => stdClass Object
                (
                    "type" => Point
                    "coordinates" => Array
                        (
                            [0" => -83.0466419
                            [1" => 42.3323378
                        )

                    "city" => Detroit
                    "state" => MI
                    "zip_code" => 48226
                )

            "arrayType" => 
        ],
        [
            "name" => categories
            "type" => array
            "isCustomType" => 
            "paramType" => array
            "value" => []
            "arrayType" => categories
        ]
    ],
    "classNamespace" => Tests
];
```

### Set A Callback

You can also set a callback that will recieve the render data, to control thins
such as the class name, namespace, and etc. Take the following example.

**Callback script: preRenderCallback.php**
```php

return function (array $renderData, $isUnitTest) {
    if (!$isUnitTest) {
        $renderData['classNamespace'] .= '\\Bar';
    }
    
    return $renderData;
};
```

**Pass preRenderCallback.php to the command line script:**
```bash
jsontosrc -n Tests -c preRenderCallback.php company.json Company tmp
```


### Command Line

The "jsontosrc" command line script take several arguments and options.

-n Takes a string to use as a namespace.
-u A directory to output unit test.
-a Set the property access, the default is "private."
-c callback function before template render.
-d Add debug messages to the output.
-t Turn off/on PHP 7 type hints.