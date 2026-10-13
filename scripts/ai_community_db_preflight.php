<?php

/**
 * READ-ONLY production database/schema readiness check.
 * Intentionally loads the existing Laravel console bootstrap to resolve DB
 * credentials without ever printing .env values. Never migrates/seeds/writes.
 * Run with production PHP from the authorized server root terminal.
 */
declare(strict_types=1);

$production = '/home/users/hunthub/www/hnt.rocks';
$migration = '2026_10_12_000001_create_ai_community_tables';
if (!is_file($production.'/vendor/autoload.php') || !is_file($production.'/bootstrap/app.php')) {
    fwrite(STDERR, "STOP: production Laravel bootstrap not available.\n");
    exit(2);
}

try {
    chdir($production);
    require_once $production.'/vendor/autoload.php';
    $app = require $production.'/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $schema = $app->make('db')->connection()->getSchemaBuilder();
    $db = $app->make('db')->connection();

    if (!$schema->hasTable('users') || !$schema->hasTable('feed_posts') || !$schema->hasTable('migrations')) {
        throw new RuntimeException('Core schema tables missing.');
    }
    $columns = [
        'users.is_ai_character' => $schema->hasColumn('users', 'is_ai_character'),
        'feed_posts.ai_user_declared' => $schema->hasColumn('feed_posts', 'ai_user_declared'),
        'feed_posts.admin_confirmed_ai' => $schema->hasColumn('feed_posts', 'admin_confirmed_ai'),
        'ai_community_characters.table' => $schema->hasTable('ai_community_characters'),
        'ai_community_drafts.table' => $schema->hasTable('ai_community_drafts'),
    ];
    $migrationRegistered = $db->table('migrations')->where('migration', $migration)->exists();

    echo "HNT_AI_COMMUNITY_PRODUCTION_DB_PREFLIGHT_READ_ONLY\n";
    echo "MIGRATION_RECORDED ", ($migrationRegistered ? 'yes' : 'no'), "\n";
    foreach ($columns as $key => $exists) {
        echo $key, " ", ($exists ? 'present' : 'missing'), "\n";
    }

    $communityParts = $columns['users.is_ai_character']
        || $columns['ai_community_characters.table']
        || $columns['ai_community_drafts.table'];

    if ($migrationRegistered) {
        if (!$columns['users.is_ai_character'] || !$columns['ai_community_characters.table']
            || !$columns['ai_community_drafts.table']) {
            echo "DECISION STOP_INCOMPLETE_EXISTING_MIGRATION\n";
        } else {
            echo "DECISION STOP_MIGRATION_ALREADY_RECORDED__INVESTIGATE_BEFORE_RELEASE\n";
        }
        exit(3);
    }

    if ($communityParts) {
        echo "DECISION STOP_PARTIAL_COMMUNITY_SCHEMA_COLLISION\n";
        exit(3);
    }
    if (!$columns['feed_posts.ai_user_declared'] || !$columns['feed_posts.admin_confirmed_ai']) {
        echo "DECISION STOP_REQUIRED_FEED_DISCLOSURE_COLUMNS_MISSING\n";
        exit(3);
    }
    echo "DECISION SCHEMA_PREREQUISITES_OK__DATABASE_BACKUP_STILL_REQUIRED\n";
    echo "NO_MIGRATION_NO_SCHEMA_CHANGES_NO_FEED_POSTS\n";
} catch (Throwable $e) {
    // Never log credentials, DB DSN, or raw exception messages.
    fwrite(STDERR, "STOP: Laravel DB readiness check failed (".get_class($e).").\n");
    exit(2);
}
