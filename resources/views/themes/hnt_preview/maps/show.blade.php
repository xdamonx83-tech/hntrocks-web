@include('themes.hnt_preview.maps.show-existing')
<link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/dashboard-maps/map-detail-header-layer.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-maps/map-detail-header-layer.css')) ?: time() }}">
<script src="{{ asset('assets/themes/hnt_preview/dashboard-maps/map-detail-no-popovers.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-maps/map-detail-no-popovers.js')) ?: time() }}"></script>
