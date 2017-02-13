<?php namespace Jtp\Tests\Mocks;

use Jtp\Filter;

/**
 * Class JtpDataMassage
 *
 * @package \Jtp\Tests\Mocks
 */
class JtpFilter extends Filter
{
    protected $classMap = [
        'Location' => 'Location',
        'Location::$department' => 'department',
        'Location::$name' => 'name',
        'Employees' => 'Employees',
        'Employees::$id' => 'id',
        'Employees::$first_name' => 'first_name',
        'Employees::$last_name' => 'last_name',
        'Employees::$location' => 'location',
        'Location_1' => 'Location',
        'Location_1::$type' => 'type',
        'Location_1::$coordinates' => 'coordinates',
        'Location_1::$city' => 'city',
        'Location_1::$state' => 'state',
        'Location_1::$zip_code' => 'zip_code',
        'Categories' => 'Categories',
        'Categories::$id' => 'id',
        'Categories::$name' => 'name',
        'Company' => 'Company',
        'Company::$company' => 'company',
        'Company::$employees' => 'employees',
        'Company::$location' => 'location',
        'Company::$categories' => 'categories',
    ];

    protected $namespaceMap = [
        'Foos\\NCompany\\NEmployees' => 'Foos\\NCompany\\NEmployees',
        'Foos\\NCompany' => 'Foos\\NCompany',
        'Foos' => 'Foos',
    ];

    public function setMapName($key, $value)
    {
        $this->map[$key] = $value;
    }

}