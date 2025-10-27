<?php

namespace App\Filament\Resources;

use App\Models\Invoice;
use App\Models\Cycle;
use App\Models\Area;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\InvoiceResource\Pages;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Support\Facades\DB;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon   = 'heroicon-o-receipt-refund';
    protected static ?string $navigationGroup  = 'الفواتير والتقارير';
    protected static ?string $modelLabel       = 'فاتورة';
    protected static ?string $pluralLabel      = 'الفواتير';

    /** ==== صلاحيات ==== */
    public static function isAdmin(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['مشرف', 'admin', 'super-admin']) ?? false;
    }

    public static function allowView(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('عرض الفواتير'));
    }

    public static function allowManage(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('تعديل الفواتير'));
    }

    public static function canGenerate(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('توليد الفواتير'));
    }

    public static function canExport(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('تصدير الفواتير'));
    }

    public static function canImport(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('استيراد الفواتير'));
    }

    /** عنصر القائمة يظهر فقط لمن معه عرض */
    public static function shouldRegisterNavigation(): bool
    {
        return static::allowView();
    }

    /** ربط صلاحيات Filament الافتراضية */
    public static function canViewAny(): bool       { return static::allowView(); }
    public static function canCreate(): bool        { return static::allowManage(); }
    public static function canEdit($record): bool   { return static::allowManage(); }
    public static function canDelete($record): bool { return static::allowManage(); }
    public static function canDeleteAny(): bool     { return static::allowManage(); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('subscriber_id')
                ->label('المشترك')
                ->relationship('subscriber', 'name')
                ->searchable()
                ->required(),

            Forms\Components\Select::make('collector_id')
                ->label('الجابي')
                ->relationship('collector', 'name')
                ->required(),

            Forms\Components\Select::make('cycle_id')
                ->label('الدورة')
                ->options(fn () => Cycle::query()
                    ->orderByDesc('start_date')
                    ->get()
                    ->mapWithKeys(fn ($c) => [$c->id => $c->code])
                    ->all()
                )
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('old_reading')->label('القراءة القديمة')->numeric()->required(),
            Forms\Components\TextInput::make('new_reading')->label('القراءة الجديدة')->numeric()->required(),
            Forms\Components\TextInput::make('unit_price_used')->label('سعر الكيلو المستخدم')->numeric()->required(),
            Forms\Components\TextInput::make('consumption')->label('الاستهلاك (كيلو واط)')->numeric()->required(),
            Forms\Components\TextInput::make('calculated_total')->label('السعر الكلي المحسوب')->numeric()->required(),
            Forms\Components\TextInput::make('final_amount')->label('المبلغ النهائي المدفوع')->numeric()->required(),
            Forms\Components\DateTimePicker::make('issued_at')->label('تاريخ الإصدار')->required(),

                   // ======== التسعير الثابت ========
            Toggle::make('subscriber_use_fixed_price')
                ->label('سعر الكيلو ثابت لهذة الفاتورة')
                ->helperText('لو فعّلتها، الفاتورة تحسب بسعر الكيلو الثابت وتجاهل شرائح المولّدة.')
                ->reactive()
                // ->default(false),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->alignCenter()
                    ->width('6rem')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('subscriber_name')
                    ->label('المشترك')
                    ->searchable()
                    ->sortable()
                    ->width('10rem'),

                TextColumn::make('subscriber_code_id')
                    ->label('ID المشترك')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->width('6رم'),

                TextInputColumn::make('subscriber_box_number')
                    ->label('رقم العلبة')
                    ->sortable()
                    ->rules(['required', 'string', 'max:255'])
                    ->toggleable(isToggledHiddenByDefault: true)
                     ->updateStateUsing(function ($state, $record) {
                            $record->subscriber_box_number = $state;
                            $record->save();
                        })
                    ->width('7rem'),

                TextColumn::make('subscriber_meter_number')
                    ->label('رقم العداد')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    // ->updateStateUsing(function ($state, $record) {
                    //     $record->subscriber_meter_number = $state;
                    //     $record->save();
                    // })
                    ->width('7rem'),

                TextInputColumn::make('subscriber_phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                      ->updateStateUsing(function ($state, $record) {
                            $record->subscriber_phone = $state;
                            $record->save();
                        })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('generator.name')
                    ->label('المولّدة')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->width('8rem'),

               SelectColumn::make('collector_id')
                ->label('الجابي')
                ->options(\App\Models\Collector::pluck('name', 'id')) // Adjust model name if different
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->width('8rem'),

                TextColumn::make('cycle.code')
                    ->label('الدورة')
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->width('8rem'),

                TextColumn::make('old_reading')
                    ->label('القراءة القديمة')
                    ->sortable()
                    ->width('6rem')
                    ->formatStateUsing(fn ($state) => $state === null ? '' : (string) (int) $state),

                // ===== القراءة الجديدة + منع انتقال عند قيمة أصغر + تنبيه =====
                TextInputColumn::make('new_reading')
                    ->label('القراءة الجديدة')
                    ->rules(['numeric', 'min:0'])
                    ->sortable()
                    ->width('7rem')
                    ->extraInputAttributes(fn (Invoice $record) => [
                        'class' => 'px-1',
                        'inputmode' => 'numeric',
                        'data-new-reading' => '1',
                        'data-old' => (string) (float) ($record->old_reading ?? 0),
                        'data-new' => (string) (float) ($record->new_reading ?? 0),

                        // Enter = التالي (مع فحص القيمة)
                        'x-on:keydown.enter.prevent.stop' => '
                            const oldVal = parseFloat($el.dataset.old || "0");
                            const newRVal = parseFloat($el.dataset.new || "0");
                            const newVal = parseFloat(($el.value || "0"));
                            if (!Number.isNaN(newVal) && newVal < oldVal) {
                                $el.value = newRVal >= oldVal ?newRVal :oldVal ;
                                alert("القراءة الجديدة أصغر من القراءة القديمة. سوف يتم أرجاع القيمة "+$el.value);
                                return;
                            }
                            const inputs = Array.from(document.querySelectorAll("input[data-new-reading=\"1\"]"));
                            const idx = inputs.indexOf($el);
                            if (idx !== -1) {
                                const next = inputs[idx + 1];
                                if (next) {
                                    $el.dispatchEvent(new Event("change", { bubbles: true }));
                                    $el.blur();
                                    $nextTick(() => {
                                        next.focus();
                                        if (typeof next.select === "function") next.select();
                                        next.scrollIntoView({ block: "center" });
                                    });
                                }
                            }
                        ',

                        // ↓ = التالي مع فحص القيمة
                        'x-on:keydown.arrow-down.prevent.stop' => '
                            const oldVal = parseFloat($el.dataset.old || "0");
                             const newRVal = parseFloat($el.dataset.new || "0");
                            const newVal = parseFloat(($el.value || "0"));
                            if (!Number.isNaN(newVal) && newVal < oldVal) {
                                $el.value = newRVal >= oldVal ?newRVal :oldVal ;
                                alert("القراءة الجديدة أصغر من القراءة القديمة. سوف يتم أرجاع القيمة "+$el.value);
                                return;
                            }
                            const inputs = Array.from(document.querySelectorAll("input[data-new-reading=\"1\"]"));
                            const idx = inputs.indexOf($el);
                            const next = inputs[idx + 1];
                            if (next) {
                                $el.dispatchEvent(new Event("change", { bubbles: true }));
                                $el.blur();
                                $nextTick(() => { next.focus(); next.select && next.select(); next.scrollIntoView({ block: "center" }); });
                            }
                        ',

                        // ↑ = السابق مع فحص القيمة
                        'x-on:keydown.arrow-up.prevent.stop' => '
                            const oldVal = parseFloat($el.dataset.old || "0");
                             const newRVal = parseFloat($el.dataset.new || "0");
                            const newVal = parseFloat(($el.value || "0"));
                            if (!Number.isNaN(newVal) && newVal < oldVal) {
                              $el.value = newRVal >= oldVal ?newRVal :oldVal ;
                                alert("القراءة الجديدة أصغر من القراءة القديمة. سوف يتم أرجاع القيمة "+$el.value);
                                return;
                            }
                            const inputs = Array.from(document.querySelectorAll("input[data-new-reading=\"1\"]"));
                            const idx = inputs.indexOf($el);
                            const prev = inputs[idx - 1];
                            if (prev) {
                                $el.dispatchEvent(new Event("change", { bubbles: true }));
                                $el.blur();
                                $nextTick(() => { prev.focus(); prev.select && prev.select(); prev.scrollIntoView({ block: "center" }); });
                            }
                        ',
                    ])
                    ->disabled(fn () => ! static::allowManage())
                    ->afterStateUpdated(function ($state, Invoice $record) {
                        // ممنوع قيمة أصغر من القديمة: لا نحفظ وننبه
                        $isEmpty = ($state === '' || $state === null);
                        $old  = (float) ($record->old_reading ?? 0);
                        if (!$isEmpty) {
                            $newVal = (float) $state;
                            if ($newVal < $old) {
                                Notification::make()
                                    ->title('القراءة الجديدة أصغر من القراءة القديمة')
                                    ->warning()
                                    ->persistent()
                                    ->send();

                                  if ($user = auth()->user()) {
                                    Notification::make()
                                   ->title('القراءة الجديدة أصغر من القراءة القديمة')
                                    ->body("القراءة الجديدة: {$newVal} | القراءة القديمة: {$old} ")
                                    ->warning()
                                    ->sendToDatabase($user);
                                 }

                                return; // لا نحفظ أي شيء
                            }
                        }

                        $new  =  (float) $state;
                        $cons = max(0, (float)($new ?? 0) - $old);

                        $record->load(
                            'subscriber:*',//id,use_fixed_price,fixed_kwh_price,generator_id
                            'generator:id,price_per_kwh',
                            'generator.tariffs:id,generator_id,from_kwh,to_kwh,price_per_kwh'
                        );

                        $generator = $record->generator ?: $record->subscriber?->generator;

                         // Check if subscriber uses fixed price

                        
                        if($record->subscriber_use_fixed_price){

                             $unit =$record->unit_price_used;
                        }else {
                             $unit = $generator
                            ? (float) $generator->priceForConsumption($cons)
                            : (float) ($record->unit_price_used ?? 0);
                        }
                           
                          
                           

                        $record->new_reading      = $new;
                        $record->consumption      = $cons;
                        $record->unit_price_used  = $unit;
                        $record->calculated_total = round($cons * $unit, 2);
                        $record->final_amount     = $record->calculated_total;
                        $record->save();

                        // تنبيه استهلاك صفر مرتين متتاليتين
                        if ((int)$record->consumption === 0) {
                            $record->loadMissing('cycle:id,start_date', 'subscriber:id,name');

                            $prev = Invoice::query()
                                ->join('cycles', 'cycles.id', '=', 'invoices.cycle_id')
                                ->where('invoices.subscriber_id', $record->subscriber_id)
                                ->where('invoices.id', '!=', $record->id)
                                ->where('cycles.start_date', '<', optional($record->cycle)->start_date)
                                ->orderBy('cycles.start_date', 'desc')
                                ->orderBy('invoices.id', 'desc')
                                ->select('invoices.*')
                                ->first();

                            if ($prev && (int) $prev->consumption === 0 && $record->subscriber_status=="active") {
                                $prev->loadMissing('cycle:id,start_date');
                                $name     = $record->subscriber_name ?? '—';
                                $prevCode = $prev->cycle?->code ?? '';
                                $nowCode  = $record->cycle?->code ?? '';

                                Notification::make()
                                    ->title('تنبيه: استهلاك صفر متكرر')
                                    ->body("المشترك {$name} استهلاكه صفر مرتين متتاليتين ({$prevCode} ثم {$nowCode}).")
                                    ->warning()
                                    ->persistent()
                                    ->send();

                             if ($user = auth()->user()) {
                                    Notification::make()
                                    ->title('تنبيه: استهلاك صفر متكرر')
                                    ->body("المشترك {$name} استهلاكه صفر مرتين متتاليتين ({$prevCode} ثم {$nowCode}).")
                                    ->warning()
                                    ->sendToDatabase($user);
                                 }
                            }
                        }
                    }),

                TextColumn::make('consumption')
                    ->label('الاستهلاك')
                    ->sortable()
                    ->width('6rem')
                    ->formatStateUsing(fn ($state) => $state === null ? '' : (string) (int) $state)
                    ->summarize([
                        Sum::make()
                            ->label('')
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0)),
                    ]),

             ToggleColumn::make('subscriber_use_fixed_price')
                    ->label('سعر ثابت')
                    ->onColor('success')  // Color when enabled
                    ->offColor('danger')  // Color when disabled
                    ->onIcon('heroicon-o-check')
                    ->offIcon('heroicon-o-x-mark')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('تفعيل أو تعطيل السعر الثابت'),

            TextInputColumn::make('unit_price_used')
                ->rules(['numeric', 'min:0'])
                ->label('سعر الكيلو')
                ->sortable()
                ->width('7rem')
                ->disabled(fn () => !static::allowManage())
                ->afterStateUpdated(function ($state, Invoice $record) {
                    try {
                        $unitPrice = (float) $state;
                        
                        // Update the record with calculations
                        $record->update([
                            'unit_price_used' => $unitPrice,
                            'calculated_total' => round($record->consumption * $unitPrice, 2),
                            'final_amount' => round($record->consumption * $unitPrice, 2),
                        ]);
                        
                        // Optional: Show success notification
                        Notification::make()
                            ->title('تم تحديث السعر والحسابات بنجاح')
                            ->success()
                            ->send();


                        if ($user = auth()->user()) {
                        Notification::make()
                        ->title('تم تحديث السعر والحسابات بنجاح')
                        ->success()
                        ->sendToDatabase($user);
                        }
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('خطأ في تحديث السعر')
                            ->body('حدث خطأ أثناء حفظ البيانات')
                            ->danger()
                            ->send();


                      if ($user = auth()->user()) {
                        Notification::make()
                        ->title('خطأ في تحديث السعر')
                        ->body('حدث خطأ أثناء حفظ البيانات')
                        ->danger()
                        ->sendToDatabase($user);
                        }
                            
                    }
                }),

                TextColumn::make('final_amount')
                    ->label('المبلغ النهائي')
                    ->sortable()
                    ->width('8rem')
                    // ->html()
                    ->formatStateUsing(function ($state, Invoice $record) {
                    //     $final = (float) ($record->final_amount ?? 0);
                    //     $calc  = (float) ($record->calculated_total ?? 0);
                    //     $finalFmt = number_format($final);

                    //     if ($final < $calc - 0.0001) {
                    //         $calcFmt = number_format($calc, 2);
                    //         return '<div><div><strong>'.$finalFmt.'</strong></div>'
                    //             .'<div style="text-decoration:line-through;opacity:.65;font-size:.85em">'.$calcFmt.'</div></div>';
                    //     }

                        return  number_format($state);
                    })
                    ->summarize([
                        Sum::make()
                            ->label('')
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0)),
                    ]),

                // 🔹 عمود الملاحظة — مخفي افتراضياً
                TextColumn::make('note')
                    ->label('ملاحظة')
                    ->wrap()
                    ->limit(40)
                    ->tooltip(fn ($record) => (string)($record->note ?? ''))
                    ->toggleable(isToggledHiddenByDefault: true),

                SelectColumn::make('subscriber_status')
                ->label('الحالة')
                ->width('7rem')
                ->options([
                    'active'        => 'فعال',
                    'disconnected'  => 'مفصول',
                    'cancelled'     => 'ملغى',
                    'changed_meter' => 'تم تغيير العداد',
                    'changed_name'  => 'تم تغيير الاسم',
                ])
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable()
                ->afterStateUpdated(function ($record, $state) {
                    // Update the invoice field itself
                    $record->subscriber_status = $state;
                    $record->save();

                    // Update the actual subscriber model
                    // if ($record->subscriber) {
                    //     $record->subscriber->update(['status' => $state]);
                    // }
                }),


                    // old
                // BadgeColumn::make('subscriber.status')
                //     ->label('الحالة')
                //     ->width('7rem')
                //     ->colors([
                //         'success' => fn (?string $state): bool => $state === 'active',
                //         'warning' => fn (?string $state): bool => in_array($state, ['disconnected', 'changed_meter', 'changed_name'], true),
                //         'danger'  => fn (?string $state): bool => $state === 'cancelled',
                //     ])
                //     ->formatStateUsing(fn (?string $state) => match ($state) {
                //         'active'         => 'فعال',
                //         'disconnected'   => 'مفصول',
                //         'cancelled'      => 'ملغى',
                //         'changed_meter'  => 'تم تغيير العداد',
                //         'changed_name'   => 'تم تغيير الاسم',
                //         default          => '—',
                //     }),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['cycle.generator']))
            ->filters([
                SelectFilter::make('collector_id')
                    ->label('حسب الجابي')
                    ->relationship('collector', 'name')
                    ->placeholder('الكل')
                    ->preload()
                    ->searchable(),

                //  // Generator filter
                 SelectFilter::make('generator_id')
                        ->label('حسب المولّدة')
                        ->relationship('generator', 'name')
                        ->placeholder('الكل')
                        ->preload()
                        ->searchable(),


                SelectFilter::make('cycle_id')
                    ->label('حسب الدورة')
                    ->options(fn () => Cycle::query()
                        ->where('is_archived', 0)
                        ->orderByDesc('start_date')
                        ->get()
                        ->mapWithKeys(fn ($c) => [$c->id => $c->code])
                        ->all()
                    )
                    ->placeholder('الكل')
                    ->preload()
                    ->searchable(),


                SelectFilter::make('area_id')
                    ->label('حسب المنطقة')
                    ->options(fn () => Area::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->placeholder('الكل')
                    ->preload()
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        $val = $data['value'] ?? null;
                        if (!$val) return $query;
                        return $query->whereHas('generator', fn (Builder $q) => $q->where('area_id', (int) $val));
                    }),

                SelectFilter::make('subscriber_status')
                    ->label('حسب حالة المشترك')
                    ->options([
                        'active'         => 'فعال',
                        'disconnected'   => 'مفصول',
                        'cancelled'      => 'ملغى',
                        'changed_meter'  => 'تم تغيير العداد',
                        'changed_name'   => 'تم تغيير الاسم',
                    ])
                    ->multiple()
                    ->placeholder('الكل')
                    ->preload()
                    ->searchable(false)
                    ->query(function (Builder $query, array $data) {
                        $values = $data['values'] ?? (isset($data['value']) ? [$data['value']] : []);
                        $values = array_values(array_filter((array) $values));
                        if (empty($values)) return $query;

                        return $query->whereHas('subscriber', function (Builder $q) use ($values) {
                            $q->whereIn('status', $values);
                        });
                    }),

                Filter::make('new_reading_empty')
                    ->label('بدون قراءة جديدة')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereNull('new_reading')),

                Filter::make('consecutive_zeros')
                    ->label('استهلاك صفر مرتين متتاليتين')
                    ->query(function (Builder $query) {
                        $query->whereRaw("
                            invoices.consumption = 0
                            AND EXISTS (
                                SELECT 1
                                FROM invoices AS i2
                                WHERE i2.subscriber_id = invoices.subscriber_id
                                  AND i2.consumption = 0
                                  AND i2.id = (
                                      SELECT MAX(i3.id)
                                      FROM invoices AS i3
                                      WHERE i3.subscriber_id = invoices.subscriber_id
                                        AND i3.id < invoices.id
                                  )
                            )
                        ");
                    }),
            ])
            ->paginated([5, 10, 25])
            ->actions([
                Action::make('addNote')
                    ->label('ملاحظة')
                    ->icon('heroicon-o-pencil-square')
                    ->color('info')
                    ->visible(fn () => static::allowManage())
                    ->modalHeading('إضافة / تعديل ملاحظة')
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label('الملاحظة')
                            ->rows(6)
                            ->maxLength(5000)
                            ->placeholder('اكتب هنا ملاحظة لهذه الفاتورة...')
                            ->default(fn (Invoice $record) => (string)($record->note ?? '')),
                    ])
                    ->action(function (array $data, Invoice $record) {
                        $record->note = (string) ($data['note'] ?? '');
                        $record->save();

                        Notification::make()
                            ->title('تم حفظ الملاحظة')
                            ->success()
                            ->send();
                    })
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('md'),

                Action::make('discount')
                    ->label('خصم')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('warning')
                    ->visible(fn () => static::allowManage())
                    ->modalHeading('تعديل سعر الكيلو / المبلغ النهائي')
                    ->form([
                        Forms\Components\TextInput::make('unit_price_used')
                            ->label('سعر الكيلو')
                            ->numeric()->minValue(0)
                            ->default(fn (Invoice $record) => (float) $record->unit_price_used)
                            ->required(),
                        Forms\Components\TextInput::make('final_amount')
                            ->label('المبلغ النهائي (اتركه فارغًا ليُعاد حسابه تلقائياً)')
                            ->numeric()->minValue(0)
                            ->placeholder(fn (Invoice $record) => number_format((float) $record->final_amount, 2))
                            ->dehydrated(fn ($state) => filled($state)),
                    ])
                    ->action(function (array $data, Invoice $record) {
                        $unit = isset($data['unit_price_used']) && $data['unit_price_used'] !== ''
                            ? (float) $data['unit_price_used']
                            : (float) $record->unit_price_used;

                        $record->unit_price_used  = $unit;
                        $record->calculated_total = round(((float) $record->consumption) * $unit, 2);

                        if (array_key_exists('final_amount', $data)) {
                            $record->final_amount = round((float) $data['final_amount'], 2);
                        } else {
                            $record->final_amount = $record->calculated_total;
                        }

                        $record->save();
                    })
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('md'),

                DeleteAction::make()->label('حذف')->visible(fn () => static::allowManage()),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('حذف جماعي')
                    ->visible(fn () => static::allowManage())
                    ->after(function () {
                        try {
                            DB::statement('ALTER TABLE `invoices` AUTO_INCREMENT = 1');
                        } catch (\Throwable $e) {
                            // نتجاهل
                        }
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // مصفّى على الدورات غير المؤرشفة + eager loading انتقائي
        return parent::getEloquentQuery()
            ->whereHas('cycle', fn (Builder $q) => $q->where('is_archived', 0))
            ->with([
                'subscriber:id,name,phone,generator_id,status,box_number,meter_number',
                'collector:id,name',
                'cycle:id,start_date',
                'generator:id,name,area_id,price_per_kwh',
                'generator.tariffs:id,generator_id,from_kwh,to_kwh,price_per_kwh',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit'   => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
