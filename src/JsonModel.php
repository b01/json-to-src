<?php namespace Jtp;

use stdClass;

/**
 * Class JsonModel
 *
 * @package \Jtp
 */
trait JsonModel
{
    /**
     * Converts to a JSON string.
     *
     * Properties set to NULL will be omitted from the output.
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }

    /**
     * Get data for JSON serialization.
     *
     * Properties set to NULL will be omitted from the output.
     *
     * @return string
     */
    public function jsonSerialize() {
        $data = new stdClass();
        $vars = get_object_vars($this);

        foreach ($vars as $property => $value) {
            if ($value === null) {
                continue;
            }

            $data->{$property} = $value;
        }

        return $data;
    }

    /**
     * Set fields by JSON string, will overwrite any previously set values.
     *
     * @param string $jsonString
     * @return $this
     */
    public function setByJson($jsonString)
    {
        $object = json_decode($jsonString);

        $this->setByObject($object);

        return $this;
    }

    /**
     * Set fields by JSON string, will overwrite any previously set values.
     *
     * @param \stdClass $object
     * @return $this
     */
    public function setByObject(stdClass $object)
    {
        $vars = get_object_vars($object);

        if (is_array($vars)) {
            foreach ($vars as $property => $value) {
                $this->$property = $value;
            }
        }

        return $this;
    }

    /**
     * Set fields by JSON string, will overwrite any previously set values.
     *
     * @param \stdClass $object
     * @return $this
     */
    public function rSetByObject(stdClass $object, $nameSpace = '')
    {
        $vars = get_object_vars($object);

        if (is_array($vars)) {
            foreach ($vars as $property => $value) {
                $class = $nameSpace . '\\' . ucfirst($property);

                $value = $this->hydrateClass($value, $class);

                $this->{$property} = $value;
            }
        }

        return $this;
    }

    /**
     * Verify that a property exists and its value is of a specified type.
     *
     * @param string $property
     * @param string $type
     * @param mixed $value
     * @param string $class
     * @return true
     * @throws \Jtp\JtpException
     */
    public function validateProperty($property, $type, $value, $class = __CLASS__)
    {
        $aType = gettype($value);
        if ($aType !== $type) {
            throw new JtpException(
                JtpException::BAD_PROPERTY_TYPE,
                [$property, $type, $aType]
            );
        }

        if ($value === null) {
            throw new JtpException(
                JtpException::PROPERTY_EMPTY,
                [$class, $property, $aType]
            );
        }

        return true;
    }

    /**
     * @param $value
     * @param $class
     * @return mixed
     */
    private function hydrateClass($value, $class)
    {
        $newValue = $value;

        if (class_exists($class)) {
            // Cast a single stdClass to a custom class.
            if (is_object($value)) {
                $temp = new $class();
                $temp->rSetByObject($value);
                $newValue = $temp;
            // An array of stdClass to a custom class.
            } else if (is_array($value) && count($value) > 0 && is_object($value[0])) {
                // Cast each item in the array then append it to the property.
                $temp = [];
                foreach ($value as $key => $item) {
                    $item = $this->hydrateClass($item, $class);
                    $temp[] = $item;
                }
                $newValue = $temp;
            }
        }

        return $newValue;
    }
}
