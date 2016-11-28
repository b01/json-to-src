#!/usr/bin/env php
<?php
require_once __DIR__
    . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . 'vendor'
    . DIRECTORY_SEPARATOR . 'autoload.php';

require_once __DIR__ . DIRECTORY_SEPARATOR . 'command-line-helpers.php';

use Jtp\Converter;
use Ulrichsg\Getopt\Getopt;
echo PHP_EOL . 'FYI current working directory is: ' . getcwd() . PHP_EOL;

// Command line opiton flags.
$flags = ''
    . 'n::' // optional namespace.
    . 'd::' // optional debug mode.
;

// Allows us to c
$getopt = new Getopt($flags);
try {
    $getopt->parse();
} catch (Exception $err) {
    echo $err->getMessage() . PHP_EOL;
    exit(2);
}
$options = $getopt->getOptions();
$indexArgs = $getopt->getOperands();

$jsonFile = getArg(0, $indexArgs);

if ($jsonFile === null) {
    echo 'Please specify a JSON file as the first argument.' . PHP_EOL;
    exit(1);
}

if (!file_exists($jsonFile)) {
    echo 'The file "' . $jsonFile . '" does not exists.' . PHP_EOL;
    exit(1);
}

$className = getArg(1, $indexArgs);
if ($className === null) {
    echo 'Please specify a class name as the second argument.' . PHP_EOL;
    exit(1);
}

$outDir = getArg(2, $indexArgs);
if ($outDir === null) {
    echo 'Please specify a directory to save the generated files as the third argument.' . PHP_EOL;
    exit(1);
}

$namespace = getArg(-1, $indexArgs, 'n', $options, '');

try {
    $jsonString = file_get_contents($jsonFile);
    $converter = new Converter($jsonString, $className, $namespace);

    $twigLoader = new Twig_Loader_Filesystem(__DIR__
        . DIRECTORY_SEPARATOR . '..'
        . DIRECTORY_SEPARATOR . 'templates'
    );
    $twig = new Twig_Environment($twigLoader);
    $classTemplate = $twig->loadTemplate('class-php.twig');
    $unitTestTemplate = $twig->loadTemplate('class-unit-php.twig');

    $converter->setClassTemplate($classTemplate);
    $converter->setUnitTestTemplate($unitTestTemplate);
    $converter->generateSource();
    $converter->save($outDir);
    echo 'Done' . PHP_EOL;
} catch (\Exception $error) {
    echo $error->getMessage() . PHP_EOL;
}

?>
