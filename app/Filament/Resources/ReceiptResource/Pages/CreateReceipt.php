<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateReceipt extends CreateRecord
{
    protected static string $resource = ReceiptResource::class;

    // Toast بعد الإنشاء
    protected function getCreatedNotification(): ?Notification
    {
        $r = $this->record;
        $name = optional($r->invoice?->subscriber)->name;
        return Notification::make()
            ->title('تم إنشاء وصل')
            ->body("رقم الوصل: {$r->id}" . ($name ? " — المشترك: {$name}" : ''))
            ->success();
    }

    // إشعار يُخزَّن في قاعدة البيانات
    protected function afterCreate(): void
    {
        $r = $this->record;
        if ($user = auth()->user()) {
            Notification::make()
                ->title('وصل جديد')
                ->body("تم إنشاء وصل رقم {$r->id}".(optional($r->invoice?->subscriber)->name ? " للمشترك: {$r->invoice->subscriber->name}" : ''))
                ->success()
                ->sendToDatabase($user);
        }
    }
}
