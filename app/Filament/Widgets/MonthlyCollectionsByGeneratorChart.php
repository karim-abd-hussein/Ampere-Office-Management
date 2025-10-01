<?php

namespace App\Filament\Widgets;

use App\Models\Generator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class MonthlyCollectionsByGeneratorChart extends ChartWidget
{
    protected static ?string $heading = 'التحصيل الشهري لكل مولدة (آخر 6 أشهر)';
    protected static ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $start = now()->copy()->subMonths(5)->startOfMonth();
        $end   = now()->copy()->endOfMonth();

        // labels: YYYY-MM لآخر 6 أشهر (مع الحالي)
        $labels = [];
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $labels[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        // كاش بسيط 60 ثانية
        $cacheKey = 'chart:collections:6m:invoices:v2:' . $start->format('Y-m') . ':' . $end->format('Y-m');

        $rows = Cache::remember($cacheKey, 60, function () use ($start, $end) {
            return DB::table('invoices')
                ->selectRaw('generator_id, YEAR(issued_at) as y, MONTH(issued_at) as m, SUM(final_amount) as total')
                ->whereBetween('issued_at', [$start, $end])
                ->groupBy('generator_id', 'y', 'm')
                ->orderBy('y')->orderBy('m')
                ->get();
        });

        // group: [generator_id][YYYY-MM] => total
        $grouped = [];
        foreach ($rows as $r) {
            $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            $grouped[$r->generator_id][$key] = (float) $r->total;
        }

        $generators = Generator::query()->pluck('name', 'id')->toArray();

        $datasets = [];
        foreach ($generators as $gid => $gname) {
            $values = [];
            foreach ($labels as $ym) {
                $values[] = isset($grouped[$gid][$ym]) ? (float) $grouped[$gid][$ym] : 0.0;
            }
            if (array_sum($values) > 0) {
                $datasets[] = [
                    'label' => $gname,
                    'data'  => $values,
                ];
            }
        }

        return [
            'datasets' => $datasets,
            'labels'   => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
