<?php

namespace App\Services\Arcade;

use App\Enums\Arcade\ArcadeMatchPlayerResult;
use App\Enums\Arcade\ArcadeMatchStatus;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeInvitation;
use App\Models\Arcade\ArcadeMatch;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class ArcadeNotificationService
{
    public const INVITATION_RECEIVED = 'arcade_invitation_received';
    public const INVITATION_ACCEPTED = 'arcade_invitation_accepted';
    public const OPPONENT_READY = 'arcade_opponent_ready';
    public const MATCH_WON = 'arcade_match_won';
    public const MATCH_LOST = 'arcade_match_lost';
    public const MATCH_DRAW = 'arcade_match_draw';

    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function invitationReceived(ArcadeInvitation $invitation): void
    {
        $invitation->loadMissing(['game', 'inviter', 'invitee']);
        $game = $invitation->game;
        $recipient = $invitation->invitee;
        $actor = $invitation->inviter;

        if (! $game || ! $recipient || ! $actor) {
            return;
        }

        $gameName = $this->germanGameName($game);
        $actorName = $this->userName($actor);
        $data = array_merge($this->gameData($game), [
            'invitation_id' => (int) $invitation->id,
            'actor_id' => (int) $actor->id,
        ]);

        $this->send(
            "arcade:invitation:{$invitation->id}:received:{$recipient->id}",
            $recipient,
            $actor,
            self::INVITATION_RECEIVED,
            'Arcade-Einladung',
            "{$actorName} hat dich zu {$gameName} eingeladen.",
            '/arcade',
            $data,
        );
    }

    public function invitationAccepted(ArcadeInvitation $invitation, User $actor): void
    {
        $invitation->loadMissing(['game', 'inviter', 'match']);
        $game = $invitation->game;
        $recipient = $invitation->inviter;
        $match = $invitation->match;

        if (! $game || ! $recipient || ! $match) {
            return;
        }

        $gameName = $this->germanGameName($game);
        $actorName = $this->userName($actor);
        $data = array_merge($this->gameData($game), [
            'invitation_id' => (int) $invitation->id,
            'match_id' => (int) $match->id,
            'actor_id' => (int) $actor->id,
        ]);

        $this->send(
            "arcade:invitation:{$invitation->id}:accepted:{$recipient->id}",
            $recipient,
            $actor,
            self::INVITATION_ACCEPTED,
            'Arcade-Einladung angenommen',
            "{$actorName} hat deine Spieleinladung zu {$gameName} angenommen.",
            "/arcade/matches/{$match->id}",
            $data,
        );
    }

    public function opponentReady(ArcadeMatch $match, User $actor): void
    {
        $match->loadMissing(['game', 'players.user']);
        $game = $match->game;

        if (! $game) {
            return;
        }

        $gameName = $this->germanGameName($game);
        $actorName = $this->userName($actor);

        foreach ($match->players as $participant) {
            $recipient = $participant->user;
            if (! $recipient || (int) $recipient->id === (int) $actor->id) {
                continue;
            }

            $data = array_merge($this->gameData($game), [
                'match_id' => (int) $match->id,
                'actor_id' => (int) $actor->id,
            ]);

            $this->send(
                "arcade:match:{$match->id}:ready:{$actor->id}:{$recipient->id}",
                $recipient,
                $actor,
                self::OPPONENT_READY,
                'Gegner bereit',
                "{$actorName} ist bereit für {$gameName}.",
                "/arcade/matches/{$match->id}",
                $data,
            );
        }
    }

    public function matchFinished(ArcadeMatch $match): void
    {
        $match->loadMissing(['game', 'players.user']);
        $game = $match->game;

        if (! $game || $match->status !== ArcadeMatchStatus::Finished) {
            return;
        }

        $gameName = $this->germanGameName($game);

        foreach ($match->players as $participant) {
            $recipient = $participant->user;
            if (! $recipient) {
                continue;
            }

            $result = $participant->result instanceof ArcadeMatchPlayerResult
                ? $participant->result
                : ArcadeMatchPlayerResult::tryFrom((string) $participant->result);

            if (! in_array($result, [ArcadeMatchPlayerResult::Win, ArcadeMatchPlayerResult::Loss, ArcadeMatchPlayerResult::Draw], true)) {
                continue;
            }

            [$type, $title, $body] = match ($result) {
                ArcadeMatchPlayerResult::Win => [self::MATCH_WON, 'Arcade-Match gewonnen', "Du hast {$gameName} gewonnen!"],
                ArcadeMatchPlayerResult::Loss => [self::MATCH_LOST, 'Arcade-Match verloren', "Du hast {$gameName} verloren."],
                ArcadeMatchPlayerResult::Draw => [self::MATCH_DRAW, 'Arcade-Match unentschieden', "{$gameName} endet unentschieden."],
                default => [null, null, null],
            };

            if (! $type) {
                continue;
            }

            $data = array_merge($this->gameData($game), [
                'match_id' => (int) $match->id,
                'result' => $result->value,
                'winner_seat' => $match->winner_seat !== null ? (int) $match->winner_seat : null,
            ]);

            $this->send(
                "arcade:match:{$match->id}:result:{$recipient->id}",
                $recipient,
                null,
                $type,
                (string) $title,
                (string) $body,
                "/arcade/matches/{$match->id}",
                $data,
            );
        }
    }

    private function gameData(ArcadeGame $game): array
    {
        $nameDe = trim((string) ($game->name_de ?: $game->name_en ?: $game->key));
        $nameEn = trim((string) ($game->name_en ?: $game->name_de ?: $game->key));

        return [
            'game_slug' => (string) $game->key,
            'game_name' => $nameEn,
            'game_name_de' => $nameDe,
            'game_name_en' => $nameEn,
        ];
    }

    private function germanGameName(ArcadeGame $game): string
    {
        return trim((string) ($game->name_de ?: $game->name_en ?: $game->key));
    }

    private function userName(User $user): string
    {
        return trim((string) ($user->name ?: $user->username ?: 'Hunter'));
    }

    private function send(
        string $dedupeKey,
        User $recipient,
        ?User $actor,
        string $type,
        string $title,
        string $body,
        string $actionUrl,
        array $data,
    ): void {
        try {
            $this->notifications->sendUnique(
                $dedupeKey,
                $recipient,
                $actor,
                $type,
                $title,
                $body,
                $actionUrl,
                $data,
            );
        } catch (Throwable $error) {
            Log::warning('Arcade notification dispatch failed.', [
                'type' => $type,
                'recipient_id' => $recipient->id,
                'dedupe_key' => $dedupeKey,
                'error' => $error->getMessage(),
            ]);
        }
    }
}
