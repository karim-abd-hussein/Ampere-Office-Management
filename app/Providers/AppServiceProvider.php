<?php

namespace App\Providers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    app()->setLocale('ar');

      if (app()->environment('production')) {
        // كاش للأدوار
        Cache::rememberForever('roles_translations', function () {
            return File::exists(lang_path('ar/roles.php'))
                ? include lang_path('ar/roles.php')
                : [];
        });

        // كاش للصلاحيات
        Cache::rememberForever('permissions_translations', function () {
            return File::exists(lang_path('ar/permissions.php'))
                ? include lang_path('ar/permissions.php')
                : [];
        });
    }
    }
}
