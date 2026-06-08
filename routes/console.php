<?php

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

Artisan::command('hunthub:status', function (): void {
    $this->info('hnt.rocks Laravel foundation is ready.');
})->purpose('Show hnt.rocks foundation status');

Artisan::command('hunthub:health', function (): int {
    $ok = true;

    $this->info('hnt.rocks Health Check');
    $this->line('--------------------');

    $appUrl = (string) config('app.url');
    $publicDiskUrl = (string) config('filesystems.disks.public.url');
    $publicStoragePath = public_path('storage');
    $publicStorageRealPath = realpath($publicStoragePath);
    $storagePublicRealPath = realpath(storage_path('app/public'));

    $checks = [
        'APP_KEY gesetzt' => filled(config('app.key')),
        'APP_URL gesetzt' => filled($appUrl),
        'APP_URL zeigt auf hnt.rocks' => str_contains($appUrl, 'hnt.rocks'),
        'Public-Disk-URL ohne alte Hunthub-Domain' => ! str_contains($publicDiskUrl, 'hunthub.online'),
        'storage/ beschreibbar' => is_writable(storage_path()),
        'storage/logs/ beschreibbar' => is_writable(storage_path('logs')),
        'storage/framework/views/ beschreibbar' => is_writable(storage_path('framework/views')),
        'bootstrap/cache/ beschreibbar' => is_writable(base_path('bootstrap/cache')),
        'public/storage vorhanden' => file_exists($publicStoragePath) || is_link($publicStoragePath),
        'public/storage zeigt auf storage/app/public' => $publicStorageRealPath && $storagePublicRealPath && $publicStorageRealPath === $storagePublicRealPath,
    ];

    foreach ($checks as $label => $passed) {
        $passed ? $this->info('[OK] '.$label) : $this->error('[FEHLT] '.$label);
        $ok = $ok && $passed;
    }

    try {
        DB::connection()->getPdo();
        $this->info('[OK] Datenbankverbindung');
    } catch (Throwable $exception) {
        $ok = false;
        $this->error('[FEHLT] Datenbankverbindung: '.$exception->getMessage());
    }

    foreach (['users', 'user_profiles', 'feed_posts', 'feed_comments', 'mentions', 'teams', 'lfg_posts', 'team_lfg_posts', 'moments', 'cups', 'media_assets'] as $table) {
        try {
            $exists = Schema::hasTable($table);
            $exists ? $this->info('[OK] Tabelle '.$table) : $this->warn('[OFFEN] Tabelle '.$table.' fehlt oder Migration wurde noch nicht ausgeführt');
        } catch (Throwable $exception) {
            $ok = false;
            $this->error('[FEHLT] Tabellencheck nicht möglich: '.$exception->getMessage());
            break;
        }
    }

    return $ok ? Command::SUCCESS : Command::FAILURE;
})->purpose('Run a compact hnt.rocks deployment and database health check');

Artisan::command('hunthub:audit-routes', function (): int {
    $requiredRoutes = [
        'home', 'login', 'login.store', 'register', 'register.store',
        'password.request', 'password.email', 'password.reset', 'password.store',
        'legal.impressum', 'legal.datenschutz', 'legal.nutzungsbedingungen', 'legal.netiquette',
        'feed.index', 'profile.show', 'profile.edit', 'members.index',
        'teams.index', 'lfg.index', 'team-lfg.index', 'messages.index', 'notifications.index',
        'media.index', 'moments.index', 'cups.index', 'cups.show', 'hall-of-fame.index',
        'referrals.index', 'gamification.index', 'reports.store',
        'settings.privacy.edit', 'settings.security.index', 'admin.index', 'admin.reports.index',
        'api.health', 'api.v1.bootstrap', 'api.v1.feed.show', 'api.v1.feed.store',
        'api.v1.cups.show', 'api.v1.cups.register', 'api.v1.cups.submissions.store', 'api.v1.notifications.read', 'api.v1.notifications.read-all',
    ];

    $missing = [];
    foreach ($requiredRoutes as $route) {
        if (! Route::has($route)) {
            $missing[] = $route;
        }
    }

    if ($missing === []) {
        $this->info('Alle wichtigen hnt.rocks-Routen sind registriert.');
        return Command::SUCCESS;
    }

    foreach ($missing as $route) {
        $this->error('Fehlende Route: '.$route);
    }

    return Command::FAILURE;
})->purpose('Check the most important hnt.rocks route names');

