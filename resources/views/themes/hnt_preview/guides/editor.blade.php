@extends('themes.hnt_preview.guides.layout')

@section('title', __('guides.editor.title').' · HNT.ROCKS')
@section('body_class', 'guide-editor-page')
@section('content')
<header class="guide-editor-head">
<div><span>{{ __('guides.kicker') }}</span><h1>{{ __('guides.editor.title') }}</h1><p>{{ __('guides.editor.subtitle') }}</p></div>
<div class="guide-editor-save-state"><span class="guide-status {{ $guide->status }}">{{ __('guides.status.'.$guide->status) }}</span><strong data-autosave-state>{{ __('guides.editor.saved') }}</strong><small data-autosave-time>{{ __('guides.editor.saved_at', ['time' => $revision->updated_at?->diffForHumans()]) }}</small></div>
</header>
@if($revision->moderation_reason)<aside class="guide-moderation-note"><i class="ph ph-warning"></i><div><strong>{{ __('guides.editor.changes_title') }}</strong><p>{{ $revision->moderation_reason }}</p></div></aside>@endif
<form class="guide-editor-form" action="{{ route('guides.update', $guide) }}" method="post" data-guide-editor data-submit-url="{{ route('guides.submit', $guide) }}" data-media-url="{{ route('guides.media.store', $guide) }}" data-preview-url="{{ route('guides.preview', $guide) }}">
@csrf @method('put')
<div class="guide-editor-layout">
<aside class="guide-editor-left">
<section><span class="guide-panel-kicker">{{ __('guides.editor.add_block') }}</span><div class="guide-block-palette">
@foreach(['heading','paragraph','steps','list','image','notice','warning'] as $type)<button type="button" data-add-block="{{ $type }}"><i class="ph {{ match($type) {'heading'=>'ph-text-h-two','paragraph'=>'ph-text-align-left','steps'=>'ph-list-numbers','list'=>'ph-list-bullets','image'=>'ph-image','notice'=>'ph-info','warning'=>'ph-warning'} }}"></i><span>{{ __('guides.editor.block_'.$type) }}</span></button>@endforeach
</div></section>
<section class="guide-editor-checklist"><span class="guide-panel-kicker">{{ __('guides.admin.checklist') }}</span><p><i class="ph ph-check-circle"></i>{{ __('guides.admin.check_content') }}</p><p><i class="ph ph-shield-check"></i>{{ __('guides.admin.check_media') }}</p><p><i class="ph ph-users-three"></i>{{ __('guides.admin.check_rules') }}</p></section>
</aside>
<main class="guide-editor-center">
<section class="guide-editor-card">
<span class="guide-panel-kicker">{{ __('guides.editor.metadata') }}</span>
<label>{{ __('guides.editor.title_label') }}<input type="text" name="title" maxlength="160" value="{{ old('title', $revision->title) }}" placeholder="{{ __('guides.editor.title_placeholder') }}"></label>
<label>{{ __('guides.editor.summary_label') }}<textarea name="summary" maxlength="420" placeholder="{{ __('guides.editor.summary_placeholder') }}">{{ old('summary', $revision->summary) }}</textarea></label>
<div class="guide-editor-fields">
<label>{{ __('guides.editor.category') }}<select name="category_id"><option value="">{{ __('guides.all_categories') }}</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((int) old('category_id', $revision->category_id) === $category->id)>{{ $category->label() }}</option>@endforeach</select></label>
<label>{{ __('guides.filters.language') }}<select name="language">@foreach(['de','en'] as $value)<option value="{{ $value }}" @selected(old('language', $revision->language) === $value)>{{ __('guides.language.'.$value) }}</option>@endforeach</select></label>
<label>{{ __('guides.filters.difficulty') }}<select name="difficulty">@foreach(['beginner','advanced','expert'] as $value)<option value="{{ $value }}" @selected(old('difficulty', $revision->difficulty) === $value)>{{ __('guides.difficulty.'.$value) }}</option>@endforeach</select></label>
<label>{{ __('guides.filters.platform') }}<select name="platform">@foreach(['all','pc','playstation','xbox'] as $value)<option value="{{ $value }}" @selected(old('platform', $revision->platform) === $value)>{{ __('guides.platform.'.$value) }}</option>@endforeach</select></label>
</div>
<label>{{ __('guides.editor.tags') }}<input type="text" name="tags_text" value="{{ implode(', ', (array) $revision->tags) }}"><small>{{ __('guides.editor.tags_help') }}</small></label>
</section>
<section class="guide-editor-card">
<div class="guide-editor-card-head"><div><span class="guide-panel-kicker">{{ __('guides.editor.content') }}</span><h2>{{ __('guides.editor.content') }}</h2></div><span data-block-count>{{ count((array) $revision->content_blocks) }}</span></div>
<div class="guide-editor-blocks" data-editor-blocks><div class="guide-editor-empty" data-editor-empty>{{ __('guides.editor.empty_blocks') }}</div></div>
</section>
</main>
<aside class="guide-editor-right">
<section class="guide-cover-uploader">
<span class="guide-panel-kicker">{{ __('guides.editor.cover') }}</span>
<div data-cover-preview>@if($revision->cover_media_id)<img src="{{ route('guides.media.show', $revision->cover_media_id) }}" alt="">@else<i class="ph ph-image"></i><span>{{ __('guides.cover_missing') }}</span>@endif</div>
<input type="file" accept="image/jpeg,image/png,image/webp" data-cover-input hidden>
<input type="hidden" name="cover_media_id" value="{{ $revision->cover_media_id }}">
<button class="guide-btn compact" type="button" data-cover-upload>{{ __('guides.editor.cover_upload') }}</button>
<small>{{ __('guides.editor.cover_help') }}</small>
</section>
<section class="guide-editor-actions">
<button class="guide-btn primary" type="submit" data-save-guide><i class="ph ph-floppy-disk"></i>{{ __('guides.editor.save') }}</button>
<a class="guide-btn" href="{{ route('guides.preview', $guide) }}" target="_blank"><i class="ph ph-eye"></i>{{ __('guides.editor.preview') }}</a>
<button class="guide-btn dark" type="button" data-submit-guide><i class="ph ph-paper-plane-tilt"></i>{{ __('guides.editor.submit') }}</button>
</section>
</aside>
</div>
<input type="hidden" name="content_blocks" data-content-blocks-input>
</form>
<script type="application/json" id="guideEditorData">{!! json_encode([
    'blocks' => $revision->content_blocks ?: [],
    'labels' => [
        'heading' => __('guides.editor.block_heading'),
        'paragraph' => __('guides.editor.block_paragraph'),
        'steps' => __('guides.editor.block_steps'),
        'list' => __('guides.editor.block_list'),
        'image' => __('guides.editor.block_image'),
        'notice' => __('guides.editor.block_notice'),
        'warning' => __('guides.editor.block_warning'),
        'heading_level' => __('guides.editor.heading_level'),
        'text' => __('guides.editor.text'),
        'items' => __('guides.editor.items'),
        'caption' => __('guides.editor.caption'),
        'box_title' => __('guides.editor.box_title'),
        'image_upload' => __('guides.editor.image_upload'),
        'move_up' => __('guides.editor.move_up'),
        'move_down' => __('guides.editor.move_down'),
        'remove' => __('guides.editor.remove'),
        'saving' => __('guides.editor.saving'),
        'saved' => __('guides.editor.saved'),
        'save_failed' => __('guides.editor.save_failed'),
        'submit_confirm' => __('guides.editor.submit_confirm'),
        'unsaved_warning' => __('guides.editor.unsaved_warning'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection
