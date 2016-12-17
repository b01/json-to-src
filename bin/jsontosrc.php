#!/usr/bin/env php
<?php
require_once __DIR__
    . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . 'vendor'
    . DIRECTORY_SEPARATOR . 'autoload.php';

require_once __DIR__ . DIRECTORY_SEPARATOR . 'command-line-helpers.php';

use Jtp\ClassParser;
use Jtp\Converter;
use Ulrichsg\Getopt\Getopt;

echo PHP_EOL . 'FYI current working directory is: ' . getcwd() . PHP_EOL;

// Command line option flags.
$flags = ''
    . 'n::' // optional namespace.
    . 'u::' // optional separate unit test directory.
    . 'a::' // optional default property access level.
    . 'c::' // optional callback function before template render.
    . 'r::' // optional recursion limit.
    . 'd' // optional debug mode.
    . 't' // optional turn on type hints.
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
$debug = (bool) getArg(-1, $indexArgs, 'd', $options, false);
$typeHints = (bool) getArg(-1, $indexArgs, 't', $options, false);
$unitTestDir = getArg(-1, $indexArgs, 'u', $options, null);
$accessLvl = getArg(-1, $indexArgs, 'a', $options, 'private');
$callbackScript = getArg(-1, $indexArgs, 'c', $options, null);
$recursionLimit = getArg(-1, $indexArgs, 'r', $options, 20);

try {
    $jsonString = file_get_contents($jsonFile);
    ClassParser::setDebugMode($debug);
    $classParser = new ClassParser($recursionLimit);
    $classParser->withAccessLevel($accessLvl);

    // Use a template engine to generate source code string.
    $twigLoader = new Twig_Loader_Filesystem(__DIR__
        . DIRECTORY_SEPARATOR . '..'
        . DIRECTORY_SEPARATOR . 'templates'
    );
    $twig = new Twig_Environment($twigLoader);
    // Load Twig custom filters and functions.
    $twig->addExtension(new \Jtp\TwigTools($typeHints));
    // Load template for generating the class source code.
    $classTemplate = $twig->loadTemplate('class-php.twig');
    // Load template for generating the class unit test source code.
    $unitTestTemplate = $twig->loadTemplate('class-unit-php.twig');

    $converter = new Converter($classParser);
    $converter->setClassTemplate($classTemplate)
        ->setUnitTestTemplate($unitTestTemplate);

    // Pre-template callback function
    if (file_exists($callbackScript)) {
        $preRenderCallback = require_once $callbackScript;
        $converter->withPreRenderCallback($preRenderCallback);
    }

    $converter->generateSource($jsonString, $className, $namespace);
    $converter->save($outDir, $unitTestDir);
    echo 'Done' . PHP_EOL;
} catch (\Exception $error) {
    echo $error->getMessage() . PHP_EOL;
}

?>
