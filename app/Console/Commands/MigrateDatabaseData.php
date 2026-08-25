<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateDatabaseData extends Command
{
    /**
     * Dipakai sekali pas cutover ke DB engine baru (mis. SQLite/MySQL dev existing → Postgres
     * Docker). Nggak pakai dump SQL text (`DatabaseBackupController`) karena dialect SQL beda-beda
     * antara SQLite/MySQL/Postgres (backtick vs double-quote, dst) — di sini datanya disalin baris
     * per baris lewat query builder, jadi driver-agnostic dan nggak perlu peduli sintaks SQL sama sekali.
     */
    protected $signature = 'malas:migrate-data
        {--from=sqlite : Nama connection sumber, sesuai key di config/database.php (mis. sqlite, mysql)}
        {--to=pgsql : Nama connection tujuan, sesuai key di config/database.php (mis. pgsql)}
        {--truncate : Kosongkan dulu tabel tujuan sebelum menyalin (aman dipakai berkali-kali/re-run)}
        {--chunk=500 : Jumlah baris per batch insert}';

    protected $description = 'Salin seluruh data dari satu koneksi database ke koneksi lain (mis. SQLite/MySQL lama ke Postgres baru), dipakai sekali pas cutover engine DB.';

    public function handle(): int
    {
        $from = (string) $this->option('from');
        $to = (string) $this->option('to');
        $chunk = max(1, (int) $this->option('chunk'));

        if ($from === $to) {
            $this->error('--from dan --to nggak boleh sama.');

            return self::FAILURE;
        }

        $sourceConnection = DB::connection($from);
        $targetConnection = DB::connection($to);

        try {
            $sourceConnection->getPdo();
            $targetConnection->getPdo();
        } catch (\Throwable $e) {
            $this->error("Gagal konek ke salah satu koneksi: {$e->getMessage()}");

            return self::FAILURE;
        }

        $tables = $this->listTables($sourceConnection);

        if ($tables === []) {
            $this->warn("Nggak ada tabel ditemukan di koneksi '{$from}'.");

            return self::SUCCESS;
        }

        $this->info("Menyalin ".count($tables)." tabel dari '{$from}' ke '{$to}'...");

        if (! $this->option('no-interaction') && ! $this->confirm("Lanjutkan? Ini akan ".($this->option('truncate') ? 'MENGOSONGKAN lalu ' : '')."mengisi tabel di koneksi '{$to}'.", true)) {
            $this->warn('Dibatalkan.');

            return self::SUCCESS;
        }

        foreach ($tables as $table) {
            if (! Schema::connection($to)->hasTable($table)) {
                $this->warn("Skip '{$table}' — tabel belum ada di koneksi tujuan (jalankan `php artisan migrate` di sana dulu).");

                continue;
            }

            $total = $sourceConnection->table($table)->count();

            if ($total === 0) {
                $this->line("  {$table}: kosong, skip.");

                continue;
            }

            if ($this->option('truncate')) {
                $targetConnection->table($table)->truncate();
            }

            // Nggak pakai chunkById() — sebagian tabel pivot (mis. announcement_user, tabel Spatie
            // permission yang emang selalu kosong) nggak punya kolom `id`. Karena ini migrasi
            // sekali-jalan buat app skala homelab (bukan data besar), cursor() + array_chunk cukup
            // dan nggak butuh kolom unik buat ordering.
            $copied = 0;
            foreach ($sourceConnection->table($table)->cursor()->chunk($chunk) as $rows) {
                $data = $rows->map(fn ($row) => (array) $row)->all();

                if ($data !== []) {
                    $targetConnection->table($table)->insert($data);
                }

                $copied += count($data);
            }

            $this->line("  {$table}: {$copied}/{$total} baris disalin.");
        }

        $this->info('Selesai. Cek jumlah baris tiap tabel penting secara manual sebelum switch DB_CONNECTION di .env.');

        return self::SUCCESS;
    }

    private function listTables(\Illuminate\Database\Connection $connection): array
    {
        $driver = $connection->getDriverName();

        $rows = match ($driver) {
            'sqlite' => $connection->select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' AND name != 'migrations'"),
            'pgsql' => $connection->select("SELECT tablename AS name FROM pg_catalog.pg_tables WHERE schemaname = 'public' AND tablename != 'migrations'"),
            default => array_filter(
                $connection->select('SHOW TABLES'),
                fn ($row) => array_values((array) $row)[0] !== 'migrations',
            ),
        };

        return array_values(array_map(fn ($row) => array_values((array) $row)[0], $rows));
    }
}
