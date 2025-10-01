<div class="border-t border-gray-200 dark:border-white/10">
    <div class="grid grid-cols-3 gap-2 text-sm">
        @if($hasFilters)
            <div class="col-span-1 font-medium px-3 py-2">الملخّص</div>
            <div class="px-3 py-2">هذه الصفحة</div>
            <div class="px-3 py-2"></div>

            <div class="col-span-1"></div>
            <div class="px-3 py-2 text-center">{{ number_format($pageCons) }}</div>
            <div class="px-3 py-2 text-center">{{ number_format($pageFinal, 0) }}</div>
        @endif

        <div class="col-span-1 font-medium px-3 py-2">{{ $hasFilters ? '' : 'الملخّص' }}</div>
        <div class="px-3 py-2">كافة الفواتير</div>
        <div class="px-3 py-2"></div>

        <div class="col-span-1"></div>
        <div class="px-3 py-2 text-center">{{ number_format($allCons) }}</div>
        <div class="px-3 py-2 text-center">{{ number_format($allFinal, 0) }}</div>
    </div>
</div>
