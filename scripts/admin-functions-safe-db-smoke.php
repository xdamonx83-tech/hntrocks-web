<?php

declare(strict_types=1);

/**
 * Isolated MySQL smoke test for the admin profile moderation worktree.
 *
 * IMPORTANT:
 * - Uses the existing MySQL database ONLY as a container.
 * - Every QA table gets a unique hard-coded-safe prefix (qa?????_).
 * - No migrate:fresh, no DROP DATABASE and no unprefixed test tables.
 * - The script aborts unless Laravel confirms the dedicated prefixed connection.
 * - Cleanup removes only tables carrying this run's unique prefix.
 */

use App\Models\ProfileModerationFlag;
use App\Models\User;
use App\Services\ProfileModerationService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$worktree = realpath(__DIR__.'/..');
$expectedWorktree = '/home/users/hunthub/admin/hntrocks-admin';
$liveRoot = '/home/users/hunthub/www/hnt.rocks';

if ($worktree !== $expectedWorktree) {
    fwrite(STDERR, "ABBRUCH: Script muss im separaten Admin-Worktree laufen.\n");
    exit(10);
}

if (! is_file($liveRoot.'/.env')) {
    fwrite(STDERR, "ABBRUCH: Live-.env nicht gefunden.\n");
    exit(11);
}

require $worktree.'/vendor/autoload.php';

$env = Dotenv\Dotenv::createArrayBacked($liveRoot)->safeLoad();

$host = (string) ($env['DB_HOST'] ?? '127.0.0.1');
$port = (string) ($env['DB_PORT'] ?? '3306');
$socket = (string) ($env['DB_SOCKET'] ?? '');
$username = (string) ($env['DB_USERNAME'] ?? '');
$password = (string) ($env['DB_PASSWORD'] ?? '');
$database = (string) ($env['DB_DATABASE'] ?? '');

if ($username === '' || $database === '') {
    fwrite(STDERR, "ABBRUCH: DB-Zugangsdaten konnten nicht sicher gelesen werden.\n");
    exit(12);
}

$prefix = 'qa'.substr(hash('sha256', (string) microtime(true).'|'.(string) getmypid()), 0, 5).'_';

if (! preg_match('/^qa[a-f0-9]{5}_$/', $prefix)) {
    fwrite(STDERR, "ABBRUCH: Unerwarteter QA-Tabellenprefix.\n");
    exit(13);
}

