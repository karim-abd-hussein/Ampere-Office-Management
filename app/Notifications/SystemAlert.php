<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemAlert extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
        public ?string $icon = null,
    ) {
        //
    }

    /**
     * قنوات الإرسال: قاعدة البيانات فقط
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * البيانات التي ستُخزّن في عمود data بجدول notifications
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body'  => $this->body,
            'url'   => $this->url,
            'icon'  => $this->icon,
        ];
    }
}
