<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class CacheTranslations extends Command
{
    protected $signature = 'app:cache-translations';
    protected $description = 'Cache translation files for faster access';

    public function handle(): void
    {
        $locale = app()->getLocale();
        $langPath = resource_path("lang/{$locale}");

        if (!File::exists($langPath)) {
            $this->error("Language path [{$langPath}] not found.");
            return;
        }

        $translations = [];

        foreach (File::allFiles($langPath) as $file) {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            $translations[$filename] = File::getRequire($file);
        }

        Cache::put("translations.{$locale}", $translations);

        $this->info("Translations for '{$locale}' have been cached.");
    }
}
