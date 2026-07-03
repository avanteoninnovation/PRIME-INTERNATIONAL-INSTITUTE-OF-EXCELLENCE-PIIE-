<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$roles = DB::table('roles')->get();
foreach ($roles as $r) {
    echo (isset($r->id) ? $r->id : '?') . ' = ' . $r->name . " (school_id=" . ($r->school_id ?? 0) . ")\n";
}
echo "\n--- Middleware check ---\n";
echo implode("\n", glob(app_path('Http/Middleware/*.php')));
