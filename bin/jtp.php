<?php

require_once __DIR__
    . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . 'vendor'
    . DIRECTORY_SEPARATOR . 'autoload.php';

use Jtp\Converter;
//use Twig_Environment;
//use Twig_Loader_Filesystem;

echo 'FYI current working directory is: ' . getcwd() . PHP_EOL;

// Command line opiton flags.
$flags = ''
    . 'f:' // required JSON input file.
    . 'c:' // required class name.
    . 'n:' // Optional namespace.
    . 'o:' // required directtory to save generated files.
;

$options = getopt($flags);

/**
 * Get a value from a switch option or numbered argument.
 *
 * @param int $index
 * @param array $arguments
 * @param string $key
 * @param array $switches
 * @param type $default
 * @return array
 */
function getArg(
    int $index,
    array & $arguments,
    string $key,
    array & $switches,
    $default = null
) {
    $value = $default;

    if (array_key_exists($key, $switches)) {
        $value = $switches[$key];
    } else if (array_key_exists($index, $arguments)) {
        $value = $arguments[$index];
    }

    return $value;
}

$jsonFile = getArg(1, $argv, 'f', $options);

if ($jsonFile === null) {
    echo 'Please specify a JSON file as the first argument, or with -f.';
    exit(1);
}

if (!file_exists($jsonFile)) {
    echo 'The file ' . $jsonFile . ' does not exists.';
    exit(1);
}

$className = getArg(2, $argv, 'c', $options);
if ($className === null) {
    echo 'Please specify a class name as the second argument, or with -c.';
}

$outDir = getArg(3, $argv, 'o', $options);
if ($jsonFile === null) {
    echo 'Please specify a direcoty to save the generated files as the third argument, or with -o.';
    exit(1);
}

$namespace = getArg(4, $argv, 'n', $options, '');

try {
    $jsonString = file_get_contents($jsonFile);
    $converter = new Converter($jsonString, $className, $namespace);

    $twigLoader = new \Twig_Loader_Filesystem(__DIR__
        . DIRECTORY_SEPARATOR . '..'
        . DIRECTORY_SEPARATOR . 'templates'
    );
    $twig = new \Twig_Environment($twigLoader);
    $classTemplate = $twig->loadTemplate('class-php.twig');
    $unitTestTemplate = $twig->loadTemplate('class-unit-php.twig');

    $converter->setClassTemplate($classTemplate);
    $converter->setUnitTestTemplate($unitTestTemplate);
    $converter->generateSource();
    $converter->save($outDir);
} catch (\Exception $error) {
    echo $error->getMessage() . PHP_EOL;
}

?>
