<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use Filament\Support\Colors\Color;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('مكتب الأمبير')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                \App\Filament\Widgets\DashboardStats::class,
                \App\Filament\Widgets\GeneratorsCollectionsTable::class,
                \App\Filament\Widgets\LatestNewSubscribers::class,
                \App\Filament\Widgets\MonthlyCollectionsByGeneratorChart::class,
                \App\Filament\Widgets\MonthlyNewSubscribersChart::class,
            ])

            // زر المزامنة في التوب بار (قبل جرس الإشعارات)
            ->renderHook('panels::topbar.end', function () {
                $mounted = \Livewire\Livewire::mount('topbar.sync-button');
                $html = is_string($mounted) ? $mounted : $mounted->html();

                if (view()->exists('filament.topbar.notifications')) {
                    $html .= view('filament.topbar.notifications')->render();
                }

                return $html;
            })

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\EnsureCanViewDashboard::class,

                // ✅ تشغيل النسخ اليومي تلقائيًا على أول زيارة كل يوم
                \App\Http\Middleware\AutoDailyBackup::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
