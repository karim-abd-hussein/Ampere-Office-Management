<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Cycle;

class CyclePolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['مشرف', 'admin', 'super-admin']);
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('عرض الدورات');
    }

    public function view(User $user, Cycle $cycle): bool
    {
        return $this->isAdmin($user) || $user->can('عرض الدورات');
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('إضافة الدورات');
    }

    public function update(User $user, Cycle $cycle): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل الدورات');
    }

    public function delete(User $user, Cycle $cycle): bool
    {
        // الحذف مربوط مع تعديل
        return $this->isAdmin($user) || $user->can('تعديل الدورات');
    }

    public function restore(User $user, Cycle $cycle): bool
    {
        return $this->delete($user, $cycle);
    }

    public function forceDelete(User $user, Cycle $cycle): bool
    {
        return $this->delete($user, $cycle);
    }
}
