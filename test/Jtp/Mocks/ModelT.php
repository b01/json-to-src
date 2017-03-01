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
     *
     * @var array
     */
    private $engine;

    /**
     *
     * @var \Jtp\Tests\Mocks\MakeT;
     */
    private $makeT;

    /**
     * @var string
     */
    private $name;

    /**
     * @return array
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     *
     * @return \Jtp\Tests\Mocks\MakeT
     */
    public function getMakeT()
    {
        return $this->makeT;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param \Jtp\Tests\Mocks\Engine $engine
     * @return $this
     */
    public function setEngine(Engine $engine)
    {
        if (!isset($this->engine)) {
            $this->engine = [];
        }

        $this->engine[] = $engine;

        return $this;
    }

    /**
     * @param \Jtp\Tests\Mocks\MakeT $makeT
     */
    public function setMakeT(MakeT $makeT)
    {
        $this->makeT = $makeT;

        return $this;
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
