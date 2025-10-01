<?php

namespace App\Filament\Resources\CycleResource\Pages;

use App\Filament\Resources\CycleResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCycle extends CreateRecord
{
    protected static string $resource = CycleResource::class;

    /**
     * نخصص التوست الذي يعرضه Filament بعد الإنشاء
     * (هيك بطلع Toast واحد فقط).
     */
    protected function getCreatedNotification(): ?Notification
    {
        $code = (string) ($this->record->code ?? '');

        return Notification::make()
            ->success()
            ->title('تمت إضافة دورة جديدة')
            ->body($code !== '' ? "الدورة: {$code}" : null);
    }

    /**
     * نحفظ إشعاراً في قاعدة البيانات فقط (بدون إظهار Toast إضافي).
     */
    protected function afterCreate(): void
    {
        if ($user = auth()->user()) {
            $code = (string) ($this->record->code ?? '');

            Notification::make()
                ->success()
                ->title('تمت إضافة دورة جديدة')
                ->body($code !== '' ? "الدورة: {$code}" : null)
                ->sendToDatabase($user); // لا توجد send() هنا → ما في Toast ثاني
        }
    }
}
