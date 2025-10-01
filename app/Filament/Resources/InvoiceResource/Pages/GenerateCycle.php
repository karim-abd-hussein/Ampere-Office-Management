<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Generator;
use App\Models\Subscriber;
use App\Models\Invoice;
use App\Models\Cycle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\DB;

class GenerateCycle extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = InvoiceResource::class;

    // ❌ لا تحدد View مخصص
    // protected static string $view = 'filament-panels::pages.actions';

    protected static ?string $title = 'إضافة دورة';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormModel(): string
    {
        return Invoice::class;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الدورة')
                    ->schema([
                        Forms\Components\Select::make('generator_id')
                            ->label('المولدة')
                            ->options(fn () => Generator::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if (!$state) {
                                    $set('rows', []);
                                    return;
                                }

                                $gen = Generator::find($state);

                                $subs = Subscriber::query()
                                    ->where('generator_id', $state)
                                    ->with('generator')
                                    ->get();

                                $rows = [];
                                foreach ($subs as $sub) {
                                    $lastNew = Invoice::where('subscriber_id', $sub->id)
                                        ->latest('issued_at')->value('new_reading') ?? 0;

                                    $rows[] = [
                                        'subscriber_id'   => $sub->id,
                                        'name'            => $sub->name,
                                        'phone'           => $sub->phone,
                                        'box_number'      => $sub->box_number,
                                        'meter_number'    => $sub->meter_number,
                                        'old_reading'     => (float) $lastNew,
                                        'new_reading'     => (float) $lastNew,
                                        'consumption'     => 0,
                                        'unit_price_used' => (float) ($gen->price_per_kwh ?? 0),
                                        'final_amount'    => 0,
                                    ];
                                }

                                $set('rows', $rows);
                                $set('unit_price_default', (float) ($gen->price_per_kwh ?? 0));
                            }),

                        Forms\Components\Select::make('cycle_id')
                            ->label('الدورة')
                            ->options(fn () => Cycle::query()->orderByDesc('id')->pluck('id', 'id'))
                            ->searchable(),

                        Forms\Components\DateTimePicker::make('issued_at')
                            ->label('تاريخ الإصدار')
                            ->default(now())
                            ->required(),

                        Forms\Components\TextInput::make('unit_price_default')
                            ->label('سعر الكيلو الافتراضي')
                            ->numeric()
                            ->step('0.01')
                            ->minValue(0)
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('المشتركون')
                    ->schema([
                        Forms\Components\Repeater::make('rows')
                            ->label(false)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(8)
                            ->schema([
                                Forms\Components\Hidden::make('subscriber_id'),

                                Forms\Components\TextInput::make('name')
                                    ->label('الاسم')->disabled()->dehydrated(false),

                                Forms\Components\TextInput::make('phone')
                                    ->label('الهاتف')->disabled()->dehydrated(false),

                                Forms\Components\TextInput::make('box_number')
                                    ->label('رقم العلبة')->disabled()->dehydrated(false),

                                Forms\Components\TextInput::make('meter_number')
                                    ->label('رقم العداد')->disabled()->dehydrated(false),

                                Forms\Components\TextInput::make('old_reading')
                                    ->label('KW قديم')->numeric()->disabled(),

                                Forms\Components\TextInput::make('new_reading')
                                    ->label('KW جديد')->numeric()->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        $old   = (float) $get('../../old_reading');
                                        $cons  = max(0, (float) $state - $old);
                                        $price = (float) $get('../../unit_price_used');
                                        $set('../../consumption', $cons);
                                        $set('../../final_amount', round($cons * $price, 2));
                                    }),

                                Forms\Components\TextInput::make('unit_price_used')
                                    ->label('سعر الكيلو')->numeric()->step('0.01')->minValue(0)->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        $cons = (float) $get('../../consumption');
                                        $set('../../final_amount', round($cons * (float) $state, 2));
                                    }),

                                Forms\Components\TextInput::make('consumption')
                                    ->label('الاستهلاك')->numeric()->disabled(),

                                Forms\Components\TextInput::make('final_amount')
                                    ->label('المبلغ')->numeric()->disabled(),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function createInvoices(): void
    {
        $data = $this->form->getState();

        $genId   = $data['generator_id'] ?? null;
        $cycleId = $data['cycle_id']     ?? null;
        $issued  = $data['issued_at']    ?? now();
        $rows    = $data['rows']         ?? [];

        if (!$genId || empty($rows)) {
            Notification::make()->title('اختر المولدة أولاً')->danger()->send();
            return;
        }

        DB::transaction(function () use ($rows, $genId, $cycleId, $issued) {
            foreach ($rows as $row) {
                $cons = (int)($row['consumption'] ?? 0);
                if ($cons < 0) $cons = 0;

                Invoice::create([
                    'subscriber_id'   => $row['subscriber_id'],
                    'generator_id'    => $genId,
                    'collector_id'    => null,
                    'cycle_id'        => $cycleId,
                    'old_reading'     => (int)($row['old_reading'] ?? 0),
                    'new_reading'     => (int)($row['new_reading'] ?? 0),
                    'consumption'     => $cons,
                    'unit_price_used' => (float)($row['unit_price_used'] ?? 0),
                    'calculated_total'=> round($cons * (float)($row['unit_price_used'] ?? 0), 2),
                    'final_amount'    => round((float)($row['final_amount'] ?? 0), 2),
                    'issued_at'       => $issued,
                ]);
            }
        });

        Notification::make()->title('تم إنشاء فواتير الدورة')->success()->send();

        $this->redirect(InvoiceResource::getUrl('index'));
    }

    // زر “إنشاء الفواتير” في الهيدر
    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('إنشاء الفواتير')
                ->icon('heroicon-o-check')
                ->action('createInvoices'),
        ];
    }
}