Artisan::command('hnt:prelaunch-check', function (): int {
    $failed = 0;
    $warnings = 0;

    $ok = function (string $label): void {
        $this->info('[OK] '.$label);
    };

    $fail = function (string $label, ?string $detail = null) use (&$failed): void {
        $failed++;
        $this->error('[BLOCKER] '.$label.($detail ? ' · '.$detail : ''));
    };

    $warn = function (string $label, ?string $detail = null) use (&$warnings): void {
        $warnings++;
        $this->warn('[WARNUNG] '.$label.($detail ? ' · '.$detail : ''));
    };

    $check = function (string $label, bool $passed, ?string $detail = null) use ($ok, $fail): void {
        $passed ? $ok($label) : $fail($label, $detail);
    };

    $this->info('hnt.rocks Pre-Launch Check');
    $this->line('--------------------------');

    $appUrl = rtrim((string) config('app.url'), '/');
    $check('APP_ENV ist production', app()->environment('production'), 'Aktuell: '.app()->environment());
    $check('APP_DEBUG ist false', ! (bool) config('app.debug'), 'APP_DEBUG darf vor Launch nicht aktiv sein.');
    $check('APP_URL zeigt auf https://hnt.rocks', $appUrl === 'https://hnt.rocks', 'Aktuell: '.$appUrl);
    $check('Session-Cookie ist secure', (bool) config('session.secure'), 'SESSION_SECURE_COOKIE sollte true sein.');
    $check('Session-Cookie ist httpOnly', (bool) config('session.http_only'), 'httpOnly sollte true sein.');
    $check('Session SameSite ist lax oder strict', in_array((string) config('session.same_site'), ['lax', 'strict'], true), 'Aktuell: '.(string) config('session.same_site'));
    $check('Visitor Tracking verlangt Consent', (bool) config('hunthub.visitor_tracking.require_consent'), 'HH_VISITOR_TRACKING_REQUIRE_CONSENT sollte true sein.');
    $check('Cup-Screenshots liegen nicht auf public Disk', (string) config('hunthub.cups.submission_screenshot_disk') !== 'public', 'Empfohlen: HH_CUP_SUBMISSION_SCREENSHOT_DISK=local');

    $mailer = (string) config('mail.default');
    $mailHost = (string) config('mail.mailers.smtp.host');
    $check('Mail nutzt nicht sendmail', $mailer !== 'sendmail', 'sendmail braucht proc_open und ist auf dem Server deaktiviert.');
    if ($mailer === 'smtp' && ! in_array($mailHost, ['127.0.0.1', 'localhost'], true)) {
        $warn('SMTP-Host ist nicht lokal', 'Aktuell: '.$mailHost.' · Funktioniert nur, wenn Zertifikat/Transport bewusst so gesetzt sind.');
    } else {
        $ok('Mail-Transport wirkt launch-kompatibel');
    }

    if (((bool) config('hunthub.feed_video_transcoding.enabled', true) || (bool) config('hunthub.moment_video_transcoding.enabled', true)) && (string) config('queue.default') === 'sync') {
        $warn('Video-Transcoding läuft aktuell nicht asynchron', 'QUEUE_CONNECTION=database oder redis setzen und Queue-Worker für Feed-/Moments-Videos starten.');
    } else {
        $ok('Video-Transcoding wirkt queue-kompatibel');
    }

    $requiredRoutes = [
        'home', 'login', 'register', 'password.request', 'password.reset',
        'legal.impressum', 'legal.datenschutz', 'legal.nutzungsbedingungen', 'legal.netiquette',
        'cups.index', 'cups.show', 'hall-of-fame.index', 'feed.index', 'reports.store',
        'admin.index', 'admin.reports.index', 'api.health',
    ];

    foreach ($requiredRoutes as $routeName) {
        Route::has($routeName)
            ? $ok('Route registriert: '.$routeName)
            : $fail('Route fehlt: '.$routeName);
    }

    $externalNeedles = [
        'fonts.googleapis.com',
        'fonts.gstatic.com',
        'cdn.jsdelivr.net/npm/@phosphor-icons/web',
        'social.hunthub.online',
    ];
    $scanFiles = [
        resource_path('views/partials/head.blade.php'),
        resource_path('views/partials/vikinger/head.blade.php'),
        public_path('assets/vikinger/css/styles.min.css'),
        public_path('assets/vikinger/css/hnt-local-fonts.css'),
        public_path('assets/vikinger/fonts/phosphor/regular/style.css'),
    ];

    foreach ($externalNeedles as $needle) {
        $foundIn = [];
        foreach ($scanFiles as $file) {
            if (is_file($file) && str_contains((string) file_get_contents($file), $needle)) {
                $foundIn[] = str_replace(base_path().'/', '', $file);
            }
        }

        $foundIn === []
            ? $ok('Keine Launch-Altlast gefunden: '.$needle)
            : $fail('Launch-Altlast gefunden: '.$needle, implode(', ', $foundIn));
    }

    $de = include resource_path('lang/de/ui.php');
    $en = include resource_path('lang/en/ui.php');
    $deKeys = array_keys(is_array($de) ? $de : []);
    $enKeys = array_keys(is_array($en) ? $en : []);
    sort($deKeys);
    sort($enKeys);

    $check('DE/EN Sprachdateien haben gleiche Keys', $deKeys === $enKeys, 'Unterschiede bitte vor Launch prüfen.');

    foreach (['login_title', 'legal_datenschutz', 'cookie_settings', 'cup_leaderboard_preliminary_title', 'report_cup', 'report_moment'] as $key) {
        isset($de[$key], $en[$key])
            ? $ok('Sprachkey vorhanden: ui.'.$key)
            : $fail('Sprachkey fehlt: ui.'.$key);
    }

    if ($warnings > 0) {
        $this->warn('Warnungen: '.$warnings.' · prüfen, aber nicht zwingend Blocker.');
    }

    if ($failed > 0) {
        $this->error('Pre-Launch Check fehlgeschlagen. Blocker: '.$failed);
        return Command::FAILURE;
    }

    $this->info('Pre-Launch Check bestanden. Hinweis: Das ersetzt keinen Browser-, DB- und Rechtscheck.');
    return Command::SUCCESS;
})->purpose('Run hnt.rocks pre-launch checks for config, routes, local assets and language keys');

