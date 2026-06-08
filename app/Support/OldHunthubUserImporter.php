<?php

namespace App\Support;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class OldHunthubUserImporter
{
    private bool $dryRun = false;
    private bool $includeInactive = false;

    /** @var array<int, int> */
    private array $oldToNewUserIds = [];

    /** @var array<string, int> */
    private array $referralLinkIdsByOldUserId = [];

    /** @var array<string, int> */
    private array $stats = [
        'users_created' => 0,
        'users_existing' => 0,
        'users_skipped_inactive' => 0,
        'profiles_created' => 0,
        'privacy_created' => 0,
        'referral_links_created' => 0,
        'referral_links_existing' => 0,
        'referral_signups_created' => 0,
        'referral_signups_existing' => 0,
        'social_created' => 0,
        'social_updated' => 0,
        'social_existing' => 0,
        'social_skipped' => 0,
        'warnings' => 0,
    ];

    /** @var list<string> */
    private array $warnings = [];

    public function run(Command $command, string $usersSqlPath, ?string $socialSqlPath = null, bool $dryRun = false, bool $includeInactive = false): int
    {
        $this->dryRun = $dryRun;
        $this->includeInactive = $includeInactive;
        $this->oldToNewUserIds = [];
        $this->referralLinkIdsByOldUserId = [];
        foreach (array_keys($this->stats) as $key) {
            $this->stats[$key] = 0;
        }
        $this->warnings = [];

        $usersSqlPath = $this->resolvePath($usersSqlPath);
        $socialSqlPath = $socialSqlPath ? $this->resolvePath($socialSqlPath) : null;

        if (! is_file($usersSqlPath)) {
            $command->error('users.sql wurde nicht gefunden: '.$usersSqlPath);
            return Command::FAILURE;
        }

        if ($socialSqlPath && ! is_file($socialSqlPath)) {
            $command->error('social_accounts.sql wurde nicht gefunden: '.$socialSqlPath);
            return Command::FAILURE;
        }

        if (! Schema::hasTable('users')) {
            $command->error('Die Tabelle users existiert nicht. Erst Migrationen ausführen.');
            return Command::FAILURE;
        }

        $oldUsers = $this->parseInsertRows($usersSqlPath, 'users');
        $oldSocialAccounts = $socialSqlPath ? $this->parseInsertRows($socialSqlPath, 'social_accounts') : [];

        $command->info('Alter User-Dump: '.count($oldUsers).' Datensätze');
        $command->info('Alter Social-Dump: '.count($oldSocialAccounts).' Datensätze');

        if ($this->dryRun) {
            $command->warn('DRY-RUN: Es werden keine Änderungen gespeichert.');
        }

        DB::beginTransaction();

        try {
            $this->importUsers($oldUsers);
            $this->importReferralLinks($oldUsers);
            $this->importReferralSignups($oldUsers);
            $this->importSocialAccounts($oldSocialAccounts);

            if ($this->dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }

        $this->printResult($command);

        return Command::SUCCESS;
    }

    /**
     * @param list<array<string, mixed>> $oldUsers
     */
    private function importUsers(array $oldUsers): void
    {
        foreach ($oldUsers as $oldUser) {
            $oldId = (int) ($oldUser['id'] ?? 0);
            $isActive = (int) ($oldUser['is_active'] ?? 0) === 1;

            if (! $isActive && ! $this->includeInactive) {
                $this->stats['users_skipped_inactive']++;
                continue;
            }

            $email = $this->normalizeEmail($oldUser['email'] ?? null);
            $username = $this->normalizeUsername($oldUser['username'] ?? null, $oldId);
            $password = (string) ($oldUser['password_hash'] ?? '');

            if (! $email || $password === '') {
                $this->warn('User '.$oldId.' übersprungen: E-Mail oder Passwort-Hash fehlt.');
                continue;
            }

            $existing = DB::table('users')->where('email', $email)->first();

            if ($existing) {
                $this->oldToNewUserIds[$oldId] = (int) $existing->id;
                $this->stats['users_existing']++;
                $this->ensureProfileAndPrivacy((int) $existing->id, $this->dateOrNow($oldUser['created_at'] ?? null), $this->dateOrNow($oldUser['updated_at'] ?? null));
                continue;
            }

            $username = $this->uniqueUsername($username, $oldId);
            $createdAt = $this->dateOrNow($oldUser['created_at'] ?? null);
            $updatedAt = $this->dateOrNow($oldUser['updated_at'] ?? null);

            $insert = [
                'name' => Str::limit($username, 80, ''),
                'username' => $username,
                'email' => $email,
                'email_verified_at' => $createdAt,
                'password' => $password,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ];

            $this->addColumnIfExists($insert, 'users', 'avatar_path', null);
            $this->addColumnIfExists($insert, 'users', 'cover_path', null);
            $this->addColumnIfExists($insert, 'users', 'remember_token', null);
            $this->addColumnIfExists($insert, 'users', 'xp_total', 0);
            $this->addColumnIfExists($insert, 'users', 'level', 1);
            $this->addColumnIfExists($insert, 'users', 'trust_score', 0);
            $this->addColumnIfExists($insert, 'users', 'last_xp_at', null);
            $this->addColumnIfExists($insert, 'users', 'is_admin', false);
            $this->addColumnIfExists($insert, 'users', 'status', $isActive ? 'active' : 'suspended');
            $this->addColumnIfExists($insert, 'users', 'suspended_at', $isActive ? null : $updatedAt);
            $this->addColumnIfExists($insert, 'users', 'last_login_at', null);
            $this->addColumnIfExists($insert, 'users', 'last_login_ip', null);

            $newId = (int) DB::table('users')->insertGetId($insert);
            $this->oldToNewUserIds[$oldId] = $newId;
            $this->stats['users_created']++;

            $this->ensureProfileAndPrivacy($newId, $createdAt, $updatedAt);
        }
    }

    /**
     * @param list<array<string, mixed>> $oldUsers
     */
    private function importReferralLinks(array $oldUsers): void
    {
        if (! Schema::hasTable('referral_links')) {
            return;
        }

        foreach ($oldUsers as $oldUser) {
            $oldId = (int) ($oldUser['id'] ?? 0);
            $newUserId = $this->oldToNewUserIds[$oldId] ?? null;
            $code = trim((string) ($oldUser['referral_code'] ?? ''));

            if (! $newUserId || $code === '') {
                continue;
            }

            $existing = DB::table('referral_links')->where('user_id', $newUserId)->orWhere('code', $code)->first();

            if ($existing) {
                $this->referralLinkIdsByOldUserId[(string) $oldId] = (int) $existing->id;
                $this->stats['referral_links_existing']++;
                continue;
            }

            $createdAt = $this->dateOrNow($oldUser['created_at'] ?? null);
            $updatedAt = $this->dateOrNow($oldUser['updated_at'] ?? null);

            $id = (int) DB::table('referral_links')->insertGetId([
                'user_id' => $newUserId,
                'code' => Str::limit($code, 32, ''),
                'clicks_count' => 0,
                'signups_count' => 0,
                'completed_profiles_count' => 0,
                'last_clicked_at' => null,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);

            $this->referralLinkIdsByOldUserId[(string) $oldId] = $id;
            $this->stats['referral_links_created']++;
        }
    }

    /**
     * @param list<array<string, mixed>> $oldUsers
     */
    private function importReferralSignups(array $oldUsers): void
    {
        if (! Schema::hasTable('referral_signups')) {
            return;
        }

        foreach ($oldUsers as $oldUser) {
            $oldReferredId = (int) ($oldUser['id'] ?? 0);
            $oldReferrerId = (int) ($oldUser['referred_by_user_id'] ?? 0);

            if ($oldReferrerId <= 0) {
                continue;
            }

            $newReferredUserId = $this->oldToNewUserIds[$oldReferredId] ?? null;
            $newReferrerUserId = $this->oldToNewUserIds[$oldReferrerId] ?? null;
            $referralLinkId = $this->referralLinkIdsByOldUserId[(string) $oldReferrerId] ?? null;

            if (! $newReferredUserId || ! $newReferrerUserId) {
                $this->warn('Referral für alten User '.$oldReferredId.' übersprungen: Referrer oder User wurde nicht importiert.');
                continue;
            }

            $existing = DB::table('referral_signups')->where('referred_user_id', $newReferredUserId)->first();

            if ($existing) {
                $this->stats['referral_signups_existing']++;
                continue;
            }

            $registeredAt = $this->dateOrNow($oldUser['referred_at'] ?? $oldUser['created_at'] ?? null);
            $createdAt = $this->dateOrNow($oldUser['created_at'] ?? null);
            $updatedAt = $this->dateOrNow($oldUser['updated_at'] ?? null);
            $referralCode = (string) DB::table('referral_links')->where('id', $referralLinkId)->value('code');

            DB::table('referral_signups')->insert([
                'referral_link_id' => $referralLinkId,
                'referrer_id' => $newReferrerUserId,
                'referred_user_id' => $newReferredUserId,
                'referral_code' => $referralCode ?: 'legacy-'.$oldReferrerId,
                'registered_at' => $registeredAt,
                'profile_completed_at' => null,
                'status' => 'registered',
                'metadata' => json_encode(['source' => 'legacy_import', 'old_referrer_id' => $oldReferrerId, 'old_referred_user_id' => $oldReferredId], JSON_UNESCAPED_SLASHES),
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);

            if ($referralLinkId) {
                DB::table('referral_links')->where('id', $referralLinkId)->increment('signups_count');
            }

            $this->stats['referral_signups_created']++;
        }
    }

    /**
     * @param list<array<string, mixed>> $oldSocialAccounts
     */
    private function importSocialAccounts(array $oldSocialAccounts): void
    {
        if ($oldSocialAccounts === [] || ! Schema::hasTable('social_accounts')) {
            return;
        }

        foreach ($oldSocialAccounts as $oldSocial) {
            $oldUserId = (int) ($oldSocial['user_id'] ?? 0);
            $newUserId = $this->oldToNewUserIds[$oldUserId] ?? null;
            $provider = strtolower(trim((string) ($oldSocial['provider'] ?? '')));
            $providerUserId = trim((string) ($oldSocial['provider_user_id'] ?? ''));

            if (! $newUserId || $provider === '' || $providerUserId === '') {
                $this->stats['social_skipped']++;
                continue;
            }

            $existing = DB::table('social_accounts')
                ->where('provider', $provider)
                ->where('provider_user_id', $providerUserId)
                ->first();

            $payload = $this->socialPayload($oldSocial, $newUserId, $provider, $providerUserId);

            if ($existing) {
                if ((int) $existing->user_id !== $newUserId) {
                    $this->warn('Social '.$provider.':'.$providerUserId.' existiert bereits bei anderem User und wurde nicht umgehängt.');
                    $this->stats['social_existing']++;
                    continue;
                }

                DB::table('social_accounts')->where('id', $existing->id)->update($payload);
                $this->stats['social_updated']++;
                continue;
            }

            DB::table('social_accounts')->insert($payload);
            $this->stats['social_created']++;
        }
    }

    /**
     * @param array<string, mixed> $oldSocial
     * @return array<string, mixed>
     */
    private function socialPayload(array $oldSocial, int $newUserId, string $provider, string $providerUserId): array
    {
        $createdAt = $this->dateOrNow($oldSocial['created_at'] ?? null);
        $updatedAt = $this->dateOrNow($oldSocial['updated_at'] ?? null);
        $lastLoginAt = $this->dateOrNull($oldSocial['last_login_at'] ?? null);
        $providerEmail = $this->normalizeEmail($oldSocial['provider_email'] ?? null);
        $providerName = $this->nullableString($oldSocial['provider_name'] ?? null);
        $providerAvatar = $this->nullableString($oldSocial['provider_avatar'] ?? null);

        $payload = [
            'user_id' => $newUserId,
            'provider' => $provider,
            'provider_user_id' => $providerUserId,
            'provider_email' => $providerEmail,
            'provider_name' => $providerName,
            'last_login_at' => $lastLoginAt,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];

        if (Schema::hasColumn('social_accounts', 'provider_nickname')) {
            $payload['provider_nickname'] = null;
        }

        if (Schema::hasColumn('social_accounts', 'avatar_url')) {
            $payload['avatar_url'] = $providerAvatar;
        } elseif (Schema::hasColumn('social_accounts', 'provider_avatar')) {
            $payload['provider_avatar'] = $providerAvatar;
        }

        if (Schema::hasColumn('social_accounts', 'raw_profile')) {
            $payload['raw_profile'] = json_encode(['source' => 'legacy_import'], JSON_UNESCAPED_SLASHES);
        }

        return $payload;
    }

    private function ensureProfileAndPrivacy(int $userId, string $createdAt, string $updatedAt): void
    {
        if (Schema::hasTable('user_profiles') && ! DB::table('user_profiles')->where('user_id', $userId)->exists()) {
            DB::table('user_profiles')->insert([
                'user_id' => $userId,
                'headline' => null,
                'bio' => null,
                'platform' => null,
                'playstyle' => null,
                'region' => null,
                'language' => null,
                'hunt_role' => null,
                'discord_name' => null,
                'steam_url' => null,
                'twitch_url' => null,
                'youtube_url' => null,
                'is_lfg_available' => false,
                'profile_visibility' => 'public',
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
            $this->stats['profiles_created']++;
        }

        if (Schema::hasTable('user_privacy_settings') && ! DB::table('user_privacy_settings')->where('user_id', $userId)->exists()) {
            DB::table('user_privacy_settings')->insert([
                'user_id' => $userId,
                'profile_visibility' => 'public',
                'allow_messages_from' => 'registered',
                'allow_team_invites' => true,
                'allow_lfg_invites' => true,
                'show_online_status' => true,
                'show_activity_feed' => true,
                'show_gamification' => true,
                'data_usage_consent' => false,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
            $this->stats['privacy_created']++;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseInsertRows(string $path, string $table): array
    {
        $sql = file_get_contents($path);

        if (! is_string($sql) || $sql === '') {
            throw new RuntimeException('SQL-Datei konnte nicht gelesen werden: '.$path);
        }

        $pattern = '/INSERT\s+INTO\s+`'.preg_quote($table, '/').'`\s*\((.*?)\)\s*VALUES\s*(.*?);/is';

        if (! preg_match($pattern, $sql, $matches)) {
            throw new RuntimeException('Kein INSERT für Tabelle '.$table.' gefunden: '.$path);
        }

        $columns = array_map(
            fn (string $column) => trim($column, " `\t\n\r\0\x0B"),
            explode(',', $matches[1])
        );

        $tuples = $this->splitSqlTuples($matches[2]);
        $rows = [];

        foreach ($tuples as $tuple) {
            $values = $this->parseSqlTuple($tuple);

            if (count($values) !== count($columns)) {
                throw new RuntimeException('Spalten-/Werte-Anzahl passt nicht in '.$table.'.');
            }

            $rows[] = array_combine($columns, $values);
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function splitSqlTuples(string $values): array
    {
        $tuples = [];
        $length = strlen($values);
        $level = 0;
        $start = null;
        $inString = false;
        $escape = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $values[$i];

            if ($inString) {
                if ($char === '\\' && ! $escape) {
                    $escape = true;
                    continue;
                }

                if ($char === "'" && ! $escape) {
                    $inString = false;
                }

                $escape = false;
                continue;
            }

            if ($char === "'") {
                $inString = true;
                continue;
            }

            if ($char === '(') {
                if ($level === 0) {
                    $start = $i;
                }
                $level++;
                continue;
            }

            if ($char === ')') {
                $level--;
                if ($level === 0 && $start !== null) {
                    $tuples[] = substr($values, $start, $i - $start + 1);
                    $start = null;
                }
            }
        }

        return $tuples;
    }

    /**
     * @return list<mixed>
     */
    private function parseSqlTuple(string $tuple): array
    {
        $tuple = trim($tuple);
        $tuple = trim($tuple, '()');
        $values = [];
        $length = strlen($tuple);
        $buffer = '';
        $inString = false;
        $escape = false;
        $valueWasQuoted = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $tuple[$i];

            if ($inString) {
                if ($char === '\\' && ! $escape) {
                    $escape = true;
                    continue;
                }

                if ($char === "'" && ! $escape) {
                    $inString = false;
                    continue;
                }

                $buffer .= $char;
                $escape = false;
                continue;
            }

            if ($char === "'") {
                $inString = true;
                $valueWasQuoted = true;
                continue;
            }

            if ($char === ',') {
                $values[] = $this->normalizeSqlValue($buffer, $valueWasQuoted);
                $buffer = '';
                $valueWasQuoted = false;
                continue;
            }

            $buffer .= $char;
        }

        $values[] = $this->normalizeSqlValue($buffer, $valueWasQuoted);

        return $values;
    }

    private function normalizeSqlValue(string $value, bool $wasQuoted = false): mixed
    {
        $value = trim($value);

        if ($wasQuoted) {
            return str_replace(["\\'", '\\\\'], ["'", '\\'], $value);
        }

        if (strcasecmp($value, 'NULL') === 0) {
            return null;
        }

        if (preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        }

        return str_replace(["\\'", '\\\\'], ["'", '\\'], $value);
    }

    private function uniqueUsername(string $username, int $oldId): string
    {
        $base = Str::limit($username, 28, '');
        $candidate = $base;
        $counter = 1;

        while (DB::table('users')->where('username', $candidate)->exists()) {
            $suffix = '-'.$oldId;
            if ($counter > 1) {
                $suffix .= '-'.$counter;
            }
            $candidate = Str::limit($base, 32 - strlen($suffix), '').$suffix;
            $counter++;
        }

        return $candidate;
    }

    private function normalizeUsername(mixed $username, int $oldId): string
    {
        $username = is_string($username) ? trim($username) : '';

        if ($username === '') {
            $username = 'legacy-user-'.$oldId;
        }

        $username = preg_replace('/\s+/', '_', $username) ?: 'legacy-user-'.$oldId;

        return Str::limit($username, 32, '');
    }

    private function normalizeEmail(mixed $email): ?string
    {
        if (! is_string($email)) {
            return null;
        }

        $email = strtolower(trim($email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function dateOrNow(mixed $value): string
    {
        return $this->dateOrNull($value) ?: now()->toDateTimeString();
    }

    private function dateOrNull(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function addColumnIfExists(array &$payload, string $table, string $column, mixed $value): void
    {
        if (Schema::hasColumn($table, $column)) {
            $payload[$column] = $value;
        }
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }

    private function warn(string $message): void
    {
        $this->warnings[] = $message;
        $this->stats['warnings']++;
    }

    private function printResult(Command $command): void
    {
        $command->newLine();
        $command->table(['Bereich', 'Anzahl'], collect($this->stats)->map(fn ($value, $key) => [$key, $value])->values()->all());

        if ($this->warnings !== []) {
            $command->newLine();
            $command->warn('Hinweise:');
            foreach ($this->warnings as $warning) {
                $command->line('- '.$warning);
            }
        }

        $command->newLine();
        $command->info($this->dryRun ? 'Dry-Run abgeschlossen, Änderungen wurden zurückgerollt.' : 'Import abgeschlossen.');
    }
}
