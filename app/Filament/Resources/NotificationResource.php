<?php

namespace App\Filament\Resources;

use App\Models\SystemNotification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\NotificationResource\Pages;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Notifications\Notification;

class NotificationResource extends Resource
{
    protected static ?string $model = SystemNotification::class;
    protected static ?string $slug  = 'notifications';

    protected static ?string $modelLabel  = 'إشعار';
    protected static ?string $pluralLabel = 'الإشعارات';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereMorphedTo('notifiable', auth()->user())
            ->latest('created_at');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                // === رقم الإشعار: أرقام إنكليزية + محاذاة يمين ===
               TextColumn::make('num_id')
    ->label('رقم الإشعار')
    ->sortable()
    ->searchable()
    ->width('6rem')
    // نخلي القيمة HTML ونلفّها بعنصر بمحاذاة يمين واتجاه LTR
    ->html()
    ->formatStateUsing(function ($state) {
        $val = number_format((int) $state, 0, '.', '');
        return '<span dir="ltr" lang="en" style="display:block; text-align:right;">' . $val . '</span>';
    })
    // ترويسة العمود يمين أيضاً
    ->extraHeaderAttributes(['class' => 'text-right']),

                TextColumn::make('data.title')
                    ->label('العنوان')
                    ->wrap()
                    ->searchable()
                    ->extraAttributes(['class' => 'whitespace-normal break-words']),

                TextColumn::make('data.body')
                    ->label('المحتوى')
                    ->wrap()
                    ->searchable()
                    ->extraAttributes(['class' => 'whitespace-normal break-words']),

                TextColumn::make('created_at')
                    ->label('منذ')
                    ->since()
                    ->sortable()
                    ->extraAttributes(['class' => 'whitespace-nowrap']),
            ])

            ->headerActions([
                TableAction::make('markAllRead')
                    ->label('تعليم الكل كمقروء')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function () {
                        $user = auth()->user();
                        if (! $user) return;

                        $affected = $user->notifications()
                            ->whereNull('read_at')
                            ->update(['read_at' => now()]);

                        Notification::make()
                            ->title('تم التعليم')
                            ->body("تم تعليم {$affected} إشعار/إشعارات كمقروء.")
                            ->success()
                            ->send();
                    }),
            ])

            ->paginated([5, 10, 25])
            ->filters([
                SelectFilter::make('read_state')
                    ->label('الحالة')
                    ->options([
                        ''        => 'الكل',
                        'unread'  => 'غير مقروء',
                        'read'    => 'مقروء',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $val = $data['value'] ?? null;
                        if ($val === 'unread') {
                            $query->whereNull('read_at');
                        } elseif ($val === 'read') {
                            $query->whereNotNull('read_at');
                        }
                        return $query;
                    }),

                Filter::make('date_range')
                    ->label('حسب التاريخ')
                    ->form([
                        DatePicker::make('from')->label('من تاريخ'),
                        DatePicker::make('to')->label('إلى تاريخ'),
                    ])
                    ->indicateUsing(function (array $data): array {
                        return array_filter([
                            filled($data['from'] ?? null) ? ('من ' . $data['from']) : null,
                            filled($data['to']   ?? null) ? ('إلى ' . $data['to'])   : null,
                        ]);
                    })
                    ->query(function (Builder $query, array $data) {
                        $from = $data['from'] ?? null;
                        $to   = $data['to']   ?? null;

                        return $query
                            ->when($from, fn (Builder $q) => $q->whereDate('created_at', '>=', $from))
                            ->when($to,   fn (Builder $q) => $q->whereDate('created_at', '<=', $to));
                    }),
            ])

            ->actions([
                DeleteAction::make()
                    ->label('حذف')
                    ->requiresConfirmation(),
            ])

            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('حذف جماعي')
                    ->requiresConfirmation(),
            ])

            ->emptyStateHeading('لا توجد إشعارات')
            ->emptyStateDescription('لا يوجد عناصر لعرضها حالياً.')
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
        ];
    }
}
