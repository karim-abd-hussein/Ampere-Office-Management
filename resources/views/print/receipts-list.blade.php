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
                $consumption  = ($inv?->consumption ?? 0);
                $unitUsed     = ($inv?->unit_price_used ?? 0);   // السعر المستخدم فعليًا (قد يكون مخفّض)
                $finalAmount  = ($inv?->final_amount ?? 0);
                $calcTotal    = ($inv?->calculated_total ?? ($consumption * $unitUsed));

            @endphp

            @for ($i = 1; $i <= $copies; $i++)
                <div class="ticket">

                    <div class="row">
                        <h4>مكتب الأمبير</h4>
                        <h5>0945894893 - 0934211772</h5>
                    </div>

                    

                    <div class="row">
                        <div>رقم الفاتورة</div>
                        <div><strong>{{ $inv?->id }}</strong></div>
                    </div>


                    <div class="row">
                        <div>رقم المشترك</div>
                        <div><strong>{{ $inv?->subscriber_code_id ?? '—' }}</strong></div>
                    </div>

                    <div class="row">
                        <div>اسم المشترك</div>
                        <div><strong>{{ $inv?->subscriber_name ?? '—' }}</strong></div>
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
                        <div>{{ ($inv?->consumption ?? 0)}}</div>
                    </div>

                    <div class="row">
                        <div>السعر</div>
                        <div>{{ number_format($unitUsed) }}</div>
                    </div>

                    <div class="hr"></div>
                    <div class="row bold">
                        <div>المبلغ النهائي</div>
                        <div>{{ number_format(($inv?->final_amount ?? 0), 0) }}</div>
                    </div>
                </div>
            @endfor
        @endforeach
    </div>
@endif

</body>
</html>
