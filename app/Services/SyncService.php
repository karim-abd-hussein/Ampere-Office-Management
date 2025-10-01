<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class SyncService
{
    /**
     * يصدّر البيانات إلى ZIP يحوي JSON لكل جدول.
     * @return array [absolutePath, downloadName]
     */
    public function exportToZip(): array
    {
        $ts = now()->format('Ymd_His');
        $tempDir = storage_path("app/sync_export_{$ts}");
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        // اجمع البيانات
        $datasets = $this->collectDatasets();

        // اكتب JSON لكل مجموعة
        foreach ($datasets as $name => $rows) {
            $json = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            file_put_contents($tempDir . "/{$name}.json", $json);
        }

        // اضغط للمسار النهائي
        $zipPath = storage_path("app/sync-{$ts}.zip");
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('لا يمكن إنشاء ملف ZIP.');
        }

        foreach (glob($tempDir.'/*.json') as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();

        // تنظيف الدير
        foreach (glob($tempDir.'/*.json') as $file) @unlink($file);
        @rmdir($tempDir);

        $downloadName = 'sync-' . $ts . '.zip';
        return [$zipPath, $downloadName];
    }

    /**
     * يستورد من ZIP (يعمل upsert حسب id إن وجد).
     * يعيد نص إحصائي مختصر.
     */
    public function importFromZip(string $zipAbsolutePath): string
    {
        $tmp = storage_path('app/sync_import_' . Str::random(8));
        mkdir($tmp, 0775, true);

        $zip = new ZipArchive();
        if ($zip->open($zipAbsolutePath) !== true) {
            throw new \RuntimeException('ملف ZIP غير صالح.');
        }
        $zip->extractTo($tmp);
        $zip->close();

        $stats = [];

        // helper
        $upsert = function (string $model, array $rows, array $nullable = []) use (&$stats) {
            if (! class_exists($model) || empty($rows)) return;
            /** @var \Illuminate\Database\Eloquent\Model $m */
            $m = new $model;
            $table = $m->getTable();

            // نظّف المفاتيح
            $rows = array_map(function ($r) use ($nullable) {
                foreach ($nullable as $k) {
                    if (array_key_exists($k, $r) && $r[$k] === '') $r[$k] = null;
                }
                return $r;
            }, $rows);

            // حقل id موجود؟ جرّب upsert عليه
            $columns = array_keys($rows[0]);
            $updateCols = array_values(array_diff($columns, ['id','created_at','updated_at']));
            $uniqueBy = in_array('id', $columns, true) ? ['id'] : [];

            // upsert على دفعات
            $chunks = array_chunk($rows, 1000);
            $affected = 0;
            foreach ($chunks as $chunk) {
                $affected += $m->newQuery()->upsert($chunk, $uniqueBy, $updateCols);
            }

            $stats[] = $table . ': ' . $affected;
        };

        // اقرا كل ملف JSON موجود ونفّذ
        $readJson = function (string $name): array {
            $p = $name;
            if (! is_file($p)) return [];
            $arr = json_decode(file_get_contents($p), true);
            return is_array($arr) ? $arr : [];
        };

        // المسارات
        $areas             = $readJson($tmp.'/areas.json');
        $generators        = $readJson($tmp.'/generators.json');
        $tariffs           = $readJson($tmp.'/generator_tariffs.json');
        $collectors        = $readJson($tmp.'/collectors.json');
        $companies         = $readJson($tmp.'/companies.json');
        $subscribers       = $readJson($tmp.'/subscribers.json');
        $cycles            = $readJson($tmp.'/cycles.json');
        $invoices          = $readJson($tmp.'/invoices.json');
        $receipts          = $readJson($tmp.'/receipts.json');
        $companyInvoices   = $readJson($tmp.'/company_invoices.json');

        // موديلات (اسمّها توقعاتي حسب مشروعك—موجودة عندك)
        $upsert(\App\Models\Area::class,              $areas);
        $upsert(\App\Models\Generator::class,         $generators);
        if (class_exists(\App\Models\GeneratorTariff::class)) {
            $upsert(\App\Models\GeneratorTariff::class, $tariffs);
        } elseif (class_exists(\App\Models\Tariff::class)) {
            $upsert(\App\Models\Tariff::class, $tariffs);
        }
        $upsert(\App\Models\Collector::class,         $collectors);
        $upsert(\App\Models\Company::class,           $companies);
        $upsert(\App\Models\Subscriber::class,        $subscribers, ['phone','national_id']);
        $upsert(\App\Models\Cycle::class,             $cycles);
        $upsert(\App\Models\Invoice::class,           $invoices);
        $upsert(\App\Models\Receipt::class,           $receipts);
        if (class_exists(\App\Models\CompanyInvoice::class)) {
            $upsert(\App\Models\CompanyInvoice::class, $companyInvoices);
        }

        // تنظيف
        foreach (glob($tmp.'/*.json') as $f) @unlink($f);
        @rmdir($tmp);

        return 'الصفوف المحدثة/المضافة: ' . implode(' | ', $stats);
    }

    /**
     * تجمع البيانات وتعيد مصفوفة name => rows
     */
    protected function collectDatasets(): array
    {
        $data = [];

        $data['areas']            = \App\Models\Area::query()->get()->toArray();
        $data['generators']       = \App\Models\Generator::query()->get()->toArray();

        // شرائح المولدات (إن وجدت)
        if (class_exists(\App\Models\GeneratorTariff::class)) {
            $data['generator_tariffs'] = \App\Models\GeneratorTariff::query()->get()->toArray();
        } elseif (class_exists(\App\Models\Tariff::class)) {
            $data['generator_tariffs'] = \App\Models\Tariff::query()->get()->toArray();
        } else {
            $data['generator_tariffs'] = [];
        }

        $data['collectors']       = class_exists(\App\Models\Collector::class) ? \App\Models\Collector::query()->get()->toArray() : [];
        $data['companies']        = \App\Models\Company::query()->get()->toArray();
        $data['subscribers']      = \App\Models\Subscriber::query()->get()->toArray();
        $data['cycles']           = \App\Models\Cycle::query()->get()->toArray();
        $data['invoices']         = \App\Models\Invoice::query()->get()->toArray();
        $data['receipts']         = \App\Models\Receipt::query()->get()->toArray();
        $data['company_invoices'] = class_exists(\App\Models\CompanyInvoice::class) ? \App\Models\CompanyInvoice::query()->get()->toArray() : [];

        return $data;
    }
}
