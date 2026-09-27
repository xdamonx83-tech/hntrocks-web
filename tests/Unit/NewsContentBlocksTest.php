<?php

namespace Tests\Unit;

use App\Models\NewsArticle;
use App\Rules\NewsContentBlocks;
use App\Support\NewsContentDocument;
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

    public function test_v2_document_accepts_safe_marks_and_keeps_legacy_blocks_valid(): void
    {
        $document = ['schema_version' => 2, 'type' => 'doc', 'content' => [
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Heading']]],
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Link', 'marks' => [
                ['type' => 'bold'], ['type' => 'link', 'attrs' => ['href' => 'https://hnt.rocks/news']],
            ]]]],
        ]];
        $this->assertSame([], $this->validate($document));
        $this->assertTrue(NewsContentDocument::hasContent($document));
        $this->assertSame('Heading' . "\n" . 'Link', NewsContentDocument::plainText($document));
        $this->assertSame([], $this->validate([['type' => 'paragraph', 'text' => 'Legacy article.']]));
    }

    public function test_v2_document_rejects_script_nodes_html_and_javascript_links(): void
    {
        foreach ([
            ['type' => 'script', 'text' => 'alert(1)'],
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Bad', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]]]]],
            ['type' => 'paragraph', 'attrs' => ['onclick' => 'alert(1)'], 'content' => []],
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Bad', 'marks' => [['type' => 'style', 'attrs' => ['color' => 'red']]]]]],
        ] as $node) {
            $this->assertNotEmpty($this->validate(['schema_version' => 2, 'type' => 'doc', 'content' => [$node]]));
        }
    }

    public function test_v2_document_accepts_tiptap_list_and_link_attributes(): void
    {
        $document = ['schema_version' => 2, 'type' => 'doc', 'content' => [
            ['type' => 'orderedList', 'attrs' => ['start' => 1, 'type' => null], 'content' => [
                ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'attrs' => ['textAlign' => null], 'content' => [
                    ['type' => 'text', 'text' => 'Read', 'marks' => [['type' => 'link', 'attrs' => [
                        'href' => 'https://hnt.rocks/news', 'target' => '_blank', 'rel' => 'noopener noreferrer nofollow', 'class' => null, 'title' => null,
                    ]]]],
                ]]]],
            ]],
        ]];
        $this->assertSame([], NewsContentDocument::validate($document));
        $this->assertSame([], $this->validate($document));
    }

    public function test_v2_media_nodes_expose_every_asset_id(): void
    {
        $document = ['schema_version' => 2, 'type' => 'doc', 'content' => [
            ['type' => 'hntImage', 'attrs' => ['mediaAssetId' => 11, 'alt' => 'Hunter', 'caption' => '', 'alignment' => 'left', 'width' => '33%', 'layout' => 'float']],
            ['type' => 'hntVideo', 'attrs' => ['mediaAssetId' => 12, 'posterAssetId' => 13, 'caption' => '', 'alignment' => 'center', 'width' => '100%', 'layout' => 'wide']],
            ['type' => 'hntBeforeAfter', 'attrs' => ['beforeAssetId' => 14, 'afterAssetId' => 15, 'beforeAlt' => 'Before', 'afterAlt' => 'After', 'beforeLabel' => 'Before', 'afterLabel' => 'After', 'caption' => 'Compare']],
            ['type' => 'hntGallery', 'attrs' => ['columns' => 2, 'items' => [['mediaAssetId' => 16, 'alt' => 'One', 'caption' => ''], ['mediaAssetId' => 17, 'alt' => 'Two', 'caption' => '']]]],
            ['type' => 'hntMediaTextLayout', 'attrs' => ['mediaAssetId' => 18, 'mediaType' => 'image', 'posterAssetId' => null, 'position' => 'right', 'ratio' => '40-60', 'alt' => 'Media', 'caption' => ''], 'content' => [['type' => 'paragraph']]],
        ]];
        $this->assertSame([11 => 'image', 12 => 'video', 13 => 'image', 14 => 'image', 15 => 'image', 16 => 'image', 17 => 'image', 18 => 'image'], NewsContentDocument::validate($document));
        $this->assertSame([11, 12, 13, 14, 15, 16, 17, 18], NewsContentDocument::mediaIds($document));
    }

    public function test_v2_server_rendering_keeps_text_crawlable_and_escapes_it(): void
    {
        $document = ['schema_version' => 2, 'type' => 'doc', 'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => '<script>alert(1)</script>']]],
        ]];
        $html = NewsContentDocument::render($document, []);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
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
