<?php
namespace App\Services;
use App\Models\Employee;
use App\Models\Company;
use App\Models\Branch;
class Access
{
    public static function employees()
    {
        $query = Employee::query();
        $user = auth()->user();
        if (!$user->isSuperAdmin()) $query->where('company_id', $user->company_id);
        if ($user->isManager()) $query->where('branch_id', $user->branch_id);
        return $query;
    }
    public static function companies()
    {
        $user = auth()->user();
        return $user->isSuperAdmin() ? Company::query() : Company::whereKey($user->company_id);
    }
    public static function branches($companyId = null)
    {
        $user = auth()->user();
        $query = Branch::query();
        if ($user->isSuperAdmin()) { if ($companyId) $query->where('company_id', $companyId); return $query; }
        $query->where('company_id', $user->company_id);
        if ($user->isManager()) $query->whereKey($user->branch_id);
        return $query;
    }
    public static function employee(Employee $employee) { abort_unless(auth()->user()->canAccessCompany($employee->company_id) && auth()->user()->canAccessBranch($employee->branch_id), 403); }
    public static function company($id) { abort_unless(auth()->user()->canAccessCompany($id), 403); }
    public static function branch($id, $companyId = null) { if(!$id){abort_unless(auth()->user()->isSuperAdmin(),403);return null;} $branch = Branch::findOrFail($id); abort_unless(auth()->user()->canAccessBranch($branch->id) && (!$companyId || (int)$branch->company_id === (int)$companyId), 403); return $branch; }
}
