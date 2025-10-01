<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CompanyInvoice;

class CompanyInvoicePolicy
{
    /** هل هو مدير عام؟ */
    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(['مشرف', 'admin', 'super-admin']);
    }

    /** عرض القائمة */
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('عرض فواتير الشركات');
    }

    /** عرض سجل واحد */
    public function view(User $user, CompanyInvoice $invoice): bool
    {
        return $this->viewAny($user);
    }

    /** إنشاء — غير مستخدمة لهذا المورد */
    public function create(User $user): bool
    {
        return false;
    }

    /** تعديل سطر الفاتورة (الأمبير/سعر الأمبير) */
    public function update(User $user, CompanyInvoice $invoice): bool
    {
        return $this->isAdmin($user) || $user->can('تعديل فواتير الشركات');
    }

    /** حذف */
    public function delete(User $user, CompanyInvoice $invoice): bool
    {
        return $this->update($user, $invoice);
    }

    public function restore(User $user, CompanyInvoice $invoice): bool
    {
        return false;
    }

    public function forceDelete(User $user, CompanyInvoice $invoice): bool
    {
        return false;
    }
}
