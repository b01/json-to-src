<?php
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
    string $key = null,
    array & $switches = null,
    $default = null
) {
    $value = $default;

    if (array_key_exists($index, $arguments)) {
        $value = $arguments[$index];
    } else if (is_string($key)
        && is_array($switches)
        && array_key_exists($key, $switches)) {
        $value = $switches[$key];
    }

    return $value;
}
?>
