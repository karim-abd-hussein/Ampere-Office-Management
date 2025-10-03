<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AreaResource\Pages;
use App\Models\Area;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AreaResource extends Resource
{
    protected static ?string $model = Area::class;

    protected static ?string $navigationLabel   = 'المناطق';
    protected static ?string $modelLabel        = 'منطقة';
    protected static ?string $pluralModelLabel  = 'المناطق';
    protected static ?string $navigationGroup   = 'إدارة الكهرباء';
    protected static ?string $navigationIcon    = 'heroicon-o-map';

    /* ===== صلاحيات موحّدة ===== */
    public static function isAdmin(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['مشرف','admin','super-admin']) ?? false;
    }
    public static function allowView(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('عرض المناطق'));
    }
    public static function allowCreate(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('إضافة المناطق'));
    }
    public static function allowManage(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('تعديل المناطق'));
    }

    /** ظهور عنصر القائمة فقط لمن معه عرض */
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
            Forms\Components\TextInput::make('name')
                ->label('اسم المنطقة')
                ->required()
                ->maxLength(100),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->searchable(),
                TextColumn::make('name')->label('اسم المنطقة')->searchable(),
            ])
            ->actions([
                EditAction::make()->label('تعديل')->visible(fn () => static::allowManage()),
                DeleteAction::make()->label('حذف')->visible(fn () => static::allowManage()),
            ])
            ->paginated([5, 10, 25])
            ->bulkActions([
                DeleteBulkAction::make()->label('حذف متعدد')->visible(fn () => static::allowManage()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAreas::route('/'),
            'create' => Pages\CreateArea::route('/create'),
            'edit'   => Pages\EditArea::route('/{record}/edit'),
        ];
    }
}
