<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon  = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'الأدوار';
    protected static ?string $pluralLabel     = 'الأدوار';
    protected static ?string $modelLabel      = 'دور';
    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    /** ===== صلاحيات ==== */
    public static function isAdmin(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['مشرف','admin','super-admin']) ?? false;
    }
    public static function allowView(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('عرض الأدوار'));
    }
    public static function allowCreate(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('إضافة الأدوار'));
    }
    public static function allowManage(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('تعديل الأدوار'));
    }

    /** عنصر القائمة يظهر فقط لمن معه عرض */
    public static function shouldRegisterNavigation(): bool
    {
        return static::allowView();
    }

    /** CRUD gating لـ Filament */
    public static function canViewAny(): bool       { return static::allowView(); }
    public static function canCreate(): bool        { return static::allowCreate(); }
    public static function canEdit($record): bool   { return static::allowManage(); }
    public static function canDelete($record): bool { return static::allowManage(); }
    public static function canDeleteAny(): bool     { return static::allowManage(); }

    public static function form(Form $form): Form
    {
        $permTranslations = Cache::rememberForever('permission_translations', fn () => Lang::get('permissions'));
        $permList         = Cache::rememberForever('cached_permissions_list', fn () => Permission::pluck('name', 'id')->toArray());

        // ✅ ترتيب المجموعات (مضاف: الجباة، المولدات، المناطق)
        $groupOrder = [
            'المشتركين',
            'الشركات',
            'الموظفون',
            'الجباة',
            'المولدات',
            'المناطق',
            'الوصولات',
            'الفواتير',
            'الدورات',
            'الأدوار',
            'أخرى',
        ];

        $grouped = array_fill_keys($groupOrder, []);

        foreach ($permList as $id => $name) {
            $label = $permTranslations[$name] ?? $name;

            $g = 'أخرى';
            if     (str_contains($name, 'المشتركين'))  $g = 'المشتركين';
            elseif (str_contains($name, 'الشركات'))     $g = 'الشركات';
            elseif (str_contains($name, 'الموظف'))      $g = 'الموظفون';
            elseif (str_contains($name, 'الجباة'))      $g = 'الجباة';
            elseif (str_contains($name, 'المولدات'))    $g = 'المولدات';
            elseif (str_contains($name, 'المناطق'))     $g = 'المناطق';   // ✅ جديد
            elseif (str_contains($name, 'الوصولات'))    $g = 'الوصولات';
            elseif (str_contains($name, 'الفواتير'))    $g = 'الفواتير';
            elseif (str_contains($name, 'الدورات'))     $g = 'الدورات';
            elseif (str_contains($name, 'الأدوار'))     $g = 'الأدوار';

            $grouped[$g][$id] = $label;
        }

        // نسطح الخيارات: id => "[المجموعة] التسمية"
        $flatOptions = [];
        foreach ($groupOrder as $g) {
            if (empty($grouped[$g])) continue;
            asort($grouped[$g], SORT_NATURAL | SORT_FLAG_CASE);
            foreach ($grouped[$g] as $id => $label) {
                $flatOptions[$id] = "[$g] $label";
            }
        }

        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('اسم الدور')
                ->required()
                ->unique(ignoreRecord: true),

            Forms\Components\Hidden::make('guard_name')
                ->default(config('auth.defaults.guard', 'web')),

            Forms\Components\CheckboxList::make('permissions')
                ->label('الصلاحيات')
                ->relationship('permissions', 'name')
                ->options($flatOptions)
                ->searchable()
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        $permTranslations = Cache::rememberForever('permission_translations', fn () => Lang::get('permissions'));
        $roleTranslations = Cache::rememberForever('roles_translations', fn () => Lang::get('roles'));

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الدور')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn (string $state) => $roleTranslations[$state] ?? $state),

                TagsColumn::make('permissions.name')
                    ->label('الصلاحيات')
                    ->badge()
                    ->color('warning')
                    ->limit(6)
                    ->formatStateUsing(function ($state) use ($permTranslations) {
                        if (is_array($state)) {
                            return array_map(fn ($s) => $permTranslations[$s] ?? $s, $state);
                        }
                        return $permTranslations[$state] ?? $state;
                    }),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->with('permissions'))
            ->filters([
                SelectFilter::make('permissions')
                    ->label('حسب الصلاحية')
                    ->relationship('permissions', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Permission $p) => $permTranslations[$p->name] ?? $p->name)
                    ->preload()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل')->visible(fn()=>static::allowManage()),
                Tables\Actions\DeleteAction::make()->label('حذف')->visible(fn()=>static::allowManage())->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('حذف جماعي')->visible(fn()=>static::allowManage())->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit'   => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
