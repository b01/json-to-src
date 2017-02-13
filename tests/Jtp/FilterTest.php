<?php namespace Jtp\Tests;

require_once MOCK_DIR . '/JtpFilter7.php';

/**
 * Class FilterTest
 *
 * @package \Jtp\Tests
 * @coversDefaultClass \Jtp\Filter
 */
class FilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__invoke
     * @uses \Jtp\Filter::doRemapping()
     * @uses \Jtp\Filter::getMappedName
     * @uses \Jtp\Filter::renameTypes
     */
    public function testCanRenameAClass()
    {
        $jtpFilter = new JtpFilter();
        $classKey = 'Location';
        $fixture = [
            'name' => 'Location',
            'fullName' => '',
            'classNamespace' => '',
            'properties' => []
        ];

        $jtpFilter->setMapName($classKey, 'Loc');
        $actual = $jtpFilter($classKey, $fixture);

        $this->assertEquals('Loc', $actual['name']);
    }

    /**
     * @covers ::doRemapping
     * @uses \Jtp\Filter::__invoke
     * @uses \Jtp\Filter::getMappedName
     * @uses \Jtp\Filter::renameTypes
     */
    public function testCanRenameNamespace()
    {
        $jtpFilter = new JtpFilter();
        $classKey = 'Location';
        $namespaceKey = 'Foos';
        $fixture = [
            'name' => '',
            'fullName' => '',
            'classNamespace' => $namespaceKey,
            'properties' => []
        ];

        $jtpFilter->setMapName($namespaceKey, 'Foo');
        $actual = $jtpFilter($classKey, $fixture);

        $this->assertEquals('Foo', $actual['classNamespace']);
    }

    /**
     * @covers ::renameTypes
     * @uses \Jtp\Filter::__invoke
     * @uses \Jtp\Filter::doRemapping
     * @uses \Jtp\Filter::getMappedName
     */
    public function testCanRenameProperty()
    {
        $jtpFilter = new JtpFilter();
        $fixture = [
            'name' => 'Employees',
            'fullName' => 'Company\\Employees',
            'classNamespace' => 'Company',
            'properties' => [
                ['name' => 'first_name', 'namespace' => 'Company', 'isCustomType' => false]
            ]
        ];

        $jtpFilter->setMapName('Employees::$first_name', 'firstName');
        $actual = $jtpFilter('Employees', $fixture);

        $this->assertEquals('firstName', $actual['properties'][0]['name']);
    }

    /**
     * @covers ::getMappedName
     * @uses \Jtp\Filter::__invoke
     * @uses \Jtp\Filter::doRemapping
     * @uses \Jtp\Filter::renameTypes
     */
    public function testCanRenameFullName()
    {
        $jtpFilter = new JtpFilter();
        $fixture = [
            'name' => 'Bars',
            'fullName' => 'Foo\\Bars',
            'classNamespace' => 'Foo',
            'properties' => []
        ];

        $jtpFilter->setMapName('Bars', 'Bar');
        $data = $jtpFilter('Bars', $fixture);
        $actual = $data['classNamespace'] . '\\' . $data['name'];

        $this->assertEquals('Foo\\Bar', $actual);
    }

    /**
     * @covers ::renameTypes
     * @covers ::getMappedType
     * @uses \Jtp\Filter::__invoke
     * @uses \Jtp\Filter::doRemapping
     * @uses \Jtp\Filter::getMappedName
     */
    public function testWillRenamePropertyArrayType()
    {
        $jtpFilter = new JtpFilter();
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

        $jtpFilter->setMapName('Company\\NEmployee', 'Company');
        $jtpFilter->setMapName('Departments', 'Department');
        $data = $jtpFilter('Employee', $fixture);
        $actual = $data['properties'][0]['arrayType'];

        $this->assertEquals('Company\\Department', $actual);
    }

    /**
     * @covers ::renameTypes
     * @uses \Jtp\Filter::__invoke
     * @uses \Jtp\Filter::doRemapping
     * @uses \Jtp\Filter::getMappedName
     */
    public function testWillRenamePropertyNamespace()
    {
        $jtpFilter = new JtpFilter();
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

        $jtpFilter->setMapName('Company\\NEmployees', 'Company');
        $data = $jtpFilter('Department', $fixture);
        $actual = $data['properties'][0]['namespace'];

        $this->assertEquals('Company', $actual);
    }

    /**
     * @covers ::renameTypes
     * @covers ::getMappedType
     * @uses \Jtp\Filter::__invoke
     * @uses \Jtp\Filter::doRemapping
     * @uses \Jtp\Filter::getMappedName
     */
    public function testWillRenamePropertyParamType()
    {
        $jtpFilter = new JtpFilter();
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

        $jtpFilter->setMapName('Company\\NEmployees', 'Company');
        $data = $jtpFilter('Department', $fixture);
        $actual = $data['properties'][0]['paramType'];

        $this->assertEquals('Company\\Location', $actual);
    }
}
