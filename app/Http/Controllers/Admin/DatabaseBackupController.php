<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupController extends Controller
{
    // Tabel yang tidak di-backup/import (ephemeral atau dikelola SSO)
    private const EXCLUDED = [
        'users',
        'migrations',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'failed_jobs',
        'password_reset_tokens',
    ];

    public function index(): Response
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        return Inertia::render('Admin/Settings/Database');
    }

    public function download(): StreamedResponse
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        $tables = $this->getBackupTables();
        $filename = 'malas-backup-'.now()->format('Y-m-d-His').'.sql';

        return response()->streamDownload(function () use ($tables): void {
            echo "-- MALAS Database Backup\n";
            echo '-- Generated : '.now()->toDateTimeString()."\n";
            echo '-- Tables    : '.implode(', ', $tables)."\n";
            echo "-- NOTE: Tabel 'users' tidak di-backup (dikelola via SSO)\n\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                echo "-- --------------------------------------------------------\n";
                echo "-- Table: `{$table}`\n";
                echo "-- --------------------------------------------------------\n";

                echo "DELETE FROM `{$table}`;\n";

                $rows = DB::table($table)->get();

                if ($rows->isEmpty()) {
                    echo "\n";

                    continue;
                }

                $columns = array_keys((array) $rows->first());
                $colList = '`'.implode('`, `', $columns).'`';

                foreach ($rows as $row) {
                    $vals = array_map(function ($v): string {
                        if ($v === null) {
                            return 'NULL';
                        }

                        return "'".str_replace(
                            ['\\',  "'",  "\n",  "\r",  "\x00", "\x1a"],
                            ['\\\\', "\\'", '\\n', '\\r', '\\0',  '\\Z'],
                            (string) $v
                        )."'";
                    }, (array) $row);

                    echo "INSERT INTO `{$table}` ({$colList}) VALUES (".implode(', ', $vals).");\n";
                }

                echo "\n";
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";
        }, $filename, ['Content-Type' => 'application/sql']);
    }

    public function import(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        $request->validate([
            'backup_file' => ['required', 'file', 'max:102400'],
        ]);

        $content = file_get_contents($request->file('backup_file')->getRealPath());

        if (! str_starts_with($content, '-- MALAS Database Backup')) {
            return back()->with('error', 'File bukan backup MALAS yang valid. Pastikan file yang diupload adalah hasil download dari halaman ini.');
        }

        // Parse statement satu per satu
        $statements = $this->parseStatements($content);

        // Safety: buang statement yang menyentuh tabel excluded
        $excluded = self::EXCLUDED;
        $statements = array_filter($statements, function (string $sql) use ($excluded): bool {
            foreach ($excluded as $table) {
                // Cek apakah statement menyebut tabel ini dalam konteks DML/DDL
                if (preg_match('/\b(DELETE\s+FROM|INSERT\s+INTO|UPDATE|TRUNCATE|DROP\s+TABLE|CREATE\s+TABLE)\s+[`"]?'.preg_quote($table, '/').'[`"]?/i', $sql)) {
                    return false;
                }
            }

            return true;
        });

        try {
            DB::beginTransaction();
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($statements as $sql) {
                $sql = trim($sql);
                if ($sql === '' || str_starts_with($sql, '--')) {
                    continue;
                }
                DB::unprepared($sql);
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::commit();

            return back()->with('success', 'Database berhasil diimpor. Semua data (kecuali user) telah dipulihkan dari backup.');
        } catch (\Throwable $e) {
            DB::rollBack();
            // Pastikan FK checks kembali aktif meski rollback
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } catch (\Throwable) {
            }

            return back()->with('error', 'Import gagal dan dibatalkan (rollback). Pesan error: '.$e->getMessage());
        }
    }

    private function parseStatements(string $content): array
    {
        $statements = [];
        $current = '';

        foreach (explode("\n", $content) as $line) {
            $trimmed = ltrim($line);

            // Skip komentar dan baris kosong
            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }

            $current .= $line."\n";

            // Statement selesai kalau baris diakhiri titik koma
            if (str_ends_with(rtrim($line), ';')) {
                $statements[] = trim($current);
                $current = '';
            }
        }

        return array_filter($statements, fn (string $s) => $s !== '');
    }

    private function getBackupTables(): array
    {
        $rows = DB::select('SHOW TABLES');

        return array_values(array_filter(
            array_map(fn ($row) => array_values((array) $row)[0], $rows),
            fn (string $table) => ! in_array($table, self::EXCLUDED, true),
        ));
    }
}
