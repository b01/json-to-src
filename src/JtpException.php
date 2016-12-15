<?php namespace Jtp;

use Exception;

/**
 * Class JtpException
 */
class JtpException extends Exception
{
    /** @var integer WHen the error is unknown. */
    const UNKNOWN = 1;

    /** @var integer Indicates a JSON file did not decode to a stdClass object. */
    const BAD_JSON_DECODE = 2;

    /** @var integer Indicates a unusable name for a name-space. */
    const BAD_NAMESPACE = 3;

    /** @var integer Indicates a unusable name for a class. */
    const BAD_CLASS_NAME = 4;

    /** @var integer Indicates a directory was not found or writable. */
    const NOT_WRITEABLE = 5;

    /** @var integer Indicates tried to set a bad default access level. */
    const BAD_ACCESS_LEVEL = 6;

    const BAD_PROPERTY_TYPE = 7;

    const PROPERTY_EMPTY = 8;

    /**
     * List of error codes and their corresponding messages.
     *
     * @var array
     */
    private static $errorMap = [
        self::UNKNOWN => 'An unknown error has occurred.',
        self::BAD_JSON_DECODE => 'The decoded JSON does not contain any fields that can be converted to PHP, with last JSON error: %s: JSON: "%s".',
        self::BAD_NAMESPACE => 'Invalid character(s) found when trying to use the namespace "%s".',
        self::BAD_CLASS_NAME => 'Invalid character(s) found when trying to use the class name "%s".',
        self::NOT_WRITEABLE => 'Directory is not writable: "%s".',
        self::BAD_ACCESS_LEVEL => 'Access level "%s" is not allowed. Only: %s',
        self::BAD_PROPERTY_TYPE => '%s should be of type "%s", actual type is %s.',
        self::PROPERTY_EMPTY => '%s::%s has been set to null, needs to be "%s".'
    ];

    /**
     * Constructor
     *
     * @param numeric $code Error code.
     * @param array $variables To fill in placeholders for \vsprintf.
     */
    public function __construct($code, array $variables = NULL)
    {
        $message = $this->getMessageByCode($code, $variables);
        parent::__construct($message, $code);
    }

    /**
     * Convert error code to human readable text.
     *
     * @param numeric & $code
     * @param array $data
     * @return string
     */
    public function getMessageByCode(& $code, array $data = null)
    {
        // If we do not use a reference, then that defeats the purpose of
        // making the $errorMap a static property.
        $map = &static::getErrorMap();

        // When you can't find the code, use a default one.
        if (!\array_key_exists($code, $map)) {
            $code = static::UNKNOWN;
        }

        if (\is_array($data) && count($data) > 0) {
            return \vsprintf($map[$code], $data);
        }

        return $map[$code];
    }

    /**
     * Since the error map is a static property
     * @return array
     */
    static protected function &getErrorMap()
    {
        return static::$errorMap;
    }
}
?>
