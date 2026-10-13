<?php
// Called only by test-learning-local.py. It creates one uniquely owned MySQL DB/user and removes them after proof of ownership.
require __DIR__.'/../services/identity-service/vendor/autoload.php';
$app = require __DIR__.'/../services/identity-service/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error): void { fwrite(STDERR, "Learning test database setup/cleanup failed; inspect locally.\n"); exit(1); });

$db = \Illuminate\Support\Facades\DB::connection();
if ($db->getDriverName() !== 'mysql' || ! in_array($db->getConfig('host'), ['127.0.0.1', 'localhost'], true)) exit(2);
$pdo = $db->getPdo();
$name = 'learning_foundation_test';

if (($argv[1] ?? '') === 'create') {
    $databaseCreated = false;
    $userCreated = false;
    try {
        // A collision is a hard failure: this script never resets or adopts pre-existing objects.
        $pdo->exec("CREATE DATABASE `learning_foundation_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $databaseCreated = true;
        $password = bin2hex(random_bytes(24));
        $pdo->exec("CREATE USER 'learning_foundation_test'@'localhost' IDENTIFIED BY ".$pdo->quote($password));
        $userCreated = true;
        $pdo->exec("GRANT ALL PRIVILEGES ON `learning_foundation_test`.* TO 'learning_foundation_test'@'localhost'");
        echo json_encode(['DB_CONNECTION' => 'mysql', 'DB_HOST' => $db->getConfig('host'), 'DB_PORT' => (string) $db->getConfig('port'), 'DB_DATABASE' => $name, 'DB_USERNAME' => $name, 'DB_PASSWORD' => $password, 'DB_URL' => '']);
    } catch (Throwable $error) {
        if ($userCreated) $pdo->exec("DROP USER 'learning_foundation_test'@'localhost'");
        if ($databaseCreated) $pdo->exec("DROP DATABASE `learning_foundation_test`");
        fwrite(STDERR, "Learning test objects already exist or could not be created; nothing pre-existing was changed.\n");
        exit(1);
    }
} elseif (($argv[1] ?? '') === 'cleanup') {
    $password = stream_get_contents(STDIN);
    $proof = new PDO('mysql:host='.$db->getConfig('host').';port='.$db->getConfig('port').';dbname='.$name, $name, $password);
    $proof = null;
    $pdo->exec("DROP DATABASE `learning_foundation_test`");
    $pdo->exec("DROP USER 'learning_foundation_test'@'localhost'");
    echo "PASS cleanup: temporary Learning database and account removed\n";
} else {
    exit(2);
}
