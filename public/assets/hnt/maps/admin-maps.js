(function () {
    'use strict';

    var configElement = document.getElementById('hntAdminMapConfig');
    var mapElement = document.getElementById('hntAdminMap');

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
    var bounds = window.L.latLngBounds([[0, 0], [height, width]]);
    var statusElement = document.querySelector('[data-admin-map-status]');
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var markerReferences = [];
    var iconSizes = {
        boss: 32,
        spawn: 24,
        supply: 22,
        cash: 22,
        tower: 24,
        bugs: 24,
        wild: 24
    };

    var map = window.L.map(mapElement, {
        crs: window.L.CRS.Simple,
        minZoom: -2,
        maxZoom: 3,
        zoomSnap: 0.25,
        maxBounds: bounds,
        maxBoundsViscosity: 1,
        attributionControl: false
    });

    window.L.imageOverlay(config.imageUrl, bounds).addTo(map);

    if (config.linesUrl) {
        window.L.imageOverlay(config.linesUrl, bounds, {opacity: 0.55, interactive: false}).addTo(map);
    }

    function setStatus(text, state) {
        if (!statusElement) {
            return;
        }

        statusElement.textContent = text;
        statusElement.dataset.state = state || '';
    }

    function markerIcon(type) {
        var size = iconSizes[type];

        if (!size) {
            return null;
        }

        return window.L.divIcon({
            className: 'hh-admin-map-marker hh-admin-map-marker--' + type,
            html: '<span><img src="/assets/hnt/maps/icons/' + type + '.webp" alt=""></span>',
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2]
        });
    }

    function clamp(value, maximum) {
        return Math.max(0, Math.min(maximum, value));
    }

    function savePosition(reference, previousLatLng) {
        var position = reference.layer.getLatLng();
        var x = clamp(position.lng, width);
        var y = clamp(position.lat, height);

        reference.layer.setLatLng([y, x]);
        reference.layer.dragging.disable();
        reference.layer.getElement()?.classList.add('is-dirty');
        setStatus('Speichert…', 'saving');

        fetch(reference.marker.position_url, {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({x: x, y: y})
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('save_failed');
            }

            return response.json();
        }).then(function (result) {
            reference.marker.x = Number(result.x);
            reference.marker.y = Number(result.y);
            reference.layer.setLatLng([reference.marker.y, reference.marker.x]);
            reference.layer.getElement()?.classList.remove('is-dirty');
            setStatus('Gespeichert', 'saved');
        }).catch(function () {
            reference.layer.setLatLng(previousLatLng);
            reference.layer.getElement()?.classList.remove('is-dirty');
            setStatus('Speichern fehlgeschlagen – Position zurückgesetzt', 'error');
        }).finally(function () {
            reference.layer.dragging.enable();
        });
    }

    (Array.isArray(config.markers) ? config.markers : []).forEach(function (marker) {
        var icon = markerIcon(marker.type);
        var layer = window.L.marker([Number(marker.y), Number(marker.x)], {
            draggable: true,
            icon: icon || window.L.divIcon({
                className: 'hh-admin-map-marker hh-admin-map-marker--fallback',
                html: '<span>' + (marker.type === 'compound' ? 'C' : '•') + '</span>',
                iconSize: [26, 26],
                iconAnchor: [13, 13]
            })
        });

        var reference = {marker: marker, layer: layer};
        var dragStartPosition = null;
        var tooltip = document.createElement('span');
        var tooltipTitle = document.createElement('strong');
        var tooltipMeta = document.createElement('span');

        tooltipTitle.textContent = '#' + marker.id + ' · ' + marker.label;
        tooltipMeta.textContent = marker.type + ' · ' + marker.status;
        tooltip.appendChild(tooltipTitle);
        tooltip.appendChild(tooltipMeta);

        layer.bindTooltip(tooltip, {direction: 'top'});
        layer.on('dragstart', function () {
            dragStartPosition = layer.getLatLng();
            setStatus('Ungespeicherte Änderung', 'dirty');
        });
        layer.on('dragend', function () {
            savePosition(reference, dragStartPosition);
        });
        layer.addTo(map);
        markerReferences.push(reference);
    });

    document.querySelector('[data-admin-map-filter]')?.addEventListener('change', function (event) {
        markerReferences.forEach(function (reference) {
            var visible = !event.target.value || reference.marker.type === event.target.value;

            if (visible && !map.hasLayer(reference.layer)) {
                reference.layer.addTo(map);
            } else if (!visible && map.hasLayer(reference.layer)) {
                map.removeLayer(reference.layer);
            }
        });
    });

    map.fitBounds(bounds, {padding: [16, 16]});
})();
