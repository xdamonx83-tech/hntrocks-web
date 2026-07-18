<?php

namespace Tests\Unit;

use App\Services\Guides\GuideContentService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class GuideContentServiceTest extends TestCase
{
    public function test_it_keeps_only_known_fields_and_normalizes_content(): void
    {
        $service = new GuideContentService();
        $blocks = $service->sanitizeBlocks([
            [
                'id' => 'safe-heading',
                'type' => 'heading',
                'level' => 9,
                'text' => '  Safe heading  ',
                'html' => '<script>alert(1)</script>',
            ],
            [
                'id' => 'list',
                'type' => 'list',
                'items' => [' First ', '', ' Second '],
                'arbitrary' => 'discarded',
            ],
        ]);

        $this->assertSame([
            'id' => 'safe-heading',
            'type' => 'heading',
            'level' => 4,
            'text' => 'Safe heading',
        ], $blocks[0]);
        $this->assertSame(['First', 'Second'], $blocks[1]['items']);
        $this->assertArrayNotHasKey('html', $blocks[0]);
        $this->assertArrayNotHasKey('arbitrary', $blocks[1]);
    }

    public function test_it_rejects_unknown_block_types(): void
    {
        $this->expectException(ValidationException::class);

        (new GuideContentService())->sanitizeBlocks([
            ['type' => 'iframe', 'src' => 'https://example.com'],
        ]);
    }

    public function test_reading_time_is_derived_from_structured_text(): void
    {
        $service = new GuideContentService();
        $blocks = [[
            'id' => 'body',
            'type' => 'paragraph',
            'text' => implode(' ', array_fill(0, 401, 'hunter')),
        ]];

        $this->assertSame(3, $service->readingTime($blocks));
    }
}
