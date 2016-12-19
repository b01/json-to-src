<?php namespace Jtp\Tests;

date_default_timezone_set('America/Detroit');

require_once __DIR__
    . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . 'vendor'
    . DIRECTORY_SEPARATOR . 'autoload.php';

const FIXTURES_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures';
const TEST_TEMP_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'tmp';

function deleteDir($dirPath)
{
    if (! is_dir($dirPath)) {
        throw new InvalidArgumentException($dirPath . ' must be a directory');
    }

    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }

    $files = glob($dirPath . '*', GLOB_MARK);

    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
        }
    }

    rmdir($dirPath);
}

if (is_dir(TEST_TEMP_DIR)) {
    deleteDir(TEST_TEMP_DIR);
}

mkdir(TEST_TEMP_DIR . DIRECTORY_SEPARATOR . 'src', 0777, true);
mkdir(TEST_TEMP_DIR . DIRECTORY_SEPARATOR . 'test', 0777, true);
?>