Artisan::command('hnt:feed-video-transcode-pending {--limit=20} {--sync}', function (): int {
    $limit = max(1, min(100, (int) $this->option('limit')));

    $assets = \App\Models\MediaAsset::query()
        ->whereIn('context', ['feed', 'team_feed', 'moments'])
        ->where('type', 'video')
        ->where(function ($query): void {
            $query->where('status', 'processing')
                ->orWhere(function ($feedQuery): void {
                    $feedQuery->whereIn('context', ['feed', 'team_feed'])
                        ->where(function ($transcodingQuery): void {
                            $transcodingQuery->whereNull('metadata->feed_transcoding->status')
                                ->orWhere('metadata->feed_transcoding->status', 'queued')
                                ->orWhere('metadata->feed_transcoding->status', 'failed');
                        });
                })
                ->orWhere(function ($momentQuery): void {
                    $momentQuery->where('context', 'moments')
                        ->where(function ($transcodingQuery): void {
                            $transcodingQuery->whereNull('metadata->moment_transcoding->status')
                                ->orWhere('metadata->moment_transcoding->status', 'queued')
                                ->orWhere('metadata->moment_transcoding->status', 'failed');
                        });
                });
        })
        ->latest()
        ->limit($limit)
        ->get();

    if ($assets->isEmpty()) {
        $this->info('Keine Feed-/Team-/Moments-Videos zum Transcodieren gefunden.');
        return Command::SUCCESS;
    }

    foreach ($assets as $asset) {
        if ((bool) $this->option('sync')) {
            $this->line('Transcodiere MediaAsset #'.$asset->id.' synchron ['.$asset->context.'] ...');
            app(\App\Jobs\TranscodeFeedVideo::class, ['mediaAssetId' => (int) $asset->id])->handle();
            continue;
        }

        \App\Jobs\TranscodeFeedVideo::dispatch((int) $asset->id);
        $this->line('Job für MediaAsset #'.$asset->id.' ['.$asset->context.'] dispatcht.');
    }

    return Command::SUCCESS;
})->purpose('Dispatch or run pending feed, team feed and moments video transcoding jobs');

Artisan::command('hunthub:import-old-users {users_sql} {social_sql?} {--dry-run} {--include-inactive}', function (): int {
    return app(\App\Support\OldHunthubUserImporter::class)->run(
        command: $this,
        usersSqlPath: (string) $this->argument('users_sql'),
        socialSqlPath: $this->argument('social_sql') ? (string) $this->argument('social_sql') : null,
        dryRun: (bool) $this->option('dry-run'),
        includeInactive: (bool) $this->option('include-inactive'),
    );
})->purpose('Import legacy Hunthub users, referral codes and social account links from SQL dumps');


