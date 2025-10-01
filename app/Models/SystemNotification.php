<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;

class SystemNotification extends BaseDatabaseNotification
{
    protected $table = 'notifications';
}
