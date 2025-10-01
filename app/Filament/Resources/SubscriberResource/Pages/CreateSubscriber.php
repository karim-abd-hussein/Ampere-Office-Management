<?php

namespace App\Filament\Resources\SubscriberResource\Pages;

use App\Filament\Resources\SubscriberResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateSubscriber extends CreateRecord
{
    protected static string $resource = SubscriberResource::class;

    /**
     * Toast واحد مخصص بعد الإضافة.
     */
    protected function getCreatedNotification(): ?Notification
    {
        $name = (string) ($this->record->name ?? '');

        return Notification::make()
            ->title('تمت إضافة زبون')
            ->body("تمت إضافة الزبون: {$name}")
            ->success();
    }

    /**
     * خزّن إشعارًا في قاعدة البيانات يظهر في واجهة الإشعارات.
     */
    protected function afterCreate(): void
    {
        $name = (string) ($this->record->name ?? '');

        if ($user = auth()->user()) {
            Notification::make()
                ->title('زبون جديد')
                ->body("تمت إضافة الزبون: {$name}")
                ->success()
                ->sendToDatabase($user);
        }
    }
}
