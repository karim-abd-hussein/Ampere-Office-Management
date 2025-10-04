<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GeneratorResource\Pages;
use App\Models\Area;
use App\Models\Generator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GeneratorResource extends Resource
{
    protected static ?string $model = Generator::class;

    protected static ?string $navigationIcon   = 'heroicon-o-bolt';
    protected static ?string $navigationLabel  = 'المولدات';
    protected static ?string $pluralModelLabel = 'المولدات';
    protected static ?string $navigationGroup  = 'إدارة الكهرباء';
    protected static ?string $modelLabel       = 'مولدة';

    /* ===== صلاحيات موحّدة ===== */
    public static function isAdmin(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['مشرف', 'admin', 'super-admin']) ?? false;
    }
    public static function allowView(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('عرض المولدات'));
    }
    public static function allowCreate(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('إضافة المولدات'));
    }
    public static function allowManage(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('تعديل المولدات'));
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

    public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->with(['area', 'tariffs']);
}



    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('اسم المولدة')
                ->required(),

            Forms\Components\TextInput::make('code')
                ->label('رمز المولدة')
                ->required()
                ->unique(ignoreRecord: true),

            Forms\Components\Select::make('area_id')
                ->label('المنطقة')
                ->relationship('area', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Textarea::make('location')
                ->label('الموقع')
                ->rows(2),

            Forms\Components\Toggle::make('is_active')
                ->label('مفعّلة')
                ->default(true),

            // شرائح التسعير
            Forms\Components\Repeater::make('tariffs')
                ->label('شرائح التسعير (ك.و.س)')
                ->relationship() // uses generator->tariffs()
                ->columns(3)
                ->reorderable(true)
                ->minItems(1)
                ->defaultItems(1)
                ->schema([
                    Forms\Components\TextInput::make('from_kwh')
                        ->label('من (ك.و.س)')
                        ->numeric()->minValue(0)->required(),

                    Forms\Components\TextInput::make('to_kwh')
                        ->label('إلى (ك.و.س)')
                        ->numeric()->minValue(0)->nullable()
                        ->helperText('اتركه فارغاً للّامحدود'),

                    Forms\Components\TextInput::make('price_per_kwh')
                        ->label('سعر الكيلو')
                        ->numeric()->step('0.01')->minValue(0)->required(),
                ])
                ->itemLabel(fn (array $state) => isset($state['from_kwh'])
                    ? ($state['from_kwh'] . ' → ' . ($state['to_kwh'] ?? '∞'))
                    : null)
                ->helperText('رتّب من الأصغر للأكبر. لا تجعل الشرائح متداخلة.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),

                TextColumn::make('name')
                    ->label('اسم المولدة')
                    ->searchable(),

                TextColumn::make('code')
                    ->label('الرمز')
                    ->searchable(),

                TextColumn::make('area.name')
                    ->label('المنطقة')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('tariffs_summary')
                    ->label('التسعير')
                    ->getStateUsing(function (Generator $record) {
                        return $record->tariffs
                            ->sortBy('from_kwh')
                            ->map(fn ($t) => "{$t->from_kwh}-" . ($t->to_kwh ?? '∞') . ' @ ' . number_format((float)$t->price_per_kwh, 0))
                            ->implode(' | ');
                    })
                    ->wrap(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('فعالة'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->date(),
            ])
            ->filters([
                // ✅ فلتر حسب المنطقة
                SelectFilter::make('area_id')
                    ->label('حسب المنطقة')
                    ->options(fn () => Area::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->preload()
                    ->searchable(),

                // ✅ فلتر حسب الحالة
                SelectFilter::make('is_active')
                    ->label('حسب الحالة')
                    ->options([
                        '1' => 'مفعّلة',
                        '0' => 'غير مفعّلة',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $val = $data['value'] ?? null;
                        if ($val === '1' || $val === 1) {
                            return $query->where('is_active', 1);
                        }
                        if ($val === '0' || $val === 0) {
                            return $query->where('is_active', 0);
                        }
                        return $query;
                    }),
            ])
            ->paginated([5, 10, 25])
            ->actions([
                EditAction::make()->visible(fn () => static::allowManage()),
                DeleteAction::make()->visible(fn () => static::allowManage()),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->visible(fn () => static::allowManage()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGenerators::route('/'),
            'create' => Pages\CreateGenerator::route('/create'),
            'edit'   => Pages\EditGenerator::route('/{record}/edit'),
        ];
    }
}
