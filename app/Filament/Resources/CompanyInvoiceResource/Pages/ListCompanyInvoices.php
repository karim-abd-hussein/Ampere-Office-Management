<?php

namespace App\Filament\Resources\CompanyInvoiceResource\Pages;

use App\Filament\Resources\CompanyInvoiceResource;
use App\Models\Company;
use App\Models\CompanyInvoice;
use App\Models\Cycle;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Throwable;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ListCompanyInvoices extends ListRecords
{
    protected static string $resource = CompanyInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // إضافة شركة
            Actions\Action::make('addCompany')
                ->label('إضافة شركة')
                ->icon('heroicon-o-building-office-2')
                ->color('primary')
                ->modalHeading('إضافة شركة')
                ->visible(fn () => CompanyInvoiceResource::allowAddCompany())
                ->form([
                    TextInput::make('name')->label('اسم الشركة')->required(),
                    TextInput::make('phone')->label('رقم الهاتف')->tel(),
                    TextInput::make('ampere')->label('الأمبير')->numeric()->step('0.01')->minValue(0)->default(0),
                    TextInput::make('price_per_amp')->label('سعر الأمبير')->numeric()->step('0.01')->minValue(0)->default(0),
                    TextInput::make('fixed_amount')->label('المبلغ الثابت')->numeric()->step('0.01')->minValue(0)->default(0),
                    Select::make('status')->label('الحالة')->default('active')->required()
                        ->options(['active' => 'فعال', 'disconnected' => 'مفصول', 'cancelled' => 'ملغى']),
                    Textarea::make('notes')->label('ملاحظات')->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        DB::transaction(fn () => Company::create([
                            'name'          => $data['name'],
                            'phone'         => $data['phone'] ?? null,
                            'ampere'        => (float) ($data['ampere'] ?? 0),
                            'price_per_amp' => (float) ($data['price_per_amp'] ?? 0),
                            'fixed_amount'  => (float) ($data['fixed_amount'] ?? 0),
                            'status'        => $data['status'] ?? 'active',
                            'notes'         => $data['notes'] ?? null,
                        ]));

                        Notification::make()->title('تمت إضافة الشركة')->success()->send();
                        $this->dispatch('refresh');
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->title('فشل الإضافة')->body($e->getMessage())->danger()->persistent()->send();
                    }
                })
                ->modalSubmitActionLabel('حفظ')
                ->modalWidth('lg'),

            // توليد فواتير للدورة
            Actions\Action::make('generateForCycle')
                ->label('توليد فواتير للدورة')
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->modalHeading('توليد فواتير الشركات')
                ->visible(fn () => CompanyInvoiceResource::allowGenerate())
                ->form([
                    Select::make('cycle_id')
                        ->label('اختر الدورة')
                        ->options(fn () => Cycle::query()
                            ->orderByDesc('start_date')
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => ($c->code ?? ($c->start_date?->format('Y-m-d') ?? ('#' . $c->id)))] )
                            ->all()
                        )
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $cycleId = (int) $data['cycle_id'];

                    try {
                        $created = 0;

                        DB::transaction(function () use ($cycleId, &$created) {
                            Company::query()->orderBy('id')->chunkById(500, function ($companies) use ($cycleId, &$created) {
                                foreach ($companies as $c) {
                                    $exists = CompanyInvoice::query()
                                        ->where('company_id', $c->id)
                                        ->where('cycle_id', $cycleId)
                                        ->exists();

                                    if ($exists) continue;

                                    CompanyInvoice::create([
                                        'company_id'    => $c->id,
                                        'cycle_id'      => $cycleId,
                                        'ampere'        => (float) $c->ampere,
                                        'price_per_amp' => (float) $c->price_per_amp,
                                        'fixed_amount'  => (float) $c->fixed_amount,
                                        'total_amount'  => (float) $c->fixed_amount, // يبقى حسب منطقك الحالي
                                        'issued_at'     => now(),
                                    ]);

                                    $created++;
                                }
                            });
                        });

                        Notification::make()
                            ->title("تم توليد {$created} فاتورة شركة")
                            ->success()->send();

                        $this->dispatch('refresh');
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->title('فشل التوليد')->body($e->getMessage())->danger()->persistent()->send();
                    }
                }),

            // تصدير
            Actions\Action::make('exportXlsx')
                ->label('تصدير إكسل')
                ->icon('heroicon-o-arrow-down-tray')
                ->modalHeading('تصدير فواتير الشركات')
                ->visible(fn () => CompanyInvoiceResource::allowExport())
                ->form([
                    Select::make('cycle_ids')
                        ->label('دورات')
                        ->options(fn () => Cycle::query()
                            ->orderByDesc('start_date')
                            ->get()
                            ->mapWithKeys(fn ($c) => [$c->id => ($c->code ?? ($c->start_date?->format('Y-m-d') ?? ('#' . $c->id)))] )
                            ->all()
                        )
                        ->multiple()
                        ->required()
                        ->preload()
                        ->searchable(),
                ])
                ->action(function (array $data) {
                    $ids = array_map('intval', $data['cycle_ids'] ?? []);
                    if (empty($ids)) {
                        Notification::make()->title('اختر دورة واحدة على الأقل')->danger()->send();
                        return;
                    }

                    $codes = Cycle::query()
                        ->whereIn('id', $ids)
                        ->orderByDesc('start_date')
                        ->get()
                        ->map(fn ($c) => $c->code ?? ($c->start_date?->format('Y-m-d') ?? ('#' . $c->id)))
                        ->values()
                        ->all();

                    $file = 'فواتير الشركات - ' . (count($codes) === 1 ? $codes[0] : implode('، ', $codes)) . '.xlsx';

                    return Excel::download(
                        new class($ids) implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents {
                            public function __construct(private array $cycleIds) {}

                            public function collection()
                            {
                                return CompanyInvoice::query()
                                    ->with(['company:id,name,phone,status', 'cycle:id,start_date'])
                                    ->whereIn('cycle_id', $this->cycleIds)
                                    ->orderByDesc('cycle_id')
                                    ->orderBy('company_id')
                                    ->get()
                                    ->map(function (CompanyInvoice $ci) {
                                        $statusAr = match ($ci->company?->status) {
                                            'active'       => 'فعال',
                                            'disconnected' => 'مفصول',
                                            'cancelled'    => 'ملغى',
                                            default        => '—',
                                        };

                                        return [
                                            $ci->company?->name ?? '',
                                            $ci->company?->phone ?? '',
                                            $ci->cycle?->code ?? ($ci->cycle?->start_date?->format('Y-m-d') ?? ''),
                                            (float) $ci->ampere,
                                            (float) $ci->price_per_amp,
                                            (float) $ci->fixed_amount,
                                            (float) $ci->total_amount,
                                            $statusAr,
                                        ];
                                    });
                            }

                            public function headings(): array
                            {
                                return [
                                    'الشركة', 'الهاتف', 'الدورة', 'الأمبير', 'سعر الأمبير', 'المبلغ الثابت', 'الإجمالي', 'الحالة',
                                ];
                            }

                            public function styles(Worksheet $sheet): array
                            {
                                $sheet->getStyle('A1:H1')->getFont()->setBold(true);
                                $sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)
                                      ->getStartColor()->setARGB('FFEDEDED');
                                return [];
                            }

                            public function registerEvents(): array
                            {
                                return [
                                    \Maatwebsite\Excel\Events\AfterSheet::class => function ($event) {
                                        $ws = $event->sheet->getDelegate();
                                        $ws->setRightToLeft(true);
                                        $last = $ws->getHighestRow();
                                        $ws->getStyle("A1:H{$last}")
                                           ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                                    },
                                ];
                            }
                        },
                        $file
                    );
                }),
        ];
    }
}
