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
        'Address_1::$address_line_1' => 'addressLine1',
        'Address_1::$address_line_2' => 'addressLine2',
        'Address_1::$zip_code' => 'zipCode',
        'Departments' => 'Department',
        'Employee::$first_name' => 'firstName',
        'Employee::$last_name' => 'lastName',
// Namespaces
        'Company\\NEmployee\\NLocation' => 'Company\\Employees\\Location',
        'Company\\NEmployee' => 'Company\\Employees',
        'Company\\NEmployee\\NDepartments' => 'Company\\Departments'
    ];

    public function setMapName($key, $value)
    {
        $this->map[$key] = $value;
    }
}