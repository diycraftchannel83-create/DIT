<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class InspectAcademicDatabase extends Command
{
    protected $signature = 'academic:inspect';

    protected $description = 'Read-only check of the existing SIAKAD database schema and record counts';

    private array $tables = [
        'users',
        'students',
        'lecturers',
        'study_programs',
        'academic_years',
        'semesters',
        'courses',
        'course_classes',
        'class_schedules',
        'pmb_applicants',
        'pmb_documents',
        'pmb_payment_records',
    ];

    public function handle(): int
    {
        try {
            $connection = DB::connection('academic');
            $database = $connection->selectOne('SELECT DATABASE() AS db_name');
            $this->info('Academic database connection: OK');
            $this->line('Database: '.($database->db_name ?? '(unknown)'));

            $rows = $connection->select(
                'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()'
            );
            $available = array_fill_keys(array_map(
                static fn ($row) => $row->TABLE_NAME,
                $rows
            ), true);

            $this->newLine();
            $this->table(['Table', 'Status', 'Rows'], array_map(
                function (string $table) use ($connection, $available): array {
                    if (! isset($available[$table])) {
                        return [$table, 'MISSING', '-'];
                    }

                    // Names come only from this fixed allowlist.
                    $count = $connection->table($table)->count();

                    return [$table, 'OK', (string) $count];
                },
                $this->tables
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Academic database check failed: '.$exception->getMessage());
            $this->line('No schema or data changes were attempted.');

            return self::FAILURE;
        }
    }
}
