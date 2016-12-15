<?php namespace Jtp\Tests\Mocks;

use Jtp\JsonModel;

class Engine
{
    use JsonModel;

    private $serial;

    public function getSerial()
    {
        return $this->serial;
    }

    public function setSerial($serial)
    {
        $this->serial = $serial;

        return $this;
    }
}
