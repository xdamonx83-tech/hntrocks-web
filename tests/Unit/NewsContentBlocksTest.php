<?php

namespace Tests\Unit;

use App\Models\NewsArticle;
use App\Rules\NewsContentBlocks;
use PHPUnit\Framework\TestCase;

class NewsContentBlocksTest extends TestCase
{
    public function test_plain_text_blocks_use_the_supported_structured_schema(): void
    {
        $errors = $this->validate([
            ['type' => 'paragraph', 'text' => 'The first paragraph.'],
            ['type' => 'heading', 'text' => 'The details', 'level' => 2],
            ['type' => 'list', 'style' => 'unordered', 'items' => ['One', 'Two']],
            ['type' => 'quote', 'text' => 'A short quote.', 'attribution' => 'A hunter'],
        ]);

        $this->assertSame([], $errors);
    }

    public function test_before_after_blocks_require_both_images_alt_text_and_labels(): void
    {
        $errors = $this->validate([[
            'type' => 'before_after',
            'before_media_id' => 12,
            'before_alt' => 'Before view',
            'before_label' => 'Before',
            'after_label' => 'After',
        ]]);

        $this->assertNotEmpty($errors);
    }

    public function test_unknown_block_types_and_extra_fields_are_rejected(): void
    {
        $this->assertNotEmpty($this->validate([['type' => 'html', 'html' => '<script>alert(1)</script>']]));
        $this->assertNotEmpty($this->validate([['type' => 'paragraph', 'text' => 'Safe text.', 'html' => '<b>extra</b>']]));
    }

    public function test_news_articles_support_only_the_four_editor_locales_and_workflow_states(): void
    {
        $this->assertSame(['de', 'en', 'es', 'ru'], NewsArticle::LOCALES);
        $this->assertSame(['draft', 'scheduled', 'published', 'archived'], NewsArticle::statuses());
    }

    private function validate(mixed $value): array
    {
        $errors = [];
        (new NewsContentBlocks())->validate('content_json', $value, function (string $message) use (&$errors): void {
            $errors[] = $message;
        });

        return $errors;
    }
}
