<?php namespace Jtp\Tests;

use Jtp\Tests\Mocks\JtpDataMassage;

/**
 * Class TemplateDataMassagerTest
 *
 * @package \Jtp\Tests
 * @coversDefaultClass \Jtp\TemplateDataMassage
 */
class TemplateDataMassagerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->jtpDataMassage = new JtpDataMassage();
    }

    /**
     * @covers ::__invoke
     * @uses \Jtp\TemplateDataMassage::doRenaming
     */
    public function testCanRenameAClass()
    {
        $jtpDataMassage = new JtpDataMassage();
        $classKey = 'Location';
        $fixture = [
            'name' => 'Location',
            'fullName' => '',
            'classNamespace' => '',
            'properties' => []
        ];

        $jtpDataMassage->setMapName($classKey, 'Loc');
        $actual = $jtpDataMassage($classKey, $fixture);

        $this->assertEquals('Loc', $actual['name']);
    }

    /**
     * @covers ::doRenaming
     * @uses \Jtp\TemplateDataMassage::__invoke
     */
    public function testCanRenameNamespace()
    {
        $jtpDataMassage = new JtpDataMassage();
        $classKey = 'Location';
        $namespaceKey = 'Foos';
        $fixture = [
            'name' => '',
            'fullName' => '',
            'classNamespace' => $namespaceKey,
            'properties' => []
        ];

        $jtpDataMassage->setMapName($namespaceKey, 'Foo');
        $actual = $jtpDataMassage($classKey, $fixture);

        $this->assertEquals('Foo', $actual['classNamespace']);
    }

    /**
     * @covers ::renameProperties
     * @uses \Jtp\TemplateDataMassage::__invoke
     * @uses \Jtp\TemplateDataMassage::doRenaming
     * @uses \Jtp\TemplateDataMassage::renameNamespace
     */
    public function testCanRenameProperty()
    {
        $jtpDataMassage = new JtpDataMassage();
        $fixture = [
            'name' => 'Employees',
            'fullName' => 'Company\\Employees',
            'classNamespace' => 'Company',
            'properties' => [
                ['name' => 'first_name', 'namespace' => 'Company', 'isCustomType' => false]
            ]
        ];

        $jtpDataMassage->setMapName('Employees::$first_name', 'firstName');
        $actual = $jtpDataMassage('Employees', $fixture);

        $this->assertEquals('firstName', $actual['properties'][0]['name']);
    }

    /**
     * @covers ::doRenaming
     * @uses \Jtp\TemplateDataMassage::__invoke
     * @uses \Jtp\TemplateDataMassage::doRenaming
     * @uses \Jtp\TemplateDataMassage::renameNamespace
     * @uses \Jtp\TemplateDataMassage::renameProperties
     */
    public function testCanRenameFullName()
    {
        $jtpDataMassage = new JtpDataMassage();
        $fixture = [
            'name' => 'Bars',
            'fullName' => 'Foo\\Bars',
            'classNamespace' => 'Foo',
            'properties' => []
        ];

        $jtpDataMassage->setMapName('Bars', 'Bar');
        $data = $jtpDataMassage('Bars', $fixture);
        $actual = $data['classNamespace'] . '\\' . $data['name'];

        $this->assertEquals('Foo\\Bar', $actual);
    }

    /**
     * @covers ::renameTypes
     * @uses \Jtp\TemplateDataMassage::__invoke
     * @uses \Jtp\TemplateDataMassage::doRenaming
     * @uses \Jtp\TemplateDataMassage::getMappedName
     */
    public function testWillRenamePropertyArrayType()
    {
        $jtpDataMassage = new JtpDataMassage();
        $fixture = [
            'name' => 'Employee',
            'fullName' => 'Company\\Employee',
            'classNamespace' => 'Company',
            'properties' => [
                [
                    'access' => 'protected',
                    'name' => 'departments',
                    'type' => 'array',
                    'isCustomType' => '',
                    'paramType' => 'array',
                    'value' => [],
                    'arrayType' => 'Company\NEmployee\Departments',
                    'namespace' => 'Company',
                ]
            ]
        ];

        $jtpDataMassage->setMapName('Company\\NEmployee\\Departments', 'Company\\Department');
        $data = $jtpDataMassage('Employee', $fixture);
        $actual = $data['properties'][0]['arrayType'];

        $this->assertEquals('Company\\Department', $actual);
    }

    /**
     * @covers ::renameTypes
     * @uses \Jtp\TemplateDataMassage::__invoke
     * @uses \Jtp\TemplateDataMassage::doRenaming
     */
    public function testWillRenamePropertyNamespace()
    {
        $jtpDataMassage = new JtpDataMassage();
        $fixture = [
            'name' => 'Department',
            'classNamespace' => 'Company\\NEmployees',
            'properties' => [[
                    'access' => 'protected',
                    'name' => 'location',
                    'type' => 'Location',
                    'isCustomType' => '1',
                    'paramType' => 'Company\\NEmployees\\Location',
                    'value' => 'stdClass Object',
                    'arrayType' => '',
                    'namespace' => 'Company\\NEmployees',
                ]
            ]
        ];

        $jtpDataMassage->setMapName('Company\\NEmployees', 'Company');
        $data = $jtpDataMassage('Department', $fixture);
        $actual = $data['properties'][0]['namespace'];

        $this->assertEquals('Company', $actual);
    }

    /**
     * @covers ::renameTypes
     * @uses \Jtp\TemplateDataMassage::__invoke
     * @uses \Jtp\TemplateDataMassage::doRenaming
     */
    public function testWillRenamePropertyParamType()
    {
        $jtpDataMassage = new JtpDataMassage();
        $fixture = [
            'name' => 'Department',
            'classNamespace' => 'Company\\NEmployees',
            'properties' => [[
                    'access' => 'protected',
                    'name' => 'location',
                    'type' => 'Location',
                    'isCustomType' => '1',
                    'paramType' => 'Company\\NEmployees\\Location',
                    'value' => 'stdClass Object',
                    'arrayType' => '',
                    'namespace' => 'Company\\NEmployees',
                ]
            ]
        ];

        $jtpDataMassage->setMapName('Company\\NEmployees\\Location', 'Company\\Location');
        $data = $jtpDataMassage('Department', $fixture);
        $actual = $data['properties'][0]['paramType'];

        $this->assertEquals('Company\\Location', $actual);
    }
}
