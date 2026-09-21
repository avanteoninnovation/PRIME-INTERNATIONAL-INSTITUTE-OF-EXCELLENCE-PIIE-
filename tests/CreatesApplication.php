<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // PHPUnit must never inherit the developer's live MySQL connection.
        // Keep normal browser/Artisan development unchanged; this applies
        // only to the application instance created by the test runner.
        if ($app->environment('testing')) {
            config([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => ':memory:',
                // Explicit mysql usage must fail against a harmless database
                // name instead of ever reaching piie_main.
                'database.connections.mysql.database' => '__piie_phpunit_blocked__',
            ]);

            \Illuminate\Support\Facades\DB::purge('sqlite');
            \Illuminate\Support\Facades\DB::reconnect('sqlite');
            \Illuminate\Support\Facades\DB::purge('mysql');
        }

        return $app;
    }
}
