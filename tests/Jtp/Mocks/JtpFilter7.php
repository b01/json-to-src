<?php

use Jtp\Filter;

/**
 * Class JtpDataMassage
 *
 * @package \Jtp\Tests\Mocks
 */
class JtpFilter extends Filter
{
    protected $map = [
        'Address' => 'Address',
        'Location_1' => 'Location1',
        'Address_1' => 'Address1',
        'Address_1::$address_line_1' => 'addressLine1',
        'Address_1::$address_line_2' => 'addressLine2',
        'Address_1::$zip_code' => 'zipCode',
        'Departments' => 'Department',
        'Employee::$first_name' => 'firstName',
        'Employee::$last_name' => 'lastName',
// Namespaces
        'Company\\NEmployee\\NLocation' => 'Company\\Employees\\Locations',
        'Company\\NEmployee\\NDepartments' => 'Company\\Departments',
        'Company\\NEmployee' => 'Company\\Employees',
    ];

    public function setMapName($key, $value)
    {
        $this->map[$key] = $value;
    }
}