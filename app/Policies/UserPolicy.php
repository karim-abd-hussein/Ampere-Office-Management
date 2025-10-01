<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['مشرف', 'admin', 'super-admin']);
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('عرض الموظفين');
    }

    public function view(User $user, User $model): bool
    {
        return $this->isAdmin($user) || $user->can('عرض الموظفين');
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('إضافة الموظفين');
    }

    public function update(User $user, User $model): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل الموظفين');
    }

    public function delete(User $user, User $model): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل الموظفين');
    }

    public function restore(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }
}
