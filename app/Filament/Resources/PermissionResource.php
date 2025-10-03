<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Spatie\Permission\Models\Permission;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon  = 'heroicon-o-lock-closed';
    protected static ?string $navigationLabel = 'الصلاحيات';
    protected static ?string $pluralLabel     = 'الصلاحيات';
    protected static ?string $modelLabel      = 'صلاحية';
    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    /* ===== صلاحيات موحّدة ===== */
    public static function isAdmin(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['مشرف','admin','super-admin']) ?? false;
    }
    public static function allowView(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('عرض الصلاحيات'));
    }
    public static function allowCreate(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('إضافة الصلاحيات'));
    }
    public static function allowManage(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('تعديل الصلاحيات'));
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
                ->label('اسم الصلاحية')
                ->required()
                ->unique(ignoreRecord: true),

            Forms\Components\Hidden::make('guard_name')->default('web'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->searchable(),
                TextColumn::make('name')->label('اسم الصلاحية')->sortable()->searchable(),
            ])
            ->actions([
                EditAction::make()->label('تعديل')->visible(fn () => static::allowManage()),
                DeleteAction::make()->label('حذف')->visible(fn () => static::allowManage())->requiresConfirmation(),
            ])
            ->paginated([5, 10, 25])
            ->bulkActions([
                DeleteBulkAction::make()->label('حذف جماعي')->visible(fn () => static::allowManage()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit'   => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