Artisan::command('hunthub:wipe-test-content {--force : Wirklich löschen} {--dry-run : Nur zählen, nichts löschen} {--keep-storage-files : Dateien unter storage/app/public behalten} {--keep-messages : Nachrichten/Conversations behalten} {--keep-notifications : Benachrichtigungen behalten} {--keep-gamification : XP, Badge-Zuweisungen, Quest-Fortschritt und User-Stats behalten} {--with-visitors : Visitor-Tracking ebenfalls leeren} {--with-giveaways : Giveaways selbst zusätzlich löschen} {--with-referrals : Referral-Signups leeren und Referral-Zähler zurücksetzen}', function (): int {
    $dryRun = (bool) $this->option('dry-run');
    $force = (bool) $this->option('force');

    $baseTables = [
        'feed_comment_reactions',
        'mentions',
        'feed_bookmarks',
        'feed_reactions',
        'feed_comments',
        'feed_post_media',
        'feed_posts',
        'lfg_applications',
        'lfg_posts',
        'team_lfg_applications',
        'team_lfg_posts',
        'team_members',
        'teams',
        'moment_bookmarks',
        'moment_reactions',
        'moment_comments',
        'moments',
        'cup_submissions',
        'cup_team_members',
        'cup_teams',
        'cups',
        'reports',
        'giveaway_entries',
    ];

    $messageTables = [
        'messages',
        'conversation_participants',
        'conversations',
    ];

    $notificationTables = [
        'user_notifications',
        'notifications',
    ];

    $gamificationTables = [
        'xp_events',
        'badge_user',
        'quest_user',
    ];

    $optionalTables = [];

    if (! (bool) $this->option('keep-messages')) {
        $optionalTables = array_merge($optionalTables, $messageTables);
    }

    if (! (bool) $this->option('keep-notifications')) {
        $optionalTables = array_merge($optionalTables, $notificationTables);
    }

    if (! (bool) $this->option('keep-gamification')) {
        $optionalTables = array_merge($optionalTables, $gamificationTables);
    }

    if ((bool) $this->option('with-visitors')) {
        $optionalTables[] = 'visitor_events';
    }

    if ((bool) $this->option('with-giveaways')) {
        $optionalTables[] = 'giveaways';
    }

    if ((bool) $this->option('with-referrals')) {
        $optionalTables[] = 'referral_signups';
    }

    $tables = collect(array_merge($baseTables, $optionalTables))
        ->unique()
        ->filter(fn (string $table): bool => Schema::hasTable($table))
        ->values()
        ->all();

    $mediaContexts = [
        'feed',
        'team_feed',
        'team_avatar',
        'team_cover',
        'moments',
        'moments_cover',
        'cups/covers',
        'cups/screenshots',
    ];

    if (! (bool) $this->option('keep-messages')) {
        $mediaContexts[] = 'messages';
    }

    $this->info('hnt.rocks Testinhalt-Cleanup');
    $this->line('-----------------------------');
    $this->line('User, Profile, Social-Logins, Sessions, Datenschutz-/Security-Settings, Sidebar-/Mobile-Nav-Konfiguration und Referral-Links bleiben erhalten.');

    $counts = [];
    foreach ($tables as $table) {
        try {
            $counts[$table] = DB::table($table)->count();
        } catch (Throwable $exception) {
            $counts[$table] = 'Fehler: '.$exception->getMessage();
        }
    }

    $mediaAssetsQuery = Schema::hasTable('media_assets')
        ? DB::table('media_assets')->whereIn('context', $mediaContexts)
        : null;

    $mediaAssetsCount = $mediaAssetsQuery ? (int) $mediaAssetsQuery->count() : 0;

    foreach ($counts as $table => $count) {
        $this->line(str_pad($table, 32).$count);
    }
    $this->line(str_pad('media_assets '.implode(',', $mediaContexts), 32).$mediaAssetsCount);

    if ($dryRun) {
        $this->warn('Dry-Run: Es wurde nichts gelöscht.');
        return Command::SUCCESS;
    }

    if (! $force) {
        $this->error('Abbruch: Zum echten Löschen ist --force erforderlich. Für Vorschau zuerst --dry-run nutzen.');
        $this->line('Beispiel: php artisan hunthub:wipe-test-content --dry-run');
        $this->line('Löschen:  php artisan hunthub:wipe-test-content --force');
        return Command::FAILURE;
    }

    if (! (bool) $this->option('keep-storage-files') && Schema::hasTable('media_assets')) {
        DB::table('media_assets')
            ->whereIn('context', $mediaContexts)
            ->orderBy('id')
            ->chunkById(100, function ($assets): void {
                foreach ($assets as $asset) {
                    $disk = filled($asset->disk ?? null) ? (string) $asset->disk : 'public';
                    $paths = array_values(array_filter([
                        $asset->path ?? null,
                        $asset->thumbnail_path ?? null,
                    ]));

                    if ($paths === []) {
                        continue;
                    }

                    try {
                        Storage::disk($disk)->delete($paths);
                    } catch (Throwable $exception) {
                        $this->warn('Datei konnte nicht gelöscht werden: '.$exception->getMessage());
                    }
                }
            });
    }

    Schema::disableForeignKeyConstraints();

    try {
        foreach ($tables as $table) {
            DB::table($table)->truncate();
            $this->info('[OK] '.$table.' geleert');
        }

        if (Schema::hasTable('media_assets')) {
            DB::table('media_assets')->whereIn('context', $mediaContexts)->delete();
            $this->info('[OK] media_assets für Content-Kontexte gelöscht');
        }

        if (! (bool) $this->option('keep-gamification') && Schema::hasTable('users')) {
            $reset = [];

            foreach ([
                'xp_total' => 0,
                'level' => 1,
                'trust_score' => 0,
                'last_xp_at' => null,
            ] as $column => $value) {
                if (Schema::hasColumn('users', $column)) {
                    $reset[$column] = $value;
                }
            }

            if ($reset !== []) {
                DB::table('users')->update($reset);
                $this->info('[OK] User-Gamification-Stats zurückgesetzt');
            }
        }

        if ((bool) $this->option('with-referrals') && Schema::hasTable('referral_links')) {
            $referralReset = [];
            foreach (['clicks_count', 'signups_count', 'completed_profiles_count'] as $column) {
                if (Schema::hasColumn('referral_links', $column)) {
                    $referralReset[$column] = 0;
                }
            }
            if (Schema::hasColumn('referral_links', 'last_clicked_at')) {
                $referralReset['last_clicked_at'] = null;
            }
            if ($referralReset !== []) {
                DB::table('referral_links')->update($referralReset);
                $this->info('[OK] Referral-Zähler zurückgesetzt');
            }
        }
    } finally {
        Schema::enableForeignKeyConstraints();
    }

    $this->info('Fertig. User und Account-Verknüpfungen wurden nicht gelöscht.');

    return Command::SUCCESS;
})->purpose('Delete test content while keeping users and login/account data');

