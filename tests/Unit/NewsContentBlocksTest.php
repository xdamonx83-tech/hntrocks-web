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

    public function test_empty_paragraphs_and_hard_breaks_survive_validation_and_server_rendering(): void
    {
        foreach ([0, 1, 4] as $emptyCount) {
            $content = [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'A']]]];
            for ($index = 0; $index < $emptyCount; $index++) $content[] = ['type' => 'paragraph'];
            $content[] = ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'B']]];
            $document = ['schema_version' => 2, 'type' => 'doc', 'content' => $content];
            $this->assertSame([], NewsContentDocument::validate($document));
            $html = NewsContentDocument::render($document, []);
            $this->assertSame($emptyCount, substr_count($html, 'class="news-empty-paragraph"'));
            $this->assertSame($emptyCount, substr_count($html, '<br>'));
            $this->assertSame($content, $document['content']);
            $expectedText = 'A'.str_repeat("\n", $emptyCount + 1).'B';
            $this->assertSame($expectedText, NewsContentDocument::plainText($document));
        }

        $hardBreak = ['schema_version' => 2, 'type' => 'doc', 'content' => [[
            'type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'First'], ['type' => 'hardBreak'], ['type' => 'text', 'text' => 'Second']],
        ]]];
        $this->assertSame([], NewsContentDocument::validate($hardBreak));
        $this->assertSame('<p>First<br>Second</p>', NewsContentDocument::render($hardBreak, []));
    }

    public function test_media_text_keeps_repeated_empty_paragraphs_and_legacy_newlines(): void
    {
        $layout = ['type' => 'hntMediaTextLayout', 'attrs' => ['mediaAssetId' => 33, 'mediaType' => 'image', 'posterAssetId' => null, 'position' => 'left', 'ratio' => '50-50', 'alt' => '', 'caption' => ''], 'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'A']]],
            ['type' => 'paragraph'], ['type' => 'paragraph'], ['type' => 'paragraph'],
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'B']]],
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Heading']]],
            ['type' => 'bulletList', 'content' => [['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'List item']]]]]]],
        ]];
        $document = ['schema_version' => 2, 'type' => 'doc', 'content' => [$layout]];
        $this->assertSame([33 => 'image'], NewsContentDocument::validate($document));
        $html = NewsContentDocument::render($document, [33 => ['url' => '/media/image']]);
        $this->assertSame(3, substr_count($html, 'class="news-empty-paragraph"'));
        $this->assertStringContainsString('<h2>Heading</h2><ul><li><p>List item</p></li></ul>', $html);

        $legacy = $document;
        $legacy['content'][0] = ['type' => 'hntMediaTextLayout', 'attrs' => ['mediaAssetId' => 33, 'mediaType' => 'image', 'posterAssetId' => null, 'position' => 'left', 'ratio' => '50-50', 'alt' => '', 'caption' => '', 'text' => "A\n\n\nB"]];
        $legacyHtml = NewsContentDocument::render($legacy, [33 => ['url' => '/media/image']]);
        $this->assertSame([33 => 'image'], NewsContentDocument::validate($legacy));
        $this->assertSame(2, substr_count($legacyHtml, 'class="news-empty-paragraph"'));

        $textContent = $legacy;
        $textContent['content'][0]['attrs']['textContent'] = ['A', '', 'B'];
        unset($textContent['content'][0]['attrs']['text']);
        $this->assertSame([33 => 'image'], NewsContentDocument::validate($textContent));
        $this->assertSame(1, substr_count(NewsContentDocument::render($textContent, [33 => ['url' => '/media/image']]), 'class="news-empty-paragraph"'));
    }

    public function test_hnt_dividers_are_enum_backed_decorative_and_render_responsively(): void
    {
        foreach (['large', 'small'] as $variant) {
            $document = ['schema_version' => 2, 'type' => 'doc', 'content' => [['type' => 'hntDivider', 'attrs' => ['variant' => $variant]]]];
            $this->assertSame([], NewsContentDocument::validate($document));
            $html = NewsContentDocument::render($document, []);
            $asset = $variant === 'large' ? 'divider-big.webp' : 'divider-small.webp';
            $this->assertStringContainsString('news-hnt-divider-'.$variant, $html);
            $this->assertStringContainsString('/assets/news/dividers/'.$asset, $html);
            $this->assertStringContainsString('alt="" aria-hidden="true"', $html);
            $this->assertStringNotContainsString($variant, NewsContentDocument::plainText($document));
        }
        $invalid = ['schema_version' => 2, 'type' => 'doc', 'content' => [['type' => 'hntDivider', 'attrs' => ['variant' => 'https://evil.test/image.png']]]];
        $this->assertNotEmpty($this->validate($invalid));
    }

    public function test_v2_normal_quotes_keep_paragraphs_and_hard_breaks(): void
    {
        $document = ['schema_version' => 2, 'type' => 'doc', 'content' => [[
            'type' => 'blockquote', 'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'First line']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Second line'], ['type' => 'hardBreak'], ['type' => 'text', 'text' => 'Third line']]],
            ],
        ]]];
        $this->assertSame([], NewsContentDocument::validate($document));
        $this->assertSame("First line\nSecond line\nThird line", NewsContentDocument::plainText($document));
        $this->assertSame('<blockquote><p>First line</p><p>Second line<br>Third line</p></blockquote>', NewsContentDocument::render($document, []));
    }

    public function test_v2_font_sizes_accept_only_presets_and_reset_by_removing_the_mark(): void
    {
        foreach (['14px', '16px', '18px', '20px', '24px', '28px', '32px'] as $size) {
            $document = ['schema_version' => 2, 'type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [
                ['type' => 'text', 'text' => 'Sized', 'marks' => [['type' => 'hntFontSize', 'attrs' => ['size' => $size]]]],
            ]]]];
            $this->assertSame([], NewsContentDocument::validate($document));
            $this->assertStringContainsString('font-size:'.$size, NewsContentDocument::render($document, []));
        }
        $reset = ['schema_version' => 2, 'type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Default']]]]];
        $this->assertSame('<p>Default</p>', NewsContentDocument::render($reset, []));
        foreach (['13px', '14px;color:red', 'calc(100px)', '1rem'] as $size) {
            $invalid = ['schema_version' => 2, 'type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [
                ['type' => 'text', 'text' => 'Unsafe', 'marks' => [['type' => 'hntFontSize', 'attrs' => ['size' => $size]]]],
            ]]]];
            $this->assertNotEmpty($this->validate($invalid));
        }
    }

    public function test_v2_media_text_accepts_each_ratio_both_positions_video_and_rich_text(): void
    {
        foreach (['33-67', '40-60', '50-50', '60-40', '67-33'] as $ratio) foreach (['left', 'right'] as $position) {
            $heading = ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Bayou']]];
            $formatted = ['type' => 'text', 'text' => 'Hunt', 'marks' => [['type' => 'bold'], ['type' => 'hntFontSize', 'attrs' => ['size' => '20px']]]];
            $paragraph = ['type' => 'paragraph', 'content' => [$formatted]];
            $layout = ['type' => 'hntMediaTextLayout', 'attrs' => ['mediaAssetId' => 30, 'mediaType' => 'video', 'posterAssetId' => null, 'position' => $position, 'ratio' => $ratio, 'alt' => '', 'caption' => 'Trailer'], 'content' => [$heading, $paragraph]];
            $document = ['schema_version' => 2, 'type' => 'doc', 'content' => [$layout]];
            $this->assertSame([30 => 'video'], NewsContentDocument::validate($document));
            $this->assertStringContainsString('ratio-'.$ratio, NewsContentDocument::render($document, [30 => ['url' => '/media/video']]));
            $this->assertStringContainsString('font-size:20px', NewsContentDocument::render($document, [30 => ['url' => '/media/video']]));
        }
    }

    public function test_v2_media_text_keeps_multiple_paragraphs_for_both_column_orders(): void
    {
        foreach (['left', 'right'] as $position) {
            $layout = ['type' => 'hntMediaTextLayout', 'attrs' => ['mediaAssetId' => 31, 'mediaType' => 'image', 'posterAssetId' => null, 'position' => $position, 'ratio' => '50-50', 'alt' => 'Hunter', 'caption' => ''], 'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Paragraph one.']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Paragraph two.']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Paragraph three.'], ['type' => 'hardBreak'], ['type' => 'text', 'text' => 'Still paragraph three.']]],
            ]];
            $document = ['schema_version' => 2, 'type' => 'doc', 'content' => [$layout]];
            $html = NewsContentDocument::render($document, [31 => ['url' => '/media/image']]);
            $this->assertSame([31 => 'image'], NewsContentDocument::validate($document));
            $this->assertStringContainsString('news-media-text '.$position.' ratio-50-50', $html);
            $this->assertStringContainsString('<div class="news-media-text-copy"><p>Paragraph one.</p><p>Paragraph two.</p><p>Paragraph three.<br>Still paragraph three.</p></div>', $html);
        }
    }

    public function test_legacy_flat_media_text_renders_but_placeholder_is_never_saved_or_rendered(): void
    {
        $legacy = ['schema_version' => 2, 'type' => 'doc', 'content' => [[
            'type' => 'hntMediaTextLayout', 'attrs' => ['mediaAssetId' => 32, 'mediaType' => 'image', 'posterAssetId' => null, 'position' => 'right', 'ratio' => '40-60', 'alt' => '', 'caption' => '', 'text' => "First paragraph.\n\nSecond paragraph."],
        ]]];
        $this->assertSame([32 => 'image'], NewsContentDocument::validate($legacy));
        $legacyHtml = NewsContentDocument::render($legacy, [32 => ['url' => '/media/image']]);
        $this->assertStringContainsString('<div class="news-media-text-copy"><p>First paragraph.</p><p class="news-empty-paragraph"><br></p><p>Second paragraph.</p></div>', $legacyHtml);

        foreach (["Click here and write your story...", "Click here and write your story…"] as $placeholder) {
            $legacy['content'][0]['attrs']['text'] = $placeholder;
            $this->assertNotEmpty($this->validate($legacy));
            $this->assertStringNotContainsString($placeholder, NewsContentDocument::render($legacy, [32 => ['url' => '/media/image']]));
            $placeholderNode = ['schema_version' => 2, 'type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $placeholder]]]]];
            $this->assertNotEmpty($this->validate($placeholderNode));
            $this->assertStringNotContainsString($placeholder, NewsContentDocument::render($placeholderNode, []));
        }
    }

    public function test_v2_hnt_quote_renders_multiline_text_with_optional_background_and_author(): void
    {
        $quote = ['type' => 'hntQuote', 'attrs' => ['author' => 'HNT Team', 'backgroundMediaAssetId' => null, 'backgroundPosition' => 'center', 'overlay' => 60], 'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'First']]],
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Second'], ['type' => 'hardBreak'], ['type' => 'text', 'text' => 'Third']]],
        ]];
        $document = ['schema_version' => 2, 'type' => 'doc', 'content' => [$quote]];
        $this->assertSame([], NewsContentDocument::validate($document));
        $this->assertStringContainsString('<blockquote><p>First</p><p>Second<br>Third</p></blockquote><cite>HNT Team</cite>', NewsContentDocument::render($document, []));
        $quote['attrs']['backgroundMediaAssetId'] = 40;
        $quote['attrs']['backgroundPosition'] = 'top';
        $quote['attrs']['overlay'] = 80;
        $document['content'] = [$quote];
        $this->assertSame([40 => 'image'], NewsContentDocument::validate($document));
        $this->assertSame([40], NewsContentDocument::mediaIds($document));
        $this->assertStringContainsString('src="/media/quote"', NewsContentDocument::render($document, [40 => ['url' => '/media/quote']]));
        foreach ([['overlay' => 75], ['backgroundPosition' => 'left; color:red'], ['backgroundMediaAssetId' => 0]] as $invalidAttrs) {
            $document['content'][0]['attrs'] = array_merge($quote['attrs'], $invalidAttrs);
            $this->assertNotEmpty($this->validate($document));
        }
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
