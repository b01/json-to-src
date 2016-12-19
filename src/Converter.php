<?php namespace Jtp;

use stdClass;
use Twig_Template;

/**
 * @package \Jtp\Converter
 */
class Converter
{
    use Debug;

    /** @var string Regular expression use to verify a class name.*/
    const REGEX_CLASS = '/^[a-zA-Z][a-zA-Z0-9_]*$/';

    /** @var string Regular expression use to verify a name-space.*/
    const REGEX_NS = '/^[a-zA-Z][a-zA-Z0-9\\\\]*[a-zA-Z]?$/';

    /** @var \Jtp\ClassParser */
    private $classParser;

    /**
     * ex: [ "className" => ["name" => "property1", "type" => "integer"], ... ]
     *
     * @var array
     */
    private $classes;

    /** @var Twig_Template */
    private $classTemplate;

    /**
     * Function to receive render data before render for alteration.
     *
     * @var callable
     */
    private $preRenderCallback;

    /** @var array Source code for unit test. */
    private $unitTests;

    /** @var \Twig_Template */
    private $unitTestTemplate;

    /**
     * Constructor
     *
     * @param string $jsonString
     * @param \Jtp\ClassParser $classParser
     * @throws \Jtp\JtpException
     */
    public function __construct(
        ClassParser $classParser
    ) {
        $this->classParser = $classParser;
        $this->classes = [];
        $this->unitTests = [];
    }

    /**
     * Build source from JSON.
     *
     * @return array Each element is the PHP source.
     */
    public function generateSource($jsonString, $className, $namespaceBase = '')
    {
        if (preg_match(self::REGEX_CLASS, $className) !== 1) {
            throw new JtpException(JtpException::BAD_CLASS_NAME, [$className]);
        }

        if (!empty($namespaceBase)
            && preg_match(self::REGEX_NS, $namespaceBase) !== 1) {
            throw new JtpException(JtpException::BAD_NAMESPACE, [$namespaceBase]);
        }

        $stdObject = $this->getRootObject($jsonString);
        $function = $this->classParser;
        $this->classes = $function($stdObject, $className, $namespaceBase);

        foreach ($this->classes as &$classData) {
           $classData['source'] = $this->buildSource(
                $classData,
                $this->classTemplate,
                $this->preRenderCallback
            );
            $classData['unitSource'] = $this->buildSource(
                $classData,
                $this->unitTestTemplate,
                $this->preRenderCallback,
                true
            );
        }

        return $this->classes;
    }

    /**
     * Save source files to disk.
     *
     * @param string $directory Directory to save the files.
     * @param string $unitTestDir Specify a separate directory for unit tests.
     * @return void
     * @throws JtpException
     */
    public function save($directory, $unitTestDir = null)
    {
        // Validate the directory.
        if (!is_writeable($directory)) {
            throw new JtpException(JtpException::NOT_WRITEABLE, [$directory]);
        }

        // When set, validate the unit test directory.
        if ($unitTestDir !== null && (!is_dir($unitTestDir) || !is_writeable($unitTestDir))) {
            throw new JtpException(JtpException::NOT_WRITEABLE, [$unitTestDir]);
        }

        if ($unitTestDir === null) {
            $unitTestDir = $directory;
        }

        foreach($this->classes as $class) {
            $this->saveSourceFile($directory, $class['classNamespace'], $class['name'], $class['source']);
            $this->saveSourceFile($unitTestDir, $class['classNamespace'], $class['name'] . 'Test', $class['unitSource']);
        }
    }

    /**
     * For every key/field found in the JSON, show the name used for the class. This is a PHP array output to a file
     * "classMap.php".
     *
     * @return boolean Indicate true when the file was written.
     */
    public function saveClassMap($directory)
    {
        $classMap = "/**\n"
            . " * Each key is based on the field pulled from the JSON, the\n"
            . " * value is the name used in the source code output.\n"
            . " */\n"
            . "<?php\n\$classMap = [\n";

        foreach($this->classes as $key => $class) {
            $classMap .= "\t['{$key}' => '{$class['name']}'],\n";

            foreach($class['properties'] as $property) {
                $classMap .= "\t['{$key}_{$property['name']}' => '{$property['name']}'],\n";
            }
        }

        $classMap .= "];\n";

        return file_put_contents($directory . DIRECTORY_SEPARATOR . 'classMap.php', $classMap) > 0;
    }

    /**
     * Set template to generate class source.
     *
     * @param Twig_Template $template
     * @return Converter
     */
    public function setClassTemplate(Twig_Template $template)
    {
        $this->classTemplate = $template;

        return $this;
    }

    /**
     * Set the template to generate unit test.
     *
     * @param \Twig_Template $template
     * @return Converter
     */
    public function setUnitTestTemplate(Twig_Template $template)
    {
        $this->unitTestTemplate = $template;

        return $this;
    }

    /**
     * Filter the class data through a callback that allows the client to transform the source output.
     *
     * Set a function to call before rendering the source code.
     * The callable will be passed the render data, and a boolean value to
     * indicate "TRUE" when building the source code for a unit test and "FALSE" for a
     * actual class.
     *
     * @return Converter
     * @param callable $callable
     * @return Converter
     */
    public function withPreRenderCallback(callable $callable)
    {
        $this->preRenderCallback = $callable;

        return $this;
    }

    /**
     * Build the source code.
     *
     * @param array $classData
     * @param \Twig_Template $template
     * @param callable $callback
     */
    private function buildSource(
        array $classData,
        Twig_Template $template = null,
        callable $callback = null,
        $isUnitTest = false
    ) {
        $source = '';

        if ($template instanceof Twig_Template) {
            // Filter the class data through a callback that allows the client to transform the source output.
            if (is_callable($callback)) {
                $classData = $callback($classData, $isUnitTest);
            }

            $source = $template->render($classData);
        }

        return $source;
    }

    /**
     * Get object from JSON string.
     *
     * Verify the JSON contains an object or an array where the first elements is
     * an object.
     *
     * @param string $jsonString
     * @return bool
     * @throws \Jtp\JtpException
     */
    private function getRootObject($jsonString)
    {
        $decoded = json_decode($jsonString);
        $object = null;

        if (is_object($decoded)) {
            $object = $decoded;
        } else if (is_array($decoded)
            && count($decoded) > 0
            && is_object($decoded[0])) {
            $object = $decoded[0];
        }

        if (!$object instanceof stdClass) {
            throw new JtpException(
                JtpException::BAD_JSON_DECODE,
                [json_last_error_msg(), $jsonString]
            );
        }

        return $object;
    }

    /**
     *
     * @param string $directory
     * @param string $classNamespace
     * @param string $name
     * @param string $source
     */
    private function saveSourceFile($directory, $classNamespace, $name, $source)
    {
        $namespaceDir = $directory . DIRECTORY_SEPARATOR . $classNamespace;
        $namespaceDir = str_replace('\\', DIRECTORY_SEPARATOR, $namespaceDir);

        if (!is_dir($namespaceDir)) {
            mkdir($namespaceDir, 0777, true);
        }

        $filename = $namespaceDir . DIRECTORY_SEPARATOR . $name . '.php';

        if (!empty($source)) {
            file_put_contents($filename, $source);
        }
    }
}
?>
