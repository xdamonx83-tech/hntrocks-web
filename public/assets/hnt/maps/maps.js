(function () {
    'use strict';

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
        zoomControl: true,
        attributionControl: false
    });

    window.L.imageOverlay(config.imageUrl, bounds).addTo(map);

    if (config.linesUrl) {
        window.L.imageOverlay(config.linesUrl, bounds, {opacity: 0.72, interactive: false}).addTo(map);
    }

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

    map.fitBounds(bounds, {padding: [12, 12]});
    map.setMaxBounds(bounds.pad(0.35));
    window.setTimeout(function () { map.invalidateSize(); }, 0);
}());
