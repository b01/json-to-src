#!/usr/bin/env php
<?php
require_once __DIR__
    . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . 'vendor'
    . DIRECTORY_SEPARATOR . 'autoload.php';

require_once __DIR__ . DIRECTORY_SEPARATOR . 'command-line-helpers.php';

use Jtp\StdClassParser;
use Jtp\Converter;
use Ulrichsg\Getopt\Getopt;

echo PHP_EOL . 'FYI current working directory is: ' . getcwd() . PHP_EOL;

// Command line option flags.
$flags = ''
    . 'n::' // optional Takes a string to use as a namespace.
    . 'u::' // optional A separate directory to output unit test.
    . 'a::' // optional Set the property access, the default is "private."
    . 'c::' // optional A callback function to modify template data before render.
    . 'r::' // optional Control how deep to go for nested objects, the default is 20.
    . 'p::' // An optional string to be used as a namespace prefix.
    . 'v' // optional Add debug messages to the output.
    . 't' // optional Turn on PHP 7 type hints.
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
$verbosity = (bool) getArg(-1, $indexArgs, 'v', $options, false);
$typeHints = (bool) getArg(-1, $indexArgs, 't', $options, false);
$unitTestDir = getArg(-1, $indexArgs, 'u', $options, null);
$accessLvl = getArg(-1, $indexArgs, 'a', $options, 'private');
$callbackScript = getArg(-1, $indexArgs, 'c', $options, null);
$recursionLimit = getArg(-1, $indexArgs, 'r', $options, 20);
$nsPrefix = getArg(-1, $indexArgs, 'p', $options, 'N');

try {
    $jsonString = file_get_contents($jsonFile);
    StdClassParser::setDebugMode($verbosity);
    $classParser = new StdClassParser($recursionLimit);
    $classParser->withAccessLevel($accessLvl);
    $classParser->withNamespacePrefix($nsPrefix);

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

    $preRenderCallback = null;

    // Pre-template callback function
    if (file_exists($callbackScript)) {
        $preRenderCallback = require_once $callbackScript;
    }

    if (class_exists('JtpDataMassage')) {
        $preRenderCallback = new JtpDataMassage();
        $converter->withPreRenderCallback([$preRenderCallback, '__invoke']);
    } elseif (is_callable($preRenderCallback)) {
        $converter->withPreRenderCallback($preRenderCallback);
    }

    $converter->generateSource($jsonString, $className, $namespace);
    $converter->save($outDir, $unitTestDir);
    $converter->saveMaps($outDir);

    echo 'Done' . PHP_EOL;
} catch (Exception $error) {
    echo $error->getMessage() . PHP_EOL;
}

?>
