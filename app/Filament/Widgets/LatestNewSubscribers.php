<?php

namespace App\Filament\Widgets;

use App\Models\Subscriber;
use App\Models\Generator;
use App\Models\Area;
use App\Models\Cycle;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Filament\Forms\Components\Select;

class LatestNewSubscribers extends BaseWidget
{
    protected static ?string $heading = 'آخر المشتركين الجدد';
    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder|Relation|null
    {
        return Subscriber::query()
            ->with([
                'generator:id,name,area_id',
                'generator.area:id,name',
            ])
            ->latest('subscription_date');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label('رقم المشترك')
                ->sortable(),

            Tables\Columns\TextColumn::make('name')
                ->label('الاسم')
                ->searchable()
                ->wrap(),

            Tables\Columns\TextColumn::make('generator.name')
                ->label('المولّدة')
                ->sortable()
                ->toggleable(),

            Tables\Columns\TextColumn::make('generator.area.name')
                ->label('المنطقة')
                ->sortable()
                ->toggleable(),

            Tables\Columns\TextColumn::make('meter_number')
                ->label('رقم العداد')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('subscription_date')
                ->label('تاريخ الاشتراك')
                ->date('Y-m-d')
                ->sortable(),
        ];
    }

  protected function getTableFilters(): array
{
    return [
        Tables\Filters\Filter::make('period')
            ->label('الفترة')
            ->form([
                Select::make('range')
                    ->label('الفترة')
                    ->options([
                        'week'  => 'هذا الأسبوع',
                        'month' => 'هذا الشهر',
                        'year'  => 'هذه السنة',
                    ])
                    ->native(false),
            ])
            ->query(function (Builder $query, array $data) {
                $range = $data['range'] ?? null;
                if (! $range) return $query;

                $start = match ($range) {
                    'week'  => \Illuminate\Support\Carbon::now()->startOfWeek(),
                    'month' => \Illuminate\Support\Carbon::now()->startOfMonth(),
                    'year'  => \Illuminate\Support\Carbon::now()->startOfYear(),
                    default => null,
                };

                if ($start) {
                    $query->whereDate('subscription_date', '>=', $start->toDateString());
                }

                return $query;
            })
            ->indicateUsing(fn (array $data) => match ($data['range'] ?? null) {
                'week'  => 'الفترة: هذا الأسبوع',
                'month' => 'الفترة: هذا الشهر',
                'year'  => 'الفترة: هذه السنة',
                default => null,
            }),

        Tables\Filters\SelectFilter::make('cycle_id')
            ->label('حسب الدورة')
            ->options(fn () => \App\Models\Cycle::query()
                ->orderByDesc('start_date')
                ->get(['id', 'start_date'])
                ->mapWithKeys(fn ($c) => [$c->id => $c->code])
                ->all()
            )
            ->placeholder('الكل')
            ->preload()
            ->searchable()
            ->query(function (Builder $query, array $data) {
                $val = $data['value'] ?? null;
                if (! $val) return $query;

                return $query->whereExists(function ($q) use ($val) {
                    $q->selectRaw('1')
                      ->from('invoices')
                      ->whereColumn('invoices.subscriber_id', 'subscribers.id')
                      ->where('invoices.cycle_id', (int) $val);
                });
            }),

        // باقي فلاتر المولدة والمنطقة كما هي…
    ];
}

}
