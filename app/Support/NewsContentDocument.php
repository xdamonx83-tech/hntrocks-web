<?php

namespace App\Support;

use InvalidArgumentException;

class NewsContentDocument
{
    private const EDITOR_PLACEHOLDERS = ['click here and write your story...', 'click here and write your story…'];
    private const WIDTHS = ['25%', '33%', '40%', '50%', '60%', '66%', '75%', '100%'];
    private const ALIGNMENTS = ['left', 'center', 'right'];
    private const LAYOUTS = ['normal', 'wide', 'full', 'float'];
    private const RATIOS = ['33-67', '40-60', '50-50', '60-40', '67-33'];
    private const FONT_SIZES = ['14px', '16px', '18px', '20px', '24px', '28px', '32px'];

    public static function isV2(mixed $value): bool
    {
        return is_array($value) && ($value['schema_version'] ?? null) === 2;
    }

    public static function validate(array $document): array
    {
        self::keys($document, ['schema_version', 'type', 'content']);
        if (($document['schema_version'] ?? null) !== 2 || ($document['type'] ?? null) !== 'doc'
            || ! self::list($document['content'] ?? null, 0, 150)) {
            throw new InvalidArgumentException('Invalid V2 News document.');
        }
        $assets = [];
        $count = 0;
        foreach ($document['content'] as $node) self::node($node, 0, $count, $assets);
        return $assets;
    }

    public static function hasContent(array $document): bool
    {
        if (! self::isV2($document)) return $document !== [];
        foreach ($document['content'] ?? [] as $node) {
            if (in_array($node['type'] ?? null, ['hntImage', 'hntVideo', 'hntBeforeAfter', 'hntGallery', 'hntMediaTextLayout'], true)) return true;
            if (trim(self::plainNode($node)) !== '') return true;
        }
        return false;
    }

    public static function mediaIds(array $document): array
    {
        if (! self::isV2($document)) {
            $ids = [];
            foreach ($document as $block) foreach (['media_id', 'poster_media_id', 'before_media_id', 'after_media_id'] as $key) {
                if (! empty($block[$key])) $ids[] = (int) $block[$key];
            }
            return array_values(array_unique($ids));
        }
        $ids = [];
        $walk = function (array $nodes) use (&$walk, &$ids): void {
            foreach ($nodes as $node) {
                $attrs = $node['attrs'] ?? [];
                foreach (['mediaAssetId', 'posterAssetId', 'beforeAssetId', 'afterAssetId', 'backgroundMediaAssetId'] as $key) {
                    if (! empty($attrs[$key])) $ids[] = (int) $attrs[$key];
                }
                foreach ($attrs['items'] ?? [] as $item) if (! empty($item['mediaAssetId'])) $ids[] = (int) $item['mediaAssetId'];
                if (is_array($node['content'] ?? null)) $walk($node['content']);
            }
        };
        $walk($document['content'] ?? []);
        return array_values(array_unique($ids));
    }

    public static function plainText(array $document): string
    {
        return implode("\n", array_map(self::plainNode(...), $document['content'] ?? []));
    }

    public static function render(array $document, array $media): string
    {
        if (! self::isV2($document)) return '';
        return implode('', array_map(fn ($node) => self::renderNode($node, $media), $document['content'] ?? []));
    }

