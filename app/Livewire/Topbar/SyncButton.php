<?php

namespace App\Livewire\Topbar;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Filament Actions + Forms
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;

use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\FileUpload;

class SyncButton extends Component implements HasActions, HasForms
{
    use WithFileUploads;
    use InteractsWithActions;
    use InteractsWithForms;

    /** ملف ZIP عند الاستيراد (يُحقن من المودال) */
    public $zip;

    protected $rules = [
        'zip' => 'required|file|mimes:zip|mimetypes:application/zip,application/x-zip-compressed,application/octet-stream|max:512000',
    ];

    protected $messages = [
        'zip.required' => 'الرجاء اختيار ملف المزامنة (ZIP).',
        'zip.mimes'    => 'الملف يجب أن يكون بصيغة ZIP.',
        'zip.max'      => 'حجم الملف كبير جدًا (الحد الأقصى 500MB).',
    ];

    public function render()
    {
        return view('livewire.topbar.sync-button');
    }

    /** === أزرار الأكشن التي يرسمها <x-filament-actions::actions> === */
    public function getActions(): array
    {
        return [
            $this->importAction(),
            $this->exportAction(),
        ];
    }

    public function importAction(): Action
    {
        return Action::make('importSync')
            ->label('استيراد (ZIP)')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->modalHeading('استيراد المزامنة')
            ->modalDescription('اختر ملف المزامنة بصيغة ZIP ثم نفّذ الاستيراد.')
            ->form([
                FileUpload::make('zip')
                    ->label('ملف ZIP')
                    ->acceptedFileTypes([
                        'application/zip',
                        'application/x-zip-compressed',
                        'application/octet-stream',
                        '.zip',
                    ])
                    ->storeFiles(false) // نعطيك الملف نفسه (TemporaryUploadedFile)
                    ->required(),
            ])
            ->action(function (array $data) {
                $this->zip = $data['zip'];
                $res = $this->import();

                $ok  = (bool)($res['ok'] ?? false);
                $msg = (string)($res['msg'] ?? '');

                if ($ok) {
                    Notification::make()->title('تم الاستيراد')->body($msg)->success()->send();
                    if ($user = auth()->user()) {
                        Notification::make()->title('استيراد المزامنة')->body($msg)->success()->sendToDatabase($user);
                    }
                    $this->dispatch('reload-page');
                } else {
                    Notification::make()->title('فشل الاستيراد')->body($msg ?: 'تعذر إتمام الاستيراد.')->danger()->send();
                    if ($user = auth()->user()) {
                        Notification::make()->title('فشل الاستيراد')->body($msg ?: 'تعذر إتمام الاستيراد.')->danger()->sendToDatabase($user);
                    }
                }
            });
    }

    public function exportAction(): Action
    {
        return Action::make('exportSync')
            ->label('تصدير (ZIP إنشاء)')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('تصدير المزامنة')
            ->modalDescription('سيتم إنشاء ملف ZIP وتحميله.')
            ->action(function () {
                $this->export(); // سيطلق حدث sync-download
                Notification::make()
                    ->title('بدأ التصدير')
                    ->body('سيتم تنزيل ملف المزامنة حالًا.')
                    ->success()
                    ->send();
            });
    }

    /* =========================== Export =========================== */

