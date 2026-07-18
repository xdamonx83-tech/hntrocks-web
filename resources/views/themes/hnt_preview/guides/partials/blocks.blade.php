@php
    $sections = [];
    $currentSection = null;

    foreach ((array) $revision->content_blocks as $blockIndex => $block) {
        $type = (string) ($block['type'] ?? '');

        if ($type === 'heading' && filled($block['text'] ?? null)) {
            if ($currentSection !== null) {
                $sections[] = $currentSection;
            }

            $currentSection = [
                'heading' => $block,
                'blocks' => [],
                'source_index' => $blockIndex,
            ];

            continue;
        }

        if ($currentSection === null) {
            $currentSection = [
                'heading' => null,
                'blocks' => [],
                'source_index' => $blockIndex,
            ];
        }

        $currentSection['blocks'][] = $block;
    }

    if ($currentSection !== null) {
        $sections[] = $currentSection;
    }
@endphp

@forelse($sections as $section)
    @php
        $heading = $section['heading'];
        $sectionNumber = str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT);
        $headingText = (string) ($heading['text'] ?? '');
        $headingId = $heading
            ? 'guide-block-'.preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($heading['id'] ?? $section['source_index']))
            : 'guide-section-'.$loop->iteration;
        $headingLevel = (int) ($heading['level'] ?? 2);
    @endphp

    <section class="guide-article-section" id="{{ $headingId }}">
        <span>{{ $sectionNumber }} · {{ $headingText !== '' ? mb_strtoupper($headingText) : 'EINLEITUNG' }}</span>

        @if($headingText !== '')
            @if($headingLevel === 3)
                <h3>{{ $headingText }}</h3>
            @elseif($headingLevel === 4)
                <h4>{{ $headingText }}</h4>
            @else
                <h2>{{ $headingText }}</h2>
            @endif
        @endif

        @foreach($section['blocks'] as $block)
            @php $type = (string) ($block['type'] ?? ''); @endphp

            @if($type === 'paragraph')
                <p>{!! nl2br(e((string) ($block['text'] ?? ''))) !!}</p>
            @elseif($type === 'steps')
                <ol class="guide-steps">
                    @foreach((array) ($block['items'] ?? []) as $item)
                        <li>
                            <b>Schritt {{ $loop->iteration }}</b>
                            <p>{{ $item }}</p>
                        </li>
                    @endforeach
                </ol>
            @elseif($type === 'list')
                <ul class="guide-check-list">
                    @foreach((array) ($block['items'] ?? []) as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @elseif($type === 'image' && ! empty($block['media_id']))
                <figure class="guide-figure">
                    <img src="{{ route('guides.media.show', (int) $block['media_id']) }}" alt="{{ $block['caption'] ?? $revision->title }}" loading="lazy">
                    @if(! empty($block['caption']))
                        <figcaption>{{ $block['caption'] }}</figcaption>
                    @endif
                </figure>
            @elseif(in_array($type, ['notice', 'warning'], true))
                <div class="guide-note {{ $type === 'warning' ? 'warning' : 'info' }}">
                    <i class="ph {{ $type === 'warning' ? 'ph-warning' : 'ph-info' }}" aria-hidden="true"></i>
                    <div>
                        <strong>{{ filled($block['title'] ?? null) ? $block['title'] : __('guides.blocks.'.$type) }}</strong>
                        <p>{!! nl2br(e((string) ($block['text'] ?? ''))) !!}</p>
                    </div>
                </div>
            @endif
        @endforeach
    </section>
@empty
    <div class="guide-content-empty">Für diesen Guide wurden noch keine Inhaltsblöcke veröffentlicht.</div>
@endforelse
