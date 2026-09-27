<?php

declare(strict_types=1);

/**
 * Isolated DB smoke test for the admin profile moderation worktree.
 *
 * Safety properties:
 * - reads connection credentials from the live .env without modifying it
 * - creates a NEW database with a hard-coded hntrocks_admin_test_* prefix
 * - verifies Laravel resolved exactly that database before migrate:fresh
 * - always drops only that generated test database in finally
 * - never runs migrations against the production database
 */

use App\Models\ProfileModerationFlag;
use App\Models\User;
use App\Services\ProfileModerationService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$worktree = realpath(__DIR__.'/..');
$expectedWorktree = '/home/users/hunthub/admin/hntrocks-admin';
$liveRoot = '/home/users/hunthub/www/hnt.rocks';
$liveEnvPath = $liveRoot.'/.env';

if ($worktree !== $expectedWorktree) {
    fwrite(STDERR, "ABBRUCH: Script muss im separaten Admin-Worktree laufen.\n");
    exit(10);
}

if (! is_file($liveEnvPath)) {
    fwrite(STDERR, "ABBRUCH: Live-.env nicht gefunden.\n");
    exit(11);
}

require $worktree.'/vendor/autoload.php';

$env = Dotenv\Dotenv::parse(file_get_contents($liveEnvPath));

$host = (string) ($env['DB_HOST'] ?? '127.0.0.1');
$port = (string) ($env['DB_PORT'] ?? '3306');
$socket = (string) ($env['DB_SOCKET'] ?? '');
$user = (string) ($env['DB_USERNAME'] ?? '');
$password = (string) ($env['DB_PASSWORD'] ?? '');
$liveDatabase = (string) ($env['DB_DATABASE'] ?? '');

if ($user === '' || $liveDatabase === '') {
    fwrite(STDERR, "ABBRUCH: DB-Zugangsdaten konnten nicht sicher gelesen werden.\n");
    exit(12);
}

$testDatabase = 'hntrocks_admin_test_'.date('Ymd_His').'_'.getmypid();

if (! preg_match('/^hntrocks_admin_test_[A-Za-z0-9_]+$/', $testDatabase)) {
    fwrite(STDERR, "ABBRUCH: Unerwarteter Testdatenbankname.\n");
    exit(13);
}

if ($testDatabase === $liveDatabase) {
    fwrite(STDERR, "ABBRUCH: Test- und Live-Datenbank dürfen niemals identisch sein.\n");
    exit(14);
}

$dsn = $socket !== ''
    ? 'mysql:unix_socket='.$socket.';charset=utf8mb4'
    : 'mysql:host='.$host.';port='.$port.';charset=utf8mb4';

$serverPdo = null;
$created = false;

