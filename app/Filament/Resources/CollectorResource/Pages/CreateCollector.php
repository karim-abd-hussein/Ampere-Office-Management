<?php

namespace App\Filament\Resources\CollectorResource\Pages;

use App\Filament\Resources\CollectorResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCollector extends CreateRecord
{
    protected static string $resource = CollectorResource::class;

    /** إشعار واحد فقط يُحفظ في صفحة الإشعارات */
    protected function afterCreate(): void
    {
        if ($user = auth()->user()) {
            Notification::make()
                ->title('تمت إضافة جابي')
                ->body('الاسم: ' . ($this->record->name ?? ''))
                ->success()
                ->sendToDatabase($user);   // ✅ لا نستدعي ->send() حتى ما يصير إشعارين
        }
    }
}
