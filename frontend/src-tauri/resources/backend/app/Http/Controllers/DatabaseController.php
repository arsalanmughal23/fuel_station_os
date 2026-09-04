<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use App\Http\Requests\RestoreDatabaseRequest;

class DatabaseController extends Controller
{
    /**
     * Get database info
     */
    public function info()
    {
        $dbPath = storage_path('database/database.sqlite');
        
        $info = [
            'database_path' => $dbPath,
            'exists' => File::exists($dbPath),
            'size' => File::exists($dbPath) ? File::size($dbPath) : 0,
            'size_human' => File::exists($dbPath) ? $this->formatBytes(File::size($dbPath)) : 'N/A',
            'last_modified' => File::exists($dbPath) ? Carbon::createFromTimestamp(File::lastModified($dbPath))->toISOString() : null,
            'tables' => [],
            'connection' => 'sqlite',
        ];

        if (File::exists($dbPath)) {
            try {
                $tables = DB::connection('sqlite')->select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
                $info['tables'] = array_column($tables, 'name');
                $info['table_count'] = count($info['tables']);
            } catch (\Exception $e) {
                $info['error'] = $e->getMessage();
            }
        }

        return response()->json($info);
    }

    /**
     * Create database backup
     */
    public function backup(Request $request)
    {
        $dbPath = storage_path('database/database.sqlite');
        
        if (!File::exists($dbPath)) {
            return response()->json(['error' => 'Database file not found'], 404);
        }

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $backupName = "fuel_station_backup_{$timestamp}.sqlite";
        
        // Create backup copy
        $backupPath = storage_path("app/backups/{$backupName}");
        
        // Ensure backup directory exists
        if (!File::exists(storage_path('app/backups'))) {
            File::makeDirectory(storage_path('app/backups'), 0755, true);
        }
        
        // Copy database file (simple file copy - SQLite file is consistent at any point)
        File::copy($dbPath, $backupPath);
        
        // Return file for download (KEEP the backup file for future restores)
        return Response::download($backupPath, "fuel_station_backup_{$timestamp}.sqlite", [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"fuel_station_backup_{$timestamp}.sqlite\"",
        ]);
    }

    /**
     * Restore database from backup
     */
    public function restore(RestoreDatabaseRequest $request)
    {
        $dbPath = storage_path('database/database.sqlite');
        $backupFile = $request->file('backup_file');

        try {
            // Create backup of current database before restore
            $currentBackupPath = storage_path('database/database.sqlite.backup_' . now()->format('Y-m-d_H-i-s'));
            if (File::exists($dbPath)) {
                File::copy($dbPath, $currentBackupPath);
            }

            // Save uploaded file to temp location first
            $tempPath = storage_path('app/temp_restore.sqlite');
            $backupFile->move(storage_path('app'), 'temp_restore.sqlite');
            
            // Verify the uploaded file is a valid SQLite database
            try {
                $testPdo = new PDO("sqlite:{$tempPath}");
                $testPdo->query("SELECT 1");
            } catch (\Exception $e) {
                File::delete($tempPath);
                return response()->json([
                    'error' => 'Invalid SQLite database file: ' . $e->getMessage(),
                ], 400);
            }

            // Replace database with uploaded backup (atomic operation)
            if (File::exists($dbPath)) {
                File::delete($dbPath);
            }
            File::move($tempPath, $dbPath);

            // Reconnect to new database
            DB::purge('sqlite');
            DB::reconnect('sqlite');

            // Verify connection works
            DB::connection('sqlite')->getPdo()->query("SELECT 1");

            return response()->json([
                'message' => 'Database restored successfully',
                'previous_backup' => basename($currentBackupPath),
            ]);
        } catch (\Exception $e) {
            // Attempt to restore original database on failure
            if (isset($currentBackupPath) && File::exists($currentBackupPath)) {
                if (File::exists($dbPath)) {
                    File::delete($dbPath);
                }
                File::copy($currentBackupPath, $dbPath);
                DB::purge('sqlite');
                DB::reconnect('sqlite');
            }
            
            // Clean up temp file if exists
            $tempPath = storage_path('app/temp_restore.sqlite');
            if (File::exists($tempPath)) {
                File::delete($tempPath);
            }

            return response()->json([
                'error' => 'Failed to restore database: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List available backups
     */
    public function listBackups()
    {
        $backupDir = storage_path('app/backups');
        
        if (!File::exists($backupDir)) {
            return response()->json(['backups' => []]);
        }

        $files = File::files($backupDir);
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'size_human' => $this->formatBytes($file->getSize()),
                'created_at' => Carbon::createFromTimestamp($file->getMTime())->toISOString(),
                'download_url' => route('database.backups.show', ['filename' => $file->getFilename()]),
            ];
        }

        // Sort by newest first
        usort($backups, fn($a, $b) => $b['created_at'] <=> $a['created_at']);

        return response()->json(['backups' => $backups]);
    }

    /**
     * Download specific backup
     */
    public function downloadBackup($filename)
    {
        $filePath = storage_path("app/backups/{$filename}");
        
        if (!File::exists($filePath)) {
            return response()->json(['error' => 'Backup not found'], 404);
        }

        return Response::download($filePath, $filename, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    /**
     * Delete backup
     */
    public function deleteBackup($filename)
    {
        $filePath = storage_path("app/backups/{$filename}");
        
        if (!File::exists($filePath)) {
            return response()->json(['error' => 'Backup not found'], 404);
        }

        File::delete($filePath);

        return response()->json(['message' => 'Backup deleted successfully']);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}