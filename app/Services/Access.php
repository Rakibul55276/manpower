<?php
namespace App\Services;
use App\Models\Employee;
use App\Models\Company;
class Access
{
    public static function employees()
    {
        $query = Employee::query();
        if (!auth()->user()->isAdmin()) { $query->whereIn('company_id', auth()->user()->companies()->select('companies.id')); }
        return $query;
    }
    public static function companies()
    {
        return auth()->user()->isAdmin() ? Company::query() : auth()->user()->companies();
    }
    public static function employee(Employee $employee) { abort_unless(auth()->user()->canAccessCompany($employee->company_id), 403); }
    public static function company($id) { abort_unless(auth()->user()->canAccessCompany($id), 403); }
}
