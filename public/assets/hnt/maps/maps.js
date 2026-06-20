(function () {
    'use strict';

    var toolsPanel = document.querySelector('[data-map-tools-panel]');
    var toolsBackdrop = document.querySelector('[data-map-tools-backdrop]');
    var toolsToggle = document.querySelector('[data-map-tools-toggle]');

    function setToolsOpen(isOpen) {
        if (!toolsPanel) {
            return;
        }

        toolsPanel.classList.toggle('is-open', isOpen);
        toolsBackdrop?.classList.toggle('is-open', isOpen);
        toolsToggle?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        document.body.classList.toggle('hnt-map-tools-open', isOpen);
    }

    toolsToggle?.addEventListener('click', function () { setToolsOpen(true); });
    toolsBackdrop?.addEventListener('click', function () { setToolsOpen(false); });
    document.querySelector('[data-map-tools-close]')?.addEventListener('click', function () { setToolsOpen(false); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setToolsOpen(false);
        }
    });

    document.querySelector('[data-map-select]')?.addEventListener('change', function (event) {
        if (event.target.value) {
            window.location.assign(event.target.value);
        }
    });

    var configElement = document.getElementById('hntMapConfig');
    var mapElement = document.getElementById('hntMap');

    if (!configElement || !mapElement || typeof window.L === 'undefined') {
        return;
    }

    var config;

    try {
        config = JSON.parse(configElement.textContent);
    } catch (error) {
        mapElement.hidden = true;
        return;
    }

    var width = Number(config.width);
    var height = Number(config.height);
    var initialZoomOffset = window.matchMedia('(max-width: 768px)').matches ? 0.25 : 0.5;

    function markerLatLng(marker) {
        return [Number(marker.y), Number(marker.x)];
    }

    var bounds = window.L.latLngBounds([[0, 0], [height, width]]);
    var map = window.L.map(mapElement, {
        crs: window.L.CRS.Simple,
        minZoom: -2,
        maxZoom: 3,
        zoomSnap: 0.25,
        zoomControl: false,
        attributionControl: false
    });

    window.L.control.zoom({position: 'topright'}).addTo(map);
    window.L.imageOverlay(config.imageUrl, bounds).addTo(map);

    var linesLayer = config.linesUrl
        ? window.L.imageOverlay(config.linesUrl, bounds, {opacity: 0.72, interactive: false}).addTo(map)
        : null;
    var colors = {
        compound: '#d6a84f',
        boss: '#b8463a',
        spawn: '#6d9dc5',
        supply: '#69a878',
        extract: '#d9d2c2',
        cash: '#c2ad4a',
        tower: '#9b7653',
        bugs: '#7d9260',
        wild: '#9c6f5d',
        tarot: '#876f9e'
    };
    var layers = {};
    var markerReferences = [];
    var filterInputs = {};
    // Marker types without a supplied asset keep the established circle-marker fallback.
    var iconSizes = {
        boss: 32,
        spawn: 24,
        supply: 18,
        cash: 18,
        tower: 24,
        bugs: 24,
        wild: 24
    };
    var activeBossPoint = null;
    var activeBossRings = [];
    var screenshotModal = null;
    var screenshotImage = null;
    var screenshotError = null;
    var screenshotLastFocus = null;

    Object.keys(colors).forEach(function (type) {
        layers[type] = window.L.layerGroup().addTo(map);
    });

    function markerIcon(type) {
        var size = iconSizes[type];

        if (!size) {
            return null;
        }

        return window.L.divIcon({
            className: 'hnt-map-icon-marker hnt-map-icon-marker--' + type,
            html: '<span class="hnt-map-icon-ring"><img src="/assets/hnt/maps/icons/' + type + '.webp" alt=""></span>',
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2],
            popupAnchor: [0, -(size / 2 + 3)]
        });
    }

    function clearBossRings() {
        activeBossRings.forEach(function (ring) { layers.boss.removeLayer(ring); });
        activeBossRings = [];
        activeBossPoint = null;
    }

    function toggleBossRings(point) {
        if (activeBossPoint === point) {
            clearBossRings();
            return;
        }

        clearBossRings();
        activeBossPoint = point;
        activeBossRings = [
            window.L.circle(point.getLatLng(), {
                radius: 50,
                color: '#a94b42',
                weight: 2,
                opacity: 0.88,
                fillColor: '#8f342e',
                fillOpacity: 0.1,
                interactive: false
            }),
            window.L.circle(point.getLatLng(), {
                radius: 150,
                color: '#d6a84f',
                weight: 2,
                opacity: 0.82,
                fillColor: '#d6a84f',
                fillOpacity: 0.035,
                interactive: false
            })
        ];
        activeBossRings.forEach(function (ring) { ring.addTo(layers.boss); });
    }

    function closeScreenshotModal() {
        if (!screenshotModal || screenshotModal.hidden) {
            return;
        }

        screenshotModal.hidden = true;
        screenshotImage.removeAttribute('src');
        document.body.classList.remove('hnt-map-lightbox-open');
        screenshotLastFocus?.focus();
        screenshotLastFocus = null;
    }

    function ensureScreenshotModal() {
        if (screenshotModal) {
            return;
        }

        screenshotModal = document.createElement('div');
        screenshotModal.className = 'hnt-map-lightbox';
        screenshotModal.hidden = true;
        screenshotModal.setAttribute('role', 'dialog');
        screenshotModal.setAttribute('aria-modal', 'true');
        screenshotModal.setAttribute('aria-labelledby', 'hntMapLightboxTitle');

        var panel = document.createElement('div');
        var header = document.createElement('header');
        var title = document.createElement('h2');
        var closeButton = document.createElement('button');

        panel.className = 'hnt-map-lightbox-panel';
        header.className = 'hnt-map-lightbox-header';
        title.id = 'hntMapLightboxTitle';
        title.textContent = config.cashScreenshotText;
        closeButton.type = 'button';
        closeButton.className = 'hnt-map-lightbox-close';
        closeButton.setAttribute('aria-label', config.closeText);
        closeButton.innerHTML = '<i class="ph ph-x" aria-hidden="true"></i>';
        closeButton.addEventListener('click', closeScreenshotModal);

        screenshotImage = document.createElement('img');
        screenshotImage.className = 'hnt-map-lightbox-image';
        screenshotImage.addEventListener('load', function () {
            screenshotImage.hidden = false;
            screenshotError.hidden = true;
        });
        screenshotImage.addEventListener('error', function () {
            screenshotImage.hidden = true;
            screenshotError.hidden = false;
        });

        screenshotError = document.createElement('p');
        screenshotError.className = 'hnt-map-lightbox-error';
        screenshotError.textContent = config.cashScreenshotErrorText;
        screenshotError.hidden = true;

        header.appendChild(title);
        header.appendChild(closeButton);
        panel.appendChild(header);
        panel.appendChild(screenshotImage);
        panel.appendChild(screenshotError);
        screenshotModal.appendChild(panel);
        screenshotModal.addEventListener('click', function (event) {
            if (event.target === screenshotModal) {
                closeScreenshotModal();
            }
        });
        document.querySelector('.hnt-map-stage').appendChild(screenshotModal);
    }

    function openScreenshotModal(marker, trigger) {
        ensureScreenshotModal();
        screenshotLastFocus = trigger || document.activeElement;
        screenshotImage.hidden = true;
        screenshotError.hidden = true;
        screenshotImage.alt = config.cashScreenshotText + ': ' + marker.label;
        screenshotImage.src = marker.image_url;
        screenshotModal.hidden = false;
        document.body.classList.add('hnt-map-lightbox-open');
        screenshotModal.querySelector('.hnt-map-lightbox-close').focus();
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeScreenshotModal();
        }
    });

    (Array.isArray(config.markers) ? config.markers : []).forEach(function (marker) {
        if (!layers[marker.type]) {
            return;
        }

        var icon = markerIcon(marker.type);
        var point = icon
            ? window.L.marker(markerLatLng(marker), {icon: icon})
            : window.L.circleMarker(markerLatLng(marker), {
                radius: marker.type === 'boss' ? 8 : 6,
                color: '#141412',
                weight: 1.5,
                fillColor: colors[marker.type],
                fillOpacity: 1
            });
        var popup = document.createElement('div');
        var title = document.createElement('strong');
        var type = document.createElement('span');
        title.textContent = marker.label;
        type.textContent = config.typeLabels[marker.type] || marker.type;
        popup.appendChild(title);
        popup.appendChild(type);
        point.bindPopup(popup);

        if (marker.type === 'compound') {
            var compoundLabel = document.createElement('span');
            compoundLabel.textContent = marker.label;
            point.bindTooltip(compoundLabel, {
                permanent: true,
                direction: 'top',
                offset: [0, -9],
                className: 'hnt-map-compound-label'
            });
        }

        if (marker.type === 'boss') {
            point.on('click', function () { toggleBossRings(point); });
        }

        if (marker.type === 'cash' && marker.image_url) {
            point.on('click', function () { openScreenshotModal(marker, point.getElement()); });
        }

        point.addTo(layers[marker.type]);
        markerReferences.push({
            marker: marker,
            point: point,
            label: marker.label,
            typeLabel: config.typeLabels[marker.type] || marker.type
        });
    });

    document.querySelectorAll('[data-map-filter]').forEach(function (input) {
        filterInputs[input.value] = input;
        input.addEventListener('change', function () {
            var layer = layers[input.value];

            if (!layer) {
                return;
            }

            if (input.checked) {
                layer.addTo(map);
            } else {
                map.removeLayer(layer);
            }
        });
    });

    function searchForms(value) {
        var text = String(value || '').toLocaleLowerCase().trim();
        var transliterated = text
            .replace(/ä/g, 'ae')
            .replace(/ö/g, 'oe')
            .replace(/ü/g, 'ue')
            .replace(/ß/g, 'ss');
        var withoutDiacritics = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');

        return Array.from(new Set([text, transliterated, withoutDiacritics])).filter(Boolean);
    }

