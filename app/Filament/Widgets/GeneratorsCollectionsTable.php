<?php

namespace App\Filament\Widgets;

use App\Models\Cycle;
use App\Models\Generator;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\Summarizers\Sum;

class GeneratorsCollectionsTable extends BaseWidget
{
    protected static ?string $heading = 'تحصيل كل مولّدة';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        // الاعتماد فقط على الفواتير (بدون أي اشتراط لوجود وصول)
        $baseQuery = Generator::query()
            ->select([
                'generators.id',
                'generators.name',
                'generators.status',
                'generators.is_active',
            ])
            ->leftJoin('invoices', 'invoices.generator_id', '=', 'generators.id')
            ->groupBy('generators.id', 'generators.name', 'generators.status', 'generators.is_active')
            ->selectRaw('COALESCE(SUM(invoices.consumption), 0) AS consumption_total')
            ->selectRaw('COALESCE(SUM(invoices.final_amount), 0) AS collected_total');

        $ACTIVE_WORDS = ['active','on','running','enabled','1','true','yes'];

        return $table
            ->query($baseQuery)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('المولّدة')
                    ->searchable()
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->label('الملخص')
                            ->using(fn () => null),
                    ]),

                Tables\Columns\TextColumn::make('status_raw')
                    ->label('الحالة')
                    ->getStateUsing(function (Generator $record) use ($ACTIVE_WORDS) {
                        $flagActive   = (int) ($record->is_active ?? 0) === 1;
                        $statusActive = in_array(strtolower((string) $record->status), $ACTIVE_WORDS, true);

                        if (!$flagActive) return 'inactive';
                        if (!is_null($record->status) && !$statusActive) return 'inactive';
                        return 'active';
                    })
                    ->formatStateUsing(fn (string $state) => $state === 'active' ? 'نشطة' : 'غير نشطة')
                    ->badge()
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('consumption_total')
                    ->label('الاستهلاك (ك.و.س)')
                    ->alignCenter()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0))
                    ->summarize([
                        Sum::make()
                            ->label('')
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0)),
                    ]),

                Tables\Columns\TextColumn::make('collected_total')
                    ->label('التحصيل')
                    ->alignCenter()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0))
                    ->summarize([
                        Sum::make()
                            ->label('')
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0)),
                    ]),
            ])
            ->filters([
                // الفترة من تاريخ الفاتورة issued_at (فواتير فقط)
                Tables\Filters\SelectFilter::make('period')
                    ->label('الفترة')
                    ->options([
                        'month' => 'هذا الشهر',
                        'week'  => 'هذا الأسبوع',
                        'year'  => 'هذه السنة',
                        'all'   => 'الكل',
                    ])
                    ->default('all')
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? 'all';
                        if ($value === 'all') return $query;

                        $now = Carbon::now();
                        [$from, $to] = match ($value) {
                            'week'  => [$now->copy()->startOfWeek(),  $now->copy()->endOfWeek()],
                            'year'  => [$now->copy()->startOfYear(),  $now->copy()->endOfYear()],
                            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
                        };

                        // ملاحظة: هذا الشرط سيُظهر فقط المولدات التي لديها فواتير ضمن الفترة
                        return $query->whereBetween('invoices.issued_at', [$from, $to]);
                    }),

                Tables\Filters\SelectFilter::make('cycle_id')
                    ->label('الدورة')
                    ->options(fn () => Cycle::query()
                        ->orderByDesc('start_date')
                        ->get()
                        ->mapWithKeys(fn ($c) => [$c->id => ($c->code ?? ('دورة #' . $c->id))])
                        ->all()
                    )
                    ->searchable()
                    ->preload()
                    ->placeholder('كل الدورات')
                    ->query(fn (Builder $query, array $data) =>
                        !empty($data['value'])
                            ? $query->where('invoices.cycle_id', (int) $data['value'])
                            : $query
                    ),

                Tables\Filters\SelectFilter::make('g_status')
                    ->label('الحالة')
                    ->options([
                        'active'   => 'نشطة',
                        'inactive' => 'غير نشطة',
                    ])
                    ->placeholder('كل الحالات')
                    ->query(function (Builder $query, array $data) use ($ACTIVE_WORDS) {
                        $val = $data['value'] ?? null;
                        if (!$val) return $query;

                        if ($val === 'active') {
                            return $query->where('generators.is_active', 1)
                                ->where(function ($qq) use ($ACTIVE_WORDS) {
                                    $qq->whereNull('generators.status')
                                       ->orWhereIn(DB::raw('LOWER(generators.status)'), $ACTIVE_WORDS);
                                });
                        }

                        return $query->where(function ($q) use ($ACTIVE_WORDS) {
                            $q->where('generators.is_active', '!=', 1)
                              ->orWhere(function ($qq) use ($ACTIVE_WORDS) {
                                  $qq->whereNotNull('generators.status')
                                     ->whereNotIn(DB::raw('LOWER(generators.status)'), $ACTIVE_WORDS);
                              });
                        });
                    }),
            ])
            ->defaultSort('collected_total', 'desc')
            ->searchDebounce(500)
            ->paginated([10, 25, 50])
            ->striped();
    }
}
