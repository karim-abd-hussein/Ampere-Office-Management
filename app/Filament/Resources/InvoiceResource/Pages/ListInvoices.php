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
                    $gen_id=(int) $data['generator_id'];
                    $cycle =Cycle::query()
                        ->where('is_archived', 0)
                        ->where('generator_id',$gen_id)
                        ->orderByDesc('start_date')
                        ->orderByDesc('id')
                        ->first(); 
                    //$this->getLatestCycle();

                    if (! $cycle) {
                        Notification::make()->title('لا توجد دورات بعد')->body('أضِف دورة أولاً.')->danger()->persistent()->send();
                        if ($user = auth()->user()) {
                        Notification::make()->title('لا توجد دورات بعد')->body('أضِف دورة أولاً.')->danger()->sendToDatabase($user);
                        }
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

                            $unit = 0;
                            // (float) ($sub->generator?->price_per_kwh ?? 0);
                            // if($sub->use_fixed_price){
                            //         $unit = $sub->fixed_kwh_price;
                            //         }

                                 Invoice::create([
                                            'subscriber_code_id'  => $sub->code_id,
                                            'subscriber_name'  => $sub->name,
                                            'subscriber_phone'  => $sub->phone,
                                            'subscriber_meter_number'  =>  $sub->meter_number,
                                            'subscriber_use_fixed_price'  => $sub->use_fixed_price==1 ?true : false,
                                            'subscriber_status'  => $sub->status,

                                            'subscriber_id'    => $sub->id,
                                            'generator_id'     => $sub->generator_id,
                                            'collector_id'     => null,
                                            'cycle_id'         =>  $cycle->id,
                                            'issued_at'        =>now(),
                                            'old_reading'      => 0,
                                            'new_reading'      => null,
                                            'consumption'      => 0,
                                            'unit_price_used'  => $unit,
                                            'calculated_total' => 0,
                                            'final_amount'     => 0,
                                        ]);

                        });

                        Notification::make()->title("تمت إضافة المشترك  وإنشاء فاتورة")->success()->persistent()->send();
                       if ($user = auth()->user()) {
                        Notification::make()->title("تمت إضافة المشترك  وإنشاء فاتورة")->success()->sendToDatabase($user);
                        }
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
                        ->options(fn () =>  Cycle::query()->where('is_archived',0)->orderByDesc('start_date')->get()->mapWithKeys(fn($c)=>[$c->id=>$c->code])->all())
                        ->searchable()->required(),
                    // Select::make('collector_id')->label('الجابي (اختياري)')
                    //     ->options(fn () => Collector::query()->orderBy('name')->pluck('name','id')->all())
                    //     ->searchable()->nullable(),
                ])
                ->action(function (array $data) {
                    $cycleId = (int) $data['cycle_id'];
                     $collectorId =0; //$data['collector_id'] ?? null;

                    try {
                        $created = 0;
                        DB::transaction(function () use ($cycleId, $collectorId, &$created) {
                            $cycle = Cycle::find($cycleId);
                            $base = (optional($cycle)->start_date)
                                ? \Illuminate\Support\Carbon::parse(optional($cycle)->start_date)->startOfDay()
                                : now()->startOfDay();
                            $offset = 0;

                            Subscriber::query()
                                ->with(['generator:id,price_per_kwh'])
                                ->where('generator_id', $cycle->generator_id)
                                ->orderBy('id')
                                ->chunkById(500, function ($subs) use ($cycleId, $collectorId, &$created, $base, &$offset) {
                                    foreach ($subs as $sub) {
                                        if (Invoice::query()->where('subscriber_id',$sub->id)->where('cycle_id',$cycleId)->exists()) {
                                            continue;
                                        }

                                        $lastInvoice = Invoice::query()
                                            ->where('subscriber_id', $sub->id)
                                            ->orderByDesc('issued_at')->orderByDesc('id')->first();


                                            $collectorId=null;
                                            $prevNewReading=0;
                                            $status="active";
                                            if(!is_null($lastInvoice)){

                                                $collectorId=$lastInvoice->collector_id;
                                                $prevNewReading=$lastInvoice->new_reading??0;
                                                $status=$lastInvoice->subscriber_status;
                                            }
                                            // dd($prevNewReading);
                                            //->value('new_reading') ?? 0;

                                         $unit = (float) ($sub->generator?->price_per_kwh ?? 0);
                                            if($sub->use_fixed_price){
                                               $unit = $sub->fixed_kwh_price;
                                            }
                                       

                                        Invoice::create([

                                            'subscriber_name'  => $sub->name,
                                            'subscriber_phone'  => $sub->phone,
                                            'subscriber_meter_number'  =>  $sub->meter_number,
                                            'subscriber_use_fixed_price'  => $sub->use_fixed_price,
                                            'subscriber_status'  => $status,
                                            'subscriber_code_id'  => $sub->code_id,
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

                        Notification::make()->title("تم توليد {$created} فاتورة جديدة")->success()->persistent()->send();
                    if ($user = auth()->user()) {
                        Notification::make()->title("تم توليد {$created} فاتورة جديدة")->success()->sendToDatabase($user);
                        }
                         $this->dispatch('refresh');
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->title('فشل توليد الفواتير')->body($e->getMessage())->danger()->persistent()->send();
                          if ($user = auth()->user()) {
                        Notification::make()->title('فشل توليد الفواتير')->body($e->getMessage())->danger()->sendToDatabase($user);
                        }
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
                        ->options(fn () => Cycle::query()->where('is_archived',0)->orderByDesc('start_date')->get()->mapWithKeys(fn($c)=>[$c->id=>$c->code])->all())
                        ->multiple()->required()->preload()->searchable(),
                ])
                ->action(function (array $data) {
                    $ids = array_map('intval', $data['cycle_ids'] ?? []);
                    if (empty($ids)) {
                        Notification::make()->title('الرجاء اختيار دورة واحدة على الأقل')->danger()->send();

                     if ($user = auth()->user()) {
                        Notification::make()->title('الرجاء اختيار دورة واحدة على الأقل')->danger()->sendToDatabase($user);
                         }
                        return;
                    }

                    $codes = Cycle::query()->whereIn('id', $ids)->orderByDesc('start_date')->get()->pluck('code')->all();
                    $fileLabel = count($codes) === 1 ? $codes[0] : ('دورات مختارة - ' . implode('، ', array_slice($codes, 0, 3)) . (count($codes) > 3 ? '…' : ''));
                    $file = 'فواتير ' . $fileLabel . '.xlsx';

                     Notification::make()->title('تصدير الفواتير')->body("تم تجهيز ملف التصدير: {$file}")->success()->send();
                    if ($user = auth()->user()) {
                        Notification::make()->title('تصدير الفواتير')->body("تم تجهيز ملف التصدير: {$file}")->success()->sendToDatabase($user);
                    }

                    return Excel::download(
                        new class($ids) implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents {
                            public function __construct(private array $cycleIds) {}
                            public function collection()
                            {
                                $q = \App\Models\Invoice::query()
                                    ->with(['subscriber:id,name,phone,status,box_number,meter_number',
                                    'cycle:id,start_date,generator_id', // ✅ Include generator_id
                                    'cycle.generator:id,name', // ✅ Load generator relationship
                                    'collector:id,name'])
                                    ->select(['subscriber_id','subscriber_name','subscriber_phone','subscriber_box_number','subscriber_meter_number','subscriber_code_id','subscriber_status','cycle_id','old_reading','new_reading','consumption','unit_price_used','final_amount','issued_at','collector_id'])
                                    ->whereIn('cycle_id', $this->cycleIds)
                                    ->orderBy('cycle_id','desc')
                                    ->orderBy('issued_at','asc')
                                    ->orderBy('id','asc');

                                return $q->get()->map(function (\App\Models\Invoice $i) {
                                    $statusAr = match ($i->subscriber_status ?? null) {

                                         'changed_meter' => 'تم تغيير العداد','changed_name'  => 'تم تغيير الاسم','active'=>'فعال','disconnected'=>'مفصول','cancelled'=>'ملغى', default=>'—',
                                    };
                                    $cycleCode = $i->cycle?->code ?? '';

                                    dd($i->old_reading);
                                    return [
                                        $i->subscriber_name ?? '',
                                        $i->subscriber_phone ?? '',
                                        $i->subscriber_box_number?? '',
                                        $i->subscriber_meter_number ?? '',
                                        $i->subscriber_code_id ?? '',
                                        $cycleCode,
                                        $i->old_reading??0,
                                        is_null($i->new_reading) ? 0 :$i->new_reading,
                                        $i->consumption??0,
                                        $i->unit_price_used ?? 0,
                                        $i->final_amount ?? 0,
                                        $statusAr,
                                        $i->collector?->name ?? '—', // ✅ ADDED COLLECTOR NAME
                                    ];
                                });
                            }
                            public function headings(): array
                            {
                                return [
                                    'المشترك','رقم الهاتف','رقم العلبة','رقم العداد','ID المشترك',
                                    'الدورة','القراءة القديمة','القراءة الجديدة','الاستهلاك',
                                    'سعر الكيلو','المبلغ النهائي','الحالة','الجابي'
                                ];
                            }
                            public function styles(Worksheet $sheet): array
                            {
                                $sheet->getStyle('A1:M1')->getFont()->setBold(true);
                                $sheet->getStyle('A1:M1')->getAlignment()
                                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                    ->setVertical(Alignment::VERTICAL_CENTER);
                                $sheet->getStyle('A1:M1')->getFill()->setFillType(Fill::FILL_NONE);
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
                                            $ws->getStyle("A2:M{$last}")->getAlignment()
                                                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                                ->setVertical(Alignment::VERTICAL_CENTER);
                                        }
                                        $ws->getStyle("A1:M{$last}")->applyFromArray([
                                            'borders' => [
                                                'allBorders' => [
                                                    'borderStyle' => Border::BORDER_THIN,
                                                    'color'       => ['argb' => 'FFBFBFBF'],
                                                ],
                                            ],
                                        ]);
                                        for ($r = 2; $r <= $last; $r++) {
                                            $status = (string) $ws->getCell("M{$r}")->getValue();
                                            if (in_array($status, ['مفصول','ملغى'], true)) {
                                                $ws->getStyle("A{$r}:M{$r}")
                                                    ->getFill()->setFillType(Fill::FILL_SOLID)
                                                    ->getStartColor()->setARGB('FFFFFF00');
                                            } else {
                                                if ($r % 2 === 0) {
                                                    $ws->getStyle("A{$r}:M{$r}")
                                                        ->getFill()->setFillType(Fill::FILL_SOLID)
                                                        ->getStartColor()->setARGB('FFF2F2F2');
                                                } else {
                                                    $ws->getStyle("A{$r}:M{$r}")
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
                        ->options(fn () => Cycle::query()->where('is_archived',0)->orderByDesc('start_date')->get()->mapWithKeys(fn($c)=>[$c->id=>$c->code])->all())
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

                    if ($user = auth()->user()) {
                           Notification::make()->title('لم يتم اختيار ملف')->danger()->sendToDatabase($user);
                                }
                        return;
                    }
                    $path = Storage::disk('local')->path($storedPath);

                    $cycle = Cycle::find($cycleId);
                    $base  = $cycle && $cycle->start_date ? Carbon::parse($cycle->start_date)->startOfDay() : now()->startOfDay();

                    $importRef = pathinfo($storedPath, PATHINFO_BASENAME);


                    $c = (object)['sub_created' => 0,'created' => 0, 'updated' => 0, 'skipped' => 0, 'ignored' => 0];
                    $failedRows = [];

                    // start the class
                    $importer = new class($cycleId, $base, $c, $failedRows,$importRef) implements ToCollection, WithChunkReading {
                        private ?array $headerMap = null;
                        private int $rowNo = 1;

                        public function __construct(
                            private int $cycleId,
                            private \Illuminate\Support\Carbon $base,
                            private object $counters,
                            private array &$failedRows,
                             private ?string $importRef,
                        ) {}

                        public function collection(Collection $rowsCol)
                        {
                            if ($rowsCol->isEmpty()) return;

                            if ($this->headerMap === null) {
                                // dd($rowsCol->get(4));
                                $headers = $this->normalizeRow($rowsCol->first());
                                $headers = array_map(fn($v) => is_string($v) ? trim($v) : $v, $headers);
                                $this->headerMap = $this->buildHeaderMap($headers);

                                // تحقّق وجود الأعمدة المطلوبة واذكر المفقود بالتحديد
                                $missing = [];
                                if (! $this->hasAny($this->oldKeys()))   $missing[] = 'old_reading';
                                if (! $this->hasAny($this->newKeys()))   $missing[] = 'new_reading';
                                if (! $this->hasAny($this->unitKeys()))  $missing[] = 'unit';
                                if (! $this->hasAny($this->finalKeys())) $missing[] = 'final_amount';
                                if (! $this->hasAny(['id','code_id','subscriber_code_id'])) $missing[] = 'id';
                                // if (! $this->hasAny($this->finalKeys())) $missing[] = 'final_amount';
                                // if (! $this->hasAny($this->finalKeys())) $missing[] = 'final_amount';

                                if (!empty($missing)) {
                                    $seen = implode(' | ', array_keys($this->headerMap));

                                     Notification::make()
                                    ->title('فشل الاستيراد')
                                    ->body(
                                        'الملف ينقصه الأعمدة: ' . implode(' + ', $missing) .
                                        "\nالعناوين الموجودة بعد التطبيع: {$seen} عنواين القالب يجب أن تتبع النمط المعروف "
                                    ) // ← User sees the error message
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                       if ($user = auth()->user()) {
                                            Notification::make()->title('فشل الاستيراد')
                                    ->body(
                                        'الملف ينقصه الأعمدة: ' . implode(' + ', $missing) .
                                        "\nالعناوين الموجودة بعد التطبيع: {$seen} عنواين القالب يجب أن تتبع النمط المعروف "
                                    ) // ← User sees the error message
                                    ->danger()
                                    ->sendToDatabase($user);
                                        }


                                    throw new \RuntimeException(
                                        'الملف ينقصه الأعمدة: ' . implode(' + ', $missing) .
                                        "\nالعناوين الموجودة بعد التطبيع: {$seen} عنواين القالب يجب أن تتبع النمط المعروف"
                                    );
                                }

                                $dataRows = $rowsCol->slice(1);
                            } else {
                                $dataRows = $rowsCol;
                            }

                          $cycle = \App\Models\Cycle::find($this->cycleId);


                            foreach ($dataRows as $rowItem) {
                                $this->rowNo++;
                                $row = $this->normalizeRow($rowItem);

                                $name  = $this->cell($row, $this->headerMap, $this->nameKeys());
                                // $collectorId  = $this->cell($row, $this->headerMap, $this->collectorKeys());
                                $phone  = $this->cell($row, $this->headerMap, $this->phoneKeys());
                                $useFixedPrice  = $this->cell($row, $this->headerMap, $this->useFixedPriceKeys());
                                $fixedPriceTxt = $this->cell($row, $this->headerMap, ['سعر الكيلو', 'fixed_kwh_price', 'kwh_price', 'price_per_kwh']);
                                $meter = $this->cell($row, $this->headerMap, $this->meterKeys());
                                // $idRaw = $this->cell($row, $this->headerMap, $this->idKeys());
                                $codeId = $this->cell($row, $this->headerMap, ['id','code_id','subscriber_code_id']);
                                
                                $old   = $this->cell($row, $this->headerMap, $this->oldKeys(), disallowMeter:true);
                                $new   = $this->cell($row, $this->headerMap, $this->newKeys(), disallowMeter:true);
                                $unit  = $this->cell($row, $this->headerMap, $this->unitKeys());
                                $final = $this->cell($row, $this->headerMap, $this->finalKeys());
                                $subscriberStatus = $this->cell($row, $this->headerMap, $this->statusKeys());
                                //dd($subscriberStatus);
                                $issuedAt = $this->base->copy()->addSeconds(max(0, $this->rowNo - 2));

                                $oldReading = $this->toInt($old);
                                $newReading = ($new === '' || $new === null) ? null : $this->toInt($new);
                                if ($newReading !== null && $newReading < $oldReading) $newReading = $oldReading;

                                $unitPrice  = is_numeric($this->toFloatStr($unit))  ? (float) $this->toFloatStr($unit)  : 0;
                                $finalInput = is_numeric($this->toFloatStr($final)) ? (float) $this->toFloatStr($final) : 0;

                                $consumption = max(0, (int) (($newReading ?? 0) - $oldReading));
                                $calcTotal   = $consumption * $unitPrice;
                                $finalAmount =$finalInput; //=== null ? $calcTotal : $finalInput, 2;
                                // dd($codeId,$name);
                                if (empty($codeId)||$name==='') {
                                    $this->counters->ignored++;
                                    $this->failedRows[] = ['type'=>'ignored','row'=>$this->rowNo,'code_id'=>$codeId?: '—' , 'name'=>$name ?: '—', 'meter'=>$meter ?: '—', 'reason'=>'لايحوي أسم او id'];
                                    continue;
                                }

                                // dd($name, $phone ,$useFixedPrice,$fixedPriceTxt,$meter,$codeId,$old,$new, $unit,$final,$subscriberStatus,$issuedAt);

                                $subscriberId = 0; $reason = '';
                                  $sub = \App\Models\Subscriber::where('code_id', $codeId)->first();
                               
                                if (!is_null($sub)) {

                                    // check name

                                    if($name===trim($sub->name)){

                                        // create invoces
                                           $payload = [
                                                    'subscriber_code_id'  => $codeId,
                                                    'subscriber_name'  => $name,
                                                    'subscriber_phone'  => $phone,
                                                    'subscriber_meter_number'  =>  $meter,
                                                    'subscriber_use_fixed_price'  => $useFixedPrice==1 ?true : false,
                                                    'subscriber_id'    => $sub->id,
                                                    'subscriber_status'  => empty($subscriberStatus)?'active':$subscriberStatus,
                                                    'generator_id'     => $sub->generator_id,
                                                    'collector_id'  => $sub->collector_id, 
                                                    'cycle_id'         => $this->cycleId,
                                                    'issued_at'        => $issuedAt,
                                                    'old_reading'      => $oldReading,
                                                    'new_reading'      => $newReading,
                                                    'consumption'      => $consumption,
                                                    'unit_price_used'  => $unitPrice,
                                                    'calculated_total' => $calcTotal,
                                                    'final_amount'     => $finalAmount,
                                              ];

                                      \App\Models\Invoice::create($payload);
                                    $this->counters->created++;


                                    }else{

                                     $this->counters->skipped++;
                                    $this->failedRows[] = ['type'=>'skipped','row'=>$this->rowNo,'code_id'=>$codeId , 'name'=>$name ?: '—', 'meter'=>$meter ?: '—', 'reason'=>$reason ?: 'تطابف ال id مع أختلاف ألاسم'];

                                    }
                                    
                                }else{


                                   $sub= \App\Models\Subscriber::create([
                                        'name'              => $name,
                                        'phone'             => $phone,
                                        'box_number'        => NULL,
                                        'meter_number'      => $meter,
                                        'generator_id'      => $cycle->generator_id,
                                        'status'            => empty($subscriberStatus)?'active':$subscriberStatus,
                                        'subscription_date' => now()->toDateString(),
                                        'import_ref'        => $this->importRef, // اسم ملف الإكسل
                                        'use_fixed_price'   => $useFixedPrice==1 ?true : false,
                                        'fixed_kwh_price'   =>  trim($fixedPriceTxt)===''?NULL:(int)$fixedPriceTxt,
                                        'code_id'  => $codeId,
                                    ]);

                                    if($sub){
                                     $this->counters->sub_created++;


                                       $payload = [
                                                'subscriber_code_id'  => $codeId,
                                                'subscriber_name'  => $name,
                                                'subscriber_phone'  => $phone,
                                                'subscriber_meter_number'  =>  $meter,
                                                'subscriber_use_fixed_price'  => $useFixedPrice==1 ?true : false,
                                                'subscriber_id'    => $sub->id,
                                                'subscriber_status'  => empty($subscriberStatus)?'active':$subscriberStatus,
                                                'generator_id'     => $sub->generator_id,
                                                'collector_id'  => $sub->collector_id, 
                                                'cycle_id'         => $this->cycleId,
                                                'issued_at'        => $issuedAt,
                                                'old_reading'      => $oldReading,
                                                'new_reading'      => $newReading,
                                                'consumption'      => $consumption,
                                                'unit_price_used'  => $unitPrice,
                                                'calculated_total' => $calcTotal,
                                                'final_amount'     => $finalAmount,
                                              ];

                                      \App\Models\Invoice::create($payload);
                                    $this->counters->created++;

                                            }else{
                                          $this->counters->ignored++;
                                           $this->failedRows[] = ['type'=>'ignored','row'=>$this->rowNo,'code_id'=>$codeId ?: '—', 'name'=>$name ?: '—', 'meter'=>$meter ?: '—', 'reason'=>'فشل أدخل المستخدم الي قاعدة البيانات'];
                                              continue;

                                            }
                                }

                                // if ($subscriberId <= 0 && $meter !== '') {
                                //     $cands = \App\Models\Subscriber::where('meter_number', $meter)->orderBy('id')->get(['id','name']);
                                //     if ($cands->count() === 1) {
                                //         $subscriberId = (int) $cands->first()->id;
                                //     } elseif ($cands->count() > 1) {
                                //         if ($name !== '') {
                                //             $match = $cands->firstWhere(fn ($s) => trim($s->name) === trim($name));
                                //             $subscriberId = (int) ($match?->id ?? $cands->first()->id);
                                //         } else {
                                //             $reason = 'رقم عداد مكرر بدون اسم';
                                //         }
                                //     } else {
                                //         $reason = 'رقم العداد غير موجود';
                                //     }
                                // }

                                // if ($subscriberId <= 0 && $name !== '') {
                                //     $ids = \App\Models\Subscriber::where('name', $name)->orderBy('id')->pluck('id');
                                //     if ($ids->count() >= 1) $subscriberId = (int) $ids->first();
                                //     else $reason = $reason ?: 'الاسم غير موجود';
                                // }

                                // if ($subscriberId <= 0) {
                                //     $this->counters->skipped++;
                                //     $this->failedRows[] = ['row'=>$this->rowNo, 'name'=>$name ?: '—', 'meter'=>$meter ?: '—', 'reason'=>$reason ?: 'تعذر تحديد المشترك'];
                                //     continue;
                                // }

                            

                                // $meterIdx = $this->headerIndex($this->meterKeys());
                                // $oldIdx   = $this->headerIndex($this->oldKeys());
                                // $newIdx   = $this->headerIndex($this->newKeys());
                                // if ($meterIdx !== null && ($oldIdx === $meterIdx || $newIdx === $meterIdx)) {
                                //     $this->counters->skipped++;
                                //     $this->failedRows[] = ['row'=>$this->rowNo, 'name'=>$name ?: '—', 'meter'=>$meter ?: '—', 'reason'=>'عمود القراءة يطابق عمود رقم العداد'];
                                //     continue;
                                // }

                                // $oldReading = $this->toInt($old);
                                // $newReading = ($new === '' || $new === null) ? null : $this->toInt($new);
                                // if ($newReading !== null && $newReading < $oldReading) $newReading = $oldReading;

                                // $unitPrice  = is_numeric($this->toFloatStr($unit))  ? (float) $this->toFloatStr($unit)  : 0.0;
                                // $finalInput = is_numeric($this->toFloatStr($final)) ? (float) $this->toFloatStr($final) : null;

                                // $consumption = max(0, (int) (($newReading ?? 0) - $oldReading));
                                // $calcTotal   = round($consumption * $unitPrice, 2);
                                // $finalAmount = round($finalInput === null ? $calcTotal : $finalInput, 2);

                                // $issuedAt = $this->base->copy()->addSeconds(max(0, $this->rowNo - 2));
                                // $genId = \App\Models\Subscriber::whereKey($subscriberId)->value('generator_id');

                                // $invoice = \App\Models\Invoice::query()
                                //     ->where('subscriber_id', $subscriberId)
                                //     ->where('cycle_id', $this->cycleId)
                                //     ->first();

                                    

                                //     $payload = [
                                //                     'subscriber_name'  => $name,
                                //                     'subscriber_phone'  => $phone,
                                //                     'subscriber_meter_number'  =>  $meter,
                                //                     'subscriber_use_fixed_price'  => $useFixedPrice==1 ?true : false,
                                //                     'subscriber_id'    => $subscriberId,
                                //                     'subscriber_status'  => $subscriberStatus,
                                //                     'generator_id'     => $genId,
                                //                     'collector_id'  => empty($collectorId) ? NULL : $collectorId, 
                                //                     'cycle_id'         => $this->cycleId,
                                //                     'issued_at'        => $issuedAt,
                                //                     'old_reading'      => $oldReading,
                                //                     'new_reading'      => $newReading,
                                //                     'consumption'      => $consumption,
                                //                     'unit_price_used'  => $unitPrice,
                                //                     'calculated_total' => $calcTotal,
                                //                     'final_amount'     => $finalAmount,
                                            //   ];


                                // if ($invoice) {
                                //     $invoice->fill($payload)->save();
                                //     $this->counters->updated++;
                                // } else {
                                //     \App\Models\Invoice::create($payload);
                                //     $this->counters->created++;
                                // }
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
                        private function collectorKeys(): array  { return ['الجابي','اسم الجابي','collector_id']; }
                        private function phoneKeys(): array  { return ['الهاتف','رقم الهاتف','phone']; }
                        private function useFixedPriceKeys(): array  { return ['سعر ثابت','use_fixed_price']; }
                        private function meterKeys(): array { return ['رقم العداد','رقم العد اد','meter','meter_number']; }
                        private function idKeys(): array    { return ['ID المشترك','id','subscriber_id','subscriber id']; }
                        private function oldKeys(): array   { return ['القراءة القديمة','قراءة القديمة','قراءة القد','old','old_reading','old reading']; }
                        private function newKeys(): array   { return ['القراءة الجديدة','قراءة الجديدة','الجديده','new','new_reading','new reading']; }
                        private function unitKeys(): array  { return ['سعر الكيلو','سعر الكِلو','سعر الكيلوواط','unit','unit_price','price_per_kwh']; }
                        private function finalKeys(): array { return ['المبلغ النهائي','المبلغ النهائى','final','final_amount']; }
                        private function statusKeys(): array { return ['الحالة','حالة','حالة المشترك','status','subscriber_status','subscriber status']; }
                    };
                     // end the class
                    try {
                        Excel::import($importer, $path);
                        // array_slice($failedRows, 0, 20
                        // إشعار مُبسّط: فقط عدد المُنشأ + الذين لم يُضافوا مع أسمائهم
                        // $msg = "created :{$c->created}, updated:{$c->updated}, skipped:{$c->skipped}, sub_created:{$c->sub_created}, ignored:{$c->ignored}";
                    //   $msg = "فاتورة جديدة: {$c->created}\n"
                    //         . "تخطي: {$c->skipped}\n"
                    //         . "مستخدم جديد: {$c->sub_created}\n"
                    //         . "تجاهل: {$c->ignored}\n\n";

                    //     $names = array_map(
                    //         fn($f) => "• النوع: " . ($f['type'] ?? '-') . "\n"
                    //                 . "  السطر: " . ($f['row'] ?? '-') . "\n"
                    //                 . "  ID: " . ($f['code_id'] ?? '-') . "\n"
                    //                 . "  الاسم: " . ($f['name'] ?? '-') . "\n",
                    //         $failedRows
                    //     );

                    //     $msg .= implode("\n", $names);
                    //     // . (count($failedRows) > 20 ? "\n…" : ''

                    //     Notification::make()->title('استيراد الفواتير')->body($msg)->success()->persistent()->send();





                            // 🟢 1. First notification — summary counters
                            $msg = "فاتورة جديدة: {$c->created}\n"
                                . "تخطي: {$c->skipped}\n"
                                . "مستخدم جديد: {$c->sub_created}\n"
                                . "تجاهل: {$c->ignored}";

                            Notification::make()
                                ->title('ملخص استيراد الفواتير')
                                ->body($msg)
                                ->success()
                                ->persistent()
                                ->send();


                            // 🟡 2. Second notification — ignored rows
                            $ignoredRows = array_filter($failedRows, fn($f) => ($f['type'] ?? '') === 'ignored');
                            $ignoredMsg="";
                            if (!empty($ignoredRows)) {
                                $ignoredMsg = "الصفوف التي تم تجاهلها:\n\n" . implode(
                                    "\n",
                                    array_map(
                                        fn($f) => "• السطر: " . ($f['row'] ?? '-') .
                                                " / ID: " . ($f['code_id'] ?? '-') .
                                                " / الاسم: " . ($f['name'] ?? '-'),
                                        $ignoredRows
                                    )
                                );

                                Notification::make()
                                    ->title('تجاهل الفواتير')
                                    ->body($ignoredMsg)
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            }


                            // 🔵 3. Third notification — skipped rows
                            $skippedRows = array_filter($failedRows, fn($f) => ($f['type'] ?? '') === 'skipped');
                             $skippedMsg="";
                            if (!empty($skippedRows)) {
                                $skippedMsg = "الصفوف التي تم تخطيها:\n\n" . implode(
                                    "\n",
                                    array_map(
                                        fn($f) => "• السطر: " . ($f['row'] ?? '-') .
                                                " / ID: " . ($f['code_id'] ?? '-') .
                                                " / الاسم: " . ($f['name'] ?? '-'),
                                        $skippedRows
                                    )
                                );

                                Notification::make()
                                    ->title('الفواتير المتخطاة')
                                    ->body($skippedMsg)
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }






                        if ($user = auth()->user()) {
                           Notification::make()
                                ->title('ملخص استيراد الفواتير')
                                ->body($msg)
                                ->success()->sendToDatabase($user);

                                 Notification::make()
                                    ->title('تجاهل الفواتير')
                                    ->body($ignoredMsg)
                                    ->warning()
                                    ->sendToDatabase($user);

                                          Notification::make()
                                    ->title('الفواتير المتخطاة')
                                    ->body($skippedMsg)
                                    ->danger()
                                    ->sendToDatabase($user);


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
