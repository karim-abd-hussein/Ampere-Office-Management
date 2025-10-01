<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['مشرف','admin','super-admin']);
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('عرض الأدوار');
    }

    public function view(User $user, Role $role): bool
    {
        return $this->isAdmin($user) || $user->can('عرض الأدوار');
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('إضافة الأدوار');
    }

    public function update(User $user, Role $role): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل الأدوار');
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل الأدوار');
    }

    public function restore(User $user, Role $role): bool
    {
        return $this->delete($user, $role);
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return $this->delete($user, $role);
    }
}
