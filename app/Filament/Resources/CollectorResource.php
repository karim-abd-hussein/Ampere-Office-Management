<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CollectorResource\Pages;
use App\Models\Collector;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder; // 👈 مهم

class CollectorResource extends Resource
{
    protected static ?string $model = Collector::class;

    protected static ?string $navigationIcon  = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'الجباة';
    protected static ?string $pluralLabel     = 'الجباة';
    protected static ?string $navigationGroup = 'إدارة التوزيع';
    protected static ?string $modelLabel      = 'جابي';

    /* ===== صلاحيات موحّدة ===== */
    public static function isAdmin(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['مشرف', 'admin', 'super-admin']) ?? false;
    }
    public static function allowView(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('عرض الجباة'));
    }
    public static function allowCreate(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('إضافة الجباة'));
    }
    public static function allowManage(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('تعديل الجباة'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::allowView();
    }

    public static function canViewAny(): bool       { return static::allowView(); }
    public static function canCreate(): bool        { return static::allowCreate(); }
    public static function canEdit($record): bool   { return static::allowManage(); }
    public static function canDelete($record): bool { return static::allowManage(); }
    public static function canDeleteAny(): bool     { return static::allowManage(); }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('الاسم')->required(),
            Forms\Components\TextInput::make('phone')->label('رقم الهاتف')->tel(),
            Forms\Components\TextInput::make('national_id')->label('الرقم الوطني')->maxLength(20),

            Forms\Components\Select::make('generators')
                ->label('المولدات')
                ->multiple()
                ->relationship('generators', 'name')
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // ✅ لازم type-hint
            ->modifyQueryUsing(fn (Builder $query) => $query->with('generators:id,name'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الهاتف')->searchable(),

                TagsColumn::make('generators.name')
                    ->label('المولدات')
                    ->separator('') // بدون فواصل بين البادجات
                    ->extraAttributes(['class' => 'whitespace-normal']),
            ])
            ->filters([
                SelectFilter::make('generator')
                    ->label('حسب المولّدة')
                    ->relationship('generators', 'name')
                    ->preload()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->visible(fn () => static::allowManage()),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn () => static::allowManage()),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('حذف متعدد')
                    ->visible(fn () => static::allowManage()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCollectors::route('/'),
            'create' => Pages\CreateCollector::route('/create'),
            'edit'   => Pages\EditCollector::route('/{record}/edit'),
        ];
    }
}
