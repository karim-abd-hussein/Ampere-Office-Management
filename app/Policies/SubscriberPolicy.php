<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Subscriber;

class SubscriberPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['مشرف', 'admin', 'super-admin']);
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('عرض المشتركين');
    }

    public function view(User $user, Subscriber $subscriber): bool
    {
        return $this->isAdmin($user) || $user->can('عرض المشتركين');
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('اضافة المشتركين');
    }

    public function update(User $user, Subscriber $subscriber): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل المشتركين');
    }

    public function delete(User $user, Subscriber $subscriber): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل المشتركين');
    }

    public function restore(User $user, Subscriber $subscriber): bool
    {
        return $this->delete($user, $subscriber);
    }

    public function forceDelete(User $user, Subscriber $subscriber): bool
    {
        return $this->delete($user, $subscriber);
    }

    /** مخصص: استيراد مشتركين */
    public function import(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('استيراد المشتركين');
    }
}
