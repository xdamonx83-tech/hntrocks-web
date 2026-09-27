<?php

namespace App\Services;

use App\Models\ProfileModerationFlag;
use App\Models\UserProfile;
use Illuminate\Support\Str;

class ProfileModerationService
{
    private const MIN_FLAG_SCORE = 40;

    /**
     * Re-check the public free-text profile fields and keep the review queue in sync.
     *
     * @return array<int, ProfileModerationFlag>
     */
    public function scan(UserProfile $profile): array
    {
        $profile->loadMissing('user');

        $createdOrUpdated = [];

        foreach (['headline', 'bio'] as $field) {
            $content = trim((string) ($profile->{$field} ?? ''));
            $findings = $content === '' ? [] : $this->findings($content);
            $activeFingerprints = [];

            foreach ($findings as $finding) {
                if ($finding['score'] < self::MIN_FLAG_SCORE) {
                    continue;
                }

                $fingerprint = hash('sha256', implode('|', [
                    (string) $profile->id,
                    $field,
                    $finding['category'],
                    $this->normalizeForFingerprint($content),
                ]));

                $activeFingerprints[] = $fingerprint;

                $flag = ProfileModerationFlag::query()->firstOrNew([
                    'fingerprint' => $fingerprint,
                ]);

                if (! $flag->exists || in_array($flag->status, [
                    ProfileModerationFlag::STATUS_ACTIONED,
                    ProfileModerationFlag::STATUS_SUPERSEDED,
                ], true)) {
                    $flag->status = ProfileModerationFlag::STATUS_PENDING;
                    $flag->reviewed_by = null;
                    $flag->reviewed_at = null;
                    $flag->admin_note = null;
                }

                $flag->fill([
                    'user_id' => $profile->user_id,
                    'user_profile_id' => $profile->id,
                    'field' => $field,
                    'category' => $finding['category'],
                    'score' => $finding['score'],
                    'reason' => $finding['reason'],
                    'excerpt' => Str::limit($content, 320),
                    'source' => 'automatic',
                    'detected_at' => $flag->detected_at ?? now(),
                ])->save();

                $createdOrUpdated[] = $flag;
            }

            ProfileModerationFlag::query()
                ->where('user_profile_id', $profile->id)
                ->where('field', $field)
                ->where('source', 'automatic')
                ->where('status', ProfileModerationFlag::STATUS_PENDING)
                ->when(
                    $activeFingerprints !== [],
                    fn ($query) => $query->whereNotIn('fingerprint', $activeFingerprints)
                )
                ->update([
                    'status' => ProfileModerationFlag::STATUS_SUPERSEDED,
                    'reviewed_at' => now(),
                ]);
        }

        return $createdOrUpdated;
    }

    /**
     * Rules are intentionally conservative. A single ordinary swear word is not enough to create a flag.
     *
     * @return array<int, array{category:string, score:int, reason:string}>
     */
    private function findings(string $content): array
    {
        $findings = [];
        $lower = mb_strtolower($content);

        preg_match_all('~https?://[^\s<>]+|www\.[^\s<>]+~iu', $content, $urlMatches);
        $urls = array_values(array_unique($urlMatches[0] ?? []));

        if (count($urls) >= 2) {
            $findings[] = [
                'category' => 'spam_links',
                'score' => 55,
                'reason' => 'Mehrere externe Links im Profiltext.',
            ];
        }

        $promotionPattern = '~\b(buy|sell|cheap|discount|promo|promotion|free\s+skins?|boosting|accounts?\s+for\s+sale|kaufen|verkaufen|günstig|rabatt|gratis|angebot)\b~iu';
        if ($urls !== [] && preg_match($promotionPattern, $content)) {
            $findings[] = [
                'category' => 'spam_advertising',
                'score' => 80,
                'reason' => 'Werbe-/Verkaufsbegriffe zusammen mit externem Link erkannt.',
            ];
        }

        if (preg_match('~(?:t\.me/|telegram\.me/|wa\.me/|telegram\s*[:@])~iu', $content)
            && preg_match('~\b(dm|contact|message|write|schreib|kontakt|buy|sell|kaufen|verkaufen)\b~iu', $content)) {
            $findings[] = [
                'category' => 'off_platform_contact',
                'score' => 65,
                'reason' => 'Auffällige Aufforderung zur Kontaktaufnahme außerhalb von HNT.ROCKS.',
            ];
        }

        if (preg_match('~\b(kill\s+yourself|kys|go\s+die|bring\s+dich\s+um|verreck(?:e|t)?|geh\s+sterben)\b~iu', $content)) {
            $findings[] = [
                'category' => 'harassment_severe',
                'score' => 95,
                'reason' => 'Mögliche schwere persönliche Herabwürdigung oder Aufforderung zur Selbstverletzung.',
            ];
        }

        if (preg_match('~\b(i(?:\\'|’)ll\s+kill\s+you|i\s+will\s+kill\s+you|ich\s+(?:bring|mach)\s+dich\s+um|ich\s+töte\s+dich|te\s+voy\s+a\s+matar|я\s+тебя\s+убью)\b~iu', $content)) {
            $findings[] = [
                'category' => 'threats',
                'score' => 90,
                'reason' => 'Mögliche konkrete Drohung gegen andere Personen.',
            ];
        }

