<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceiptResource\Pages;
use App\Models\Receipt;
use App\Models\Cycle;
use App\Models\Generator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

// Actions
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;

class ReceiptResource extends Resource
{
    protected static ?string $model = Receipt::class;

    protected static ?string $navigationIcon   = 'heroicon-o-document-text';
    protected static ?string $navigationLabel  = 'الوصولات';
    protected static ?string $pluralModelLabel = 'الوصولات';
    protected static ?string $modelLabel       = 'وصل';
    protected static ?string $navigationGroup  = 'الفواتير والتقارير';
    protected static ?int    $navigationSort   = 9;

    /** ==== صلاحيات ==== */
    public static function isAdmin(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['مشرف', 'admin', 'super-admin']) ?? false;
    }

    public static function allowView(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('عرض الوصولات'));
    }

    public static function allowCreate(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('إضافة الوصولات'));
    }

    public static function allowManage(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('تعديل الوصولات'));
    }

    public static function canPrint(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('طباعة الوصولات'));
    }

    public static function canGenerate(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('توليد الوصولات'));
    }

    /** عنصر القائمة يظهر فقط لمن معه عرض */
    public static function shouldRegisterNavigation(): bool
    {
        return static::allowView();
    }

    /** ربط صلاحيات Filament الافتراضية */
    public static function canViewAny(): bool       { return static::allowView(); }
    public static function canCreate(): bool        { return static::allowCreate(); }
    public static function canEdit($record): bool   { return static::allowManage(); }
    public static function canDelete($record): bool { return static::allowManage(); }
    public static function canDeleteAny(): bool     { return static::allowManage(); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('invoice_id')
                ->label('الفاتورة')
                ->relationship('invoice', 'id')
                ->searchable()
                ->required(),

            Forms\Components\Select::make('type')
                ->label('نوع الوصل')
                ->options([
                    'user'      => 'زبون',
                    'collector' => 'جابي',
                ])
                ->required(),

            Forms\Components\DateTimePicker::make('issued_at')
                ->label('تاريخ الإصدار')
                ->default(now())
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Eager load بدون طلب عمود code (هو Accessor)
            ->modifyQueryUsing(fn ($query) => $query->with([
                'invoice:id,cycle_id,subscriber_id,subscriber_name',
                'invoice.subscriber:id,name,generator_id',
                'invoice.subscriber.generator:id,name',
                'invoice.cycle:id,start_date',
            ]))
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('رقم الوصل')->sortable(),
                // Tables\Columns\TextColumn::make('short_code')->label('رمز الوصل')->copyable()->toggleable(),
                Tables\Columns\TextColumn::make('invoice.id')->label('رقم الفاتورة')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('invoice.subscriber_name')->label('المشترك')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('invoice.subscriber.generator.name')->label('المولّدة')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('invoice.cycle.code')->label('الدورة')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('type')->label('النوع')
                    ->formatStateUsing(fn (string $state) => $state === 'user' ? 'زبون' : 'جابي')
                    ->badge()->toggleable(),
                Tables\Columns\TextColumn::make('issued_at')->label('تاريخ الإصدار')->dateTime('Y-m-d H:i')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // 🔎 بحث برمز الوصل القصير
                // Filter::make('short')
                //     ->label('بحث برمز الوصل')
                //     ->form([
                //         TextInput::make('code')->label('رمز الوصل')->placeholder('مثال: R1Z3K9'),
                //     ])
                //     ->query(function (Builder $query, array $data) {
                //         $code = trim((string)($data['code'] ?? ''));
                //         if ($code === '') return $query;
                //         $id = \App\Models\Receipt::decodeShortCode($code);
                //         return $id ? $query->whereKey($id) : $query->whereRaw('0=1');
                //     }),

                // حسب الدورة
                SelectFilter::make('cycle_id')
                    ->label('حسب الدورة')
                    ->options(fn () => Cycle::query()->orderByDesc('start_date')->get()->mapWithKeys(fn ($c) => [$c->id => $c->code])->all())
                    ->placeholder('الكل')->preload()->searchable()
                    ->query(function (Builder $query, array $data) {
                        $val = $data['value'] ?? null;
                        if (!$val) return $query;
                        return $query->whereHas('invoice', fn (Builder $q) => $q->where('cycle_id', (int) $val));
                    }),

                // حسب المولّدة
                SelectFilter::make('generator_id')
                    ->label('حسب المولّدة')
                    ->options(fn () => Generator::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->placeholder('الكل')->preload()->searchable()
                    ->query(function (Builder $query, array $data) {
                        $val = $data['value'] ?? null;
                        if (!$val) return $query;
                        return $query->whereHas('invoice.subscriber', fn (Builder $q) => $q->where('generator_id', (int) $val));
                    }),
            ])
            ->paginated([5, 10, 25])
            ->actions([
                Action::make('print')
                    ->label('عرض / طباعة')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => route('receipts.print', ['ids' => $record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn () => static::canPrint()),

                EditAction::make()->label('تعديل')->visible(fn () => static::allowManage()),
                DeleteAction::make()->label('حذف')->visible(fn () => static::allowManage()),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label('حذف جماعي')->visible(fn () => static::allowManage()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListReceipts::route('/'),
            'create' => Pages\CreateReceipt::route('/create'),
            'edit'   => Pages\EditReceipt::route('/{record}/edit'),
        ];
    }
}
