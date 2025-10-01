<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Notifications\SystemAlert;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    /**
     * بدّل إشعار الحفظ الافتراضي بإشعار مخصص (Toast واحد فقط).
     */
    protected function getCreatedNotification(): ?Notification
    {
        $name = (string) ($this->record->name ?? '');

        return Notification::make()
            ->title('تمت إضافة شركة')
            ->body("تمت إضافة الشركة: {$name}")
            ->success();
    }

    /**
     * بعد الحفظ: خزّن إشعاراً في قاعدة البيانات (يظهر في قائمة الإشعارات).
     */
    protected function afterCreate(): void
    {
        $name = (string) ($this->record->name ?? '');

        if (auth()->user()) {
            auth()->user()->notify(
                new SystemAlert('تمت إضافة شركة', "تمت إضافة الشركة: {$name}")
            );
        }
    }
}
