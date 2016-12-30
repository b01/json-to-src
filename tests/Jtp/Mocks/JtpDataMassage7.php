<?php

use Jtp\TemplateDataMassage;

/**
 * Class JtpDataMassage
 *
 * @package \Jtp\Tests\Mocks
 */
class JtpDataMassage extends TemplateDataMassage
{
    protected $classMap = [
        'Address' => 'Address',
        'Address::$row' => 'row',
        'Address::$seat' => 'seat',
        'Location' => 'Location',
        'Location::$building' => 'building',
        'Location::$floor' => 'floor',
        'Location::$address' => 'address',
        'Location_1' => 'Location',
        'Location_1::$type' => 'type',
        'Location_1::$coordinates' => 'coordinates',
        'Address_1' => 'Address',
        'Address_1::$address_line_1' => 'addressLine1',
        'Address_1::$address_line_2' => 'addressLine2',
        'Address_1::$city' => 'city',
        'Address_1::$state' => 'state',
        'Address_1::$zip_code' => 'zipCode',
        'Departments' => 'Department',
        'Departments::$id' => 'id',
        'Departments::$name' => 'name',
        'Departments::$current' => 'current',
        'Departments::$location' => 'location',
        'Departments::$address' => 'address',
        'Employee' => 'Employee',
        'Employee::$id' => 'id',
        'Employee::$first_name' => 'firstName',
        'Employee::$last_name' => 'lastName',
        'Employee::$notes' => 'notes',
        'Employee::$salary' => 'salary',
        'Employee::$commission' => 'commission',
        'Employee::$location' => 'location',
        'Employee::$departments' => 'departments',
    ];

    protected $namespaceMap = [
        'Company\NEmployee\NLocation' => 'Company\Employees\Locations',
        'Company\NEmployee' => 'Company\Employees',
        'Company\NEmployee\NDepartments' => 'Company\Employees\Departments',
        'Company' => 'Company',
    ];

    public function setClassMapKey($key, $value)
    {
        $this->classMap[$key] = $value;
    }

    public function setNamespaceMapKey($key, $value)
    {
        $this->namespaceMap[$key] = $value;
    }

}