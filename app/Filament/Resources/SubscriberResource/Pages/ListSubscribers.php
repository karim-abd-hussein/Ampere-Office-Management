<?php

namespace App\Filament\Resources\SubscriberResource\Pages;

use App\Filament\Resources\SubscriberResource;
use App\Models\Generator;
use App\Models\Subscriber;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ListSubscribers extends ListRecords
{
    protected static string $resource = SubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة زبون')
                ->visible(fn () => SubscriberResource::canCreate()),

            Actions\Action::make('importExcel')
                ->label('استيراد ')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading('استيراد الزبائن من Excel')
                ->form([
                    FileUpload::make('file')
                        ->label('ملف Excel / CSV')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                            '.xlsx',
                            '.csv',
                        ])
                        ->disk('local')          // storage/app
                        ->directory('imports')   // storage/app/imports
                        ->preserveFilenames()    // الاحتفاظ بالاسم الأصلي
                        ->required(),
                ])
                ->visible(fn () => SubscriberResource::canImport())
                ->action(function (array $data) {
                    // إعدادات لتقليل استهلاك الذاكرة
                    config([
                        'excel.cache.driver'      => 'illuminate',
                        'excel.cache.store'       => null,
                        'excel.imports.read_only' => true,
                    ]);

                    $relative  = $data['file'];                          // مثال: imports/abc.xlsx
                    $fullPath  = Storage::disk('local')->path($relative);
                    $importRef = pathinfo($relative, PATHINFO_BASENAME); // اسم الملف مع الامتداد

                    // عدّادات
                    $c = (object)[
                        'created' => 0,
                        'skipped' => 0,
                        'errors'  => 0,
                    ];

                    // [['row'=>int,'name'=>string,'meter'=>string,'reason'=>string]]
                    $failed = [];
                    $skipped = [];

                    /**
                     * مستورد Chunked:
                     * - import_ref = اسم ملف الإكسل لكل سجل.
                     * - يدعم use_fixed_price + fixed_kwh_price.
                     * - يمنع التكرار بالاسم فقط.
                     */
                    $importer = new class($c, $failed,$skipped, $importRef) implements ToCollection, WithChunkReading {
                        private ?array $headerMap = null;
                        private int $rowNo = 1;

                        public function __construct(
                            private object $counters,
                            private array  &$failed,
                            private array  &$skipped,
                            private ?string $importRef,
                        ) {}

                        public function collection(Collection $rowsCol)
                        {
                            if ($rowsCol->isEmpty()) return;

                            // الرؤوس
                            if ($this->headerMap === null) {
                                $headersRow = $rowsCol->first();
                                $headers    = $this->normalizeRow($headersRow);
                                $headers    = array_map(fn($v) => is_string($v) ? trim($v) : $v, $headers);
                                $this->headerMap = $this->buildHeaderMap($headers);
                                $dataRows = $rowsCol->slice(1);
                            } else {
                                $dataRows = $rowsCol;
                            }

                            foreach ($dataRows as $rowItem) {
                                $this->rowNo++;
                                $row = $this->normalizeRow($rowItem);

                                $name   = $this->cell($row, $this->headerMap, ['الاسم', 'name']);
                                $phone  = $this->cell($row, $this->headerMap, ['رقم الهاتف', 'phone']);
                                $meter  = $this->cell($row, $this->headerMap, ['رقم العداد', 'meter_number', 'meter']);
                                $box    = $this->cell($row, $this->headerMap, ['رقم العلبة', 'box_number', 'box']);
                                $gen    = $this->cell($row, $this->headerMap, ['المولدة (اختياري)', 'المولدة', 'generator', 'generator_id', 'generator_code', 'generator_name']);
                                $statusText = $this->cell($row, $this->headerMap, ['الحالة', 'status']);

                                // أعمدة التسعير الثابت (اختيارية)
                                $fixedFlagText = $this->cell($row, $this->headerMap, ['سعر ثابت', 'use_fixed_price', 'fixed', 'fixed_price_flag']);
                                $fixedPriceTxt = $this->cell($row, $this->headerMap, ['سعر الكيلو', 'fixed_kwh_price', 'kwh_price', 'price_per_kwh']);

                                    // Notification::make()
                                    // ->title('نتيجة الاستيراد')
                                    // ->body("{$name} {$phone} {$meter} {$box} {$gen} {$statusText} {$fixedFlagText} {$fixedPriceTxt}")
                                    // ->send();

                                   
                                // صف فاضي
                                if ($name === '' && $meter === '') {
                                     $this->counters->skipped++;
                                     $this->skipped[] = ['row'=>$this->rowNo, 'name'=>'—', 'meter'=>'—', 'reason'=>'الاسم و رقم العداد فارغ']; 
                                     continue; 
                                    
                                    }

                                if ($name === '') {
                                    $this->counters->errors++;
                                    $this->failed[] = ['row'=>$this->rowNo, 'name'=>'—', 'meter'=>($meter ?: '—'), 'reason'=>'الاسم فارغ'];
                                    continue;
                                }

                                // المولّدة (ID/Code/Name)
                                $generatorId = null;
                                if ($gen !== '') {
                                     
                                    if (is_numeric($gen)) {
                                        
                                        $generatorId = Generator::whereKey((int)$gen)->value('id');
                                        
                                    } else {
                                        $generatorId = Generator::where('code', $gen)
                                            ->orWhere('name', $gen)
                                            ->value('id');
                                    }
                                   
                                }
                                

                                // الحالة
                                $status = $this->mapStatus($statusText) ?? 'active';

                                // تنظيف الحقول
                                $name   = trim((string)$name);
                                $phone  = ($phone === '') ? null : trim((string)$phone);
                                $box    = ($box   === '') ? null : trim((string)$box);
                                $meter  = ($meter === '') ? null : trim((string)$meter);
                                $genId  = $generatorId; // قد يكون null

                                // تحويل سويتش السعر الثابت
                                $useFixed = $this->toBool($fixedFlagText);
                                // لو السعر موجود نفعّله تلقائيًا
                                $price = $this->toDecimalOrNull($fixedPriceTxt);
                                if ($price !== null) {
                                    $useFixed = true;
                                }

                                // ====== منع التكرار (بالاسم فقط) ======
                                $exists = Subscriber::query()
                                    ->where('name', $name)
                                    ->where('generator_id', $generatorId)
                                    ->exists();

                                if ($exists) {
                                    $this->counters->skipped++;
                                    $this->skipped[] = ['row'=>$this->rowNo, 'name'=> $name, 'generator_id' => $generatorId, 'reason'=>'هذا الاسم متكرر عند نفس المولدة']; 
                                    continue;
                                }
                                // ======================================

                                // إنشاء السجل
                                try {
                                    Subscriber::create([
                                        'name'              => $name,
                                        'phone'             => $phone,
                                        'box_number'        => $box,
                                        'meter_number'      => $meter,
                                        'generator_id'      => $genId,
                                        'status'            => $status,
                                        'subscription_date' => now()->toDateString(),
                                        'import_ref'        => $this->importRef, // اسم ملف الإكسل
                                        'use_fixed_price'   => $useFixed,
                                        'fixed_kwh_price'   => $price,
                                    ]);

                                    $this->counters->created++;
                                } catch (\Throwable $e) {
                                    $this->counters->errors++;
                                    $this->failed[] = [
                                        'row'   => $this->rowNo,
                                        'name'  => $name ?: '—',
                                        'meter' => $meter ?: '—',
                                        'reason'=> $e->getMessage(),
                                    ];
                                }
                            }
                        }

                        public function chunkSize(): int
                        {
                            return 500;
                        }

                        /** Helpers */
                        private function normalizeRow($row): array
                        {
                            if ($row instanceof Collection) $row = $row->toArray();
                            if (is_string($row) || is_numeric($row) || is_bool($row) || $row === null) return [(string)$row];
                            if (is_array($row)) return array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);
                            return [];
                        }

                        private function buildHeaderMap(array $headers): array
                        {
                            $map = [];
                            foreach ($headers as $i => $h) {
                                if ($h === null) continue;
                                $map[trim((string)$h)] = $i;
                            }
                            return $map;
                        }

                        private function cell(array $row, array $map, array $candidates): string
                        {
                            foreach ($candidates as $key) {
                                if (array_key_exists($key, $map)) {
                                    $i = $map[$key];
                                    return isset($row[$i]) ? trim((string)$row[$i]) : '';
                                }
                            }
                            return '';
                        }

                        private function mapStatus(?string $text): ?string
                        {
                            $t = trim(mb_strtolower((string)$text));
                            if ($t === '') return null;

                            $t = str_replace(['ى','يٰ'], ['ي','ي'], $t);

                            $active = ['active','فعال','فعّال','شغال','شغّال'];
                            $disc   = ['disconnected','مفصول','فصل','مقطوع','مقطوعة'];
                            $canc   = ['cancelled','canceled','ملغى','ملغي','ملغيّ','ملغاة'];

                            $chgMeter = ['changed_meter','change_meter','meter_changed','تم تغيير العداد','تغيير العداد','غير العداد','بدل العداد','بدّل العداد'];
                            $chgName  = ['changed_name','change_name','name_changed','تم تغيير الاسم','تغيير الاسم','غير الاسم','بدل الاسم','بدّل الاسم'];

                            if (in_array($t, $active, true))    return 'active';
                            if (in_array($t, $disc, true))      return 'disconnected';
                            if (in_array($t, $canc, true))      return 'cancelled';
                            if (in_array($t, $chgMeter, true))  return 'changed_meter';
                            if (in_array($t, $chgName, true))   return 'changed_name';

                            return null;
                        }

                        private function toBool(?string $text): bool
                        {
                            $t = mb_strtolower(trim((string)$text));
                            if ($t === '') return false;
                            $truthy = ['1','true','yes','y','on','نعم','اي','ايه','صح','مفعل','تشغيل'];
                            return in_array($t, $truthy, true);
                        }

                        private function toDecimalOrNull(?string $text): ?float
                        {
                            $t = trim((string)$text);
                            if ($t === '') return null;
                            $t = str_replace([',',' '], ['.',''], $t); // دعم 12,5
                            if (! is_numeric($t)) return null;
                            return round((float)$t, 4);
                        }
                    };

                    try {
                    // تنفيذ الاستيراد
                    Excel::import($importer, $fullPath);

                    // حذف الملف المؤقت
                    // try { Storage::disk('local')->delete($relative); } catch (\Throwable $e) {}

                $success = $c->created;
                $skippedCount = $c->skipped;
                $failedCount = $c->errors;

                // أسماء الفاشلين فقط
                $failedNames = array_map(
                    fn($f) => (string)($f['name'] ?? '—'),
                    $failed
                );

                $skippedNames = array_map(
                    fn($f) => (string)($f['name'] ?? '—'),
                    $skipped
                );

                $body = "✅ **نتيجة الاستيراد**\n";
                $body .= "────────────────────\n";
                $body .= "• ✅ نجح: {$success} فاتورة\n";
                $body .= "• ❌ فشل: {$failedCount} فاتورة\n"; 
                $body .= "• ⚠️  تجاهل: {$skippedCount} فاتورة\n";

                if ($failedCount > 0) {
                    $body .= "\n📋 **الأسماء المرفوضة:**\n";
                    $body .= implode("\n• ", $failedNames);
                }

                if ($skippedCount > 0) {
                    $body .= "\n\n📝 **الأسماء التي تم تجاهلها:**\n";
                    $body .= implode("\n• ", $skippedNames);
                }

               
                    // Immediate UI notification (user sees it now)
                    Notification::make()
                        ->title('نتيجة استيراد المشتركين') 
                        ->body($body)
                        ->success() // or warning()/danger() based on results
                        ->persistent()
                        ->send();
                     if ($user = auth()->user()) {
                    // Persistent database notification (user can see it later)
                    Notification::make()
                        ->title('نتيجة استيراد المشتركين') 
                        ->body($body)
                        ->success() // same type as above
                        ->sendToDatabase($user);
                    }

                    // حدّث الجدول
                    $this->dispatch('refresh');

                 } catch (\Throwable $e) {
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
                        if (!empty($relative) && Storage::disk('local')->exists($relative)) {
                            Storage::disk('local')->delete($relative);
                        }
                    }


                }),
        ];
    }
}
