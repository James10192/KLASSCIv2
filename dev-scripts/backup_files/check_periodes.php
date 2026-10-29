<?php
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/bootstrap/app.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$periodes = DB::table('esbtp_evaluations')->select('periode')->distinct()->pluck('periode');
echo "Périodes utilisées dans la table esbtp_evaluations:\n";
foreach ($periodes as $periode) {
    echo "- " . ($periode ?: 'NULL') . "\n";
}
