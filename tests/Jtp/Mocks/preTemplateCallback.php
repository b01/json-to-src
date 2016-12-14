<?php
/**
 * Modify the template data.
 *
 * @param array $renderData
 * @return array
 */
return function (array $renderData, $isUnitTest) {
    if (!$isUnitTest) {
        $renderData['useStmts'] = "\n\nuse Tests\\Foo;"
            . "\nuse Tests\\Bar;";

        $renderData['classAttrs'] = " extends \\Model implements \\ModelInterface";

        $tab = str_pad(" ", 4);
        $renderData['traitStmts'] = "\n{$tab}use Tests\\Baz;\n";
    }

    return $renderData;
};
?>
