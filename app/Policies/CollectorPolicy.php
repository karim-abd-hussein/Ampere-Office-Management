<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Collector;

class CollectorPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['مشرف', 'admin', 'super-admin']);
    }

    /** عرض القائمة */
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('عرض الجباة');
    }

    /** عرض سجل واحد */
    public function view(User $user, Collector $collector): bool
    {
        return $this->isAdmin($user) || $user->can('عرض الجباة');
    }

    /** إنشاء */
    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('إضافة الجباة');
    }

    /** تعديل */
    public function update(User $user, Collector $collector): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل الجباة');
    }

    /** حذف (نربطه مع تعديل) */
    public function delete(User $user, Collector $collector): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل الجباة');
    }

    public function restore(User $user, Collector $collector): bool
    {
        return $this->delete($user, $collector);
    }

    public function forceDelete(User $user, Collector $collector): bool
    {
        return $this->delete($user, $collector);
    }
}
