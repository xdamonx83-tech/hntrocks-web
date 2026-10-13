<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class AiCommunityDraftQualityGuard
{
    /** @param iterable<string> $recentBodies */
    public function validate(string $body, iterable $recentBodies, ?string $expectedEnding = null): void
    {
        $body = trim($body);
        if ($body === '' || mb_strlen($body) > 700 || preg_match('~(?:https?://|www\.|\b[a-z0-9-]+\.(?:com|net|org|gg|de|rocks)\b)~iu', $body)) {
            $this->reject('Generated post is empty, too long, or contains a link.');
        }
        // V5 model benchmark showed raw editorial metadata appended to public text:
        // "... . statement". Never silently strip it into a publishable draft.
        if (preg_match('~(?:[.!?]\\s*|\\s+)(?:statement|question|ending|style_key)\\s*[.!?]?\\s*$~iu', $body)) {
            $this->reject('Model leaked internal writing-style metadata into the draft.');
        }
        if ($expectedEnding === 'question' && ! str_ends_with($body, '?')) {
            $this->reject('Question-style draft does not end in a question.');
        }
        if ($expectedEnding === 'statement' && str_contains($body, '?')) {
            $this->reject('Statement-style draft unexpectedly contains a question.');
        }
        if (preg_match('~\\bchoke\\s+bombs?\\b.{0,100}\\b(?:crowd\\s+control|explosive\\s+(?:edge|damage)|blast\\s+damage)\\b~iu', $body)) {
            $this->reject('Model describes Choke Bomb effects inaccurately.');
        }

        // Reject known generated gibberish and repetitive synthetic sounding slogans.
        // Keep this list deliberately short: human review remains the main quality gate.
        if (preg_match('~\\b(?:laschen|überlebenskunst|hollywood[ -]stil)\\b|rückzug ist kein zeichen von schwäche~iu', $body)) {
            $this->reject('Generated text contains unnatural or inaccurate game wording.');
        }

        // '1896' is part of the game title, not a standalone weapon name.
        if (preg_match('~\\b(?:der|den|dem|ein|einen|eine|oder|the|a|an|my|with|vs)\\s+1896\\b|\\b1896\\s+(?:rifle|shotgun|gun|gewehr|schrotflinte)\\b~iu', $body)) {
            $this->reject('Generated post confuses the Hunt game title with a weapon.');
        }

        // Reject asserted past play or fabricated audience response, but allow hypotheticals such as "I'd rotate left".
        if (preg_match('~\b(?:I|we)\s+(?:just\s+)?(?:played|won|lost|queued|extracted|killed|clutched|saw|heard)\b|\b(?:I|we)\s+had\s+(?:a|an|the)\s+(?:match|game|round)\b|\b(?:ich|wir)\s+(?:habe(?:n)?|bin|sind)\s+(?:(?:gerade|gestern|heute)\s+)?(?:gespielt|gewonnen|verloren|extrahiert|gekillt|gesehen|gehört)\b|\b(?:my|our)\s+(?:last|latest)\s+(?:match|game|round)\b|\b(?:in\s+my|in\s+our)\s+(?:match|game|round)\b|\b(?:mein|unser)(?:e|em|en|er|es)?\s+letzte\w*\s+(?:match|runde)\b|\b(?:people|players|everyone)\s+(?:loved|liked|asked|told|agreed)\b~iu', $body)) {
            $this->reject('Generated post claims firsthand play or audience response.');
        }

        $tokens = $this->tokens($body);
        foreach ($recentBodies as $recent) {
            $other = $this->tokens($recent);
            if (implode(' ', $tokens) === implode(' ', $other)) {
                $this->reject('Duplicate draft blocked.');
            }
            $intersection = count(array_intersect($tokens, $other));
            $union = count(array_unique(array_merge($tokens, $other)));
            if ($union > 0 && $intersection >= 5 && $intersection / $union >= 0.72) {
                $this->reject('Near-duplicate draft blocked.');
            }
            // An identical opening across short posts often means a repeated template.
            if (count($tokens) >= 7 && count($other) >= 7 && array_slice($tokens, 0, 6) === array_slice($other, 0, 6)) {
                $this->reject('Repeated draft structure blocked.');
            }
        }
    }

    /** @return list<string> */
    private function tokens(string $body): array
    {
        preg_match_all('/[\p{L}\p{N}]+/u', mb_strtolower($body), $matches);
        // Keep short tokens: "in", "an", "am" and "of" carry meaning in short DE/EN posts.
        return array_values(array_unique($matches[0]));
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['generation' => $message]);
    }
}