Artisan::command('hnt:process-account-deletions {--force : Wirklich löschen} {--dry-run : Nur prüfen, nichts löschen} {--user= : Optional nur eine User-ID verarbeiten} {--limit=25 : Maximale Anzahl pro Lauf} {--include-future : Auch noch nicht fällige Löschvormerkungen einbeziehen}', function (): int {
    $dryRun = (bool) $this->option('dry-run');
    $force = (bool) $this->option('force');
    $limit = max(1, (int) $this->option('limit'));
    $userId = $this->option('user') !== null ? max(1, (int) $this->option('user')) : null;
    $includeFuture = (bool) $this->option('include-future');

    if (! $dryRun && ! $force) {
        $this->error('Abbruch: Für echte Kontolöschung ist --force erforderlich. Für Vorschau zuerst --dry-run nutzen.');
        $this->line('Vorschau: php artisan hnt:process-account-deletions --dry-run');
        $this->line('Löschen:  php artisan hnt:process-account-deletions --force');

        return Command::FAILURE;
    }

    if (! Schema::hasTable('account_deletion_requests')) {
        $this->error('Tabelle account_deletion_requests fehlt. Migrationen prüfen.');

        return Command::FAILURE;
    }

    $query = \App\Models\AccountDeletionRequest::query()
        ->with('user')
        ->where('status', 'pending')
        ->orderBy('scheduled_for')
        ->orderBy('id')
        ->limit($limit);

    if (! $includeFuture) {
        $query->whereNotNull('scheduled_for')->where('scheduled_for', '<=', now());
    }

    if ($userId) {
        $query->where('user_id', $userId);
    }

    $requests = $query->get();

    if ($requests->isEmpty()) {
        $this->info('Keine fälligen Kontolöschungen gefunden.');

        return Command::SUCCESS;
    }

    $service = app(\App\Services\AccountDeletionService::class);
    $processed = 0;
    $skipped = 0;
    $failed = 0;

    $this->info($dryRun ? 'Dry-Run: Kontolöschungen werden nur geprüft.' : 'Kontolöschungen werden verarbeitet.');
    $this->line('------------------------------------------------------------');

    foreach ($requests as $deletionRequest) {
        try {
            $result = $service->process($deletionRequest, $dryRun);

            if (isset($result['skipped'])) {
                $skipped++;
                $this->warn('[ÜBERSPRUNGEN] User #'.$result['user_id'].' '.$result['username'].' · Grund: '.$result['skipped']);
                continue;
            }

            $processed++;
            $rowCount = array_sum($result['counts']);
            $this->info('[OK] User #'.$result['user_id'].' '.$result['username'].' · DB-Zeilen: '.$rowCount.' · Dateien: '.$result['files'].' · gelöscht: '.$result['deleted_files'].' · fehlgeschlagen: '.$result['failed_files']);
        } catch (Throwable $exception) {
            $failed++;
            $this->error('[FEHLER] Löschung #'.$deletionRequest->id.' konnte nicht verarbeitet werden: '.$exception->getMessage());
        }
    }

    $this->line('------------------------------------------------------------');
    $this->line('Verarbeitet: '.$processed.' · Übersprungen: '.$skipped.' · Fehler: '.$failed);

    return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
})->purpose('Process pending hnt.rocks account deletion requests after the safety period');

