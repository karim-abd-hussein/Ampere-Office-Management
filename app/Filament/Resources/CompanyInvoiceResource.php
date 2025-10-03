<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyInvoiceResource\Pages;
use App\Models\CompanyInvoice;
use App\Models\Cycle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class CompanyInvoiceResource extends Resource
{
    protected static ?string $model = CompanyInvoice::class;

    protected static ?string $navigationIcon   = 'heroicon-o-receipt-percent';
    protected static ?string $navigationLabel  = 'فواتير الشركات';
    protected static ?string $navigationGroup  = 'الفواتير والتقارير';
    protected static ?string $pluralModelLabel = 'فواتير الشركات';
    protected static ?string $modelLabel       = 'فاتورة شركة';

    /** ===== صلاحيات موحّدة ===== */
    public static function isAdmin(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['مشرف','admin','super-admin']) ?? false;
    }
    public static function allowView(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('عرض فواتير الشركات'));
    }
    public static function allowManage(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('تعديل فواتير الشركات'));
    }
    public static function allowGenerate(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('توليد فواتير الشركات'));
    }
    public static function allowExport(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('تصدير فواتير الشركات'));
    }
    public static function allowPrint(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('طباعة فواتير الشركات'));
    }
    public static function allowAddCompany(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('إضافة الشركات'));
    }

    /** إظهار عنصر القائمة فقط لمن معه عرض */
    public static function shouldRegisterNavigation(): bool
    {
        return static::allowView();
    }

    /** ربط صلاحيات Filament الافتراضية */
    public static function canViewAny(): bool       { return static::allowView(); }
    public static function canCreate(): bool        { return false; } // ما في صفحة create لهالمورد
    public static function canEdit($record): bool   { return static::allowManage(); }
    public static function canDelete($record): bool { return static::allowManage(); }
    public static function canDeleteAny(): bool     { return static::allowManage(); }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['company:id,name,phone,status', 'cycle:id,start_date']);
            })
            ->columns([
                TextColumn::make('company.name')
                    ->label('الشركة')
                    ->searchable()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('المجموع')->using(fn () => null),
                    ]),

                TextColumn::make('company.phone')
                    ->label('الهاتف')
                    ->toggleable(),

                TextColumn::make('cycle.code')
                    ->label('الدورة')
                    ->toggleable(),

                // الأمبير (قابل للتعديل عند وجود صلاحية)
                TextInputColumn::make('ampere')
                    ->label('الأمبير')
                    ->rules(['numeric', 'min:0'])
                    ->extraAttributes([
                        'inputmode' => 'decimal',
                        'step'      => '0.01',
                        'min'       => '0',
                        'class'     => 'px-1 text-center',
                    ])
                    ->disabled(fn () => ! static::allowManage())
                    ->afterStateUpdated(function ($state, \App\Models\CompanyInvoice $record) {
                        $record->ampere = (float) $state;
                        $record->save();
                    })
                    ->summarize([
                        Sum::make()->label('')->formatStateUsing(fn ($state) => number_format((float) $state, 2)),
                    ]),

                // سعر الأمبير (قابل للتعديل عند وجود صلاحية)
                TextInputColumn::make('price_per_amp')
                    ->label('سعر الأمبير')
                    ->rules(['numeric', 'min:0'])
                    ->extraAttributes([
                        'inputmode' => 'decimal',
                        'step'      => '0.01',
                        'min'       => '0',
                        'class'     => 'px-1 text-center',
                    ])
                    ->disabled(fn () => ! static::allowManage())
                    ->afterStateUpdated(function ($state, \App\Models\CompanyInvoice $record) {
                        $record->price_per_amp = (float) $state;
                        $record->save();
                    }),

                // المبلغ الثابت
                TextColumn::make('fixed_amount')
                    ->label('الثابت')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0))
                    ->summarize([
                        Sum::make()->label('المجموع')->formatStateUsing(fn ($state) => number_format((float) $state, 0)),
                    ]),

                // الإجمالي النهائي
                TextColumn::make('total_amount')
                    ->label('المبلغ النهائي')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0))
                    ->summarize([
                        Sum::make()->label('المجموع')->formatStateUsing(fn ($state) => number_format((float) $state, 0)),
                    ]),

                BadgeColumn::make('company.status')
                    ->label('حالة الشركة')
                    ->colors([
                        'success' => fn ($state) => $state === 'active',
                        'warning' => fn ($state) => $state === 'disconnected',
                        'danger'  => fn ($state) => $state === 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active'       => 'فعال',
                        'disconnected' => 'مفصول',
                        'cancelled'    => 'ملغى',
                        default        => '—',
                    }),
            ])
            ->defaultSort('company_id')
            ->paginated([5, 10, 25])
            ->filters([
                SelectFilter::make('cycle_id')
                    ->label('حسب الدورة')
                    ->options(fn () => Cycle::query()
                        ->orderByDesc('start_date')
                        ->get()
                        ->mapWithKeys(fn ($c) => [$c->id => $c->code])
                        ->all()
                    )
                    ->placeholder('اختر دورة')
                    ->preload()
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('حالة الشركة')
                    ->options([
                        'active'       => 'فعال',
                        'disconnected' => 'مفصول',
                        'cancelled'    => 'ملغى',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if (!$value) return $query;
                        return $query->whereHas('company', fn (Builder $q) => $q->where('status', $value));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label('طباعة وصل')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (\App\Models\CompanyInvoice $record) => route('print.company-invoice', ['invoice' => $record->id]))
                    ->openUrlInNewTab()
                    ->tooltip('فتح وصل الطباعة')
                    ->visible(fn () => static::allowPrint()),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn () => static::allowManage()),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('حذف جماعي')
                    ->visible(fn () => static::allowManage()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyInvoices::route('/'),
        ];
    }
}
