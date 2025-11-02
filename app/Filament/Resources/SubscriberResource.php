<?php

namespace App\Filament\Resources;

use App\Models\Subscriber;
use App\Models\Generator;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Resources\SubscriberResource\Pages;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;

use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;

use Illuminate\Support\Facades\DB;

class SubscriberResource extends Resource
{
    protected static ?string $model = Subscriber::class;

    protected static ?string $navigationIcon  = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'الزبائن';
    protected static ?string $pluralLabel     = 'الزبائن';
    protected static ?string $modelLabel      = 'زبون';
    protected static ?string $navigationGroup = 'إدارة الاشتراكات';

    /** ==== صلاحيات ==== */

    public static function isAdmin(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['مشرف', 'admin', 'super-admin']) ?? false;
    }

    public static function allowView(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('عرض المشتركين'));
    }

    public static function allowCreate(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('اضافة المشتركين'));
    }

    public static function allowManage(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('تعديل المشتركين'));
    }

    public static function canImport(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('استيراد المشتركين'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::allowView();
    }

    public static function canViewAny(): bool           { return static::allowView(); }
    public static function canCreate(): bool            { return static::allowCreate(); }
    public static function canEdit($record): bool       { return static::allowManage(); }
    public static function canDelete($record): bool     { return static::allowManage(); }
    public static function canDeleteAny(): bool         { return static::allowManage(); }

    /** ==== الفورم ==== */


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('اسم الزبون')
                ->datalist(fn () => Subscriber::query()
                    ->whereNotNull('name')->orderBy('name')->limit(200)
                    ->pluck('name')->unique()->values()->all()
                )
                ->autocomplete('off')
                ->required(),

            Forms\Components\TextInput::make('phone')
                ->label('رقم الهاتف')
                ->tel(),

            Forms\Components\TextInput::make('meter_number')
                ->label('رقم العداد')
                ->required(),

            Forms\Components\TextInput::make('box_number')
                ->label('رقم العلبة')
                ->maxLength(50)
                ->nullable(),

            // Forms\Components\TextInput::make('code_id')
            //     ->label('id')
            //     ->maxLength(50)
            //     ->nullable(),

            Select::make('generator_id')
                ->label('المولدة')
                ->relationship('generator', 'name')
                ->searchable()
                ->required(),

            // Select::make('status')
            //     ->label('الحالة')
            //     ->options([
            //         'active'         => 'فعال',
            //         'disconnected'   => 'مفصول',
            //         'cancelled'      => 'ملغى',
            //         'changed_meter'  => 'تم تغيير العداد',
            //         'changed_name'   => 'تم تغيير الاسم',
            //     ])
            //     ->default('active')
            //     ->required(),

            DatePicker::make('subscription_date')
                ->label('تاريخ الاشتراك')
                ->required(),

            // ======== التسعير الثابت ========
            Toggle::make('use_fixed_price')
                ->label('سعر الكيلو ثابت لهذا المشترك')
                ->helperText('لو فعّلتها، الفواتير للمشترك تحسب بسعر الكيلو الثابت وتجاهل شرائح المولّدة.')
                ->reactive()
                ->default(false),

            Forms\Components\TextInput::make('fixed_kwh_price')
                ->label('سعر الكيلو الثابت')
                ->numeric()
                ->minValue(0)
                ->step('0.0001')
                ->placeholder('مثال: 125')
                ->suffix('ل.س')
                ->visible(fn (Get $get) => (bool) $get('use_fixed_price'))
                ->required(fn (Get $get) => (bool) $get('use_fixed_price'))
                ->dehydrated(fn (Get $get) => (bool) $get('use_fixed_price'))
                ->nullable(),

            // ملاحظة: لا يوجد حقل import_ref هنا — يتم تعبئته تلقائياً أثناء الاستيراد من الإكسل.
        ]);
    }

    /** ==== الجدول ==== */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code_id')->label('id')->sortable()->searchable(),
                TextColumn::make('name')->label('الاسم')->sortable()->searchable(),
                 TextColumn::make('phone')->label('رقم الهاتف')->sortable()->searchable(),
                TextColumn::make('meter_number')->label('رقم العداد')->sortable()->searchable(),
                TextColumn::make('box_number')->label('رقم العلبة')->sortable()->searchable(),
                TextColumn::make('generator.name')->label('المولدة')->sortable()->searchable(),
                // BadgeColumn::make('status')
                //     ->label('الحالة')
                //     ->formatStateUsing(fn (?string $state) => match ($state) {
                //         'active'         => 'فعال',
                //         'disconnected'   => 'مفصول',
                //         'cancelled'      => 'ملغى',
                //         'changed_meter'  => 'تم تغيير العداد',
                //         'changed_name'   => 'تم تغيير الاسم',
                //         default          => '—',
                //     })
                //     ->colors([
                //         'success' => fn ($state) => $state === 'active',
                //         'warning' => fn ($state) => $state === 'disconnected',
                //         'danger'  => fn ($state) => $state === 'cancelled',
                //         'info'    => fn ($state) => in_array($state, ['changed_meter','changed_name'], true),
                //     ]),

                // نوع التسعير
                BadgeColumn::make('use_fixed_price')
                    ->label('نوع التسعير')
                    ->formatStateUsing(fn ($state) => $state ? 'سعر ثابت' : 'شرائح المولّدة')
                    ->colors([
                        'success'  => fn ($state) => (bool) $state,
                        'secondary'=> fn ($state) => ! (bool) $state,
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),

                // سعر الكيلو الثابت
                TextColumn::make('fixed_kwh_price')
                    ->label('سعر الكيلو')
                    ->formatStateUsing(fn ($state) => is_null($state) ? '—' : number_format((float)$state))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('subscription_date')->label('تاريخ الاشتراك')->date()->sortable(),

                // رقم الاستيراد (مخفي افتراضياً ويمكن إظهاره)
                TextColumn::make('import_ref')
                    ->label('رقم الاستيراد')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('generator_id')
                    ->label('حسب المولّدة')
                    ->options(fn () => Generator::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->multiple()->placeholder('الكل')->preload()->searchable()
                    ->query(function (Builder $query, array $data) {
                        $vals = $data['values'] ?? (isset($data['value']) ? [$data['value']] : []);
                        $vals = array_filter(array_map('intval', (array) $vals));
                        if (empty($vals)) return $query;
                        return $query->whereIn('generator_id', $vals);
                    }),

                // SelectFilter::make('status')
                //     ->label('حسب الحالة')
                //     ->options([
                //         'active'        => 'فعال',
                //         'disconnected'  => 'مفصول',
                //         'cancelled'     => 'ملغى',
                //         'changed_meter' => 'تم تغيير العداد',
                //         'changed_name'  => 'تم تغيير الاسم',
                //     ])
                //     ->multiple()->placeholder('الكل')
                //     ->query(function (Builder $query, array $data) {
                //         $vals = $data['values'] ?? (isset($data['value']) ? [$data['value']] : []);
                //         $vals = array_values(array_filter((array) $vals));
                //         if (empty($vals)) return $query;
                //         return $query->whereIn('status', $vals);
                //     }),

                // فلتر تاريخ الاشتراك
                Filter::make('subscription_date')
                    ->label('حسب تاريخ الاشتراك')
                    ->form([
                        DatePicker::make('from')->label('من'),
                        DatePicker::make('until')->label('إلى'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $from  = $data['from']  ?? null;
                        $until = $data['until'] ?? null;

                        return $query
                            ->when($from,  fn ($q) => $q->whereDate('subscription_date', '>=', $from))
                            ->when($until, fn ($q) => $q->whereDate('subscription_date', '<=', $until));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (! empty($data['from']))  $indicators[] = 'من: ' . $data['from'];
                        if (! empty($data['until'])) $indicators[] = 'إلى: ' . $data['until'];
                        return $indicators;
                    }),

                // فلتر رقم الاستيراد
                SelectFilter::make('import_ref')
                    ->label('حسب رقم الاستيراد')
                    ->options(fn () => Subscriber::query()
                        ->whereNotNull('import_ref')
                        ->distinct()
                        ->orderBy('import_ref')
                        ->pluck('import_ref', 'import_ref')
                        ->all()
                    )
                    ->placeholder('الكل')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data) {
                        $vals = $data['values'] ?? (isset($data['value']) ? [$data['value']] : []);
                        $vals = array_values(array_filter((array) $vals));
                        if (empty($vals)) return $query;
                        return $query->whereIn('import_ref', $vals);
                    }),

                // فلتر نوع التسعير (ثابت/شرائح)
                SelectFilter::make('use_fixed_price')
                    ->label('نوع التسعير')
                    ->options(['1' => 'سعر ثابت', '0' => 'شرائح المولّدة'])
                    ->query(function (Builder $query, array $data) {
                        $val = $data['value'] ?? null;
                        if ($val === '1') return $query->where('use_fixed_price', true);
                        if ($val === '0') return $query->where('use_fixed_price', false);
                        return $query;
                    }),
            ])
            ->defaultSort('id', 'asc')
            ->modifyQueryUsing(fn ($query) => $query->with('generator'))
            ->paginated([5, 10, 25])
            ->actions([
                EditAction::make()
                    ->tooltip('تعديل')
                    ->visible(fn () => static::allowManage()),
                DeleteAction::make()
                    ->tooltip('حذف')
                    ->visible(fn () => static::allowManage()),
            ])

            ->bulkActions([
                DeleteBulkAction::make()
                    ->visible(fn () => static::allowManage())
                    ->after(function () {
                        try {
                            DB::statement('ALTER TABLE `subscribers` AUTO_INCREMENT = 1');
                        } catch (\Throwable $e) {
                            // نتجاهل أي خطأ
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSubscribers::route('/'),
            'create' => Pages\CreateSubscriber::route('/create'),
            'edit'   => Pages\EditSubscriber::route('/{record}/edit'),
        ];
    }
}
