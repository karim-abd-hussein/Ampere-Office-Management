<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Generator;

class GeneratorPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['مشرف', 'admin', 'super-admin']);
    }

    /** عرض القائمة */
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('عرض المولدات');
    }

    /** عرض سجل واحد */
    public function view(User $user, Generator $generator): bool
    {
        return $this->isAdmin($user) || $user->can('عرض المولدات');
    }

    /** إنشاء */
    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('إضافة المولدات');
    }

    /** تعديل */
    public function update(User $user, Generator $generator): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل المولدات');
    }

    /** حذف (نربطه مع تعديل) */
    public function delete(User $user, Generator $generator): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل المولدات');
    }

    public function restore(User $user, Generator $generator): bool
    {
        return $this->delete($user, $generator);
    }

    public function forceDelete(User $user, Generator $generator): bool
    {
        return $this->delete($user, $generator);
    }
}