    private static function node(mixed $node, int $depth, int &$count, array &$assets): void
    {
        if (! is_array($node) || $depth > 8 || ++$count > 1000 || ! is_string($node['type'] ?? null)) throw new InvalidArgumentException('Invalid News node.');
        self::keys($node, ['type', 'attrs', 'content', 'text', 'marks']);
        $type = $node['type'];
        $attrs = $node['attrs'] ?? [];
        if (! is_array($attrs) || array_is_list($attrs) && $attrs !== []) throw new InvalidArgumentException('Invalid News attributes.');
        $children = $node['content'] ?? [];
        if ($type === 'text') {
            self::keys($node, ['type', 'text', 'marks']);
            if (! is_string($node['text'] ?? null) || $node['text'] === '' || mb_strlen($node['text']) > 20000 || self::isEditorPlaceholder($node['text'])) throw new InvalidArgumentException('Invalid News text.');
            if (! self::list($node['marks'] ?? [], 0, 12)) throw new InvalidArgumentException('Invalid News marks.');
            foreach ($node['marks'] ?? [] as $mark) self::mark($mark);
            return;
        }
        if (in_array($type, ['paragraph', 'heading', 'blockquote', 'bulletList', 'orderedList', 'listItem', 'hntMediaTextLayout', 'hntQuote'], true)
            && ! self::list($children, 0, 150)) throw new InvalidArgumentException('Invalid News children.');
        if (! in_array($type, ['paragraph', 'heading', 'blockquote', 'bulletList', 'orderedList', 'listItem', 'horizontalRule', 'hardBreak', 'hntImage', 'hntVideo', 'hntBeforeAfter', 'hntGallery', 'hntMediaTextLayout', 'hntQuote'], true)) throw new InvalidArgumentException('Unsupported News node.');
        if (in_array($type, ['horizontalRule', 'hardBreak'], true)) { self::keys($node, ['type']); return; }
        if ($type === 'paragraph' || $type === 'heading') {
            self::keys($attrs, $type === 'heading' ? ['level', 'textAlign'] : ['textAlign']);
            if (isset($attrs['textAlign']) && ! in_array($attrs['textAlign'], self::ALIGNMENTS, true)) throw new InvalidArgumentException('Invalid alignment.');
            if ($type === 'heading' && ! in_array($attrs['level'] ?? null, [2, 3], true)) throw new InvalidArgumentException('Invalid heading.');
        } elseif ($type === 'orderedList') {
            self::keys($attrs, ['start', 'type']);
            if (isset($attrs['start']) && (! is_int($attrs['start']) || $attrs['start'] < 1)) throw new InvalidArgumentException('Invalid list start.');
            if (isset($attrs['type']) && ! in_array($attrs['type'], ['1', 'a', 'A', 'i', 'I'], true)) throw new InvalidArgumentException('Invalid list type.');
        } elseif ($type === 'hntImage' || $type === 'hntVideo') {
            self::keys($attrs, $type === 'hntImage' ? ['mediaAssetId', 'alt', 'caption', 'alignment', 'width', 'layout'] : ['mediaAssetId', 'posterAssetId', 'caption', 'alignment', 'width', 'layout']);
            self::mediaAttr($attrs, 'mediaAssetId', $type === 'hntImage' ? 'image' : 'video', $assets);
            if ($type === 'hntImage') self::string($attrs['alt'] ?? null, 300);
            if ($type === 'hntVideo' && ! empty($attrs['posterAssetId'])) self::mediaAttr($attrs, 'posterAssetId', 'image', $assets);
            self::visualAttrs($attrs);
        } elseif ($type === 'hntBeforeAfter') {
            self::keys($attrs, ['beforeAssetId', 'afterAssetId', 'beforeAlt', 'afterAlt', 'beforeLabel', 'afterLabel', 'caption']);
            self::mediaAttr($attrs, 'beforeAssetId', 'image', $assets);
            self::mediaAttr($attrs, 'afterAssetId', 'image', $assets);
            foreach (['beforeAlt', 'afterAlt', 'beforeLabel', 'afterLabel'] as $field) self::string($attrs[$field] ?? null, 300);
            self::string($attrs['caption'] ?? '', 1000);
        } elseif ($type === 'hntGallery') {
            self::keys($attrs, ['columns', 'items']);
            if (! in_array($attrs['columns'] ?? null, [2, 3], true) || ! self::list($attrs['items'] ?? null, 2, 24)) throw new InvalidArgumentException('Invalid gallery.');
            foreach ($attrs['items'] as $item) {
                if (! is_array($item)) throw new InvalidArgumentException('Invalid gallery item.');
                self::keys($item, ['mediaAssetId', 'alt', 'caption']);
                self::mediaAttr($item, 'mediaAssetId', 'image', $assets);
                self::string($item['alt'] ?? null, 300);
                self::string($item['caption'] ?? '', 1000);
            }
        } elseif ($type === 'hntMediaTextLayout') {
            self::keys($attrs, ['mediaAssetId', 'mediaType', 'posterAssetId', 'position', 'ratio', 'alt', 'caption', 'text']);
            if (! in_array($attrs['mediaType'] ?? null, ['image', 'video'], true) || ! in_array($attrs['position'] ?? null, ['left', 'right'], true)
                || ! in_array($attrs['ratio'] ?? null, self::RATIOS, true)) throw new InvalidArgumentException('Invalid media text layout.');
            self::mediaAttr($attrs, 'mediaAssetId', $attrs['mediaType'], $assets);
            if (! empty($attrs['posterAssetId'])) self::mediaAttr($attrs, 'posterAssetId', 'image', $assets);
            self::string($attrs['alt'] ?? '', 300);
            self::string($attrs['caption'] ?? '', 1000);
            if (array_key_exists('text', $attrs)) {
                self::string($attrs['text'], 20000);
                if (self::isEditorPlaceholder($attrs['text'])) throw new InvalidArgumentException('Editor placeholder cannot be saved.');
            }
        } elseif ($type === 'hntQuote') {
            self::keys($attrs, ['author', 'backgroundMediaAssetId', 'backgroundPosition', 'overlay']);
            self::string($attrs['author'] ?? '', 240);
            if (! in_array($attrs['backgroundPosition'] ?? null, ['center', 'top', 'bottom'], true)
                || ! in_array($attrs['overlay'] ?? null, [40, 60, 80], true)) throw new InvalidArgumentException('Invalid HNT quote appearance.');
            if (($attrs['backgroundMediaAssetId'] ?? null) !== null) self::mediaAttr($attrs, 'backgroundMediaAssetId', 'image', $assets);
        } else self::keys($attrs, []);

        if (in_array($type, ['hntImage', 'hntVideo', 'hntBeforeAfter', 'hntGallery'], true) && isset($node['content'])) throw new InvalidArgumentException('Media nodes cannot have children.');
        if ($type === 'blockquote' || $type === 'bulletList' || $type === 'orderedList' || $type === 'listItem' || $type === 'hntMediaTextLayout' || $type === 'hntQuote') {
            foreach ($children as $child) self::node($child, $depth + 1, $count, $assets);
        } elseif (in_array($type, ['paragraph', 'heading'], true)) {
            foreach ($children as $child) {
                if (! in_array($child['type'] ?? null, ['text', 'hardBreak'], true)) throw new InvalidArgumentException('Invalid inline News node.');
                self::node($child, $depth + 1, $count, $assets);
            }
        } elseif (isset($node['content'])) throw new InvalidArgumentException('Unexpected News children.');
    }