function matchesSearch(reference, queryForms) {
    if (reference.marker.type !== 'compound') {
        return false;
    }

    var searchableForms = searchForms(reference.label);

    return queryForms.some(function (query) {
        return searchableForms.some(function (value) { return value.includes(query); });
    });
}

    function ensureMarkerTypeVisible(type) {
        var input = filterInputs[type];

        if (!input || input.checked) {
            return;
        }

        input.checked = true;
        input.dispatchEvent(new Event('change', {bubbles: true}));
    }

    function highlightMarker(reference) {
        var point = reference.point;
        var originalStyle = {
            color: '#141412',
            weight: 1.5,
            fillColor: colors[reference.marker.type],
            fillOpacity: 1
        };

        if (typeof point.setStyle === 'function') {
            point.setStyle({color: '#d6a84f', weight: 4, fillOpacity: 1});
            point.bringToFront();
            window.setTimeout(function () { point.setStyle(originalStyle); }, 1400);
            return;
        }

        var element = point.getElement();
        if (!element) {
            return;
        }

        element.classList.add('is-search-highlighted');
        point.setZIndexOffset(1000);
        window.setTimeout(function () {
            element.classList.remove('is-search-highlighted');
            point.setZIndexOffset(0);
        }, 1400);
    }

    function selectSearchResult(reference) {
        ensureMarkerTypeVisible(reference.marker.type);
        map.setView(reference.point.getLatLng(), Math.max(map.getZoom(), 1.5), {animate: false});
        reference.point.openPopup();
        highlightMarker(reference);

        if (window.matchMedia('(max-width: 768px)').matches) {
            setToolsOpen(false);
            window.setTimeout(function () { map.invalidateSize(); }, 240);
        }
    }

    var searchInput = document.getElementById('hntMapSearch');
    var searchResults = document.getElementById('hntMapSearchResults');

    function renderSearchResults() {
        if (!searchInput || !searchResults) {
            return;
        }

        var queryForms = searchForms(searchInput.value);
        searchResults.replaceChildren();

        if (queryForms.length === 0) {
            searchResults.hidden = true;
            searchInput.setAttribute('aria-expanded', 'false');
            return;
        }

        var matches = markerReferences.filter(function (reference) {
            return matchesSearch(reference, queryForms);
        }).slice(0, 8);

        if (matches.length === 0) {
            var empty = document.createElement('p');
            empty.className = 'hnt-map-search-empty';
            empty.textContent = config.searchEmptyText;
            searchResults.appendChild(empty);
        } else {
            matches.forEach(function (reference) {
                var result = document.createElement('button');
                var label = document.createElement('strong');
                var type = document.createElement('span');

                result.type = 'button';
                result.className = 'hnt-map-search-result';
                result.setAttribute('role', 'option');
                label.textContent = reference.label;
                type.textContent = reference.typeLabel;
                result.appendChild(label);
                result.appendChild(type);
                result.addEventListener('click', function () { selectSearchResult(reference); });
                searchResults.appendChild(result);
            });
        }

        searchResults.hidden = false;
        searchInput.setAttribute('aria-expanded', 'true');
    }

    searchInput?.addEventListener('input', renderSearchResults);

    document.querySelector('[data-map-lines-toggle]')?.addEventListener('change', function (event) {
        if (!linesLayer) {
            return;
        }

        if (event.target.checked) {
            linesLayer.addTo(map);
        } else {
            map.removeLayer(linesLayer);
        }
    });

    var measureToggle = document.querySelector('[data-map-measure-toggle]');
    var measureReset = document.querySelector('[data-map-measure-reset]');
    var measureStatus = document.querySelector('[data-map-measure-status]');
    var measureHint = document.querySelector('[data-map-measure-hint]');
    var metersPerUnit = 0.5;
    var speedMPerMin = 150;
    var finishedMeasurements = window.L.layerGroup().addTo(map);
    var currentMeasurement = window.L.layerGroup().addTo(map);
    var previewMeasurement = window.L.layerGroup().addTo(map);
    var currentMeasureStart = null;
    var previewLine = null;
    var previewLabel = null;
    var measuring = false;

    function setMeasureStatus(text) {
        if (measureStatus) {
            measureStatus.textContent = text;
        }
        if (measureHint && measuring) {
            measureHint.textContent = text;
        }
    }

    function clearPreview() {
        previewMeasurement.clearLayers();
        previewLine = null;
        previewLabel = null;
    }

    function clearCurrentMeasurement() {
        currentMeasurement.clearLayers();
        currentMeasureStart = null;
        clearPreview();
    }

    function setMeasuring(active, statusText) {
        measuring = active;

        if (!active) {
            clearCurrentMeasurement();
            measureReset.disabled = finishedMeasurements.getLayers().length === 0;
        }

        mapElement.classList.toggle('is-measuring', active);
        measureToggle?.classList.toggle('is-active', active);
        measureToggle?.setAttribute('aria-pressed', active ? 'true' : 'false');
        if (measureHint) {
            measureHint.hidden = !active;
        }

        var label = measureToggle?.querySelector('span');
        if (label) {
            label.textContent = active ? config.measureEndText : config.measureStartText;
        }

        if (statusText) {
            setMeasureStatus(statusText);
        }
    }

    function resetMeasurements() {
        clearCurrentMeasurement();
        finishedMeasurements.clearLayers();
        measureReset.disabled = true;
        setMeasureStatus(measuring ? config.measurePointAText : config.measureIdleText);
    }

    function measureMarker(latlng, label) {
        return window.L.circleMarker(latlng, {
            radius: 6,
            color: '#171713',
            weight: 2,
            fillColor: '#d6a84f',
            fillOpacity: 1,
            interactive: false
        }).bindTooltip(label, {
            permanent: true,
            direction: 'top',
            offset: [0, -7],
            className: 'hnt-map-measure-label'
        });
    }

    function measurementText(start, end) {
        var dx = end.lng - start.lng;
        var dy = end.lat - start.lat;
        var unitDist = Math.hypot(dx, dy);
        var distMeters = unitDist * metersPerUnit;
        var durationSeconds = Math.round((distMeters / speedMPerMin) * 60);
        var meterDigits = distMeters < 10 ? 1 : 0;
        var meters = distMeters.toLocaleString(document.documentElement.lang, {
            minimumFractionDigits: meterDigits,
            maximumFractionDigits: meterDigits
        });

        return config.measureDistanceText
            .replace(':meters', meters)
            .replace(':seconds', String(durationSeconds));
    }

    function midpoint(start, end) {
        return window.L.latLng((start.lat + end.lat) / 2, (start.lng + end.lng) / 2);
    }

    function beginMeasurement(latlng) {
        currentMeasureStart = latlng;
        measureMarker(latlng, config.measureMarkerAText).addTo(currentMeasurement);
        measureReset.disabled = false;
        setMeasureStatus(config.measurePointBText);
    }

    function finishMeasurement(latlng) {
        var start = currentMeasureStart;
        var distanceText = measurementText(start, latlng);

        clearCurrentMeasurement();
        measureMarker(start, config.measureMarkerAText).addTo(finishedMeasurements);
        measureMarker(latlng, config.measureMarkerBText).addTo(finishedMeasurements);
        window.L.polyline([start, latlng], {
            color: '#e2c477',
            weight: 2.5,
            opacity: 0.9,
            interactive: false
        }).addTo(finishedMeasurements);
        window.L.tooltip({
            permanent: true,
            direction: 'center',
            className: 'hnt-map-measure-distance'
        }).setLatLng(midpoint(start, latlng)).setContent(distanceText).addTo(finishedMeasurements);

        measureReset.disabled = false;
        setMeasureStatus(config.measureSavedText);
    }

    measureToggle?.addEventListener('click', function () {
        setMeasuring(!measuring, measuring ? config.measureIdleText : config.measurePointAText);

        if (measuring && window.matchMedia('(max-width: 768px)').matches) {
            setToolsOpen(false);
            window.setTimeout(function () { map.invalidateSize(); }, 240);
        }
    });

    measureReset?.addEventListener('click', resetMeasurements);

    map.on('click', function (event) {
        if (!measuring || !bounds.contains(event.latlng)) {
            return;
        }

        if (!currentMeasureStart) {
            beginMeasurement(event.latlng);
            return;
        }

        finishMeasurement(event.latlng);
    });

    map.on('mousemove', function (event) {
        if (!measuring || !currentMeasureStart || !bounds.contains(event.latlng)) {
            return;
        }

        var points = [currentMeasureStart, event.latlng];
        var distanceText = measurementText(currentMeasureStart, event.latlng);

        if (!previewLine) {
            previewLine = window.L.polyline(points, {
                color: '#d6b968',
                weight: 2,
                opacity: 0.72,
                dashArray: '6 7',
                interactive: false
            }).addTo(previewMeasurement);
            previewLabel = window.L.tooltip({
                permanent: true,
                direction: 'center',
                className: 'hnt-map-measure-distance hnt-map-measure-distance--preview'
            }).setLatLng(midpoint(currentMeasureStart, event.latlng)).setContent(distanceText).addTo(previewMeasurement);
            return;
        }

        previewLine.setLatLngs(points);
        previewLabel.setLatLng(midpoint(currentMeasureStart, event.latlng)).setContent(distanceText);
    });

    map.on('mouseout', clearPreview);

    var toastTimer;

    function showMapToast(text) {
        var toast = document.querySelector('[data-map-toast]');
        if (!toast) {
            return;
        }

        window.clearTimeout(toastTimer);
        toast.textContent = text;
        toast.hidden = false;
        toastTimer = window.setTimeout(function () { toast.hidden = true; }, 2600);
    }

    function currentViewUrl() {
        var center = map.getCenter();
        var url = new URL(window.location.href);
        url.searchParams.set('x', center.lng.toFixed(2));
        url.searchParams.set('y', center.lat.toFixed(2));
        url.searchParams.set('z', map.getZoom().toFixed(2));
        return url.toString();
    }

    function showShareFallback(url) {
        var fallback = document.querySelector('[data-map-share-fallback]');
        var input = document.querySelector('[data-map-share-url]');
        if (!fallback || !input) {
            return;
        }

        input.value = url;
        fallback.hidden = false;
        input.focus();
        input.select();
    }

    document.querySelector('[data-map-share]')?.addEventListener('click', function () {
        var url = currentViewUrl();

        if (!navigator.clipboard || !window.isSecureContext) {
            showShareFallback(url);
            return;
        }

        navigator.clipboard.writeText(url).then(function () {
            showMapToast(config.shareSuccessText);
        }).catch(function () {
            showShareFallback(url);
        });
    });

    function resetMapView() {
        var fittedZoom = map.getBoundsZoom(bounds, false, window.L.point(20, 20));
        map.setView(bounds.getCenter(), Math.min(fittedZoom + initialZoomOffset, map.getMaxZoom()));
    }

    document.querySelector('[data-map-reset]')?.addEventListener('click', function () {
        resetMapView();
        setToolsOpen(false);
    });

    map.setMaxBounds(bounds.pad(0.35));

    var query = new URLSearchParams(window.location.search);
    var sharedX = Number(query.get('x'));
    var sharedY = Number(query.get('y'));
    var sharedZoom = Number(query.get('z'));
    var hasSharedView = query.has('x') && query.has('y') && query.has('z')
        && Number.isFinite(sharedX) && Number.isFinite(sharedY) && Number.isFinite(sharedZoom)
        && bounds.contains([sharedY, sharedX]);

    if (hasSharedView) {
        map.setView([sharedY, sharedX], Math.max(map.getMinZoom(), Math.min(sharedZoom, map.getMaxZoom())), {animate: false});
    } else {
        resetMapView();
    }

    window.setTimeout(function () { map.invalidateSize(); }, 0);
    window.addEventListener('resize', function () { map.invalidateSize(); });
}());
