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

    /**
     * List of strings that represent the PHP source generated from the JSON.
     *
     * @var array
     */
    private $sources;

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
        $this->sources = [
            'classes' => [],
            'tests' => []
        ];
        $this->unitTests = [];
    }

    /**
     * Build source from JSON.
     *
     * @return array Each element is the PHP source.
     */
    public function generateSource($jsonString, $className, $namespace = '')
    {
        if (preg_match(self::REGEX_CLASS, $className) !== 1) {
            throw new JtpException(JtpException::BAD_CLASS_NAME, [$className]);
        }

        if (!empty($namespace)
            && preg_match(self::REGEX_NS, $namespace) !== 1) {
            throw new JtpException(JtpException::BAD_NAMESPACE, [$namespace]);
        }

        $stdObject = $this->getRootObject($jsonString);
        $this->classes = ($this->classParser)($stdObject, $className, $namespace);
        $doCallback = is_callable($this->preRenderCallback);

        foreach ($this->classes as $className => $properties) {
            $testData = $renderData = $data = [
                'className' => $className,
                'classProperties' => $properties,
                'classNamespace' => $namespace
            ];

            if ($doCallback) {
                $renderData = ($this->preRenderCallback)($renderData, false);
            }

            $this->sources['classes'][$className] = $this->classTemplate->render($renderData);

            if ($this->unitTestTemplate instanceof Twig_Template) {
                if ($doCallback) {
                    $testData = ($this->preRenderCallback)($testData, true);
                }

                $this->sources['tests'][$className . 'Test']
                    = $this->unitTestTemplate->render($testData);
            }
        }

        return $this->sources;
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
        if (!is_writeable($directory)) {
            throw new JtpException(JtpException::NOT_WRITEABLE, [$directory]);
        }

        foreach($this->sources['classes'] as $className => $code) {
            $filename = $directory . DIRECTORY_SEPARATOR . $className . '.php';

            file_put_contents($filename, $code);
        }

        if (!is_dir($unitTestDir) || !is_writeable($directory)) {
            $unitTestDir = $directory;
        }

        foreach($this->sources['tests'] as $className => $code) {
            $filename = $unitTestDir . DIRECTORY_SEPARATOR . $className . '.php';

            file_put_contents($filename, $code);
        }
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
     * Set a function to call before rendering the source code.
     *
     * The callable will be passed the render data, and a boolean value to
     * indicate "TRUE" when generating code for a unit test and "FALSE" for a
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
}
?>
