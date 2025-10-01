<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\SubscriberResource;
use App\Models\Collector;
use App\Models\Cycle;
use App\Models\Generator;
use App\Models\Invoice;
use App\Models\Subscriber;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Throwable;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->orderBy('cycle_id', 'desc')
            ->orderBy('issued_at', 'asc')
            ->orderBy('id', 'asc');
    }

    protected function getLatestCycle(): ?Cycle
    {
        $cycle = Cycle::query()
            ->where('is_archived', 0)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        return $cycle ?: Cycle::query()
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    protected function getHeaderActions(): array
    {
        return [
            // ========== إضافة مشترك سريع ==========
            Actions\Action::make('quickAddSubscriber')
                ->label('إضافة مشترك')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->visible(fn () => SubscriberResource::canCreate())
                ->modalHeading('إضافة مشترك سريع')
                ->form([
                    TextInput::make('name')->label('اسم المشترك')->required(),
                    TextInput::make('phone')->label('رقم الهاتف')->tel()->maxLength(255),
                    TextInput::make('meter_number')->label('رقم العداد')->required(),
                    TextInput::make('box_number')->label('رقم العلبة')->maxLength(50),
                    Select::make('generator_id')->label('المولدة')
                        ->options(fn () => Generator::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->required(),
                    Select::make('status')->label('الحالة')->options([
                        'active' => 'فعال', 'disconnected' => 'مفصول', 'cancelled' => 'ملغى',
                    ])->default('active')->required(),
                    DatePicker::make('subscription_date')->label('تاريخ الاشتراك')->default(now())->required(),
                ])
                ->action(function (array $data) {
                    $cycle = $this->getLatestCycle();
                    if (! $cycle) {
                        Notification::make()->title('لا توجد دورات بعد')->body('أضِف دورة أولاً.')->danger()->persistent()->send();
                        return;
                    }

                    try {
                        DB::transaction(function () use ($data, $cycle) {
                            /** @var Subscriber $sub */
                            $sub = Subscriber::create([
                                'name'              => $data['name'],
                                'phone'             => $data['phone'] ?? null,
                                'meter_number'      => $data['meter_number'],
                                'box_number'        => $data['box_number'] ?? null,
                                'generator_id'      => (int) $data['generator_id'],
                                'status'            => $data['status'] ?? 'active',
                                'subscription_date' => $data['subscription_date'],
                            ]);

                            $unit = (float) ($sub->generator?->price_per_kwh ?? 0);

                            Invoice::create([
                                'subscriber_id'    => $sub->id,
                                'generator_id'     => $sub->generator_id,
                                'collector_id'     => null,
                                'cycle_id'         => $cycle->id,
                                'issued_at'        => now(),
                                'old_reading'      => 0,
                                'new_reading'      => null,
                                'consumption'      => 0,
                                'unit_price_used'  => $unit,
                                'calculated_total' => 0,
                                'final_amount'     => 0,
                            ]);
                        });

                        Notification::make()->title('تمت إضافة المشترك وإنشاء فاتورة')->success()->send();
                        $this->dispatch('refresh');
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->title('فشل إضافة المشترك')->body($e->getMessage())->danger()->persistent()->send();
                    }
                })
                ->modalSubmitActionLabel('حفظ')
                ->modalWidth('lg'),

            // ========== توليد فواتير للدورة ==========
            Actions\Action::make('generateForCycle')
                ->label('توليد فواتير لجميع المشتركين')
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->visible(fn () => InvoiceResource::canGenerate())
                ->modalHeading('توليد فواتير الدورة')
                ->form([
                    Select::make('cycle_id')->label('اختر الدورة')
                        ->options(fn () => Cycle::query()->orderByDesc('start_date')->get()->mapWithKeys(fn($c)=>[$c->id=>$c->code])->all())
                        ->searchable()->required(),
                    Select::make('collector_id')->label('الجابي (اختياري)')
                        ->options(fn () => Collector::query()->orderBy('name')->pluck('name','id')->all())
                        ->searchable()->nullable(),
                ])
                ->action(function (array $data) {
                    $cycleId = (int) $data['cycle_id'];
                    $collectorId = $data['collector_id'] ?? null;

                    try {
                        $created = 0;
                        DB::transaction(function () use ($cycleId, $collectorId, &$created) {
                            $base = (optional(Cycle::find($cycleId))->start_date)
                                ? \Illuminate\Support\Carbon::parse(optional(Cycle::find($cycleId))->start_date)->startOfDay()
                                : now()->startOfDay();
                            $offset = 0;

                            Subscriber::query()
                                ->with(['generator:id,price_per_kwh'])
                                ->orderBy('id')
                                ->chunkById(500, function ($subs) use ($cycleId, $collectorId, &$created, $base, &$offset) {
                                    foreach ($subs as $sub) {
                                        if (Invoice::query()->where('subscriber_id',$sub->id)->where('cycle_id',$cycleId)->exists()) {
                                            continue;
                                        }

                                        $prevNewReading = Invoice::query()
                                            ->where('subscriber_id', $sub->id)
                                            ->orderByDesc('issued_at')->orderByDesc('id')
                                            ->value('new_reading') ?? 0;

                                        $unit = (float) ($sub->generator?->price_per_kwh ?? 0);

                                        Invoice::create([
                                            'subscriber_id'    => $sub->id,
                                            'generator_id'     => $sub->generator_id,
                                            'collector_id'     => $collectorId,
                                            'cycle_id'         => $cycleId,
                                            'issued_at'        => $base->copy()->addSeconds($offset++),
                                            'old_reading'      => $prevNewReading,
                                            'new_reading'      => null,
                                            'consumption'      => 0,
                                            'unit_price_used'  => $unit,
                                            'calculated_total' => 0,
                                            'final_amount'     => 0,
                                        ]);

                                        $created++;
                                    }
                                });
                        });

                        Notification::make()->title("تم توليد {$created} فاتورة جديدة")->success()->send();
                        $this->dispatch('refresh');
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->title('فشل توليد الفواتير')->body($e->getMessage())->danger()->persistent()->send();
                    }
                }),

            // ========== تصدير إكسل ==========
            Actions\Action::make('exportXlsx')
                ->label('تصدير إكسل')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => InvoiceResource::canExport())
                ->modalHeading('تصدير إلى إكسل')
                ->form([
                    Select::make('cycle_ids')->label('اختر دورة/دورات')
                        ->options(fn () => Cycle::query()->orderByDesc('start_date')->get()->mapWithKeys(fn($c)=>[$c->id=>$c->code])->all())
                        ->multiple()->required()->preload()->searchable(),
                ])
                ->action(function (array $data) {
                    $ids = array_map('intval', $data['cycle_ids'] ?? []);
                    if (empty($ids)) {
                        Notification::make()->title('الرجاء اختيار دورة واحدة على الأقل')->danger()->send();
                        return;
                    }

                    $codes = Cycle::query()->whereIn('id', $ids)->orderByDesc('start_date')->get()->pluck('code')->all();
                    $fileLabel = count($codes) === 1 ? $codes[0] : ('دورات مختارة - ' . implode('، ', array_slice($codes, 0, 3)) . (count($codes) > 3 ? '…' : ''));
                    $file = 'فواتير ' . $fileLabel . '.xlsx';

                    if ($user = auth()->user()) {
                        Notification::make()->title('تصدير الفواتير')->body("تم تجهيز ملف التصدير: {$file}")->success()->sendToDatabase($user);
                    }

                    return Excel::download(
                        new class($ids) implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents {
                            public function __construct(private array $cycleIds) {}
                            public function collection()
                            {
                                $q = \App\Models\Invoice::query()
                                    ->with(['subscriber:id,name,phone,status,box_number,meter_number','cycle:id,start_date'])
                                    ->select(['subscriber_id','cycle_id','old_reading','new_reading','consumption','unit_price_used','final_amount','issued_at'])
                                    ->whereIn('cycle_id', $this->cycleIds)
                                    ->orderBy('cycle_id','desc')
                                    ->orderBy('issued_at','asc')
                                    ->orderBy('id','asc');

                                return $q->get()->map(function (\App\Models\Invoice $i) {
                                    $statusAr = match ($i->subscriber->status ?? null) {
                                        'active'=>'فعال','disconnected'=>'مفصول','cancelled'=>'ملغى', default=>'—',
                                    };
                                    $cycleCode = $i->cycle?->code ?? '';
                                    return [
                                        $i->subscriber->name ?? '',
                                        $i->subscriber->phone ?? '',
                                        $i->subscriber->box_number ?? '',
                                        $i->subscriber->meter_number ?? '',
                                        (int) ($i->subscriber_id ?? 0),
                                        $cycleCode,
                                        is_null($i->old_reading) ? '' : (float) $i->old_reading,
                                        is_null($i->new_reading) ? '' : (float) $i->new_reading,
                                        (float) ($i->consumption ?? 0),
                                        (float) ($i->unit_price_used ?? 0),
                                        (float) ($i->final_amount ?? 0),
                                        $statusAr,
                                    ];
                                });
                            }
                            public function headings(): array
                            {
                                return [
                                    'المشترك','رقم الهاتف','رقم العلبة','رقم العداد','ID المشترك',
                                    'الدورة','القراءة القديمة','القراءة الجديدة','الاستهلاك',
                                    'سعر الكيلو','المبلغ النهائي','الحالة'
                                ];
                            }
                            public function styles(Worksheet $sheet): array
                            {
                                $sheet->getStyle('A1:L1')->getFont()->setBold(true);
                                $sheet->getStyle('A1:L1')->getAlignment()
                                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                    ->setVertical(Alignment::VERTICAL_CENTER);
                                $sheet->getStyle('A1:L1')->getFill()->setFillType(Fill::FILL_NONE);
                                return [];
                            }
                            public function registerEvents(): array
                            {
                                return [
                                    \Maatwebsite\Excel\Events\AfterSheet::class => function ($event) {
                                        $ws   = $event->sheet->getDelegate();
                                        $ws->setRightToLeft(true);
                                        $last = (int) $ws->getHighestRow();
                                        if ($last >= 2) {
                                            $ws->getStyle("A2:L{$last}")->getAlignment()
                                                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                                ->setVertical(Alignment::VERTICAL_CENTER);
                                        }
                                        $ws->getStyle("A1:L{$last}")->applyFromArray([
                                            'borders' => [
                                                'allBorders' => [
                                                    'borderStyle' => Border::BORDER_THIN,
                                                    'color'       => ['argb' => 'FFBFBFBF'],
                                                ],
                                            ],
                                        ]);
                                        for ($r = 2; $r <= $last; $r++) {
                                            $status = (string) $ws->getCell("L{$r}")->getValue();
                                            if (in_array($status, ['مفصول','ملغى'], true)) {
                                                $ws->getStyle("A{$r}:L{$r}")
                                                    ->getFill()->setFillType(Fill::FILL_SOLID)
                                                    ->getStartColor()->setARGB('FFFFFF00');
                                            } else {
                                                if ($r % 2 === 0) {
                                                    $ws->getStyle("A{$r}:L{$r}")
                                                        ->getFill()->setFillType(Fill::FILL_SOLID)
                                                        ->getStartColor()->setARGB('FFF2F2F2');
                                                } else {
                                                    $ws->getStyle("A{$r}:L{$r}")
                                                        ->getFill()->setFillType(Fill::FILL_NONE);
                                                }
                                            }
                                        }
                                    },
                                ];
                            }
                        },
                        $file
                    );
                }),

            // ========== استيراد إكسل — تطبيع أقوى للعناوين ورسالة تشخيصية ==========
            Actions\Action::make('importXlsx')
                ->label('استيراد')
                ->icon('heroicon-o-arrow-up-tray')
                ->visible(fn () => InvoiceResource::canImport())
                ->modalHeading('استيراد فواتير من إكسل')
                ->form([
                    Select::make('cycle_id')->label('اختر الدورة لإضافة/تحديث الفواتير')
                        ->options(fn () => Cycle::query()->orderByDesc('start_date')->get()->mapWithKeys(fn($c)=>[$c->id=>$c->code])->all())
                        ->searchable()->required(),
                    FileUpload::make('file')->label('ملف إكسل (.xlsx)')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.ms-excel','.xlsx'])
                        ->disk('local')->directory('imports/invoices')->visibility('private')->maxSize(8 * 1024)->required(),
                ])
                ->action(function (array $data) {
                    @ignore_user_abort(true);
                    @ini_set('max_execution_time', '0');
                    @set_time_limit(0);
                    @ini_set('memory_limit', '512M');

                    config([
                        'excel.imports.read_only' => true,
                        'excel.cache.driver'      => 'illuminate',
                        'excel.cache.store'       => null,
                    ]);

                    $cycleId    = (int) ($data['cycle_id'] ?? 0);
                    $storedPath = $data['file'] ?? null;
                    if (! $storedPath) {
                        Notification::make()->title('لم يتم اختيار ملف')->danger()->send();
                        return;
                    }
                    $path = Storage::disk('local')->path($storedPath);

                    $cycle = Cycle::find($cycleId);
                    $base  = $cycle && $cycle->start_date ? Carbon::parse($cycle->start_date)->startOfDay() : now()->startOfDay();

                    $c = (object)['created' => 0, 'updated' => 0, 'skipped' => 0, 'ignored' => 0];
                    $failedRows = [];

                    $importer = new class($cycleId, $base, $c, $failedRows) implements ToCollection, WithChunkReading {
                        private ?array $headerMap = null;
                        private int $rowNo = 1;

                        public function __construct(
                            private int $cycleId,
                            private \Illuminate\Support\Carbon $base,
                            private object $counters,
                            private array &$failedRows,
                        ) {}

                        public function collection(Collection $rowsCol)
                        {
                            if ($rowsCol->isEmpty()) return;

                            if ($this->headerMap === null) {
                                $headers = $this->normalizeRow($rowsCol->first());
                                $headers = array_map(fn($v) => is_string($v) ? trim($v) : $v, $headers);
                                $this->headerMap = $this->buildHeaderMap($headers);

                                // تحقّق وجود الأعمدة المطلوبة واذكر المفقود بالتحديد
                                $missing = [];
                                if (! $this->hasAny($this->oldKeys()))   $missing[] = 'القراءة القديمة';
                                if (! $this->hasAny($this->newKeys()))   $missing[] = 'القراءة الجديدة';
                                if (! $this->hasAny($this->unitKeys()))  $missing[] = 'سعر الكيلو';
                                if (! $this->hasAny($this->finalKeys())) $missing[] = 'المبلغ النهائي';

                                if (!empty($missing)) {
                                    $seen = implode(' | ', array_keys($this->headerMap));
                                    throw new \RuntimeException(
                                        'الملف ينقصه الأعمدة: ' . implode(' + ', $missing) .
                                        "\nالعناوين الموجودة بعد التطبيع: {$seen}"
                                    );
                                }

                                $dataRows = $rowsCol->slice(1);
                            } else {
                                $dataRows = $rowsCol;
                            }

                            foreach ($dataRows as $rowItem) {
                                $this->rowNo++;
                                $row = $this->normalizeRow($rowItem);

                                $name  = $this->cell($row, $this->headerMap, $this->nameKeys());
                                $meter = $this->cell($row, $this->headerMap, $this->meterKeys());
                                $idRaw = $this->cell($row, $this->headerMap, $this->idKeys());

                                if (trim($name) === '' && trim($meter) === '' && trim($idRaw) === '') {
                                    $this->counters->ignored++;
                                    continue;
                                }

                                $subscriberId = 0; $reason = '';

                                if (is_numeric($idRaw) && (int)$idRaw > 0 && \App\Models\Subscriber::whereKey((int)$idRaw)->exists()) {
                                    $subscriberId = (int) $idRaw;
                                }

                                if ($subscriberId <= 0 && $meter !== '') {
                                    $cands = \App\Models\Subscriber::where('meter_number', $meter)->orderBy('id')->get(['id','name']);
                                    if ($cands->count() === 1) {
                                        $subscriberId = (int) $cands->first()->id;
                                    } elseif ($cands->count() > 1) {
                                        if ($name !== '') {
                                            $match = $cands->firstWhere(fn ($s) => trim($s->name) === trim($name));
                                            $subscriberId = (int) ($match?->id ?? $cands->first()->id);
                                        } else {
                                            $reason = 'رقم عداد مكرر بدون اسم';
                                        }
                                    } else {
                                        $reason = 'رقم العداد غير موجود';
                                    }
                                }

                                if ($subscriberId <= 0 && $name !== '') {
                                    $ids = \App\Models\Subscriber::where('name', $name)->orderBy('id')->pluck('id');
                                    if ($ids->count() >= 1) $subscriberId = (int) $ids->first();
                                    else $reason = $reason ?: 'الاسم غير موجود';
                                }

                                if ($subscriberId <= 0) {
                                    $this->counters->skipped++;
                                    $this->failedRows[] = ['row'=>$this->rowNo, 'name'=>$name ?: '—', 'meter'=>$meter ?: '—', 'reason'=>$reason ?: 'تعذر تحديد المشترك'];
                                    continue;
                                }

                                $old   = $this->cell($row, $this->headerMap, $this->oldKeys(), disallowMeter:true);
                                $new   = $this->cell($row, $this->headerMap, $this->newKeys(), disallowMeter:true);
                                $unit  = $this->cell($row, $this->headerMap, $this->unitKeys());
                                $final = $this->cell($row, $this->headerMap, $this->finalKeys());

                                $meterIdx = $this->headerIndex($this->meterKeys());
                                $oldIdx   = $this->headerIndex($this->oldKeys());
                                $newIdx   = $this->headerIndex($this->newKeys());
                                if ($meterIdx !== null && ($oldIdx === $meterIdx || $newIdx === $meterIdx)) {
                                    $this->counters->skipped++;
                                    $this->failedRows[] = ['row'=>$this->rowNo, 'name'=>$name ?: '—', 'meter'=>$meter ?: '—', 'reason'=>'عمود القراءة يطابق عمود رقم العداد'];
                                    continue;
                                }

                                $oldReading = $this->toInt($old);
                                $newReading = ($new === '' || $new === null) ? null : $this->toInt($new);
                                if ($newReading !== null && $newReading < $oldReading) $newReading = $oldReading;

                                $unitPrice  = is_numeric($this->toFloatStr($unit))  ? (float) $this->toFloatStr($unit)  : 0.0;
                                $finalInput = is_numeric($this->toFloatStr($final)) ? (float) $this->toFloatStr($final) : null;

                                $consumption = max(0, (int) (($newReading ?? 0) - $oldReading));
                                $calcTotal   = round($consumption * $unitPrice, 2);
                                $finalAmount = round($finalInput === null ? $calcTotal : $finalInput, 2);

                                $issuedAt = $this->base->copy()->addSeconds(max(0, $this->rowNo - 2));
                                $genId = \App\Models\Subscriber::whereKey($subscriberId)->value('generator_id');

                                $invoice = \App\Models\Invoice::query()
                                    ->where('subscriber_id', $subscriberId)
                                    ->where('cycle_id', $this->cycleId)
                                    ->first();

                                $payload = [
                                    'subscriber_id'    => $subscriberId,
                                    'generator_id'     => $genId,
                                    'cycle_id'         => $this->cycleId,
                                    'issued_at'        => $issuedAt,
                                    'old_reading'      => $oldReading,
                                    'new_reading'      => $newReading,
                                    'consumption'      => $consumption,
                                    'unit_price_used'  => $unitPrice,
                                    'calculated_total' => $calcTotal,
                                    'final_amount'     => $finalAmount,
                                ];

                                if ($invoice) {
                                    $invoice->fill($payload)->save();
                                    $this->counters->updated++;
                                } else {
                                    \App\Models\Invoice::create($payload);
                                    $this->counters->created++;
                                }
                            }
                        }

                        public function chunkSize(): int { return 500; }

                        // ===== Helpers =====

                        private function hasAny(array $keys): bool
                        {
                            foreach ($keys as $k) {
                                if (array_key_exists($this->normalizeHeader($k), $this->headerMap)) return true;
                            }
                            return false;
                        }

                        private function normalizeHeader(string $s): string
                        {
                            $s = trim($s);

                            // إحالات حروف عربية شائعة
                            $s = strtr($s, [
                                'أ'=>'ا','إ'=>'ا','آ'=>'ا','ٱ'=>'ا','ى'=>'ي','ة'=>'ه','ؤ'=>'و','ئ'=>'ي','ـ'=>'',
                            ]);

                            // إزالة علامات اتجاه/وصل/فواصل غير مرئية + NBSP بأنواعه
                            $s = preg_replace('/[\x{200C}\x{200D}\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', $s);
                            $s = preg_replace('/[\x{00A0}\x{1680}\x{2000}-\x{200A}\x{202F}\x{205F}\x{3000}]/u', ' ', $s);

                            // إزالة التشكيل
                            $s = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $s);

                            // أرقام عربية -> إنجليزية
                            $s = strtr($s, ['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9','٬'=>',','٫'=>'.']);

                            // تبسيط المسافات
                            $s = preg_replace('/\s+/u',' ', $s);
                            return trim($s);
                        }

                        private function normalizeRow($row): array
                        {
                            if ($row instanceof Collection) $row = $row->toArray();
                            if (is_string($row) || is_numeric($row) || is_bool($row) || $row === null) return [(string)$row];
                            if (is_array($row)) return array_map(fn($v)=>is_string($v)?trim($v):$v, $row);
                            return [];
                        }

                        private function buildHeaderMap(array $headers): array
                        {
                            $map = [];
                            foreach ($headers as $i => $h) {
                                if ($h === null || $h === '') continue;
                                $key = $this->normalizeHeader((string)$h);
                                if ($key === '') continue;
                                $map[$key] = $i;
                            }
                            return $map;
                        }

                        private function cell(array $row, array $map, array $keys, bool $disallowMeter=false): string
                        {
                            $meterIdx = $this->headerIndex($this->meterKeys());
                            foreach ($keys as $key) {
                                $norm = $this->normalizeHeader($key);
                                if (array_key_exists($norm, $map)) {
                                    $i = $map[$norm];
                                    if ($disallowMeter && $meterIdx !== null && $i === $meterIdx) {
                                        return '';
                                    }
                                    return isset($row[$i]) ? trim((string)$row[$i]) : '';
                                }
                            }
                            return '';
                        }

                        private function headerIndex(array $keys): ?int
                        {
                            foreach ($keys as $k) {
                                $nk = $this->normalizeHeader($k);
                                if (isset($this->headerMap[$nk])) return (int) $this->headerMap[$nk];
                            }
                            return null;
                        }

                        private function toFloatStr(?string $s): string
                        {
                            $s = (string) $s;
                            $s = strtr($s, ['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9', '٬'=>',','٫'=>'.']);
                            return preg_replace('/[^0-9.\-]/', '', $s) ?: '0';
                        }

                        private function toInt(?string $s): int
                        {
                            return (int) floor((float) $this->toFloatStr($s));
                        }

                        private function nameKeys(): array  { return ['المشترك','اسم المشترك','name']; }
                        private function meterKeys(): array { return ['رقم العداد','رقم العد اد','meter','meter_number']; }
                        private function idKeys(): array    { return ['ID المشترك','id','subscriber_id','subscriber id']; }
                        private function oldKeys(): array   { return ['القراءة القديمة','قراءة القديمة','قراءة القد','old','old_reading','old reading']; }
                        private function newKeys(): array   { return ['القراءة الجديدة','قراءة الجديدة','الجديده','new','new_reading','new reading']; }
                        private function unitKeys(): array  { return ['سعر الكيلو','سعر الكِلو','سعر الكيلوواط','unit','unit_price','price_per_kwh']; }
                        private function finalKeys(): array { return ['المبلغ النهائي','المبلغ النهائى','final','final_amount']; }
                    };

                    try {
                        Excel::import($importer, $path);

                        // إشعار مُبسّط: فقط عدد المُنشأ + الذين لم يُضافوا مع أسمائهم
                        $msg = "تم الاستيراد — أُنشئت: {$c->created}";
                        if ($c->skipped) {
                            $names = array_map(
                                fn($f) => ($f['name'] ?? '—') . ' / عداد ' . ($f['meter'] ?? '—'),
                                array_slice($failedRows, 0, 20)
                            );
                            $msg .= " • لم تُضَف: {$c->skipped}";
                            if (!empty($names)) {
                                $msg .= "\n" . implode("\n", $names) . (count($failedRows) > 20 ? "\n…" : '');
                            }
                        }

                        Notification::make()->title($msg)->success()->persistent()->send();

                        if ($user = auth()->user()) {
                            Notification::make()->title('استيراد الفواتير')->body($msg)->success()->sendToDatabase($user);
                        }

                        $this->dispatch('refresh');
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()
                            ->title('فشل الاستيراد')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        if ($user = auth()->user()) {
                            Notification::make()->title('فشل الاستيراد')->body($e->getMessage())->danger()->sendToDatabase($user);
                        }
                    } finally {
                        if (!empty($storedPath) && Storage::disk('local')->exists($storedPath)) {
                            Storage::disk('local')->delete($storedPath);
                        }
                    }
                }),
        ];
    }
}
