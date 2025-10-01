<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Receipt;

class ReceiptPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['مشرف', 'admin', 'super-admin']);
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('عرض الوصولات');
    }

    public function view(User $user, Receipt $receipt): bool
    {
        return $this->isAdmin($user) || $user->can('عرض الوصولات');
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('إضافة الوصولات');
    }

    public function update(User $user, Receipt $receipt): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل الوصولات');
    }

    public function delete(User $user, Receipt $receipt): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل الوصولات');
    }

    public function restore(User $user, Receipt $receipt): bool
    {
        return $this->delete($user, $receipt);
    }

    public function forceDelete(User $user, Receipt $receipt): bool
    {
        return $this->delete($user, $receipt);
    }

    /** قدرات مخصصة */
    public function print(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('طباعة الوصولات');
    }

    public function generate(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('توليد الوصولات');
    }
}
