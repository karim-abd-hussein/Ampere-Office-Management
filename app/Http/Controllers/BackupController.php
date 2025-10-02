<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Symfony\Component\Process\Process;


class BackupController extends Controller
{


public function createBackup(Request $request): JsonResponse
{
    if (!Cache::add('backup_running', 1, 1800)) {
        return response()->json([
            'success' => false,
            'message' => 'Backup is already running. Please wait.'
        ], 429);
    }

    try {
        Log::info('[MANUAL-BACKUP] initiated by user at ' . now());

        $backupDisk = \Storage::disk('local');
        $backupPath = 'backups/';

        $backupsBefore = collect($backupDisk->files($backupPath))
            ->filter(fn($file) => pathinfo($file, PATHINFO_EXTENSION) === 'zip')
            ->count();

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        // Run the backup using Symfony Process
        $artisan = base_path('artisan');

       $process = new Process(['php', 'C:\\xampp_new\\htdocs\\GeneratorApp\\artisan', 'backup:run']);

        $process->setTimeout(1800); // 30 minutes max
        $process->run();

        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Backup command failed. Error: {$errorOutput}");
        }

        $backupsAfter = collect($backupDisk->files($backupPath))
            ->filter(fn($file) => pathinfo($file, PATHINFO_EXTENSION) === 'zip')
            ->count();

        $backupCreated = $backupsAfter > $backupsBefore;

        if (!$backupCreated) {
            throw new \Exception("No new backup file was created. Output: {$output}");
        }

        $latestBackup = collect($backupDisk->files($backupPath))
            ->filter(fn($file) => pathinfo($file, PATHINFO_EXTENSION) === 'zip')
            ->map(function($file) use ($backupDisk) {
                return [
                    'name' => $file,
                    'size' => $backupDisk->size($file),
                    'modified' => $backupDisk->lastModified($file),
                ];
            })
            ->sortByDesc('modified')
            ->first();

        Cache::put('auto_backup_last_at', now()->toDateTimeString(), now()->addDays(2));
        Cache::put('last_backup_file', $latestBackup['name'] ?? null, now()->addDays(2));
        Cache::put('last_backup_size', $latestBackup['size'] ?? 0, now()->addDays(2));

        Log::info('[MANUAL-BACKUP] completed successfully. File: ' . ($latestBackup['name'] ?? 'unknown'));

        return response()->json([
            'success' => true,
            'message' => 'Backup completed successfully!',
            'backup_created' => true,
            'backup_file' => $latestBackup['name'] ?? null,
            'backup_size' => $latestBackup['size'] ?? 0,
            'output' => $output
        ]);

    } catch (\Throwable $e) {
        Log::error('[MANUAL-BACKUP] failed: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Backup failed: ' . $e->getMessage(),
            'backup_created' => false
        ], 500);

    } finally {
        Cache::forget('backup_running');
    }
}


    public function backupStatus(): JsonResponse
    {
        $isRunning = Cache::has('backup_running');
        $lastBackup = Cache::get('auto_backup_last_at');
        
        return response()->json([
            'is_running' => $isRunning,
            'last_backup' => $lastBackup
        ]);
    }

    public function showBackupPage()
    {
        $lastBackup = Cache::get('auto_backup_last_at');
        
        return view('backup-management', [
            'lastBackup' => $lastBackup
        ]);
    }
}