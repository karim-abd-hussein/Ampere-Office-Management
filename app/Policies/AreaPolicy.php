<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\User;

class AreaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('عرض المناطق');
    }

    public function view(User $user, Area $area): bool
    {
        return $user->can('عرض المناطق');
    }

    public function create(User $user): bool
    {
        return $user->can('إضافة المناطق');
    }

    public function update(User $user, Area $area): bool
    {
        return $user->can('تعديل المناطق');
    }

    public function delete(User $user, Area $area): bool
    {
        // عادةً نربط الحذف مع "تعديل المناطق" أو صلاحية مستقلة إن وُجدت
        return $user->can('تعديل المناطق') || $user->can('حذف المناطق');
    }

    public function deleteAny(User $user): bool
    {
        return $this->delete($user, new Area());
    }
}
