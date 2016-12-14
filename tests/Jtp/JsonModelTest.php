<?php namespace Jtp\Tests\Models;

use Jtp\JsonModel;
use Jtp\Tests\Mocks\ModelT;

/**
 * Class JsonModelTest
 *
 * @package \Jtp\Tests\Models
 * @coversDefaultClass \Jtp\JsonModel
 */
class JsonModelTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Jtp\JsonModel|\PHPUnit_Framework_MockObject_MockObject */
    private $model;

    public function setUp()
    {
        $this->model = $this->getMockForTrait(JsonModel::class);
    }

    /**
     * @covers ::validateProperty
     */
    public function testCanValidateProperty()
    {
        $fixture = 1234;
        $actual = $this->model->validateProperty('test', 'integer', $fixture);

        $this->assertTrue($actual);
    }

    /**
     * @covers ::validateProperty
     */
    public function testCanInValidateProperty()
    {
        $this->setExpectedException(
            RateGrabberException::class,
            '',
            RateGrabberException::PROPERTY_EMPTY
        );

        $this->model->validateProperty('test', 'NULL', null);
    }

    /**
     * @covers ::validateProperty
     */
    public function testCanInValidatePropertyType()
    {
        $this->setExpectedException(
            RateGrabberException::class,
            '',
            RateGrabberException::BAD_PROPERTY_TYPE
        );

        $this->model->validateProperty('test', 'integer', '1');
    }

    /**
     * @covers ::setByJson
     * @covers ::__toString
     * @covers ::jsonSerialize
     */
    public function testCanSetByJsonString()
    {
        $fixture = '{"name":1234, "year": null}';
        $mt = new ModelT();
        $mt->setByJson($fixture);
        $actual = (string) $mt;

        $this->assertEquals('{"name":1234}', $actual);
    }
}
