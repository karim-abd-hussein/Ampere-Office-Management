<?php

namespace App\Policies;

use App\Models\User;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('عرض الصلاحيات');
    }

    public function view(User $user): bool
    {
        return $user->can('عرض الصلاحيات');
    }

    public function create(User $user): bool
    {
        return $user->can('إضافة الصلاحيات');
    }

    public function update(User $user): bool
    {
        return $user->can('تعديل الصلاحيات');
    }

    public function delete(User $user): bool
    {
        return $user->can('تعديل الصلاحيات');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('تعديل الصلاحيات');
    }
}
