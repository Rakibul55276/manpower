<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackupManpower extends Command
{
    protected $signature = 'manpower:backup {--keep=14 : Number of newest backups to retain}';
    protected $description = 'Create a private compressed SQL backup without exposing database credentials';

    public function handle()
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->error('This portable SQL backup currently supports MySQL/MariaDB only.');
            return 1;
        }

        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);
        $path = $directory.DIRECTORY_SEPARATOR.'manpower-'.now()->format('Ymd-His').'.sql.gz';
        $temporary = $path.'.part';
        $stream = gzopen($temporary, 'wb9');
        if (!$stream) { $this->error('The backup file could not be opened.'); return 1; }

        try {
            gzwrite($stream, "-- Manpower database backup\n-- Created ".now()->toIso8601String()."\nSET FOREIGN_KEY_CHECKS=0;\n\n");
            $tables = collect(DB::select('SHOW TABLES'))->map(function ($row) { return array_values((array) $row)[0]; });
            foreach ($tables as $table) {
                $quotedTable = '`'.str_replace('`', '``', $table).'`';
                $definition = (array) DB::selectOne('SHOW CREATE TABLE '.$quotedTable);
                $create = array_values($definition)[1];
                gzwrite($stream, 'DROP TABLE IF EXISTS '.$quotedTable.";\n".$create.";\n");
                DB::table($table)->orderBy(DB::raw('1'))->chunk(500, function ($rows) use ($stream, $quotedTable) {
                    foreach ($rows as $row) {
                        $values = array_map(function ($value) {
                            return is_null($value) ? 'NULL' : DB::connection()->getPdo()->quote((string) $value);
                        }, array_values((array) $row));
                        gzwrite($stream, 'INSERT INTO '.$quotedTable.' VALUES ('.implode(',', $values).");\n");
                    }
                });
                gzwrite($stream, "\n");
            }
            gzwrite($stream, "SET FOREIGN_KEY_CHECKS=1;\n");
            gzclose($stream);
            rename($temporary, $path);
        } catch (\Throwable $exception) {
            gzclose($stream);
            @unlink($temporary);
            $this->error('Backup failed: '.$exception->getMessage());
            return 1;
        }

        $keep = max(1, (int) $this->option('keep'));
        collect(File::glob($directory.DIRECTORY_SEPARATOR.'manpower-*.sql.gz'))
            ->sortByDesc(function ($file) { return File::lastModified($file); })->slice($keep)->each(function ($file) { File::delete($file); });
        $this->info('Backup created: '.$path);
        return 0;
    }
}
