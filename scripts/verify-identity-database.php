<?php
/** Read-only inventory. Runtime tests live in test-auth-local.py with automatic cleanup. */
if (($argv[1] ?? '') === '--verify') {
    fwrite(STDERR, "Legacy DB-creating mode disabled. Run: python3 scripts/test-auth-local.py (automatic cleanup).\n");
    exit(2);
}
require __DIR__.'/../services/identity-service/vendor/autoload.php';
$app = require __DIR__.'/../services/identity-service/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db=\Illuminate\Support\Facades\DB::connection();
echo 'Configured database: '.$db->getDatabaseName()."\n";
foreach($db->select('SHOW TABLES') as $row) {
    $table=array_values((array)$row)[0];
    if(preg_match('/^[a-zA-Z0-9_]+$/',$table)) echo $table.': '.$db->table($table)->count()." rows\n";
}
