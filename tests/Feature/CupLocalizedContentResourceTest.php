<?php

namespace Tests\Feature;

use App\Http\Resources\Api\CupResource;
use App\Models\Cup;
use Illuminate\Http\Request;
use Tests\TestCase;

class CupLocalizedContentResourceTest extends TestCase
{
    public function test_cup_resource_exposes_all_four_supported_content_locales(): void
    {
        $cup = new Cup();
        $cup->forceFill([
            'id' => 42,
            'title' => 'Localization Test Cup',
            'slug' => 'localization-test-cup',
            'summary' => 'Deutsch legacy',
            'rules' => 'Deutsch legacy rules',
            'team_size' => 1,
            'status' => 'planned',
            'visibility' => 'public',
            'settings' => [
                'content' => [
                    'locales' => [
                        'de' => [
                            'summary' => 'Deutsche Kurzbeschreibung',
                            'description' => 'Deutsche Beschreibung',
                            'rules' => 'Deutsche Regeln',
                        ],
                        'en' => [
                            'summary' => 'English summary',
                            'description' => 'English description',
                            'rules' => 'English rules',
                            'scoring_rules' => 'English scoring',
                        ],
                        'es' => [
                            'summary' => 'Resumen español',
                            'description' => 'Descripción española',
                            'rules' => 'Reglas españolas',
                            'prizes' => [
                                'first' => 'Premio español',
                            ],
                            'prize_note' => 'Nota española',
                        ],
                        'ru' => [
                            'summary' => 'Русское описание',
                            'description' => 'Описание на русском',
                            'rules' => 'Правила на русском',
                            'prizes' => [
                                'first' => 'Русский приз',
                            ],
                            'prize_note' => 'Примечание на русском',
                        ],
                    ],
                ],
            ],
        ]);

        $payload = (new CupResource($cup))->toArray(Request::create('/api/v1/cups/localization-test-cup'));

        $this->assertSame(['de', 'en', 'es', 'ru'], array_keys($payload['content']));
        $this->assertSame('Resumen español', $payload['content']['es']['summary']);
        $this->assertSame('Descripción española', $payload['content']['es']['description']);
        $this->assertSame('Reglas españolas', $payload['content']['es']['rules']);
        $this->assertSame('Premio español', $payload['content']['es']['prizes'][0]['text']);
        $this->assertSame('Nota española', $payload['content']['es']['prize_notes'][0]['text']);

        $this->assertSame('Русское описание', $payload['content']['ru']['summary']);
        $this->assertSame('Описание на русском', $payload['content']['ru']['description']);
        $this->assertSame('Правила на русском', $payload['content']['ru']['rules']);
        $this->assertSame('Русский приз', $payload['content']['ru']['prizes'][0]['text']);
        $this->assertSame('Примечание на русском', $payload['content']['ru']['prize_notes'][0]['text']);
    }

    public function test_spanish_and_russian_content_fall_back_to_english_before_german(): void
    {
        $cup = new Cup();
        $cup->forceFill([
            'id' => 43,
            'title' => 'Fallback Test Cup',
            'slug' => 'fallback-test-cup',
            'summary' => 'Legacy Deutsch',
            'rules' => 'Legacy Deutsch rules',
            'team_size' => 1,
            'status' => 'planned',
            'visibility' => 'public',
            'settings' => [
                'content' => [
                    'locales' => [
                        'de' => [
                            'summary' => 'Deutsch',
                            'description' => 'Deutsch Beschreibung',
                            'rules' => 'Deutsch Regeln',
                        ],
                        'en' => [
                            'summary' => 'English fallback',
                            'description' => 'English description fallback',
                            'rules' => 'English rules fallback',
                            'scoring_rules' => 'English scoring fallback',
                        ],
                        'es' => [
                            'summary' => 'Resumen español',
                        ],
                        'ru' => [
                            'summary' => 'Русское описание',
                        ],
                    ],
                ],
            ],
        ]);

        $payload = (new CupResource($cup))->toArray(Request::create('/api/v1/cups/fallback-test-cup'));

        $this->assertSame('Resumen español', $payload['content']['es']['summary']);
        $this->assertSame('English description fallback', $payload['content']['es']['description']);
        $this->assertSame('English rules fallback', $payload['content']['es']['rules']);
        $this->assertSame('English scoring fallback', $payload['content']['es']['scoring_rules']);

        $this->assertSame('Русское описание', $payload['content']['ru']['summary']);
        $this->assertSame('English description fallback', $payload['content']['ru']['description']);
        $this->assertSame('English rules fallback', $payload['content']['ru']['rules']);
        $this->assertSame('English scoring fallback', $payload['content']['ru']['scoring_rules']);
    }
}
