<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\Summarizers\Sum;

// جديد: أزرار الصف والإجرائيات
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon   = 'heroicon-o-building-office';
    protected static ?string $navigationLabel  = 'الشركات';
    protected static ?string $pluralModelLabel = 'الشركات';
    protected static ?string $modelLabel       = 'شركة';
    protected static ?string $navigationGroup  = 'إدارة الاشتراكات';
    protected static ?int    $navigationSort   = 2;

    /** ==== صلاحيات ==== */
    public static function isAdmin(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['مشرف', 'admin', 'super-admin']) ?? false;
    }

    public static function allowView(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('عرض الشركات'));
    }

    public static function allowCreate(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('إضافة الشركات'));
    }

    public static function allowManage(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('تعديل الشركات'));
    }

    /** عنصر القائمة يظهر فقط لمن معه عرض */
    public static function shouldRegisterNavigation(): bool
    {
        return static::allowView();
    }

    /** يضبط أزرار CRUD تلقائيًا */
    public static function canViewAny(): bool       { return static::allowView(); }
    public static function canCreate(): bool        { return static::allowCreate(); }
    public static function canEdit($record): bool   { return static::allowManage(); }
    public static function canDelete($record): bool { return static::allowManage(); }
    public static function canDeleteAny(): bool     { return static::allowManage(); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('اسم الشركة')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('رقم الهاتف')
                        ->tel()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('ampere')
                        ->label('الأمبير')
                        ->numeric()
                        ->minValue(0)
                        ->step('0.01')
                        ->required(),

                    Forms\Components\TextInput::make('price_per_amp')
                        ->label('سعر الأمبير')
                        ->numeric()
                        ->minValue(0)
                        ->step('0.01')
                        ->required(),

                    Forms\Components\TextInput::make('fixed_amount')
                        ->label('المبلغ الثابت')
                        ->numeric()
                        ->minValue(0)
                        ->step('0.01')
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'active'       => 'فعال',
                            'disconnected' => 'مفصول',
                            'cancelled'    => 'ملغى',
                        ])
                        ->required()
                        ->default('active'),

                    Forms\Components\Textarea::make('notes')
                        ->label('ملاحظات')
                        ->columnSpanFull()
                        ->rows(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('رقم')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label('اسم الشركة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('ampere')
                    ->label('الأمبير')
                    ->sortable()
                    ->alignCenter()
                    ->summarize([
                        Sum::make()
                            ->label('المجموع')
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2)),
                    ])
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2)),

                TextColumn::make('price_per_amp')
                    ->label('سعر الأمبير')
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0)),

                TextColumn::make('fixed_amount')
                    ->label('المبلغ الثابت')
                    ->sortable()
                    ->alignCenter()
                    ->summarize([
                        Sum::make()
                            ->label('المجموع')
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 0)),
                    ])
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0)),

                BadgeColumn::make('status')
                    ->label('الحالة')
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

                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active'       => 'فعال',
                        'disconnected' => 'مفصول',
                        'cancelled'    => 'ملغى',
                    ]),
            ])
            ->paginated([5, 10, 25])
            ->actions([
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
            'index'  => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit'   => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
