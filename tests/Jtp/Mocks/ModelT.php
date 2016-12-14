<?php namespace Jtp\Tests\Mocks;

use Jtp\JsonModel;

/**
 * Class ModelT
 *
 * @package \Jtp\Tests\Mocks
 */
class ModelT
{
    use JsonModel;

    /**
     * @var string
     */
    private $name;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
