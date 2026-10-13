<?php
/** One-time repair of the empty schema created during foundation verification. No table/data deletion. */
require __DIR__.'/../services/identity-service/vendor/autoload.php';
$app = require __DIR__.'/../services/identity-service/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$reference = $argv[1] ?? '';
$apply = ($argv[2] ?? '') === '--apply';
if (!preg_match('/^identity_verify_[0-9]{8}_[0-9]{6}_[a-f0-9]{6}$/', $reference)) {
    fwrite(STDERR, "Expected generated verification database name\n"); exit(1);
}
try {
    $db = DB::connection();
    if ($db->getDatabaseName() !== 'identity_db' || !in_array($db->getConfig('host'), ['localhost','127.0.0.1','::1'], true)) throw new RuntimeException('Unexpected target');
    $tables = array_map(fn($r) => array_values((array)$r)[0], $db->select('SHOW TABLES'));
    $expected = array_map(fn($r) => array_values((array)$r)[0], $db->select('SHOW TABLES FROM `'.$reference.'`'));
    $expected = array_values(array_diff($expected, ['migrations'])); sort($tables); sort($expected);
    if ($tables !== $expected || count($tables) !== 16) throw new RuntimeException('Table inventory mismatch; no repair performed');
    foreach ($tables as $table) {
        if (!preg_match('/^[a-z_]+$/', $table)) throw new RuntimeException('Unexpected identifier');
        if ($db->table($table)->count() !== 0) throw new RuntimeException('Target contains data; no repair performed');
        $actual = array_values((array)$db->selectOne('SHOW CREATE TABLE `identity_db`.`'.$table.'`'))[1];
        $schema = array_values((array)$db->selectOne('SHOW CREATE TABLE `'.$reference.'`.`'.$table.'`'))[1];
        if ($actual !== $schema) throw new RuntimeException('Schema differs: '.$table);
    }
    $rows = $db->table($reference.'.migrations')->orderBy('migration')->get(['migration','batch'])->map(fn($r)=>(array)$r)->all();
    $files = array_map(fn($f)=>basename($f,'.php'),glob(__DIR__.'/../services/identity-service/database/migrations/*.php')); sort($files);
    if (array_column($rows,'migration') !== $files) throw new RuntimeException('Migration list differs');
    echo "PASS: all 16 target tables empty and structurally identical to verified migrations\n";
    if (!$apply) { echo "Read-only comparison complete; --apply adds only the missing migration ledger.\n"; exit(0); }
    $db->statement('CREATE TABLE `identity_db`.`migrations` LIKE `'.$reference.'`.`migrations`');
    $db->table('migrations')->insert($rows);
    echo "REPAIRED: migration ledger added (16 verified entries); no existing table/data modified or removed.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: '.get_class($e).($e instanceof RuntimeException && !($e instanceof \Illuminate\Database\QueryException) ? ': '.$e->getMessage() : '')."\n"); exit(1);
}
