<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\UserResource\Pages;

// جديد: أزرار الصف والإجرائيات
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon  = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'الموظفون';
    protected static ?string $pluralLabel     = 'الموظفون';
    protected static ?string $modelLabel      = 'موظف';
    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    /** ==== صلاحيات ==== */
    public static function isAdmin(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['مشرف', 'admin', 'super-admin']) ?? false;
    }

    public static function allowView(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('عرض الموظفين'));
    }

    public static function allowCreate(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('إضافة الموظفين'));
    }

    public static function allowManage(): bool
    {
        $u = auth()->user();
        return $u && (static::isAdmin() || $u->can('تعديل الموظفين'));
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
        // ترجمة الأدوار من الكاش
        $roleTranslations = Cache::rememberForever('roles_translations', fn () => Lang::get('roles'));

        // جلب الأدوار من Spatie (id => name)
        $roleOptions = Cache::rememberForever('cached_roles_list', fn () => Role::pluck('name', 'id')->toArray());

        // تطبيق الترجمات على أسماء الأدوار
        $roleOptionsTranslated = collect($roleOptions)->mapWithKeys(
            fn ($name, $id) => [$id => $roleTranslations[$name] ?? $name]
        );

        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('الاسم')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->label('البريد الإلكتروني')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('password')
                ->label('كلمة المرور')
                ->password()
                // بالموديل عندك cast => 'hashed' فممنوع نعمل Hash هنا
                ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
                ->required(fn (string $operation) => $operation === 'create')
                ->maxLength(255)
                ->visibleOn('create')
                ->autocomplete('new-password'),

            Forms\Components\Select::make('role')
                ->label('الدور')
                ->options($roleOptionsTranslated->toArray())
                ->searchable()
                ->required()
                // إظهار الدور الحالي في صفحة التعديل
                ->default(fn ($record) => $record?->roles()->first()?->id)
                // أثناء التعديل فقط بنزامن الدور مباشرة
                ->afterStateUpdated(function ($state, $livewire) {
                    if (! $livewire->record) return;
                    if ($role = Role::find($state)) {
                        $livewire->record->syncRoles([$role->name]);
                    }
                })
                // لا نحفظه بجدول users
                ->dehydrated(false),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        $roleTranslations = Cache::rememberForever('roles_translations', fn () => Lang::get('roles'));

        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم')->sortable()->searchable(),
                TextColumn::make('email')->label('البريد الإلكتروني')->sortable()->searchable(),
                TextColumn::make('roles.name')
                    ->label('الدور')
                    ->formatStateUsing(function ($state) use ($roleTranslations) {
                        if (is_array($state)) {
                            $translated = array_map(fn ($n) => $roleTranslations[$n] ?? $n, $state);
                            return implode(', ', $translated);
                        }
                        return $roleTranslations[$state] ?? $state;
                    }),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with('roles')) // Eager load
            ->filters([])
            ->actions([
                EditAction::make()->label('تعديل')->visible(fn () => static::allowManage()),
                DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn () => static::allowManage()),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label('حذف متعدد')->visible(fn () => static::allowManage()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
