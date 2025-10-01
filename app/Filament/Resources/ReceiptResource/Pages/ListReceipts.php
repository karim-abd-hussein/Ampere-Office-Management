<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use App\Models\Cycle;
use App\Models\Generator;
use App\Models\Collector;
use App\Models\Invoice;
use App\Models\Receipt;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListReceipts extends ListRecords
{
    protected static string $resource = ReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generateForCycle')
                ->label('توليد وصولات للدورة')
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->visible(fn () => ReceiptResource::canGenerate())
                ->modalHeading('توليد وصولات')
                ->form([
                    Select::make('cycle_id')
                        ->label('اختر الدورة')
                        ->options(fn () => Cycle::query()
                            ->orderByDesc('start_date')
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => $c->code])
                            ->all())
                        ->searchable()
                        ->required()
                        ->live(),

                    Select::make('generator_ids')
                        ->label('المولّدة (اختياري)')
                        ->options(fn () => Generator::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->preload()->multiple()->live(),

                    Select::make('collector_ids')
                        ->label('الجابي (اختياري)')
                        ->options(fn () => Collector::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->preload()->multiple()->live(),

                    Select::make('subscriber_statuses')
                        ->label('حالة المشترك (اختياري)')
                        ->options([
                            'active'       => 'فعال',
                            'disconnected' => 'مفصول',
                            'cancelled'    => 'ملغى',
                        ])->placeholder('كل الحالات')->multiple()->preload()->live(),

                    TextInput::make('count')
                        ->label('كم وصل بدّك؟')
                        ->numeric()->default(10)->minValue(1)->required()
                        ->helperText(function (Get $get) {
                            $available = $this->availableCount($get);
                            return $available === null ? 'اختر دورة أولًا' : "المتاح حاليًا للطباعة: {$available}";
                        })
                        ->rules([
                            'integer', 'min:1',
                            function (Get $get) {
                                $available = $this->availableCount($get);
                                $max = $available === null ? 1 : max(1, (int)$available);
                                return 'max:' . $max;
                            },
                        ]),

                    TextInput::make('copies')
                        ->label('عدد النسخ لكل وصل (التكرار)')
                        ->numeric()->default(1)->minValue(1)->maxValue(10)->required(),
                ])
                ->action(function (array $data) {
                    $cycleId      = (int) $data['cycle_id'];
                    $count        = (int) $data['count'];
                    $generatorIds = collect($data['generator_ids'] ?? [])->filter()->map(fn ($v) => (int) $v)->all();
                    $collectorIds = collect($data['collector_ids'] ?? [])->filter()->map(fn ($v) => (int) $v)->all();
                    $statuses     = collect($data['subscriber_statuses'] ?? [])->filter()->values()->all();
                    $copies       = (int) ($data['copies'] ?? 1);

                    $created = 0;
                    $newIds  = [];

                    DB::transaction(function () use ($cycleId, $count, $generatorIds, $collectorIds, $statuses, &$created, &$newIds) {
                        $invoices = Invoice::query()
                            ->where('cycle_id', $cycleId)
                            ->when(!empty($generatorIds), fn ($q) => $q->whereHas('subscriber', fn ($qq) => $qq->whereIn('generator_id', $generatorIds)))
                            ->when(!empty($collectorIds), fn ($q) => $q->whereHas('subscriber.generator.collectors', fn ($qq) => $qq->whereIn('collectors.id', $collectorIds)))
                            ->when(!empty($statuses), fn ($q) => $q->whereHas('subscriber', fn ($qq) => $qq->whereIn('status', $statuses)))
                            ->whereDoesntHave('receipts')
                            ->orderBy('id')
                            ->limit($count)
                            ->get();

                        foreach ($invoices as $inv) {
                            $r = Receipt::create([
                                'invoice_id' => $inv->id,
                                'type'       => 'user',
                                'issued_at'  => now(),
                            ]);
                            $newIds[] = $r->id;
                            $created++;
                        }
                    });

                    Notification::make()
                        ->title("تم توليد {$created} وصل.")
                        ->success()
                        ->send();

                    if ($user = auth()->user()) {
                        Notification::make()
                            ->title('توليد وصولات')
                            ->body("تم توليد {$created} وصل للدورة المحددة.")
                            ->success()
                            ->sendToDatabase($user);
                    }

                    if (! empty($newIds)) {
                        $url = route('receipts.print', ['ids' => implode(',', $newIds), 'copies' => $copies]);
                        return redirect()->to($url);
                    }
                }),
        ];
    }

    /** حساب المتاح وفق الفلاتر */
    protected function availableCount(Get $get): ?int
    {
        $cycleId = (int) ($get('cycle_id') ?? 0);
        if (! $cycleId) return null;

        $generatorIds = array_map('intval', (array) ($get('generator_ids') ?? []));
        $collectorIds = array_map('intval', (array) ($get('collector_ids') ?? []));
        $statuses     = array_values(array_filter((array) ($get('subscriber_statuses') ?? [])));

        $query = Invoice::query()
            ->where('cycle_id', $cycleId)
            ->when(!empty($generatorIds), fn ($q) => $q->whereHas('subscriber', fn ($qq) => $qq->whereIn('generator_id', $generatorIds)))
            ->when(!empty($collectorIds), fn ($q) => $q->whereHas('subscriber.generator.collectors', fn ($qq) => $qq->whereIn('collectors.id', $collectorIds)))
            ->when(!empty($statuses), fn ($q) => $q->whereHas('subscriber', fn ($qq) => $qq->whereIn('status', $statuses)))
            ->whereDoesntHave('receipts');

        return (int) $query->count();
    }

    /** أزرار الجدول (صف واحد) */
    protected function getTableActions(): array
    {
        return [
            \Filament\Tables\Actions\Action::make('printOne')
                ->label('طباعة')
                ->icon('heroicon-o-printer')
                ->visible(fn () => ReceiptResource::canPrint())
                ->form([
                    TextInput::make('copies')->label('عدد النسخ')->numeric()->default(1)->minValue(1)->maxValue(10)->required(),
                ])
                ->action(function ($record, array $data) {
                    $url = route('receipts.print', ['ids' => $record->id, 'copies' => (int) $data['copies']]);
                    return redirect()->to($url);
                }),
        ];
    }

    /** أزرار مجمّعة */
    protected function getTableBulkActions(): array
    {
        return [
            \Filament\Tables\Actions\BulkAction::make('printSelected')
                ->label('طباعة المحدّد')
                ->icon('heroicon-o-printer')
                ->visible(fn () => ReceiptResource::canPrint())
                ->form([
                    TextInput::make('copies')->label('عدد النسخ')->numeric()->default(1)->minValue(1)->maxValue(10)->required(),
                ])
                ->action(function (array $records, array $data) {
                    $ids = collect($records)->pluck('id')->implode(',');
                    $url = route('receipts.print', ['ids' => $ids, 'copies' => (int) $data['copies']]);
                    return redirect()->to($url);
                }),

            \Filament\Tables\Actions\DeleteBulkAction::make()
                ->label('حذف جماعي')
                ->visible(fn () => ReceiptResource::canDeleteAny()),
        ];
    }
}
