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
    var bounds = window.L.latLngBounds([[0, 0], [height, width]]);
    var map = window.L.map(mapElement, {
        crs: window.L.CRS.Simple,
        minZoom: -2,
        maxZoom: 3,
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
        cash: '#d2c04c'
    };
    var layers = {};

    Object.keys(colors).forEach(function (type) {
        layers[type] = window.L.layerGroup().addTo(map);
    });

    (Array.isArray(config.markers) ? config.markers : []).forEach(function (marker) {
        if (!layers[marker.type]) {
            return;
        }

        var point = window.L.circleMarker([height - Number(marker.y), Number(marker.x)], {
            radius: marker.type === 'boss' ? 9 : 7,
            color: '#141412',
            weight: 2,
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
        point.addTo(layers[marker.type]);
    });

    document.querySelectorAll('[data-map-filter]').forEach(function (input) {
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

    function resetView() {
        map.fitBounds(bounds, {padding: [20, 20]});
    }

    document.querySelector('[data-map-reset]')?.addEventListener('click', function () {
        resetView();
        setToolsOpen(false);
    });

    resetView();
    map.setMaxBounds(bounds.pad(0.35));
    window.setTimeout(function () { map.invalidateSize(); }, 0);
    window.addEventListener('resize', function () { map.invalidateSize(); });
}());
