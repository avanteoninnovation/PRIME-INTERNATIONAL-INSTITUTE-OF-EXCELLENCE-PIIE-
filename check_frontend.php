<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$setting = DB::table('global_settings')->where('key', 'frontend_view')->first();
echo "Frontend View Setting:\n";
echo json_encode($setting, JSON_PRETTY_PRINT) . "\n\n";

if (!$setting) {
    echo "Setting not found. Creating it with value '1'...\n";
    DB::table('global_settings')->insert([
        'key' => 'frontend_view',
        'value' => '1'
    ]);
    echo "✓ Setting created successfully\n";
} elseif ($setting->value != '1') {
    echo "Frontend view is disabled (value: " . $setting->value . "). Enabling it...\n";
    DB::table('global_settings')->where('key', 'frontend_view')->update(['value' => '1']);
    echo "✓ Frontend enabled\n";
} else {
    echo "✓ Frontend is already enabled\n";
}
?>
