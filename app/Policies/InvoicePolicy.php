<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Invoice;

class InvoicePolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['مشرف', 'admin', 'super-admin']);
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('عرض الفواتير');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->isAdmin($user) || $user->can('عرض الفواتير');
    }

    public function create(User $user): bool
    {
        // نسمح بالإنشاء لمن معه تعديل (ما طلبت صلاحية منفصلة لإضافة الفواتير)
        return $this->isAdmin($user) || $user->can('تعديل الفواتير');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل الفواتير');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل الفواتير');
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $this->delete($user, $invoice);
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $this->delete($user, $invoice);
    }

    /** قدرات مخصصة */
    public function generate(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('توليد الفواتير');
    }

    public function export(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('تصدير الفواتير');
    }

    public function import(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('استيراد الفواتير');
    }
}
