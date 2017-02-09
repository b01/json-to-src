<?php namespace Jtp\Tests;

require_once MOCK_DIR . '/JtpDataMassage7.php';

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
     * @uses \Jtp\TemplateDataMassage::doRemapping()
     * @uses \Jtp\TemplateDataMassage::getMappedName
     * @uses \Jtp\TemplateDataMassage::renameTypes
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
     * @covers ::doRemapping
     * @uses \Jtp\TemplateDataMassage::__invoke
     * @uses \Jtp\TemplateDataMassage::getMappedName
     * @uses \Jtp\TemplateDataMassage::renameTypes
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
     * @covers ::renameTypes
     * @uses \Jtp\TemplateDataMassage::__invoke
     * @uses \Jtp\TemplateDataMassage::doRemapping
     * @uses \Jtp\TemplateDataMassage::getMappedName
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
     * @covers ::getMappedName
     * @uses \Jtp\TemplateDataMassage::__invoke
     * @uses \Jtp\TemplateDataMassage::doRemapping
     * @uses \Jtp\TemplateDataMassage::renameTypes
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
     * @covers ::getMappedType
     * @uses \Jtp\TemplateDataMassage::__invoke
     * @uses \Jtp\TemplateDataMassage::doRemapping
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
                    'isCustomType' => false,
                    'paramType' => 'array',
                    'value' => [],
                    'arrayType' => 'Company\\NEmployee\\Departments',
                    'arrayTypeClassKey' => 'Departments',
                    'namespace' => 'Company',
                ]
            ]
        ];

        $jtpDataMassage->setMapName('Company\\NEmployee', 'Company');
        $jtpDataMassage->setMapName('Departments', 'Department');
        $data = $jtpDataMassage('Employee', $fixture);
        $actual = $data['properties'][0]['arrayType'];

        $this->assertEquals('Company\\Department', $actual);
    }

    /**
     * @covers ::renameTypes
     * @uses \Jtp\TemplateDataMassage::__invoke
     * @uses \Jtp\TemplateDataMassage::doRemapping
     * @uses \Jtp\TemplateDataMassage::getMappedName
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
                    'isCustomType' => false,
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
     * @covers ::getMappedType
     * @uses \Jtp\TemplateDataMassage::__invoke
     * @uses \Jtp\TemplateDataMassage::doRemapping
     * @uses \Jtp\TemplateDataMassage::getMappedName
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
                    'isCustomType' => true,
                    'paramType' => 'Company\\NEmployees\\Location',
                    'value' => 'stdClass Object',
                    'arrayType' => '',
                    'namespace' => 'Company\\NEmployees',
                ]
            ]
        ];

        $jtpDataMassage->setMapName('Company\\NEmployees', 'Company');
        $data = $jtpDataMassage('Department', $fixture);
        $actual = $data['properties'][0]['paramType'];

        $this->assertEquals('Company\\Location', $actual);
    }
}
