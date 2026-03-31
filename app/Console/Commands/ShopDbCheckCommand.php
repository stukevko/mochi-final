<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Laeuft nur als Artisan-Command; wird nicht bei Web-Requests aufgerufen.
 */
class ShopDbCheckCommand extends Command
{
    protected $signature = 'shop:db-check';

    protected $description = 'Prueft InnoDB-Engine und utf8mb4 Charset in MySQL/MariaDB';

    public function handle(): int
    {
        try {
            $connection = DB::connection();
            $driver = $connection->getDriverName();

            if (! in_array($driver, ['mysql', 'mariadb'], true)) {
                $this->warn(sprintf('DB-Driver (%s) ist kein MySQL/MariaDB. Check wird uebersprungen.', $driver));

                return self::SUCCESS;
            }

            $database = $connection->getDatabaseName();
            if (! is_string($database) || $database === '') {
                $this->error('Keine Database-Name ermittelbar. (getDatabaseName() liefert leer)');

                return self::FAILURE;
            }

            $notInnoDb = $connection->select(
                'select table_name, engine from information_schema.tables
                 where table_schema = ?
                   and table_type = "BASE TABLE"
                   and engine is not null
                   and engine <> "InnoDB"',
                [$database],
            );

            $notUtf8mb4 = $connection->select(
                'select table_name, table_collation from information_schema.tables
                 where table_schema = ?
                   and table_type = "BASE TABLE"
                   and (table_collation is null or table_collation not like "utf8mb4%")',
                [$database],
            );

            $hasProblems = false;

            if ($notInnoDb !== []) {
                $hasProblems = true;
                $this->warn('Folgende Tabellen sind NICHT InnoDB:');
                foreach ($notInnoDb as $row) {
                    $this->line(sprintf('- %s (%s)', $row->table_name ?? 'unknown', $row->engine ?? 'unknown'));
                }
            } else {
                $this->info('Engine-Check: OK (alle BASE TABLES InnoDB)');
            }

            if ($notUtf8mb4 !== []) {
                $hasProblems = true;
                $this->warn('Folgende Tabellen sind NICHT utf8mb4-collated:');
                foreach ($notUtf8mb4 as $row) {
                    $this->line(sprintf('- %s (%s)', $row->table_name ?? 'unknown', $row->table_collation ?? 'unknown'));
                }
            } else {
                $this->info('Charset/Collation-Check: OK (utf8mb4 bei allen BASE TABLES)');
            }

            if ($hasProblems) {
                $this->error('DB-Check: Probleme gefunden.');

                return self::FAILURE;
            }

            $this->info('DB-Check: alles erfolgreich.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('DB-Check fehlgeschlagen: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}

