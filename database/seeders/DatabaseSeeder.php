<?php

namespace Database\Seeders;
use App\Models\Cup;
use App\Models\CupTeam;

use App\Models\Conversation;
use App\Models\Badge;
use App\Models\Quest;
use App\Models\FeedPost;
use App\Models\Giveaway;
use App\Models\LfgPost;
use App\Models\Team;
use App\Models\TeamLfgPost;
use App\Models\UserNotification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@hunthub.local',
                'username' => 'admin',
                'name' => 'Hunthub Admin',
                'password' => 'ChangeMe123!',
                'profile' => [
                    'headline' => 'Hunthub Aufbauprofil',
                    'bio' => 'Dieses Profil ist ein lokaler Startnutzer für den Aufbau der neuen Hunthub-Plattform.',
                    'platform' => 'PC',
                    'playstyle' => 'Community / Organisation',
                    'region' => 'EU',
                    'language' => 'Deutsch',
                    'hunt_role' => 'Support',
                    'is_lfg_available' => false,
                    'profile_visibility' => 'public',
                ],
            ],
            [
                'email' => 'duo@hunthub.local',
                'username' => 'bayouduo',
                'name' => 'Bayou Duo',
                'password' => 'ChangeMe123!',
                'profile' => [
                    'headline' => 'Ruhiger Duo-Spieler, gerne Bounty Hunt am Abend.',
                    'bio' => 'Spielt taktisch, bevorzugt saubere Rotationen und Kommunikation statt wildem Pushen.',
                    'platform' => 'PC',
                    'playstyle' => 'Taktisch',
                    'region' => 'EU',
                    'language' => 'Deutsch',
                    'hunt_role' => 'Support / Scout',
                    'discord_name' => 'bayouduo',
                    'is_lfg_available' => true,
                    'profile_visibility' => 'public',
                ],
            ],
            [
                'email' => 'console@hunthub.local',
                'username' => 'consolehunter',
                'name' => 'Console Hunter',
                'password' => 'ChangeMe123!',
                'profile' => [
                    'headline' => 'Xbox-Spieler für entspannte Runden.',
                    'bio' => 'Sucht Mitspieler für regelmäßige Abendrunden ohne Stress.',
                    'platform' => 'Xbox',
                    'playstyle' => 'Entspannt',
                    'region' => 'EU',
                    'language' => 'Deutsch',
                    'hunt_role' => 'Allrounder',
                    'is_lfg_available' => true,
                    'profile_visibility' => 'public',
                ],
            ],
            [
                'email' => 'aggressive@hunthub.local',
                'username' => 'pushmaster',
                'name' => 'Pushmaster',
                'password' => 'ChangeMe123!',
                'profile' => [
                    'headline' => 'Aggressiver Spieler für schnelle Fights.',
                    'bio' => 'Mag Action, frühe Rotationen und direkte Duelle. Kein Busch-Simulator.',
                    'platform' => 'PlayStation',
                    'playstyle' => 'Aggressiv',
                    'region' => 'EU',
                    'language' => 'Deutsch / Englisch',
                    'hunt_role' => 'Entry',
                    'is_lfg_available' => false,
                    'profile_visibility' => 'public',
                ],
            ],
        ];

        foreach ($users as $userData) {
            $profileData = $userData['profile'];
            unset($userData['profile']);

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'password' => Hash::make($userData['password']),
                ]
            );

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );
        }

        if (Schema::hasColumn('users', 'is_admin')) {
            User::where('email', 'admin@hunthub.local')->update([
                'is_admin' => true,
                'status' => 'active',
                'suspended_at' => null,
            ]);
        }

        if (Schema::hasTable('feed_posts')) {
            $admin = User::where('username', 'admin')->first();
            $duo = User::where('username', 'bayouduo')->first();
            $console = User::where('username', 'consolehunter')->first();

            $examples = [
                [$admin, 'Willkommen im neuen Hunthub-Newsfeed. Das ist die erste technische Wall-Grundlage mit Posts, Kommentaren, Likes und gespeicherten Beiträgen.'],
                [$duo, 'Heute Abend jemand Lust auf ruhige Duo-Runden? PC, EU, gerne taktisch und mit Voice.'],
                [$console, 'Xbox-Fraktion ist auch am Start. Später können LFG-Beiträge direkt sauber mit dem Feed verbunden werden.'],
            ];

            foreach ($examples as [$author, $body]) {
                if (! $author) {
                    continue;
                }

                FeedPost::firstOrCreate(
                    ['user_id' => $author->id, 'body' => $body],
                    ['visibility' => 'public', 'status' => 'published']
                );
            }
        }

        if (Schema::hasTable('teams')) {
            $admin = User::where('username', 'admin')->first();
            $duo = User::where('username', 'bayouduo')->first();
            $console = User::where('username', 'consolehunter')->first();
            $pushmaster = User::where('username', 'pushmaster')->first();

            $teams = [
                [
                    'owner' => $admin,
                    'members' => [$duo],
                    'data' => [
                        'name' => 'Bayou Taktiker',
                        'slug' => 'bayou-taktiker',
                        'tagline' => 'Ruhige EU-Runden mit Kommunikation.',
                        'description' => 'Ein Beispielteam für taktische Hunt-Runden. Später hängen hier Team-Newsfeed, Team-LFG und Einladungslinks dran.',
                        'platform' => 'PC',
                        'playstyle' => 'Taktisch',
                        'region' => 'EU',
                        'language' => 'Deutsch',
                        'visibility' => 'public',
                        'recruitment_status' => 'open',
                        'status' => 'active',
                    ],
                ],
                [
                    'owner' => $console,
                    'members' => [$pushmaster],
                    'data' => [
                        'name' => 'Console Hunters',
                        'slug' => 'console-hunters',
                        'tagline' => 'Xbox und PlayStation Spieler für Abendrunden.',
                        'description' => 'Ein Beispielteam für Konsolenspieler. Recruiting ist offen, damit Beitrittsanfragen getestet werden können.',
                        'platform' => 'Crossplay',
                        'playstyle' => 'Entspannt',
                        'region' => 'EU',
                        'language' => 'Deutsch / Englisch',
                        'visibility' => 'public',
                        'recruitment_status' => 'open',
                        'status' => 'active',
                    ],
                ],
            ];

            foreach ($teams as $teamData) {
                if (! $teamData['owner']) {
                    continue;
                }

                $team = Team::firstOrCreate(
                    ['slug' => $teamData['data']['slug']],
                    ['owner_id' => $teamData['owner']->id, ...$teamData['data']]
                );

                $team->members()->updateOrCreate(
                    ['user_id' => $teamData['owner']->id],
                    [
                        'role' => 'owner',
                        'status' => 'active',
                        'joined_at' => now(),
                    ]
                );

                foreach ($teamData['members'] as $member) {
                    if (! $member) {
                        continue;
                    }

                    $team->members()->updateOrCreate(
                        ['user_id' => $member->id],
                        [
                            'role' => 'member',
                            'status' => 'active',
                            'accepted_by' => $teamData['owner']->id,
                            'joined_at' => now(),
                        ]
                    );
                }
            }
        }
        if (Schema::hasTable('lfg_posts')) {
            $admin = User::where('username', 'admin')->first();
            $duo = User::where('username', 'bayouduo')->first();
            $console = User::where('username', 'consolehunter')->first();
            $pushmaster = User::where('username', 'pushmaster')->first();

            $examples = [
                [
                    'user' => $duo,
                    'data' => [
                        'title' => 'Suche Duo für ruhige Bounty Hunt Runden',
                        'body' => 'Heute Abend ab 20 Uhr. PC, EU, gerne taktisch mit Voice und ohne Stress.',
                        'platform' => 'PC',
                        'playstyle' => 'Taktisch',
                        'region' => 'EU',
                        'language' => 'Deutsch',
                        'preferred_time' => 'Abends',
                        'experience_level' => 'Fortgeschritten',
                        'voice_required' => true,
                        'slots_total' => 2,
                        'slots_filled' => 1,
                        'status' => 'open',
                        'visibility' => 'public',
                    ],
                ],
                [
                    'user' => $console,
                    'data' => [
                        'title' => 'Xbox Trio für entspannte Abendrunden',
                        'body' => 'Suchen noch Mitspieler für lockere Runden. Kein Schwitzen, einfach gute Kommunikation.',
                        'platform' => 'Xbox',
                        'playstyle' => 'Entspannt',
                        'region' => 'EU',
                        'language' => 'Deutsch',
                        'preferred_time' => 'Abends',
                        'experience_level' => 'Egal',
                        'voice_required' => false,
                        'slots_total' => 3,
                        'slots_filled' => 1,
                        'status' => 'open',
                        'visibility' => 'public',
                    ],
                ],
                [
                    'user' => $pushmaster,
                    'data' => [
                        'title' => 'Aggressive Push-Runden gesucht',
                        'body' => 'PlayStation, gerne schnelle Fights und klare Calls. Keine Angst vor Action.',
                        'platform' => 'PlayStation',
                        'playstyle' => 'Aggressiv',
                        'region' => 'EU',
                        'language' => 'Deutsch / Englisch',
                        'preferred_time' => 'Flexibel',
                        'experience_level' => 'Erfahren',
                        'voice_required' => true,
                        'slots_total' => 3,
                        'slots_filled' => 1,
                        'status' => 'open',
                        'visibility' => 'public',
                    ],
                ],
            ];

            foreach ($examples as $example) {
                if (! $example['user']) {
                    continue;
                }

                LfgPost::firstOrCreate(
                    ['user_id' => $example['user']->id, 'title' => $example['data']['title']],
                    ['user_id' => $example['user']->id, ...$example['data']]
                );
            }
        }

        if (Schema::hasTable('team_lfg_posts')) {
            $admin = User::where('username', 'admin')->first();
            $duo = User::where('username', 'bayouduo')->first();
            $console = User::where('username', 'consolehunter')->first();
            $pushmaster = User::where('username', 'pushmaster')->first();
            $taktiker = Team::where('slug', 'bayou-taktiker')->first();
            $consoleTeam = Team::where('slug', 'console-hunters')->first();

            $examples = [
                [
                    'user' => $admin,
                    'team' => $taktiker,
                    'data' => [
                        'type' => 'team_seeks_players',
                        'title' => 'Bayou Taktiker suchen ruhigen dritten Spieler',
                        'body' => 'Wir suchen einen Spieler für EU-Abende, taktische Runden und klare Kommunikation. Kein Stress, aber bitte zuverlässig.',
                        'platform' => 'PC',
                        'playstyle' => 'Taktisch',
                        'region' => 'EU',
                        'language' => 'Deutsch',
                        'preferred_time' => 'Abends',
                        'experience_level' => 'Fortgeschritten',
                        'voice_required' => true,
                        'slots_total' => 1,
                        'slots_filled' => 0,
                        'status' => 'open',
                        'visibility' => 'public',
                    ],
                ],
                [
                    'user' => $console,
                    'team' => $consoleTeam,
                    'data' => [
                        'type' => 'team_seeks_players',
                        'title' => 'Console Hunters suchen Verstärkung',
                        'body' => 'Crossplay-Team sucht entspannte Spieler für regelmäßige Abendrunden.',
                        'platform' => 'Crossplay',
                        'playstyle' => 'Entspannt',
                        'region' => 'EU',
                        'language' => 'Deutsch / Englisch',
                        'preferred_time' => 'Abends',
                        'experience_level' => 'Egal',
                        'voice_required' => false,
                        'slots_total' => 2,
                        'slots_filled' => 0,
                        'status' => 'open',
                        'visibility' => 'public',
                    ],
                ],
                [
                    'user' => $pushmaster,
                    'team' => null,
                    'data' => [
                        'type' => 'player_seeks_team',
                        'title' => 'Aggressiver Spieler sucht aktives Team',
                        'body' => 'Suche ein Team, das gerne früh rotiert und Fights nicht vermeidet. PlayStation bevorzugt, Crossplay okay.',
                        'platform' => 'PlayStation',
                        'playstyle' => 'Aggressiv',
                        'region' => 'EU',
                        'language' => 'Deutsch / Englisch',
                        'preferred_time' => 'Flexibel',
                        'experience_level' => 'Erfahren',
                        'voice_required' => true,
                        'slots_total' => null,
                        'slots_filled' => 0,
                        'status' => 'open',
                        'visibility' => 'public',
                    ],
                ],
            ];

            foreach ($examples as $example) {
                if (! $example['user']) {
                    continue;
                }

                TeamLfgPost::firstOrCreate(
                    ['user_id' => $example['user']->id, 'title' => $example['data']['title']],
                    [
                        'user_id' => $example['user']->id,
                        'team_id' => $example['team']?->id,
                        ...$example['data'],
                    ]
                );
            }
        }



        if (Schema::hasTable('conversations') && Schema::hasTable('user_notifications')) {
            $admin = User::where('username', 'admin')->first();
            $duo = User::where('username', 'bayouduo')->first();

            if ($admin && $duo) {
                $conversation = Conversation::firstOrCreate([
                    'type' => 'private',
                    'created_by' => $admin->id,
                ]);

                $conversation->users()->syncWithoutDetaching([
                    $admin->id => ['last_read_at' => now()],
                    $duo->id => ['last_read_at' => null],
                ]);

                $conversation->messages()->firstOrCreate(
                    ['user_id' => $admin->id, 'body' => 'Willkommen im neuen Nachrichten-MVP. Das ist eine Beispielkonversation für den ersten Test.'],
                    ['user_id' => $admin->id, 'body' => 'Willkommen im neuen Nachrichten-MVP. Das ist eine Beispielkonversation für den ersten Test.']
                );

                UserNotification::firstOrCreate(
                    ['user_id' => $duo->id, 'type' => 'message_new', 'title' => 'Neue Nachricht'],
                    [
                        'actor_id' => $admin->id,
                        'body' => 'Hunthub Admin hat dir eine Nachricht geschrieben.',
                        'action_url' => route('messages.show', $conversation),
                    ]
                );
            }
        }

        if (Schema::hasTable('badges')) {
            $badges = [
                ['slug' => 'early-hunter', 'name' => 'Early Hunter', 'category' => 'account', 'icon' => '★', 'description' => 'Du hast deine ersten XP gesammelt.', 'sort_order' => 10],
                ['slug' => 'profile-scout', 'name' => 'Profil-Scout', 'category' => 'profile', 'icon' => '◈', 'description' => 'Dein Profil ist mindestens zur Hälfte gepflegt.', 'sort_order' => 20],
                ['slug' => 'profile-complete', 'name' => 'Profil komplett', 'category' => 'profile', 'icon' => '◆', 'description' => 'Dein Profil ist vollständig ausgefüllt.', 'sort_order' => 30],
                ['slug' => 'wall-starter', 'name' => 'Wall-Starter', 'category' => 'feed', 'icon' => '✦', 'description' => 'Du hast deinen ersten Feed-Beitrag erstellt.', 'sort_order' => 40],
                ['slug' => 'conversation-starter', 'name' => 'Gesprächsstarter', 'category' => 'feed', 'icon' => '✚', 'description' => 'Du hast deinen ersten Kommentar geschrieben.', 'sort_order' => 50],
                ['slug' => 'team-founder', 'name' => 'Team-Gründer', 'category' => 'teams', 'icon' => '⬟', 'description' => 'Du hast ein Team erstellt.', 'sort_order' => 60],
                ['slug' => 'lfg-hunter', 'name' => 'LFG-Hunter', 'category' => 'lfg', 'icon' => '◎', 'description' => 'Du hast ein LFG erstellt.', 'sort_order' => 70],
                ['slug' => 'team-recruiter', 'name' => 'Recruiter', 'category' => 'team-lfg', 'icon' => '⬢', 'description' => 'Du hast ein Team-LFG erstellt.', 'sort_order' => 80],
                ['slug' => 'media-scout', 'name' => 'Medien-Scout', 'category' => 'media', 'icon' => '◉', 'description' => 'Du hast Medien in die Mediathek geladen.', 'sort_order' => 90],
                ['slug' => 'moment-maker', 'name' => 'Moment-Maker', 'category' => 'moments', 'icon' => '▶', 'description' => 'Du hast deinen ersten Moment veröffentlicht.', 'sort_order' => 95],
                ['slug' => 'cup-organizer', 'name' => 'Cup-Organizer', 'category' => 'cups', 'icon' => '♜', 'description' => 'Du hast deinen ersten Cup erstellt.', 'sort_order' => 97],
                ['slug' => 'cup-contender', 'name' => 'Cup-Teilnehmer', 'category' => 'cups', 'icon' => '♞', 'description' => 'Du bist einem Cup-Team beigetreten.', 'sort_order' => 98],
                ['slug' => 'level-5', 'name' => 'Veteran I', 'category' => 'level', 'icon' => '✹', 'description' => 'Du hast Level 5 erreicht.', 'sort_order' => 100],
            ];

            foreach ($badges as $badge) {
                $badge['name_de'] ??= $badge['name'];
                $badge['description_de'] ??= $badge['description'] ?? null;
                $badge = array_merge($badge, match ($badge['slug']) {
                    'conversation-starter' => [
                        'name_en' => 'Conversation Starter',
                        'description_en' => 'You wrote your first comment.',
                    ],
                    'level-5' => [
                        'description_en' => 'You reached level 5.',
                    ],
                    'cup-contender' => [
                        'name_en' => 'Cup Participant',
                        'description_en' => 'You joined a Cup team.',
                    ],
                    'lfg-hunter' => [
                        'name_en' => 'LFG Hunter',
                        'description_en' => 'You created an LFG.',
                    ],
                    'moment-maker' => [
                        'name_en' => 'Moment Maker',
                        'description_en' => 'You published your first Moment.',
                    ],
                    'profile-complete' => [
                        'name_en' => 'Profile Completed',
                        'description_en' => 'Your profile is fully filled out.',
                    ],
                    default => [],
                });

                Badge::updateOrCreate(['slug' => $badge['slug']], $badge + ['is_active' => true]);
            }
        }

        if (Schema::hasTable('quests')) {
            $quests = [
                ['slug' => 'first-wall-post', 'name' => 'Erster Wall-Beitrag', 'category' => 'start', 'action' => 'feed_post_created', 'target_count' => 1, 'xp_reward' => 25, 'badge_slug' => 'wall-starter', 'description' => 'Erstelle deinen ersten Beitrag im Newsfeed.', 'sort_order' => 10],
                ['slug' => 'first-comment', 'name' => 'Misch dich ein', 'category' => 'start', 'action' => 'feed_comment_created', 'target_count' => 1, 'xp_reward' => 15, 'badge_slug' => 'conversation-starter', 'description' => 'Schreibe deinen ersten Kommentar.', 'sort_order' => 20],
                ['slug' => 'create-lfg', 'name' => 'Mitspieler gesucht', 'category' => 'lfg', 'action' => 'lfg_post_created', 'target_count' => 1, 'xp_reward' => 20, 'badge_slug' => 'lfg-hunter', 'description' => 'Erstelle ein globales LFG.', 'sort_order' => 30],
                ['slug' => 'create-team', 'name' => 'Team gründen', 'category' => 'teams', 'action' => 'team_created', 'target_count' => 1, 'xp_reward' => 35, 'badge_slug' => 'team-founder', 'description' => 'Erstelle dein erstes Team.', 'sort_order' => 40],
                ['slug' => 'create-team-lfg', 'name' => 'Recruiting starten', 'category' => 'team-lfg', 'action' => 'team_lfg_post_created', 'target_count' => 1, 'xp_reward' => 25, 'badge_slug' => 'team-recruiter', 'description' => 'Erstelle ein Team-LFG.', 'sort_order' => 50],
                ['slug' => 'upload-media', 'name' => 'Erstes Medium', 'category' => 'media', 'action' => 'media_uploaded', 'target_count' => 1, 'xp_reward' => 15, 'badge_slug' => 'media-scout', 'description' => 'Lade ein Medium in deine Mediathek.', 'sort_order' => 60],
                ['slug' => 'first-moment', 'name' => 'Erster Moment', 'category' => 'moments', 'action' => 'moment_created', 'target_count' => 1, 'xp_reward' => 30, 'badge_slug' => 'moment-maker', 'description' => 'Veröffentliche deinen ersten Moment.', 'sort_order' => 70],
                ['slug' => 'first-cup-team', 'name' => 'Cup-Einstieg', 'category' => 'cups', 'action' => 'cup_team_created', 'target_count' => 1, 'xp_reward' => 25, 'badge_slug' => 'cup-contender', 'description' => 'Erstelle dein erstes Cup-Team.', 'sort_order' => 80],
            ];

            foreach ($quests as $quest) {
                $quest['name_de'] ??= $quest['name'];
                $quest['description_de'] ??= $quest['description'] ?? null;
                $quest = array_merge($quest, match ($quest['slug']) {
                    'first-wall-post' => [
                        'name_en' => 'First Wall Post',
                    ],
                    'first-comment' => [
                        'name_en' => 'Join the Conversation',
                    ],
                    'create-lfg' => [
                        'name_en' => 'Looking for Teammates',
                    ],
                    'first-moment' => [
                        'name_en' => 'Moment Maker',
                    ],
                    default => [],
                });

                Quest::updateOrCreate(['slug' => $quest['slug']], $quest + [
                    'period' => null,
                    'is_repeatable' => false,
                    'is_active' => true,
                ]);
            }
        }

        if (Schema::hasTable('cups')) {
            $admin = User::where('username', 'admin')->first();
            $duo = User::where('username', 'bayouduo')->first();

            if ($admin) {
                $cup = Cup::updateOrCreate(
                    ['slug' => 'hunthub-bayou-cup'],
                    [
                        'owner_id' => $admin->id,
                        'title' => 'Hunthub Bayou Cup',
                        'summary' => 'Beispiel-Cup für Ergebnis-Einreichung, Team-Anmeldung und Leaderboard.',
                        'rules' => '1 Punkt pro Hunter-Kill. Bis zu 1 zusätzlicher Punkt für Bounty-Token, aber nur bei erfolgreicher Extraktion und mindestens einem Kill. Einreichungen müssen geprüft werden.',
                        'platform' => 'Crossplay',
                        'region' => 'EU',
                        'language' => 'Deutsch',
                        'team_size' => 3,
                        'max_teams' => 32,
                        'status' => 'active',
                        'visibility' => 'public',
                        'starts_at' => now()->subDay(),
                        'ends_at' => now()->addMonth(),
                        'registration_opens_at' => now()->subWeek(),
                        'registration_closes_at' => now()->addWeeks(2),
                        'settings' => [
                            'scoring' => 'kills_plus_max_one_bounty_bonus_only_on_extraction',
                            'submission_cooldown_minutes' => 20,
                        ],
                    ]
                );

                $team = CupTeam::firstOrCreate(
                    ['cup_id' => $cup->id, 'name' => 'Bayou Sample Team'],
                    ['cup_id' => $cup->id, 'owner_id' => $admin->id, 'name' => 'Bayou Sample Team', 'status' => 'active']
                );

                $team->members()->updateOrCreate(
                    ['user_id' => $admin->id],
                    ['role' => 'captain', 'status' => 'active', 'joined_at' => now()]
                );

                if ($duo) {
                    $team->members()->updateOrCreate(
                        ['user_id' => $duo->id],
                        ['role' => 'member', 'status' => 'active', 'joined_at' => now()]
                    );
                }
            }
        }

        if (Schema::hasTable('giveaways')) {
            Giveaway::updateOrCreate(
                ['slug' => 'hunthub-start-giveaway'],
                [
                    'title' => 'Hunthub Start-Gewinnspiel',
                    'description' => 'Technische Beispiel-Grundlage für Referral-Links und Gewinnspiel-Chancen. Später wird das im Adminbereich steuerbar.',
                    'status' => 'active',
                    'starts_at' => now()->subDay(),
                    'ends_at' => now()->addMonths(3),
                    'profile_completion_required' => 100,
                    'base_entries' => 1,
                    'profile_bonus_entries' => 1,
                    'referral_bonus_entries' => 1,
                    'max_referral_bonus_entries' => 1,
                    'rules' => [
                        'registration' => 'Eine Basis-Chance für Registrierung.',
                        'profile_complete' => 'Eine Bonus-Chance bei 100 Prozent Profilvollständigkeit.',
                        'referral_profile_complete' => 'Eine Bonus-Chance für einen eingeladenen Nutzer mit vollständigem Profil.',
                    ],
                ]
            );
        }
    }
}