try {
    $serverPdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "Erzeuge isolierte Testdatenbank: {$testDatabase}\n";
    $serverPdo->exec("CREATE DATABASE `{$testDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;

    $runtimeEnv = [
        'APP_NAME' => 'HNT.ROCKS Admin QA',
        'APP_ENV' => 'testing',
        'APP_DEBUG' => 'false',
        'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => $host,
        'DB_PORT' => $port,
        'DB_DATABASE' => $testDatabase,
        'DB_USERNAME' => $user,
        'DB_PASSWORD' => $password,
        'DB_SOCKET' => $socket,
        'CACHE_STORE' => 'array',
        'SESSION_DRIVER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'MAIL_MAILER' => 'array',
        'BROADCAST_CONNECTION' => 'log',
    ];

    foreach ($runtimeEnv as $key => $value) {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key.'='.$value);
    }

    unset($_ENV['DB_URL'], $_SERVER['DB_URL']);
    putenv('DB_URL');

    /** @var Illuminate\Foundation\Application $app */
    $app = require $worktree.'/bootstrap/app.php';

    /** @var Kernel $kernel */
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();

    $resolvedDatabase = (string) config('database.connections.mysql.database');

    if ($resolvedDatabase !== $testDatabase) {
        throw new RuntimeException(
            "SICHERHEITSABBRUCH: Laravel verwendet '{$resolvedDatabase}' statt '{$testDatabase}'."
        );
    }

    DB::purge('mysql');
    DB::reconnect('mysql');

    if ((string) DB::connection('mysql')->getDatabaseName() !== $testDatabase) {
        throw new RuntimeException('SICHERHEITSABBRUCH: aktive DB-Verbindung zeigt nicht auf die Testdatenbank.');
    }

    echo "Laravel-DB geprüft: {$testDatabase}\n";
    echo "Führe Migrationen ausschließlich in der Testdatenbank aus ...\n";

    $exitCode = $kernel->call('migrate:fresh', [
        '--database' => 'mysql',
        '--force' => true,
    ]);

    echo $kernel->output();

    if ($exitCode !== 0) {
        throw new RuntimeException('Migrationen sind fehlgeschlagen.');
    }

    echo "Teste konservatives Profil-Flagging ...\n";

    $userModel = User::query()->create([
        'name' => 'Admin QA User',
        'username' => 'admin_qa_'.getmypid(),
        'email' => 'admin-qa-'.getmypid().'@example.test',
        'password' => 'temporary-qa-password',
        'status' => 'active',
    ]);

    $profile = $userModel->profile()->create([
        'profile_visibility' => 'public',
        'bio' => "I'm mainly here to fuck around because the concept is funny to me.",
    ]);

    /** @var ProfileModerationService $moderation */
    $moderation = $app->make(ProfileModerationService::class);
    $moderation->scan($profile);

    $mildFlags = ProfileModerationFlag::query()
        ->where('user_id', $userModel->id)
        ->where('status', ProfileModerationFlag::STATUS_PENDING)
        ->count();

    if ($mildFlags !== 0) {
        throw new RuntimeException('Milde Gaming-Sprache wurde unerwartet in die Moderationsqueue aufgenommen.');
    }

    $profile->forceFill([
        'bio' => 'Buy cheap Hunt accounts now: https://example.test/shop',
    ])->save();

    $moderation->scan($profile);

    $spamFlag = ProfileModerationFlag::query()
        ->where('user_id', $userModel->id)
        ->where('field', 'bio')
        ->where('category', 'spam_advertising')
        ->where('status', ProfileModerationFlag::STATUS_PENDING)
        ->first();

    if (! $spamFlag || (int) $spamFlag->score !== 80) {
        throw new RuntimeException('Spam-/Werbeprofil wurde nicht wie erwartet markiert.');
    }

    $profile->forceFill([
        'bio' => 'Chill Hunt player looking for trios.',
    ])->save();

    $moderation->scan($profile);

    $stillPending = ProfileModerationFlag::query()
        ->where('user_id', $userModel->id)
        ->where('status', ProfileModerationFlag::STATUS_PENDING)
        ->exists();

    if ($stillPending) {
        throw new RuntimeException('Bereinigter Profiltext hat noch offene automatische Flags.');
    }

    echo "SMOKE TEST OK: Migration + Moderationslogik funktionieren isoliert.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "SMOKE TEST FEHLGESCHLAGEN: ".$e->getMessage()."\n");
    exitCode:
    $exit = 1;
} finally {
    if ($created && $serverPdo instanceof PDO) {
        if (! preg_match('/^hntrocks_admin_test_[A-Za-z0-9_]+$/', $testDatabase)) {
            fwrite(STDERR, "SICHERHEITSABBRUCH beim Cleanup: ungültiger Datenbankname.\n");
        } else {
            try {
                $serverPdo->exec("DROP DATABASE IF EXISTS `{$testDatabase}`");
                echo "Testdatenbank wieder entfernt.\n";
            } catch (Throwable $cleanupError) {
                fwrite(STDERR, "WARNUNG: Testdatenbank konnte nicht entfernt werden: ".$cleanupError->getMessage()."\n");
            }
        }
    }
}

exit($exit ?? 0);
