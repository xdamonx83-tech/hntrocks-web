<?php

namespace App\Services;

/**
 * Conservative editorial attention flags, not a factual correctness verdict.
 *
 * Only captures a few narrow claim patterns seen in private Hunt previews.
 * This scanner has no news source, game API, network access or model dependency.
 * A false positive is possible. The human editor retains the final decision.
 */
final class AiCommunityClaimRiskInspector
{
    /** @return list<array{code: string, reason: string}> */
    public static function inspect(string $body): array
    {
        $flags = [];

        // The alerting birds are ambient triggers, not a universal indicator
        // that opponents are simply nearby. Don't present this as a proven fact.
        if (preg_match('~\\b(?:krähen|kraehen|crows?)\\b.{0,125}\\b(?:nah\\s+an\\s+gegnern|gegner\\s+in\\s+der\\s+nähe|near(?:by)?\\s+(?:enemy|enemies|opponents)|(?:enemy|enemies|opponents)\\s+(?:are\\s+)?near(?:by)?)\\b~iu', $body)) {
            $flags[] = [
                'code' => 'crow_proximity',
                'reason' => 'Krähen/Gegnernähe: Auslöser und behaupteten Zusammenhang manuell prüfen.',
            ];
        }

        // UI/tool effects can change between patches. Avoid treating proposed
        // timers/counters as either already present or confirmed future features.
        if (preg_match('~\\b(?:choke\\s*bombs?|chokes?)\\b.{0,130}\\b(?:timers?|countdowns?|cooldowns?|active\\s+effects?|effekt\\w*|anzeige\\w*|zeitanzeige\\w*)\\b|\\b(?:timers?|countdowns?|cooldowns?|zeitanzeige\\w*|effekt\\w*)\\b.{0,130}\\b(?:choke\\s*bombs?|chokes?)\\b~iu', $body)) {
            $flags[] = [
                'code' => 'choke_timer',
                'reason' => 'Choke-Bomb-Timer oder Tool-Effektanzeige: aktuelle Spielmechanik/UI prüfen.',
            ];
        }

        // Patch dates, announced changes and exact stats require a verified
        // external source before they can be stated as confirmed.
        if (preg_match('~\\b(?:patch\\s*\\d+(?:[.\\-]\\d+)*|update\\s*\\d+(?:[.\\-]\\d+)*|hotfix\\s*\\d+(?:[.\\-]\\d+)*|bestätigt(?:es)?\\s+update|officially\\s+(?:confirmed|announced))\\b~iu', $body)) {
            $flags[] = [
                'code' => 'version_or_news',
                'reason' => 'Konkrete Patch- oder Ankündigungsbehauptung: Originalquelle vor Freigabe prüfen.',
            ];
        }

        return $flags;
    }
}
