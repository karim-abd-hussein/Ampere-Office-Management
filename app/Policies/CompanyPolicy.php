<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Company;

class CompanyPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['مشرف', 'admin', 'super-admin']);
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('عرض الشركات');
    }

    public function view(User $user, Company $company): bool
    {
        return $this->isAdmin($user) || $user->can('عرض الشركات');
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('إضافة الشركات');
    }

    public function update(User $user, Company $company): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل الشركات');
    }

    public function delete(User $user, Company $company): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل الشركات');
    }

    public function restore(User $user, Company $company): bool
    {
        return $this->delete($user, $company);
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return $this->delete($user, $company);
    }
}
