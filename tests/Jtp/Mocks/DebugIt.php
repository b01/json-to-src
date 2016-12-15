<?php namespace Jtp\Tests\Mocks;

use Jtp\Debug;

/**
 * Description of DebugIt
 *
 * @author Khalifah
 */
class DebugIt
{
    use Debug;

    private $debuggable;

    public function __construct(Debuggable $debuggable)
    {
        $this->debuggable = $debuggable;
    }

    public function test1()
    {
        if ($this->isDebugOn()) {
            $this->debuggable->console();
        }
    }
}
