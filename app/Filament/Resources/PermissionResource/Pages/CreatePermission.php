<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePermission extends CreateRecord
{
    protected static string $resource = PermissionResource::class;

    // إلغاء إشعار Filament الافتراضي (حتى ما يطلع إشعارين)
    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    protected function afterCreate(): void
    {
        $permName = $this->record->name ?? '';

        // إشعار منبثق
        Notification::make()
            ->title('تمت إضافة صلاحية')
            ->body("الصلاحية: {$permName}")
            ->success()
            ->send();

        // تخزين في قاعدة البيانات لواجهة الإشعارات
        if ($user = auth()->user()) {
            Notification::make()
                ->title('تمت إضافة صلاحية جديدة')
                ->body("الصلاحية: {$permName}")
                ->success()
                ->sendToDatabase($user);   // إشعار واحد فقط
        }
    }
}