    private static function mark(mixed $mark): void
    {
        if (! is_array($mark)) throw new InvalidArgumentException('Invalid mark.');
        self::keys($mark, ['type', 'attrs']);
        if (! in_array($mark['type'] ?? null, ['bold', 'italic', 'underline', 'strike', 'link', 'code', 'hntFontSize'], true)) throw new InvalidArgumentException('Unsupported mark.');
        if (($mark['type'] ?? null) === 'link') {
            self::keys($mark['attrs'] ?? [], ['href', 'target', 'rel', 'class', 'title']);
            $href = $mark['attrs']['href'] ?? null;
            if (! is_string($href) || strlen($href) > 2048 || ! self::safeHref($href)) throw new InvalidArgumentException('Invalid link.');
            foreach (['target', 'rel', 'class', 'title'] as $key) if (isset($mark['attrs'][$key]) && (! is_string($mark['attrs'][$key]) || mb_strlen($mark['attrs'][$key]) > 300)) throw new InvalidArgumentException('Invalid link attribute.');
        } elseif (($mark['type'] ?? null) === 'hntFontSize') {
            self::keys($mark['attrs'] ?? [], ['size']);
            if (! in_array($mark['attrs']['size'] ?? null, self::FONT_SIZES, true)) throw new InvalidArgumentException('Invalid News font size.');
        } else self::keys($mark['attrs'] ?? [], []);
    }

