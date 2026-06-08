<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $teamsCount = $this->active_teams_count ?? ($this->relationLoaded('activeTeams') ? $this->activeTeams->count() : 0);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->displaySummary(),
            'description' => $this->displayDescription(),
            'rules' => $this->displayRules(),
            'scoring_rules' => $this->displayScoringRules(),
            'prizes' => $this->prizeRows(),
            'prize_notes' => $this->prizeNotes(),
            // App/Web API: expose both maintained Cup language variants so native clients
            // can switch language locally without depending on the default API locale.
            'content' => [
                'de' => $this->localizedCupContent('de'),
                'en' => $this->localizedCupContent('en'),
            ],
            'platform' => $this->platform,
            'region' => $this->region,
            'language' => $this->language,
            'mode' => $this->isSoloLeaderboard() ? 'solo_leaderboard' : 'team_leaderboard',
            'mode_label' => $this->modeLabel(),
            'team_size' => (int) $this->team_size,
            'max_teams' => $this->max_teams ? (int) $this->max_teams : null,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'visibility' => $this->visibility,
            'cover_url' => $this->coverUrl(),
            'teams_count' => (int) $teamsCount,
            'registration_open' => $this->isRegistrationOpen(),
            'submission_open' => $this->isSubmissionOpen(),
            'submission_closed_reason' => $this->isSubmissionOpen() ? null : $this->submissionClosedReason(),
            'scoring' => [
                'points_per_bounty_token' => (int) config('hunthub.cups.points_per_bounty_token', 2),
                'points_per_kill' => (int) config('hunthub.cups.points_per_kill', 1),
                'require_extract_for_score' => (bool) config('hunthub.cups.require_extract_for_score', true),
                'require_bounty_for_score' => (bool) config('hunthub.cups.require_bounty_for_score', true),
            ],
            'owner' => new UserResource($this->whenLoaded('owner')),
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'registration_opens_at' => $this->registration_opens_at?->toISOString(),
            'registration_closes_at' => $this->registration_closes_at?->toISOString(),
        ];
    }

    private function localizedCupContent(string $locale): array
    {
        $defaults = $this->isBayouBloodCup() ? [
            'summary' => __('ui.cup_bayou_summary', [], $locale),
            'description' => __('ui.cup_bayou_intro_text', [], $locale),
            'rules' => __('ui.cup_bayou_rules_text', [], $locale),
            'scoring_rules' => __('ui.cup_info_scoring_text', [], $locale),
            'prizes' => [
                'first' => __('ui.cup_bayou_first_place_prize', [], $locale),
                'second' => __('ui.cup_bayou_second_place_prize', [], $locale),
                'third' => __('ui.cup_bayou_third_place_prize', [], $locale),
            ],
            'prize_note' => __('ui.cup_bayou_prize_note', [], $locale),
            'cashout_note' => __('ui.cup_default_cashout_note', [], $locale),
            'hall_of_fame_note' => __('ui.cup_default_hall_of_fame_note', [], $locale),
        ] : [
            'summary' => (string) $this->summary,
            'description' => '',
            'rules' => (string) $this->rules,
            'scoring_rules' => __('ui.cup_info_scoring_text', [], $locale),
            'prizes' => [],
            'prize_note' => '',
            'cashout_note' => '',
            'hall_of_fame_note' => '',
        ];

        return [
            'summary' => trim((string) $this->localizedContentSetting('summary', $defaults['summary'] ?? '', $locale)),
            'description' => trim((string) $this->localizedContentSetting('description', $defaults['description'] ?? '', $locale)),
            'rules' => trim((string) $this->localizedContentSetting('rules', $defaults['rules'] ?? '', $locale)),
            'scoring_rules' => trim((string) $this->localizedContentSetting('scoring_rules', $defaults['scoring_rules'] ?? '', $locale)),
            'prizes' => $this->localizedPrizeRows($locale, $defaults['prizes'] ?? []),
            'prize_notes' => $this->localizedPrizeNotes($locale, $defaults),
        ];
    }

    private function localizedPrizeRows(string $locale, array $defaults = []): array
    {
        $labels = [
            'first' => __('ui.cup_first_place', [], $locale),
            'second' => __('ui.cup_second_place', [], $locale),
            'third' => __('ui.cup_third_place', [], $locale),
        ];

        return collect($labels)
            ->map(function (string $label, string $place) use ($defaults, $locale): array {
                return [
                    'place' => $place,
                    'label' => $label,
                    'text' => trim((string) $this->localizedContentSetting('prizes.'.$place, $defaults[$place] ?? '', $locale)),
                ];
            })
            ->filter(fn (array $row): bool => $row['text'] !== '')
            ->values()
            ->all();
    }

    private function localizedPrizeNotes(string $locale, array $defaults = []): array
    {
        $labels = [
            'prize_note' => __('ui.cup_prize_note_title', [], $locale),
            'cashout_note' => __('ui.cup_cashout_note_title', [], $locale),
            'hall_of_fame_note' => __('ui.cup_hall_of_fame_note_title', [], $locale),
        ];

        return collect($labels)
            ->map(function (string $label, string $key) use ($defaults, $locale): array {
                return [
                    'key' => $key,
                    'label' => $label,
                    'text' => trim((string) $this->localizedContentSetting($key, $defaults[$key] ?? '', $locale)),
                ];
            })
            ->filter(fn (array $note): bool => $note['text'] !== '')
            ->values()
            ->all();
    }

}
