<?php

namespace App\Filament\Widgets;

use App\Models\Subscriber;
use App\Models\Invoice;
use App\Models\Generator;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Facades\DB;

class DashboardStats extends BaseWidget
{
    protected ?string $heading = 'ملخّص سريع';
    protected static ?string $pollingInterval = '60s';

    protected function getCards(): array
    {
        try {
            $now         = now();
            $startOfWeek = $now->copy()->startOfWeek();

            $newSubsThisMonth = Subscriber::whereBetween('subscription_date', [
                $now->copy()->startOfMonth(), $now,
            ])->count();

            $invoicesThisWeek = Invoice::whereBetween('issued_at', [
                $startOfWeek, $now,
            ])->count();

            // التحصيل هذا الأسبوع من الفواتير فقط
            $collectedThisWeek = Invoice::query()
                ->whereBetween('issued_at', [$startOfWeek, $now])
                ->sum('final_amount');

            $ACTIVE_WORDS = ['active','on','running','enabled','1','true','yes'];

            $activeGenerators = Generator::query()
                ->where('is_active', 1)
                ->where(function ($q) use ($ACTIVE_WORDS) {
                    $q->whereNull('status')
                      ->orWhereIn(DB::raw('LOWER(status)'), $ACTIVE_WORDS);
                })
                ->count();

            return [
                Card::make('مشتركين جدد هذا الشهر', number_format($newSubsThisMonth))
                    ->icon('heroicon-o-user-group'),

                Card::make('فواتير هذا الأسبوع', number_format($invoicesThisWeek))
                    ->icon('heroicon-o-document-text'),

                Card::make('تحصيل هذا الأسبوع', number_format((float) $collectedThisWeek, 0))
                    ->description('ل.س')
                    ->icon('heroicon-o-banknotes'),

                Card::make('مولدات نشطة', number_format($activeGenerators))
                    ->icon('heroicon-o-bolt'),
            ];
        } catch (\Throwable $e) {
            return [
                Card::make('خطأ في لوحة الإحصائيات', '—')
                    ->description($e->getMessage())
                    ->icon('heroicon-o-exclamation-triangle'),
            ];
        }
    }
}