Artisan::command('hnt:migrate-cup-screenshots-private {--force : Wirklich kopieren und Datenbank aktualisieren} {--dry-run : Nur prüfen, nichts ändern} {--delete-source : Quelldatei nach erfolgreicher Migration vom alten Disk löschen} {--limit=250 : Maximale Anzahl pro Lauf}', function (): int {
    $dryRun = (bool) $this->option('dry-run');
    $force = (bool) $this->option('force');
    $deleteSource = (bool) $this->option('delete-source');
    $limit = max(1, (int) $this->option('limit'));
    $targetDisk = (string) config('hunthub.cups.submission_screenshot_disk', 'local');

    if (! Schema::hasTable('cup_submissions') || ! Schema::hasTable('media_assets')) {
        $this->error('Benötigte Tabellen fehlen: cup_submissions und/oder media_assets.');
        return Command::FAILURE;
    }

    if (! $dryRun && ! $force) {
        $this->error('Abbruch: Für echte Migration ist --force erforderlich. Für Vorschau zuerst --dry-run nutzen.');
        $this->line('Vorschau: php artisan hnt:migrate-cup-screenshots-private --dry-run');
        $this->line('Echt:     php artisan hnt:migrate-cup-screenshots-private --force --delete-source');
        return Command::FAILURE;
    }

    if ($targetDisk === 'public') {
        $this->warn('HH_CUP_SUBMISSION_SCREENSHOT_DISK steht auf public. Das macht Screenshots nicht privat. Empfohlen: local.');
    }

    $rows = DB::table('cup_submissions')
        ->join('media_assets', 'media_assets.id', '=', 'cup_submissions.screenshot_media_asset_id')
        ->where('media_assets.context', 'cups/screenshots')
        ->where('media_assets.visibility', 'private')
        ->where(function ($query) use ($targetDisk): void {
            $query->whereNull('media_assets.disk')
                ->orWhere('media_assets.disk', '')
                ->orWhere('media_assets.disk', '!=', $targetDisk);
        })
        ->orderBy('media_assets.id')
        ->limit($limit)
        ->get([
            'media_assets.id as media_asset_id',
            'media_assets.disk',
            'media_assets.path',
            'media_assets.thumbnail_path',
            'cup_submissions.id as submission_id',
        ]);

    if ($rows->isEmpty()) {
        $this->info('Keine Cup-Screenshot-Assets gefunden, die migriert werden müssen.');
        return Command::SUCCESS;
    }

    $this->info($dryRun ? 'Dry-Run: Cup-Screenshots werden nur geprüft.' : 'Cup-Screenshots werden migriert.');
    $this->line('Ziel-Disk: '.$targetDisk.' · Anzahl: '.$rows->count());
    $this->line('------------------------------------------------------------');

    $migrated = 0;
    $skipped = 0;
    $failed = 0;

    foreach ($rows as $row) {
        $sourceDisk = filled($row->disk ?? null) ? (string) $row->disk : 'public';
        $path = ltrim((string) ($row->path ?? ''), '/');
        $thumbnailPath = ltrim((string) ($row->thumbnail_path ?? ''), '/');

        if ($path === '') {
            $skipped++;
            $this->warn('[ÜBERSPRUNGEN] MediaAsset #'.$row->media_asset_id.' hat keinen Pfad.');
            continue;
        }

        if ($sourceDisk === $targetDisk) {
            $skipped++;
            $this->line('[OK] MediaAsset #'.$row->media_asset_id.' ist bereits auf '.$targetDisk.'.');
            continue;
        }

        $sourceExists = Storage::disk($sourceDisk)->exists($path);
        $targetExists = Storage::disk($targetDisk)->exists($path);

        if (! $sourceExists && ! $targetExists) {
            $failed++;
            $this->error('[FEHLT] MediaAsset #'.$row->media_asset_id.' · '.$sourceDisk.':'.$path.' und '.$targetDisk.':'.$path.' fehlen.');
            continue;
        }

        if ($dryRun) {
            $this->line('[DRY] MediaAsset #'.$row->media_asset_id.' · Submission #'.$row->submission_id.' · '.$sourceDisk.':'.$path.' -> '.$targetDisk.':'.$path.($sourceExists ? '' : ' · Quelle fehlt, Ziel existiert bereits'));
            continue;
        }

        try {
            if (! $targetExists) {
                Storage::disk($targetDisk)->put($path, Storage::disk($sourceDisk)->get($path));
            }

            if ($thumbnailPath !== '' && ! Storage::disk($targetDisk)->exists($thumbnailPath) && Storage::disk($sourceDisk)->exists($thumbnailPath)) {
                Storage::disk($targetDisk)->put($thumbnailPath, Storage::disk($sourceDisk)->get($thumbnailPath));
            }

            DB::table('media_assets')->where('id', $row->media_asset_id)->update([
                'disk' => $targetDisk,
                'visibility' => 'private',
                'updated_at' => now(),
            ]);

            if ($deleteSource && $sourceExists) {
                Storage::disk($sourceDisk)->delete($path);

                if ($thumbnailPath !== '' && Storage::disk($sourceDisk)->exists($thumbnailPath)) {
                    Storage::disk($sourceDisk)->delete($thumbnailPath);
                }
            }

            $migrated++;
            $this->info('[OK] MediaAsset #'.$row->media_asset_id.' migriert.');
        } catch (Throwable $exception) {
            $failed++;
            $this->error('[FEHLER] MediaAsset #'.$row->media_asset_id.' konnte nicht migriert werden: '.$exception->getMessage());
        }
    }

    $this->line('------------------------------------------------------------');
    $this->line('Migriert: '.$migrated.' · Übersprungen: '.$skipped.' · Fehler: '.$failed);

    return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
})->purpose('Move existing Cup submission screenshots from public storage to the private local disk');

