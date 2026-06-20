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

    Object.keys(colors).forEach(function (type) {
        layers[type] = window.L.layerGroup().addTo(map);
    });

    (Array.isArray(config.markers) ? config.markers : []).forEach(function (marker) {
        if (!layers[marker.type]) {
            return;
        }

        var point = window.L.circleMarker(markerLatLng(marker), {
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

        point.setStyle({color: '#d6a84f', weight: 4, fillOpacity: 1});
        point.bringToFront();
        window.setTimeout(function () { point.setStyle(originalStyle); }, 1400);
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
    var measurePoints = [];
    var measureMarkers = [];
    var measureLine = null;
    var measuring = false;

    function setMeasureStatus(text) {
        if (measureStatus) {
            measureStatus.textContent = text;
        }
        if (measureHint && measuring) {
            measureHint.textContent = text;
        }
    }

    function setMeasuring(active, statusText) {
        measuring = active;
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

    function clearMeasurement() {
        measureMarkers.forEach(function (marker) { map.removeLayer(marker); });
        measureMarkers = [];
        measurePoints = [];

        if (measureLine) {
            map.removeLayer(measureLine);
            measureLine = null;
        }

        if (measureReset) {
            measureReset.disabled = true;
        }

        setMeasureStatus(measuring ? config.measurePointAText : config.measureIdleText);
    }

    function addMeasurePoint(latlng, label) {
        var marker = window.L.circleMarker(latlng, {
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
        }).addTo(map);

        measureMarkers.push(marker);
        measurePoints.push(latlng);
        measureReset.disabled = false;
    }

    measureToggle?.addEventListener('click', function () {
        setMeasuring(!measuring, measuring ? config.measureIdleText : config.measurePointAText);

        if (measuring && window.matchMedia('(max-width: 768px)').matches) {
            setToolsOpen(false);
            window.setTimeout(function () { map.invalidateSize(); }, 240);
        }
    });

    measureReset?.addEventListener('click', clearMeasurement);

    map.on('click', function (event) {
        if (!measuring || measurePoints.length >= 2 || !bounds.contains(event.latlng)) {
            return;
        }

        addMeasurePoint(event.latlng, measurePoints.length === 0 ? config.measureMarkerAText : config.measureMarkerBText);

        if (measurePoints.length === 1) {
            setMeasureStatus(config.measurePointBText);
            return;
        }

        measureLine = window.L.polyline(measurePoints, {
            color: '#e2c477',
            weight: 2.5,
            opacity: 0.9,
            interactive: false
        }).addTo(map);

        var deltaX = measurePoints[1].lng - measurePoints[0].lng;
        var deltaY = measurePoints[1].lat - measurePoints[0].lat;
        var distance = Math.sqrt((deltaX * deltaX) + (deltaY * deltaY));
        setMeasuring(false, config.measureDistanceText.replace(':distance', distance.toFixed(1)));
    });

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
