<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AutoDailyBackup
{
    public function handle($request, Closure $next)
    {
        // شغّل بس على صفحات الويب (لوحة التحكم) وبوقت هادئ
        if ($this->shouldRun()) {
            $this->runOncePerDay();
        }

        return $next($request);
    }

    protected function shouldRun(): bool
    {
        // نفّذ بعد الساعة 02:00 صباحًا
        return now()->greaterThanOrEqualTo(now()->copy()->startOfDay()->addHours(2));
    }

    protected function runOncePerDay(): void
    {
        $last = Cache::get('auto_backup_last_at');

        // إذا مْنَفَّذ اليوم، خلاص
        if ($last && Carbon::parse($last)->isSameDay(now())) {
            return;
        }

        // قفل بسيط لمنع التوازي (ينتهي خلال 30 دقيقة)
        if (! Cache::add('auto_backup_running', 1, 1800)) {
            return;
        }

        try {
            // شغّل النسخ (ملفات + DB) -> ZIP داخل storage/app/backups
            Artisan::call('backup:run');

            Cache::put('auto_backup_last_at', now()->toDateTimeString(), now()->addDays(2));
            Log::info('[AUTO-BACKUP] done at ' . now());
        } catch (\Throwable $e) {
            Log::error('[AUTO-BACKUP] failed: ' . $e->getMessage());
        } finally {
            Cache::forget('auto_backup_running');
        }
    }
}