    public function export(): void
    {
        @set_time_limit(0);

        $exportsDir = storage_path('app/sync/exports');
        $tmpRoot    = storage_path('app/sync/tmp/' . Str::uuid());

        if (!is_dir($exportsDir)) @mkdir($exportsDir, 0775, true);
        @mkdir($tmpRoot . '/data', 0775, true);

        $lastFile = storage_path('app/sync/last_export_at.txt');
        $since    = file_exists($lastFile) ? trim(file_get_contents($lastFile)) : '1970-01-01 00:00:00';
        $nowIso   = now()->toDateTimeString();

        file_put_contents($tmpRoot . '/meta.json', json_encode([
            'generated_at' => $nowIso,
            'since'        => $since,
            'app'          => config('app.name', 'Laravel'),
            'env'          => app()->environment(),
            'db'           => DB::getDriverName(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $tables = $this->allTables();
        $excludes = [
            'migrations','jobs','failed_jobs','notifications','cache','cache_locks',
            'sessions','telescope_entries','telescope_entries_tags','telescope_monitoring',
            'password_reset_tokens','personal_access_tokens',
        ];
        $tables = array_values(array_diff($tables, $excludes));

        foreach ($tables as $table) {
            $this->exportTable($table, $tmpRoot . '/data', $since);
        }

        $fileName = 'sync-' . now()->format('Ymd-His') . '.zip';
        $zipPath  = $exportsDir . DIRECTORY_SEPARATOR . $fileName;

        $zip = new \ZipArchive();
        if (true !== $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            $this->notify('danger', 'فشل التصدير', 'تعذر إنشاء ملف التصدير.');
            return;
        }

        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tmpRoot, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($rii as $file) {
            $pathInZip = ltrim(str_replace($tmpRoot, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $zip->addFile($file->getPathname(), $pathInZip);
        }
        $zip->close();

        file_put_contents($lastFile, $nowIso);
        $this->rrmdir($tmpRoot);

        $this->dispatch('sync-download', url: route('sync.download', ['f' => $fileName]), filename: $fileName);

        $this->notify('success', 'تم', 'تم إنشاء ملف المزامنة.');
    }

    private function exportTable(string $table, string $dataDir, string $since): void
    {
        $columns = Schema::getColumnListing($table);
        $hasUpdated = in_array('updated_at', $columns, true);
        $hasCreated = in_array('created_at', $columns, true);

        $query = DB::table($table);

        if ($since && $hasUpdated) {
            $query->where('updated_at', '>=', $since);
        } elseif ($since && !$hasUpdated && $hasCreated) {
            $query->where('created_at', '>=', $since);
        }

        $path = $dataDir . '/' . $table . '.jsonl';
        $fp   = fopen($path, 'wb');

        $order = $this->detectPrimary($table, $columns) ?? $columns[0];

        $query->orderBy($order)->chunk(1000, function ($rows) use ($fp) {
            foreach ($rows as $row) {
                fwrite($fp, json_encode((array)$row, JSON_UNESCAPED_UNICODE) . PHP_EOL);
            }
        });

        fclose($fp);
    }

    /* =========================== Import =========================== */

    public function import(): array
    {
        $this->validate();
        @set_time_limit(0);

        $dbName = DB::getDatabaseName();
        Log::info('[SYNC] Import start', [
            'db' => $dbName,
            'driver' => DB::getDriverName(),
        ]);

        try {
            $originalName = $this->zip->getClientOriginalName();
            $mime = $this->zip->getMimeType();
            $size = $this->zip->getSize();

            $storedRel = $this->zip->storeAs('sync/imports', 'import-' . Str::uuid() . '.zip', 'local');
            $zipPath   = Storage::disk('local')->path($storedRel);

            if (!is_file($zipPath)) {
                $alt = $this->zip->getRealPath();
                Log::warning('[SYNC] Saved zip not found, trying tmp path', ['saved' => $zipPath, 'tmp' => $alt]);
                if ($alt && is_file($alt)) $zipPath = $alt;
            }

            $exists   = is_file($zipPath);
            $filesize = $exists ? filesize($zipPath) : -1;
            Log::info('[SYNC] Uploaded file', [
                'original' => $originalName,
                'mime'     => $mime,
                'size'     => $size,
                'saved_to' => $zipPath,
                'exists'   => $exists,
                'filesize' => $filesize,
            ]);

            if (!$exists || $filesize <= 0) {
                $msg = 'لم يتم العثور على الملف بعد الحفظ (أو حجمه 0).';
                $this->notify('danger', 'فشل الاستيراد', $msg);
                return ['ok' => false, 'msg' => $msg];
            }

            $tmp = storage_path('app/sync/imports/tmp-' . Str::uuid());
            @mkdir($tmp, 0775, true);

            $zip = new \ZipArchive();
            $openRes = $zip->open(realpath($zipPath) ?: $zipPath);
            if (true !== $openRes) {
                $codes = [
                    \ZipArchive::ER_NOENT   => 'لم يتم العثور على الملف',
                    \ZipArchive::ER_NOZIP   => 'الملف ليس ZIP',
                    \ZipArchive::ER_INCONS  => 'أرشيف غير متماسك',
                    \ZipArchive::ER_CRC     => 'عطل CRC',
                ];
                $hint = $codes[$openRes] ?? ('رمز الخطأ: ' . $openRes);
                $msg = 'ملف ZIP غير صالح. ' . $hint;
                Log::warning('[SYNC] Zip open failed', ['code' => $openRes, 'path' => $zipPath]);
                $this->notify('danger', 'فشل الاستيراد', $msg);
                return ['ok' => false, 'msg' => $msg];
            }

            $zip->extractTo($tmp);
            $zip->close();

            $root = $this->locatePayloadRoot($tmp);
            if (!$root) {
                $this->rrmdir($tmp); @unlink($zipPath);
                $msg = 'هيكل غير صحيح: مطلوب meta.json ومجلد data في نفس المستوى.';
                Log::warning('[SYNC] Payload root not found');
                $this->notify('danger', 'فشل الاستيراد', $msg);
                return ['ok' => false, 'msg' => $msg];
            }

            $dataDir = $root . '/data';
            $files = glob($dataDir . '/*.jsonl') ?: [];
            sort($files);

            Log::info('[SYNC] Data directory', ['root' => $root, 'files_count' => count($files), 'files' => $files]);

            if (empty($files)) {
                $this->rrmdir($tmp); @unlink($zipPath);
                $msg = 'لا توجد ملفات .jsonl داخل مجلد data.';
                $this->notify('danger', 'فشل الاستيراد', $msg);
                return ['ok' => false, 'msg' => $msg];
            }

            $stats = [];

            $this->fkChecks(false);
            DB::beginTransaction();

            foreach ($files as $file) {
                $table = basename($file, '.jsonl');
                $stats[$table] = $this->importTable($table, $file);
                Log::info('[SYNC] Table done', ['table' => $table, 'stats' => $stats[$table]]);
            }

            DB::commit();
            $this->fkChecks(true);

            $this->rrmdir($tmp);
            @unlink($zipPath);
            $this->reset('zip');

            $collectorsCount = Schema::hasTable('collectors') ? (int) DB::table('collectors')->count() : -1;
            Log::info('[SYNC] Import finished', [
                'db' => $dbName,
                'collectors_count' => $collectorsCount,
                'stats' => $stats,
            ]);

            $totalRead     = array_sum(array_column($stats, 'read'));
            $totalInserted = array_sum(array_column($stats, 'inserted'));
            $totalUpdated  = array_sum(array_column($stats, 'updated'));

            $msg  = "مقروء: {$totalRead} | مُضاف: {$totalInserted} | مُحدَّث: {$totalUpdated}";
            if (isset($stats['collectors'])) {
                $c = $stats['collectors'];
                $msg .= "\nالجُباة => مقروء: {$c['read']}, مُضاف: {$c['inserted']}, مُحدَّث: {$c['updated']}";
            }
            $msg .= "\nقاعدة البيانات: {$dbName}";

            $this->notify('success', 'تم الاستيراد', $msg);

            return ['ok' => true, 'msg' => $msg, 'stats' => $stats, 'db' => $dbName];
        } catch (\Throwable $e) {
            try { DB::rollBack(); } catch (\Throwable $__) {}
            $this->fkChecks(true);

            Log::error('[SYNC] Import failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            $msg = 'حدث خطأ غير متوقع: ' . mb_strimwidth($e->getMessage(), 0, 300, '...');
            $this->notify('danger', 'فشل الاستيراد', $msg);
            return ['ok' => false, 'msg' => $msg];
        }
    }

    private function locatePayloadRoot(string $base): ?string
    {
        if (is_file($base . '/meta.json') && is_dir($base . '/data')) return $base;

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $file) {
            if ($file->isDir()) {
                $path = $file->getPathname();
                if (is_file($path . '/meta.json') && is_dir($path . '/data')) {
                    return $path;
                }
            }
        }
        return null;
    }

    private function importTable(string $table, string $jsonlPath): array
    {
        $stats = ['read' => 0, 'inserted' => 0, 'updated' => 0];

        if (!Schema::hasTable($table)) return $stats;

        $columns = Schema::getColumnListing($table);
        $hasId   = in_array('id', $columns, true);

        $handle = fopen($jsonlPath, 'rb');
        if (!$handle) return $stats;

        $buffer = [];
        $keysForUpdate = function(array $row) {
            $cols = array_keys($row);
            return array_values(array_diff($cols, ['id']));
        };

        $first = true;
        while (($line = fgets($handle)) !== false) {
            if ($first && str_starts_with($line, "\xEF\xBB\xBF")) $line = substr($line, 3);
            $first = false;

            $row = json_decode($line, true);
            if (!is_array($row) || empty($row)) continue;

            $row = array_intersect_key($row, array_flip($columns));
            if (!$row) continue;

            $buffer[] = $row;
            $stats['read']++;

            if (count($buffer) >= 500) {
                [$ins, $upd] = $this->flushUpsert($table, $buffer, $hasId, $keysForUpdate);
                $stats['inserted'] += $ins;
                $stats['updated']  += $upd;
            }
        }
        fclose($handle);

        if ($buffer) {
            [$ins, $upd] = $this->flushUpsert($table, $buffer, $hasId, $keysForUpdate);
            $stats['inserted'] += $ins;
            $stats['updated']  += $upd;
        }

        return $stats;
    }

    private function flushUpsert(string $table, array &$buffer, bool $hasId, \Closure $keysForUpdate): array
    {
        if (!$buffer) return [0, 0];

        $inserted = 0; $updated = 0;

        $allKeys = [];
        foreach ($buffer as $row) foreach (array_keys($row) as $k) $allKeys[$k] = true;
        $allKeys = array_keys($allKeys);
        foreach ($buffer as &$row) {
            foreach ($allKeys as $k) if (!array_key_exists($k, $row)) $row[$k] = null;
        }
        unset($row);

        if ($hasId) {
            $ids      = array_values(array_filter(array_map(fn ($r) => $r['id'] ?? null, $buffer), fn ($v) => $v !== null));
            $existing = $ids ? DB::table($table)->whereIn('id', $ids)->pluck('id')->all() : [];
            $existingSet = $existing ? array_flip($existing) : [];

            $toInsert = [];
            $toUpdate = [];

            foreach ($buffer as $row) {
                if (isset($row['id']) && isset($existingSet[$row['id']])) {
                    $toUpdate[] = $row;
                } else {
                    $toInsert[] = $row;
                }
            }

            if ($toInsert) { $inserted += DB::table($table)->insertOrIgnore($toInsert); }
            if ($toUpdate) {
                $updateCols = $keysForUpdate($toUpdate[0]) ?: array_keys($toUpdate[0]);
                $affected = DB::table($table)->upsert($toUpdate, ['id'], $updateCols);
                $updated += max(0, (int) $affected);
            }
        } else {
            $inserted += DB::table($table)->insertOrIgnore($buffer);
        }

        $buffer = [];
        return [$inserted, $updated];
    }

    private function allTables(): array
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $rows = DB::select('SHOW TABLES');
            return array_map(fn($r) => array_values((array)$r)[0], $rows);
        }

        if ($driver === 'sqlite') {
            $rows = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
            return array_map(fn($r) => $r->name, $rows);
        }

        if ($driver === 'pgsql') {
            $rows = DB::select("SELECT tablename FROM pg_tables WHERE schemaname='public'");
            return array_map(fn($r) => $r->tablename, $rows);
        }

        return [];
    }

    private function detectPrimary(string $table, array $columns): ?string
    {
        return in_array('id', $columns, true) ? 'id' : null;
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $fs) {
            $fs->isDir() ? @rmdir($fs->getPathname()) : @unlink($fs->getPathname());
        }
        @rmdir($dir);
    }

    private function fkChecks(bool $enable): void
    {
        $driver = DB::getDriverName();

        try {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=' . ($enable ? '1' : '0'));
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ' . ($enable ? 'ON' : 'OFF'));
            } elseif ($driver === 'pgsql') {
                DB::statement($enable ? 'SET CONSTRAINTS ALL IMMEDIATE' : 'SET CONSTRAINTS ALL DEFERRED');
            }
        } catch (\Throwable $e) {
            // تجاهل
        }
    }

    private function notify(string $status, string $title, string $body): void
    {
        $n = Notification::make()->title($title)->body($body);
        if ($status === 'success')      $n->success();
        elseif ($status === 'warning')  $n->warning();
        else                            $n->danger();
        $n->send();

        if ($user = auth()->user()) {
            $n2 = Notification::make()->title($title)->body($body);
            if ($status === 'success')      $n2->success();
            elseif ($status === 'warning')  $n2->warning();
            else                            $n2->danger();
            $n2->sendToDatabase($user);
        }

        $this->dispatch('notify', status: $status, title: $title, body: $body);
    }
}
