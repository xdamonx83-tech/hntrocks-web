@php
    /** @var \App\Models\MediaAsset $asset */
    $thumbUrl = $meta['thumbnail_url'] ?? null;
    $directUrl = $meta['direct_url'] ?? null;
    $targetUrl = $meta['target_url'] ?? null;
    $targetLabel = $meta['target_label'] ?? 'Kein verknüpfter Inhalt';
    $targetType = $meta['target_type'] ?? 'Medienbibliothek';
    $typeLabel = $meta['type_label'] ?? 'Datei';
    $dimensions = $meta['dimensions'] ?? null;
    $size = $meta['size'] ?? null;
@endphp

<div class="hh-admin-media-preview">
    <a class="hh-admin-media-thumb" href="{{ $directUrl ?: '#' }}" target="_blank" rel="noopener" aria-label="Medium öffnen">
        @if(($meta['is_image'] ?? false) && $thumbUrl)
            <img src="{{ $thumbUrl }}" alt="{{ $asset->original_name ?: 'Medium #'.$asset->id }}" loading="lazy">
        @elseif(($meta['is_video'] ?? false) && $directUrl)
            <video src="{{ $directUrl }}" muted playsinline preload="metadata"></video>
            <span class="hh-admin-media-play">▶</span>
        @else
            <span class="hh-admin-media-file">{{ strtoupper((string) ($asset->extension ?: 'FILE')) }}</span>
        @endif
    </a>

    <div class="hh-admin-media-info">
        <div class="hh-admin-media-tags">
            <span>{{ $typeLabel }}</span>
            @if($dimensions)
                <span>{{ $dimensions }}</span>
            @endif
            @if($size)
                <span>{{ $size }}</span>
            @endif
        </div>

        <strong>{{ $targetType }}</strong>
        <span>{{ $targetLabel }}</span>

        <div class="hh-admin-media-actions">
            @if($targetUrl)
                <a class="hh-link-button" href="{{ $targetUrl }}" target="_blank" rel="noopener">Verknüpften Inhalt öffnen</a>
            @else
                <span class="hh-admin-media-empty">Nicht mit Beitrag/Moment verknüpft</span>
            @endif

            @if($directUrl)
                <a class="hh-secondary-button" href="{{ $directUrl }}" target="_blank" rel="noopener">Datei öffnen</a>
            @endif
        </div>
    </div>
</div>