Artisan::command('hnt:moment-studio-cleanup {--hours=24 : Ab welchem Alter abgebrochene Studio-Projekte bereinigt werden} {--limit=100 : Maximale Anzahl Projekte pro Lauf} {--dry-run : Nur anzeigen, nichts löschen}', function (): int {
    $hours = max(1, (int) $this->option('hours'));
    $limit = max(1, min(500, (int) $this->option('limit')));
    $dryRun = (bool) $this->option('dry-run');
    $cutoff = now()->subHours($hours);

    $this->info('HNT Moment Studio Cleanup');
    $this->line('-------------------------');
    $this->line('Alter: '.$hours.'h · Limit: '.$limit.($dryRun ? ' · DRY RUN' : ''));

    if (! \Illuminate\Support\Facades\Schema::hasTable('moment_studio_projects') || ! \Illuminate\Support\Facades\Schema::hasTable('media_assets')) {
        $this->warn('Benötigte Tabellen fehlen. Cleanup übersprungen.');
        return Command::SUCCESS;
    }

    $projects = \App\Models\MomentStudioProject::query()
        ->where(function ($query) use ($cutoff): void {
            $query->where(function ($stale) use ($cutoff): void {
                $stale->whereIn('status', ['uploading', 'queued', 'rendering', 'failed'])
                    ->where(function ($nested) use ($cutoff): void {
                        $nested->where('expires_at', '<=', now())
                            ->orWhere('updated_at', '<=', $cutoff)
                            ->orWhere(function ($failed) use ($cutoff): void {
                                $failed->where('status', 'failed')
                                    ->whereNotNull('finished_at')
                                    ->where('finished_at', '<=', $cutoff);
                            });
                    });
            })->orWhere(function ($published) use ($cutoff): void {
                $published->where('status', 'published')
                    ->whereNotNull('finished_at')
                    ->where('finished_at', '<=', $cutoff)
                    ->whereNotNull('source_media_asset_ids');
            });
        })
        ->oldest('updated_at')
        ->limit($limit)
        ->get();

    if ($projects->isEmpty()) {
        $this->info('Keine alten Studio-Projekte zum Bereinigen gefunden.');
        return Command::SUCCESS;
    }

    $deletedFiles = 0;
    $deletedAssets = 0;
    $cleanedProjects = 0;
    $expiredProjects = 0;

    foreach ($projects as $project) {
        $sourceIds = array_values(array_filter(array_map('intval', (array) $project->source_media_asset_ids)));
        $outputId = (int) ($project->output_media_asset_id ?? 0);
        $sourceIds = array_values(array_filter($sourceIds, static fn (int $id): bool => $id > 0 && $id !== $outputId));

        $this->line('Projekt #'.$project->id.' · Status: '.$project->status.' · Quellen: '.count($sourceIds));

        if ($sourceIds !== []) {
            $assets = \App\Models\MediaAsset::withTrashed()->whereIn('id', $sourceIds)->get();

            foreach ($assets as $asset) {
                $paths = array_values(array_filter([$asset->path, $asset->thumbnail_path]));

                foreach ($paths as $path) {
                    if ($dryRun) {
                        $this->line('  würde Datei löschen: '.$asset->disk.':'.$path);
                        continue;
                    }

                    try {
                        if (\Illuminate\Support\Facades\Storage::disk($asset->disk)->exists($path)) {
                            \Illuminate\Support\Facades\Storage::disk($asset->disk)->delete($path);
                            $deletedFiles++;
                        }
                    } catch (\Throwable $exception) {
                        $this->warn('  Datei konnte nicht gelöscht werden: '.$path.' · '.$exception->getMessage());
                    }
                }

                if (! $dryRun && ! $asset->trashed()) {
                    $asset->update(['status' => 'deleted']);
                    $asset->delete();
                    $deletedAssets++;
                }
            }
        }

        if ($dryRun) {
            continue;
        }

        if (in_array($project->status, ['uploading', 'queued', 'rendering', 'failed'], true)) {
            $project->update([
                'status' => 'expired',
                'source_media_asset_ids' => [],
                'error_message' => $project->error_message ?: 'Studio-Projekt wurde automatisch bereinigt.',
                'finished_at' => $project->finished_at ?: now(),
                'expires_at' => now(),
            ]);
            $project->delete();
            $expiredProjects++;
        } else {
            $project->update(['source_media_asset_ids' => []]);
            $cleanedProjects++;
        }
    }

    $this->info('Bereinigung abgeschlossen.');
    $this->line('Dateien gelöscht: '.$deletedFiles);
    $this->line('MediaAssets gelöscht: '.$deletedAssets);
    $this->line('Veröffentlichte Projekte bereinigt: '.$cleanedProjects);
    $this->line('Abgelaufene Projekte entfernt: '.$expiredProjects);

    return Command::SUCCESS;
})->purpose('Clean expired Moment Studio source clips and abandoned render projects');
Artisan::command('hnt:hunt-news:sync {--limit=16 : Maximale Anzahl geprüfter News} {--no-auto : Nur entdecken/aktualisieren, nicht automatisch posten}', function (\App\Services\HuntNews\OfficialHuntNewsImporter $importer): int {
    $limit = max(1, min(40, (int) $this->option('limit')));
    $auto = ! (bool) $this->option('no-auto');

    try {
        $stats = $importer->sync(autoPublish: $auto, limit: $limit);
    } catch (Throwable $exception) {
        $this->error('Hunt-News-Abgleich fehlgeschlagen: '.$exception->getMessage());
        return Command::FAILURE;
    }

    $this->info('Hunt-News-Abgleich abgeschlossen.');
    $this->line('Gesehen: '.$stats['seen']);
    $this->line('Neu: '.$stats['created']);
    $this->line('Aktualisiert: '.$stats['updated']);
    $this->line('Gepostet: '.$stats['posted']);
    $this->line('Fehler: '.$stats['errors']);

    return Command::SUCCESS;
})->purpose('Import official Hunt: Showdown news and optionally publish new items as HuntNews feed posts');

