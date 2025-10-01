<?php

namespace App\Filament\Widgets;

use App\Models\Receipt;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class LatestReceipts extends BaseWidget
{
    protected static ?string $heading = 'أحدث الوصولات';
    protected int|string|array $columnSpan = 'full';

    /**
     * لازم يطابق توقيع TableWidget
     *
     * @return Builder|Relation|null
     */
    protected function getTableQuery(): Builder|Relation|null
    {
        return Receipt::query()
            ->with([
                // eager load لتخفيف الاستعلامات
                'invoice:id,subscriber_id,generator_id,final_amount,issued_at',
                'invoice.subscriber:id,name',
                'invoice.generator:id,name',
            ])
            ->latest('issued_at')
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('invoice.subscriber.name')
                ->label('المشترك')
                ->searchable(),

            Tables\Columns\TextColumn::make('invoice.generator.name')
                ->label('المولدة'),

            Tables\Columns\TextColumn::make('invoice.final_amount')
                ->label('المبلغ')
                ->money('SYP', true),

            Tables\Columns\TextColumn::make('issued_at')
                ->label('تاريخ الوصل')
                ->dateTime('Y-m-d H:i'),
        ];
    }
}
