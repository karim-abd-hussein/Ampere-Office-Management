<?php

namespace App\Filament\Widgets;

use App\Models\Cycle;
use App\Models\Generator;
use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class MonthlyNewSubscribersChart extends ChartWidget
{
    // عنوان جديد
    protected static ?string $heading = 'الاستهلاك حسب الدورات (آخر 12 دورة)';
    protected static ?string $maxHeight = '260px';

    /** فلتر المولّدات (نفس السابق) */
    public ?string $filter = 'all';

    protected function getFilters(): ?array
    {
        return ['all' => 'كل المولدات']
            + Generator::query()->orderBy('name')->pluck('name', 'id')->toArray();
    }

    protected function getData(): array
    {
        // آخر 12 دورة مرتّبة تصاعدياً للعرض
        $cycles = Cycle::query()
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->limit(12)
            ->get()
            ->reverse(); // حتى يظهر الأقدم أولاً على المحور X

        if ($cycles->isEmpty()) {
            return [
                'datasets' => [[ 'label' => 'الاستهلاك (ك.و)', 'data' => [] ]],
                'labels'   => [],
            ];
        }

        $labels   = $cycles->map(fn ($c) => $c->code)->values()->all();
        $cycleIds = $cycles->pluck('id')->all();

        // كاش بسيط على أساس آخر دورة ومعرّف الفلتر
        $cacheKey = 'chart:cycles-consumption:' . ($this->filter ?? 'all') . ':' . max($cycleIds);

        $totals = Cache::remember($cacheKey, 60, function () use ($cycleIds) {
            return Invoice::query()
                ->selectRaw('cycle_id, SUM(consumption) as total')
                ->whereIn('cycle_id', $cycleIds)
                ->when($this->filter !== 'all', fn ($q) => $q->where('generator_id', $this->filter))
                ->groupBy('cycle_id')
                ->pluck('total', 'cycle_id')
                ->toArray();
        });

        // البيانات بنفس ترتيب اللّيبلز
        $data = [];
        foreach ($cycleIds as $cid) {
            $data[] = (int) ($totals[$cid] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label'   => 'الاستهلاك (ك.و)',
                    'data'    => $data,
                    'tension' => 0.3, // نفس سلوك الخط السابق
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line'; // بدّك ياها أعمدة؟ قلّي وببدّلها لـ 'bar'
    }
} 