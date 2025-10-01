<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>طباعة وصولات</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; color:#000; background:#fff; margin:16px; }
        .toolbar { position:sticky; top:0; background:#fff; padding:8px 0; margin-bottom:10px; border-bottom:1px solid #e5e5e5; }
        .toolbar button { padding:8px 12px; border:1px solid #ccc; background:#f7f7f7; cursor:pointer; border-radius:6px; }

        .tickets { display:flex; flex-direction:column; gap:12px; }
        .ticket { width:63mm; padding:6px; border:1px dashed #aaa; }
        .title { font-weight:700; text-align:center; margin-bottom:4px; }
        .row { display:flex; justify-content:space-between; margin:2px 0; font-size:13px; }
        .hr { border-top:1px dashed #000; margin:6px 0; }
        .muted { opacity:.85; }
        .bold { font-weight:700; }

        @page { size: 80mm auto; margin: 0; }
        @media print {
            body { margin: 0; }
            .toolbar { display:none; }
            .tickets { padding: 4mm 4mm 0 4mm; }
            .ticket { width: 63mm; margin: 0; page-break-inside: avoid; page-break-after: always; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <button onclick="window.print()">طباعة</button>
</div>

@php
    $copies = max(1, (int) request('copies', 1));
@endphp

@if($receipts->isEmpty())
    <p>لا توجد وصولات للطباعة.</p>
@else
    <div class="tickets">
        @foreach($receipts as $r)
            @php
                $inv    = $r->invoice;
                $sub    = $inv?->subscriber;
                $gen    = $inv?->generator;   // يُفضّل تحميل tariffs بالعلاقات
                $cycle  = $inv?->cycle;
                $now    = now()->format('Y-m-d H:i');
                $serial = $r->short_code;

                // اسم الدورة (code) وإلا رقم الـ id
                $cycleLabel = $cycle?->code ?? ($inv?->cycle_id ?? '—');

                // أرقام أساسية من الفاتورة
                $consumption  = (float) ($inv?->consumption ?? 0);
                $unitUsed     = (float) ($inv?->unit_price_used ?? 0);   // السعر المستخدم فعليًا (قد يكون مخفّض)
                $finalAmount  = (float) ($inv?->final_amount ?? 0);
                $calcTotal    = (float) ($inv?->calculated_total ?? ($consumption * $unitUsed));

                // السعر الأساسي الحقيقي حسب استهلاك الفاتورة (شرائح/تعرفة)
                $baseUnit = 0.0;
                try {
                    if ($gen && method_exists($gen, 'priceForConsumption')) {
                        $baseUnit = (float) $gen->priceForConsumption($consumption);
                    } else {
                        $baseUnit = (float) ($gen?->price_per_kwh ?? $unitUsed);
                    }
                } catch (\Throwable $e) {
                    $baseUnit = (float) ($gen?->price_per_kwh ?? $unitUsed);
                }
                $baseTotal = round($baseUnit * $consumption, 2);

                // حالات الخصم
                $hasPerUnitDiscount = $baseUnit > 0 && $unitUsed > 0 && ($unitUsed + 0.0001) < $baseUnit;
                $perUnitDiff        = $hasPerUnitDiscount ? round($baseUnit - $unitUsed, 2) : 0.0;      // كم خفّضنا بالكيلو
                $perUnitSaveTotal   = $hasPerUnitDiscount ? round($perUnitDiff * $consumption, 2) : 0.0;

                $hasFinalDiscount   = ($finalAmount + 0.0001) < $calcTotal;                              // خصم إضافي على المجموع
                $finalDiscountAmt   = $hasFinalDiscount ? round($calcTotal - $finalAmount, 2) : 0.0;

                $totalSaved         = round($perUnitSaveTotal + $finalDiscountAmt, 2);
            @endphp

            @for ($i = 1; $i <= $copies; $i++)
                <div class="ticket">
                    <div class="title">مكتب الأمبير</div>

                    <div class="row">
                        <div>رقم الوصل</div>
                        <div><strong>{{ $serial }}@if($copies > 1) / {{ $i }}@endif</strong></div>
                    </div>

                    <div class="row">
                        <div>اسم المشترك</div>
                        <div><strong>{{ $sub?->name ?? '—' }}</strong></div>
                    </div>

                    <div class="row">
                        <div>الدورة</div>
                        <div><strong>{{ $cycleLabel }}</strong></div>
                    </div>

                    <div class="row">
                        <div>تاريخ الطباعة</div>
                        <div>{{ $now }}</div>
                    </div>

                    <div class="hr"></div>

                    <div class="row">
                        <div>القراءة القديمة</div>
                        <div>{{ (float)($inv?->old_reading ?? 0) }}</div>
                    </div>

                    <div class="row">
                        <div>القراءة الجديدة</div>
                        <div>{{ (float)($inv?->new_reading ?? 0) }}</div>
                    </div>

                    <div class="row">
                        <div>الاستهلاك (ك.و)</div>
                        <div>{{ (float) $consumption }}</div>
                    </div>

                    @if($hasPerUnitDiscount)
                        {{-- قبل/بعد الخصم على سعر الكيلو --}}
                        <div class="row">
                            <div>سعر الكيلو قبل الخصم</div>
                            <div>{{ number_format($baseUnit, 0) }}</div>
                        </div>
                        <div class="row">
                            <div>سعر الكيلو بعد الخصم</div>
                            <div>{{ number_format($unitUsed, 0) }}</div>
                        </div>
                        <div class="row muted">
                            <div>خصم / ك.و</div>
                            <div>{{ number_format($perUnitDiff, 0) }}</div>
                        </div>
                    @else
                        {{-- لا يوجد خصم على السعر لكل كيلو --}}
                        <div class="row">
                            <div>سعر الكيلو</div>
                            <div>{{ number_format($unitUsed, 0) }}</div>
                        </div>
                    @endif

                    <div class="hr"></div>

                    @if($hasPerUnitDiscount)
                        <div class="row">
                            <div>المجموع قبل الخصم</div>
                            <div>{{ number_format($baseTotal, 0) }}</div>
                        </div>
                    @endif

                    @if($hasFinalDiscount)
                        <div class="row">
                            <div>خصم إضافي على المجموع</div>
                            <div>-{{ number_format($finalDiscountAmt, 0) }}</div>
                        </div>
                    @endif

                    <div class="row bold">
                        <div>المبلغ النهائي</div>
                        <div>{{ number_format($finalAmount, 0) }}</div>
                    </div>
                </div>
            @endfor
        @endforeach
    </div>
@endif

</body>
</html>