$runtimeEnv = [
    'APP_NAME' => 'HNT.ROCKS Admin QA',
    'APP_ENV' => 'testing',
    'APP_DEBUG' => 'false',
    'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'DB_CONNECTION' => 'mysql',
    'DB_HOST' => $host,
    'DB_PORT' => $port,
    'DB_DATABASE' => $database,
    'DB_USERNAME' => $username,
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

$qaConnectionName = 'admin_qa';
$mysqlConfig = config('database.connections.mysql');

if (! is_array($mysqlConfig)) {
    fwrite(STDERR, "ABBRUCH: MySQL-Konfiguration nicht gefunden.\n");
    exit(14);
}

$qaConfig = $mysqlConfig;
$qaConfig['database'] = $database;
$qaConfig['prefix'] = $prefix;
$qaConfig['prefix_indexes'] = true;

config([
    'database.connections.'.$qaConnectionName => $qaConfig,
    'database.default' => $qaConnectionName,
]);

DB::purge($qaConnectionName);
DB::setDefaultConnection($qaConnectionName);

$connection = DB::connection($qaConnectionName);

if ((string) $connection->getDatabaseName() !== $database) {
    fwrite(STDERR, "SICHERHEITSABBRUCH: Unerwartete MySQL-Datenbank.\n");
    exit(15);
}

if ((string) $connection->getTablePrefix() !== $prefix) {
    fwrite(STDERR, "SICHERHEITSABBRUCH: QA-Tabellenprefix ist nicht aktiv.\n");
    exit(16);
}

$schema = Schema::connection($qaConnectionName);
$baseTablesCreated = false;
$migrationApplied = false;
$migration = null;
$exit = 0;

try {
    foreach (['users', 'user_profiles', 'profile_moderation_flags', 'profile_moderation_events'] as $table) {
        if ($schema->hasTable($table)) {
            throw new RuntimeException("SICHERHEITSABBRUCH: QA-Tabelle {$prefix}{$table} existiert bereits.");
        }
    }

    echo "QA-Verbindung geprüft. Alle Testtabellen verwenden Prefix: {$prefix}\n";
    echo "Erzeuge minimale isolierte QA-Basistabellen ...\n";

    $schema->create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name', 80);
        $table->string('username', 32)->unique();
        $table->string('email', 160)->unique();
        $table->string('password');
        $table->boolean('is_admin')->default(false);
        $table->string('status', 24)->default('active');
        $table->timestamps();
    });

    $schema->create('user_profiles', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('user_id')->unique();
        $table->string('headline', 120)->nullable();
        $table->text('bio')->nullable();
        $table->string('profile_visibility', 24)->default('public');
        $table->timestamps();
    });

    $baseTablesCreated = true;

    echo "Führe ausschließlich unsere neue Profilmoderations-Migration auf QA-Tabellen aus ...\n";

    $migration = require $worktree.'/database/migrations/2026_09_27_000001_create_profile_moderation_tables.php';

    if (! $migration instanceof Illuminate\Database\Migrations\Migration) {
        throw new RuntimeException('Profilmoderations-Migration konnte nicht geladen werden.');
    }

    $migration->up();
    $migrationApplied = true;

    foreach (['profile_moderation_flags', 'profile_moderation_events'] as $table) {
        if (! $schema->hasTable($table)) {
            throw new RuntimeException("Migration hat QA-Tabelle {$table} nicht erzeugt.");
        }
    }

    echo "Migration OK. Teste konservatives Profil-Flagging ...\n";

    $qaUser = User::query()->create([
        'name' => 'Admin QA User',
        'username' => 'admin_qa_'.getmypid(),
        'email' => 'admin-qa-'.getmypid().'@example.test',
        'password' => 'temporary-qa-password',
        'status' => 'active',
    ]);

    $profile = $qaUser->profile()->create([
        'profile_visibility' => 'public',
        'bio' => "I'm mainly here to fuck around because the concept is funny to me.",
    ]);

    /** @var ProfileModerationService $moderation */
    $moderation = $app->make(ProfileModerationService::class);
    $moderation->scan($profile);

    $mildFlags = ProfileModerationFlag::query()
        ->where('user_id', $qaUser->id)
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
        ->where('user_id', $qaUser->id)
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
        ->where('user_id', $qaUser->id)
        ->where('status', ProfileModerationFlag::STATUS_PENDING)
        ->exists();

    if ($stillPending) {
        throw new RuntimeException('Bereinigter Profiltext hat noch offene automatische Flags.');
    }

    echo "Teste Rollback der Profilmoderations-Migration ...\n";
    $migration->down();
    $migrationApplied = false;

    if ($schema->hasTable('profile_moderation_flags') || $schema->hasTable('profile_moderation_events')) {
        throw new RuntimeException('Rollback hat nicht alle QA-Moderationstabellen entfernt.');
    }

    echo "SMOKE TEST OK: Migration, Rollback und Moderationslogik funktionieren isoliert.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "SMOKE TEST FEHLGESCHLAGEN: ".$e->getMessage()."\n");
    $exit = 1;
} finally {
    // Only this run's prefixed connection is used for cleanup.
    // No unprefixed production table name is ever dropped.
    try {
        if ($migrationApplied && $migration instanceof Illuminate\Database\Migrations\Migration) {
            $migration->down();
        }
    } catch (Throwable $cleanupError) {
        fwrite(STDERR, "WARNUNG beim Migration-Cleanup: ".$cleanupError->getMessage()."\n");
    }

    foreach (['profile_moderation_events', 'profile_moderation_flags', 'user_profiles', 'users'] as $table) {
        try {
            $schema->dropIfExists($table);
        } catch (Throwable $cleanupError) {
            fwrite(STDERR, "WARNUNG beim Tabellen-Cleanup {$prefix}{$table}: ".$cleanupError->getMessage()."\n");
        }
    }

    echo "QA-Tabellen dieses Laufs wurden entfernt.\n";
}

exit($exit);
