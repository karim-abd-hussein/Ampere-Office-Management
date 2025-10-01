<?php

namespace App\Filament\Resources\GeneratorResource\Pages;

use App\Filament\Resources\GeneratorResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateGenerator extends CreateRecord
{
    protected static string $resource = GeneratorResource::class;

    /** ألغِ تنبيه Filament الافتراضي حتى ما يطلع إشعارين */
    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    /** إشعار واحد إلى صفحة الإشعارات */
    protected function afterCreate(): void
    {
        if ($user = auth()->user()) {
            $name = (string) ($this->record->name ?? 'مولدة');
            $code = (string) ($this->record->code ?? '');
            $body = $code !== '' ? "تمت إضافة مولدة جديدة: {$name} (الرمز {$code})." : "تمت إضافة مولدة جديدة: {$name}.";

            Notification::make()
                ->title('تمت إضافة مولدة جديدة')
                ->body($body)
                ->success()
                ->sendToDatabase($user);
        }
    }
}
