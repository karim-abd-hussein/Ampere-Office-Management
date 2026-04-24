<?php

use Illuminate\Support\Facades\Route;
use App\Models\CompanyInvoice;
use App\Http\Controllers\BackupController;
/*
|--------------------------------------------------------------------------
| طباعة الوصولات (قائمة)
|--------------------------------------------------------------------------
*/
Route::get('/print/receipts', function (\Illuminate\Http\Request $request) {
    $ids = collect(explode(',', (string) $request->query('ids')))
        ->filter()
        ->map(fn ($v) => (int) $v)
        ->unique()
        ->values();

    $receipts = \App\Models\Receipt::with(['invoice.subscriber', 'invoice'])
        ->whereIn('id', $ids)
        ->orderBy('id')
        ->get();

    return view('print.receipts-list', compact('receipts'));
})->name('receipts.print')->middleware(['web', 'auth']);

/*
|--------------------------------------------------------------------------
| طباعة وصل فاتورة شركة (سطر واحد)
|--------------------------------------------------------------------------
| مثال: route('print.company-invoice', ['invoice' => $id])
*/
Route::get('/print/company-invoice/{invoice}', function (CompanyInvoice $invoice) {
    $invoice->load(['company', 'cycle']);
    return view('print.company-invoice', compact('invoice'));
})->name('print.company-invoice')->middleware(['web', 'auth']);

/*
|--------------------------------------------------------------------------
| تنزيل ملف المزامنة بعد "التصدير"
| الـ Livewire يولّد ZIP داخل storage/app/sync/exports
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/sync-download/{f}', function (string $f) {
        $f = basename($f);
        $path = storage_path('app/sync/exports/' . $f);

        abort_unless(file_exists($path), 404);

        return response()->download($path)->deleteFileAfterSend(false);
    })->name('sync.download');

    // (اختياري) مسار اختبار سريع يولّد ZIP بسيط
    Route::get('/admin/sync/export', function () {
        $fileName = 'sync-' . now()->format('Ymd-His') . '.zip';
        $tmp = storage_path('app/tmp');
        if (! is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }
        $full = $tmp . DIRECTORY_SEPARATOR . $fileName;

        $zip = new \ZipArchive();
        if ($zip->open($full, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'تعذر إنشاء ملف ZIP');
        }
        $zip->addFromString('README.txt', "Generated at: " . now()->toDateTimeString());
        $zip->close();

        return response()->download($full, $fileName)->deleteFileAfterSend(true);
    })->name('admin.sync.export');
});



Route::middleware(['auth'])->group(function () {
    Route::post('/backup/create', [BackupController::class, 'createBackup'])->name('backup.create');
    Route::get('/backup/status', [BackupController::class, 'backupStatus'])->name('backup.status');
    Route::get('/backup', [BackupController::class, 'showBackupPage'])->name('backup.management');
});


Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
