<?php
// Called only by test-auth-local.py. JSON credentials go to its private pipe, never console logs.
require __DIR__.'/../services/identity-service/vendor/autoload.php';
$app = require __DIR__.'/../services/identity-service/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $e): void { fwrite(STDERR, "Database test setup/cleanup failed; inspect locally.\n"); exit(1); });
$db = \Illuminate\Support\Facades\DB::connection();
if ($db->getDriverName() !== 'mysql' || !in_array($db->getConfig('host'), ['127.0.0.1','localhost'], true)) exit(2);
$pdo=$db->getPdo();
$name='identity_auth_test';
if (($argv[1] ?? '') === 'create') {
    $databaseCreated=false;$userCreated=false;
    try {
        // Never reuse/reset existing test objects: CREATE must fail on collision.
        $pdo->exec("CREATE DATABASE `identity_auth_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$databaseCreated=true;
        $password=bin2hex(random_bytes(24));
        $pdo->exec("CREATE USER 'identity_auth_test'@'localhost' IDENTIFIED BY ".$pdo->quote($password));$userCreated=true;
        $pdo->exec("GRANT ALL PRIVILEGES ON `identity_auth_test`.* TO 'identity_auth_test'@'localhost'");
        echo json_encode(['DB_CONNECTION'=>'mysql','DB_HOST'=>$db->getConfig('host'),'DB_PORT'=>(string)$db->getConfig('port'),
            'DB_DATABASE'=>$name,'DB_USERNAME'=>$name,'DB_PASSWORD'=>$password,'DB_URL'=>'']);
    } catch (Throwable $e) {
        if($userCreated)$pdo->exec("DROP USER 'identity_auth_test'@'localhost'");
        if($databaseCreated)$pdo->exec("DROP DATABASE `identity_auth_test`");
        fwrite(STDERR,"Test DB setup failed; existing DB/user are not reused or deleted.\n");exit(1);
    }
} elseif (($argv[1] ?? '') === 'cleanup') {
    // Caller must prove it owns the temporary account using the generated random password.
    $password=stream_get_contents(STDIN);
    $proof=new PDO('mysql:host='.$db->getConfig('host').';port='.$db->getConfig('port').';dbname='.$name,$name,$password);
    $proof=null;
    $pdo->exec("DROP DATABASE `identity_auth_test`");
    $pdo->exec("DROP USER 'identity_auth_test'@'localhost'");
    echo "PASS cleanup: temporary database and account removed\n";
} else exit(2);
