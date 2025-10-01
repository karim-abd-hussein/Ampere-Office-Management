<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CycleResource\Pages;
use App\Models\Cycle;
use App\Models\Generator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class CycleResource extends Resource
{
    protected static ?string $model = Cycle::class;

    protected static ?string $navigationIcon   = 'heroicon-o-calendar';
    protected static ?string $navigationLabel  = 'الدورات';
    protected static ?string $modelLabel       = 'دورة';
    protected static ?string $pluralModelLabel = 'الدورات';
    protected static ?string $navigationGroup  = 'الفواتير والتقارير';

    /** ==== صلاحيات بشكل موحّد ==== */
    public static function isAdmin(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['مشرف', 'admin', 'super-admin']) ?? false;
    }

    public static function allowView(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('عرض الدورات'));
    }

    public static function allowCreate(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('إضافة الدورات'));
    }

    public static function allowManage(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('تعديل الدورات'));
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
            // ✅ تم حذف حقل المولدة كما طلبت
            Forms\Components\DatePicker::make('start_date')
                ->label('تاريخ البداية')
                ->required(),

            Forms\Components\DatePicker::make('end_date')
                ->label('تاريخ النهاية')
                ->required(),

            Forms\Components\Toggle::make('is_archived')
                ->label('مؤرشفة؟')
                ->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(false)
                    ->searchable(), // 🔎 لتفعيل البحث

                // عرض الترميز (code) المحسوب من المودل
                Tables\Columns\TextColumn::make('code')
                    ->label('الدورة')
                    ->sortable(false)
                    ->searchable(), // 🔎 لتفعيل البحث

                Tables\Columns\TextColumn::make('start_date')
                    ->label('من')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('إلى')
                    ->date()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_archived')
                    ->label('مؤرشفة؟')
                    ->boolean(),
            ])
            ->filters([
                // إذا حابب تبقي فلتر المولّدة (للدورات القديمة التي لها مولدة)
                SelectFilter::make('generator_id')
                    ->label('المولدة')
                    ->options(fn () => Generator::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()
                    ),
            ])
            ->actions([
                EditAction::make()->label('تعديل')->visible(fn () => static::allowManage()),
                DeleteAction::make()->label('حذف')->visible(fn () => static::allowManage()),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCycles::route('/'),
            'create' => Pages\CreateCycle::route('/create'),
            'edit'   => Pages\EditCycle::route('/{record}/edit'),
        ];
    }
}
