<?php

namespace App\Filament\Resources\AreaResource\Pages;

use App\Filament\Resources\AreaResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateArea extends CreateRecord
{
    protected static string $resource = AreaResource::class;

    /** توست واحد فقط */
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('تمت الإضافة')
            ->success();
    }

    /** كتابة إشعار واحد في جدول الإشعارات بعد الإنشاء */
    protected function afterCreate(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        // إدخال سطر في جدول notifications (Database Notification)
        $user->notifications()->create([
            'id'   => (string) Str::uuid(),
            'type' => 'app.area.created',
            'data' => [
                'title' => 'تمت إضافة منطقة جديدة',
                'body'  => $this->record->name,   // اسم المنطقة
            ],
        ]);
    }
}
