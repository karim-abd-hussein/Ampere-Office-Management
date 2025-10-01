<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>وصل شركة</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; color:#000; background:#fff; margin:16px; }
        .toolbar { position:sticky; top:0; background:#fff; padding:8px 0; margin-bottom:10px; border-bottom:1px solid #e5e5e5; }
        .toolbar button { padding:8px 12px; border:1px solid #ccc; background:#f7f7f7; cursor:pointer; border-radius:6px; }

        .tickets { display:flex; flex-direction:column; gap:12px; }
        .ticket { width:72mm; padding:6px; border:1px dashed #aaa; }
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
            .ticket { width: 72mm; margin: 0; page-break-inside: avoid; page-break-after: always; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <button onclick="window.print()">طباعة</button>
</div>

@php
    /** @var \App\Models\CompanyInvoice $invoice */
    $company = $invoice->company;
    $cycle   = $invoice->cycle;
    $now     = now()->format('Y-m-d H:i');

    // ملصق الدورة (نفس أسلوبك)
    $cycleLabel = $cycle?->code ?? ($cycle?->start_date?->format('Y-m-d') ?? ('#' . ($cycle?->id ?? '—')));

    // أرقام
    $ampere        = (float) ($invoice->ampere ?? 0);
    $pricePerAmp   = (float) ($invoice->price_per_amp ?? 0);
    $fixedAmount   = (float) ($invoice->fixed_amount ?? 0);
    $totalAmount   = (float) ($invoice->total_amount ?? ($ampere * $pricePerAmp + $fixedAmount));

    $calcTotal     = round($ampere * $pricePerAmp + $fixedAmount, 2);
    $diff          = round($totalAmount - $calcTotal, 2); // فرق (خصم/زيادة) إن وجد

    $statusAr = match ($company?->status) {
        'active' => 'فعال',
        'disconnected' => 'مفصول',
        'cancelled' => 'ملغى',
        default => null,
    };

    $copies = max(1, (int) request('copies', 1));
@endphp

<div class="tickets">
@for ($i = 1; $i <= $copies; $i++)
    <div class="ticket">
        <div class="title">مكتب الأمبير — وصل شركة</div>

        <div class="row">
            <div>رقم الفاتورة</div>
            <div><strong>{{ $invoice->id }}@if($copies > 1) / {{ $i }}@endif</strong></div>
        </div>

        <div class="row">
            <div>اسم الشركة</div>
            <div><strong>{{ $company?->name ?? '—' }}</strong></div>
        </div>

        @if($company?->phone)
            <div class="row">
                <div>الهاتف</div>
                <div>{{ $company->phone }}</div>
            </div>
        @endif

        <div class="row">
            <div>الدورة</div>
            <div><strong>{{ $cycleLabel }}</strong></div>
        </div>

        <div class="row">
            <div>تاريخ الطباعة</div>
            <div>{{ $now }}</div>
        </div>

        @if($statusAr && $statusAr !== 'فعال')
            <div class="row muted">
                <div>الحالة</div>
                <div>⚠️ {{ $statusAr }}</div>
            </div>
        @endif

        <div class="hr"></div>

        <div class="row">
            <div>الأمبير</div>
            <div>{{ number_format($ampere, 0) }}</div>
        </div>

        <div class="row">
            <div>سعر الأمبير</div>
            <div>{{ number_format($pricePerAmp, 0) }}</div>
        </div>

        <div class="hr"></div>


        <div class="row bold">
            <div>المبلغ النهائي</div>
            <div>{{ number_format($totalAmount, 0) }}</div>
        </div>

        @if($company?->notes)
            <div class="hr"></div>
            <div class="row muted" style="display:block">
                <div style="margin-bottom:2px;">ملاحظات</div>
                <div>{{ $company->notes }}</div>
            </div>
        @endif
    </div>
@endfor
</div>

</body>
</html>
