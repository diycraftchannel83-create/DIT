<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Separate connection to the existing SIAKAD database.
        // The configured MySQL account must have SELECT privileges only.
        config([
            'database.connections.academic' => [
                'driver' => 'mysql',
                'url' => env('ACADEMIC_DATABASE_URL'),
                'host' => env('ACADEMIC_DB_HOST', '127.0.0.1'),
                'port' => env('ACADEMIC_DB_PORT', '3306'),
                'database' => env('ACADEMIC_DB_DATABASE'),
                'username' => env('ACADEMIC_DB_USERNAME'),
                'password' => env('ACADEMIC_DB_PASSWORD'),
                'unix_socket' => env('ACADEMIC_DB_SOCKET', ''),
                'charset' => env('ACADEMIC_DB_CHARSET', 'utf8mb4'),
                'collation' => env('ACADEMIC_DB_COLLATION', 'utf8mb4_unicode_ci'),
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
                'options' => extension_loaded('pdo_mysql') ? array_filter([
                    \PDO::ATTR_TIMEOUT => 8,
                    \PDO::ATTR_PERSISTENT => false,
                ]) : [],
            ],
        ]);
    }
}