    private static function mediaAttr(array $attrs, string $key, string $type, array &$assets): void
    {
        $id = $attrs[$key] ?? null;
        if (! is_int($id) || $id < 1) throw new InvalidArgumentException('Invalid media ID.');
        if (isset($assets[$id]) && $assets[$id] !== $type) throw new InvalidArgumentException('Incompatible media type.');
        $assets[$id] = $type;
    }

    private static function visualAttrs(array $attrs): void
    {
        if (! in_array($attrs['alignment'] ?? null, self::ALIGNMENTS, true) || ! in_array($attrs['width'] ?? null, self::WIDTHS, true)
            || ! in_array($attrs['layout'] ?? null, self::LAYOUTS, true)) throw new InvalidArgumentException('Invalid media layout.');
        self::string($attrs['caption'] ?? '', 1000);
    }

    private static function safeHref(string $href): bool
    {
        if (str_starts_with($href, '/') && ! str_starts_with($href, '//') && ! preg_match('/[\x00-\x1F\x7F\\\\]/', $href)) return true;
        return filter_var($href, FILTER_VALIDATE_URL) !== false && in_array(strtolower((string) parse_url($href, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    private static function string(mixed $value, int $max): void
    {
        if (! is_string($value) || mb_strlen($value) > $max) throw new InvalidArgumentException('Invalid News string.');
    }

    private static function isEditorPlaceholder(string $value): bool
    {
        return in_array(mb_strtolower(trim($value)), self::EDITOR_PLACEHOLDERS, true);
    }

    private static function keys(array $value, array $allowed): void
    {
        if (array_diff(array_keys($value), $allowed) !== []) throw new InvalidArgumentException('Unsupported News field.');
    }

    private static function list(mixed $value, int $min, int $max): bool
    {
        return is_array($value) && array_is_list($value) && count($value) >= $min && count($value) <= $max;
    }

    private static function plainNode(array $node): string
    {
        if (($node['type'] ?? null) === 'text') return self::isEditorPlaceholder($node['text'] ?? '') ? '' : ($node['text'] ?? '');
        if (($node['type'] ?? null) === 'hardBreak') return "\n";
        if (($node['type'] ?? null) === 'hntMediaTextLayout' && empty($node['content']) && is_string($node['attrs']['text'] ?? null)) {
            return self::isEditorPlaceholder($node['attrs']['text']) ? '' : $node['attrs']['text'];
        }
        $separator = in_array($node['type'] ?? null, ['blockquote', 'hntQuote'], true) ? "\n"
            : (in_array($node['type'] ?? null, ['paragraph', 'heading'], true) ? '' : ' ');
        return implode($separator, array_map(self::plainNode(...), $node['content'] ?? []));
    }

    private static function renderNode(array $node, array $media): string
    {
        $type = $node['type'] ?? '';
        $attrs = $node['attrs'] ?? [];
        $children = implode('', array_map(fn ($child) => self::renderNode($child, $media), $node['content'] ?? []));
        if ($type === 'text') {
            $textValue = $node['text'] ?? '';
            if (self::isEditorPlaceholder($textValue)) return '';
            $text = nl2br(e($textValue), false);
            foreach ($node['marks'] ?? [] as $mark) {
                $markType = $mark['type'] ?? '';
                $tag = ['bold' => 'strong', 'italic' => 'em', 'underline' => 'u', 'strike' => 's', 'code' => 'code'][$markType] ?? null;
                if ($tag) $text = "<{$tag}>{$text}</{$tag}>";
                if ($markType === 'link' && self::safeHref($mark['attrs']['href'] ?? '')) $text = '<a href="'.e($mark['attrs']['href']).'" rel="noopener noreferrer">'.$text.'</a>';
                if ($markType === 'hntFontSize' && in_array($mark['attrs']['size'] ?? null, self::FONT_SIZES, true)) $text = '<span style="font-size:'.e($mark['attrs']['size']).'">'.$text.'</span>';
            }
            return $text;
        }
        if ($type === 'hardBreak') return '<br>';
        if ($type === 'horizontalRule') return '<hr>';
        if (in_array($type, ['paragraph', 'heading'], true)) {
            $tag = $type === 'heading' ? 'h'.(int) ($attrs['level'] ?? 2) : 'p';
            $align = in_array($attrs['textAlign'] ?? null, self::ALIGNMENTS, true) ? ' style="text-align:'.e($attrs['textAlign']).'"' : '';
            return "<{$tag}{$align}>{$children}</{$tag}>";
        }
        if (in_array($type, ['blockquote', 'bulletList', 'orderedList', 'listItem'], true)) {
            $tag = ['blockquote' => 'blockquote', 'bulletList' => 'ul', 'orderedList' => 'ol', 'listItem' => 'li'][$type];
            return "<{$tag}>{$children}</{$tag}>";
        }
        if ($type === 'hntImage' || $type === 'hntVideo') return self::mediaFigure($attrs, $media, $type === 'hntVideo');
        if ($type === 'hntMediaTextLayout') {
            $mediaHtml = self::mediaFigure($attrs, $media, ($attrs['mediaType'] ?? '') === 'video');
            $position = $attrs['position'] ?? 'left';
            $ratio = $attrs['ratio'] ?? '50-50';
            if ($children === '' && is_string($attrs['text'] ?? null) && $attrs['text'] !== '' && ! self::isEditorPlaceholder($attrs['text'])) {
                $children = implode('', array_map(fn ($paragraph) => '<p>'.nl2br(e($paragraph), false).'</p>', preg_split('/\n{2,}/', $attrs['text']) ?: []));
            }
            return '<section class="news-media-text '.e($position).' ratio-'.e($ratio).'"><div class="news-media-text-media">'.$mediaHtml.'</div><div class="news-media-text-copy">'.$children.'</div></section>';
        }
        if ($type === 'hntQuote') {
            $background = $media[$attrs['backgroundMediaAssetId'] ?? 0] ?? null;
            $position = $attrs['backgroundPosition'] ?? 'center';
            $image = $background ? '<img class="news-hnt-quote-bg" src="'.e($background['url']).'" alt="" style="object-position:'.e($position).'">' : '';
            $author = ($attrs['author'] ?? '') !== '' ? '<cite>'.e($attrs['author']).'</cite>' : '';
            return '<aside class="news-hnt-quote overlay-'.(int) ($attrs['overlay'] ?? 60).'">'.$image.'<blockquote>'.$children.'</blockquote>'.$author.'</aside>';
        }
        if ($type === 'hntGallery') {
            $items = '';
            foreach ($attrs['items'] ?? [] as $item) $items .= self::mediaFigure($item, $media, false);
            return '<section class="news-gallery columns-'.(int) ($attrs['columns'] ?? 2).'">'.$items.'</section>';
        }
        if ($type === 'hntBeforeAfter') {
            $before = $media[$attrs['beforeAssetId'] ?? 0] ?? null;
            $after = $media[$attrs['afterAssetId'] ?? 0] ?? null;
            if (! $before || ! $after) return '';
            return '<figure class="news-before-after-fallback"><img src="'.e($before['url']).'" alt="'.e($attrs['beforeAlt'] ?? '').'"><img src="'.e($after['url']).'" alt="'.e($attrs['afterAlt'] ?? '').'"><figcaption>'.e($attrs['caption'] ?? '').'</figcaption></figure>';
        }
        return '';
    }

    private static function mediaFigure(array $attrs, array $media, bool $video): string
    {
        $asset = $media[$attrs['mediaAssetId'] ?? 0] ?? null;
        if (! $asset) return '';
        $body = $video ? '<video controls preload="metadata" src="'.e($asset['url']).'"></video>'
            : '<img src="'.e($asset['url']).'" alt="'.e($attrs['alt'] ?? '').'">';
        return '<figure>'.$body.(! empty($attrs['caption']) ? '<figcaption>'.e($attrs['caption']).'</figcaption>' : '').'</figure>';
    }
}