        if (preg_match('~\b(sieg\s+heil|heil\s+hitler|white\s+power|wei(?:ß|ss)e\s+macht|blood\s+and\s+honou?r|blut\s+und\s+ehre)\b~iu', $content)) {
            $findings[] = [
                'category' => 'hate_extremism',
                'score' => 95,
                'reason' => 'Möglicherweise menschenfeindlicher oder extremistischer Profilinhalt.',
            ];
        }

        if (preg_match('~\b(porn|porno|nudes?|onlyfans|sex\s*chat|nacktbilder|nacktfotos)\b~iu', $content)) {
            $findings[] = [
                'category' => 'sexual_content',
                'score' => 70,
                'reason' => 'Möglicherweise explizit sexueller oder pornografischer Profilinhalt.',
            ];
        }

        if (preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/iu', $content)
            || preg_match('~(?<!\d)(?:\+?\d[\s()./-]?){8,15}(?!\d)~u', $content)) {
            $findings[] = [
                'category' => 'personal_contact_data',
                'score' => 45,
                'reason' => 'Mögliche persönliche Kontaktangaben im öffentlichen Profil.',
            ];
        }

        if (mb_strlen($content) >= 80) {
            $letters = preg_replace('/[^\p{L}]/u', '', $content) ?? '';
            $upper = preg_replace('/[^\p{Lu}]/u', '', $content) ?? '';

            if (mb_strlen($letters) >= 40 && mb_strlen($upper) / max(1, mb_strlen($letters)) >= 0.85) {
                $findings[] = [
                    'category' => 'formatting_spam',
                    'score' => 40,
                    'reason' => 'Sehr hoher GROSSSCHRIFT-Anteil im Profiltext.',
                ];
            }
        }

        // Mild profanity can be normal in a gaming profile and intentionally remains below the queue threshold.
        if (preg_match('~\b(fuck|fucking|shit|schei(?:ß|ss)e|verdammt)\b~iu', $lower)) {
            $findings[] = [
                'category' => 'profanity',
                'score' => 15,
                'reason' => 'Derbe Sprache erkannt.',
            ];
        }

        return $this->deduplicateFindings($findings);
    }

    /**
     * @param array<int, array{category:string, score:int, reason:string}> $findings
     * @return array<int, array{category:string, score:int, reason:string}>
     */
    private function deduplicateFindings(array $findings): array
    {
        $byCategory = [];

        foreach ($findings as $finding) {
            if (! isset($byCategory[$finding['category']]) || $finding['score'] > $byCategory[$finding['category']]['score']) {
                $byCategory[$finding['category']] = $finding;
            }
        }

        return array_values($byCategory);
    }

    private function normalizeForFingerprint(string $content): string
    {
        return preg_replace('/\s+/u', ' ', mb_strtolower(trim($content))) ?? mb_strtolower(trim($content));
    }
}
