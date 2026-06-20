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
    var countElement = document.querySelector('[data-admin-map-count]');
    var filterElement = document.querySelector('[data-admin-map-filter]');
    var addButton = document.querySelector('[data-admin-map-add]');
    var dialog = document.querySelector('[data-admin-map-dialog]');
    var form = document.querySelector('[data-admin-map-form]');
    var formMode = document.querySelector('[data-admin-map-form-mode]');
    var formTitle = document.querySelector('[data-admin-map-form-title]');
    var formError = document.querySelector('[data-admin-map-form-error]');
    var deleteButton = document.querySelector('[data-admin-map-delete]');
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var markerReferences = [];
    var selectedReference = null;
    var draftLayer = null;
    var addMode = false;
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

    function showFormError(message) {
        if (!formError) {
            return;
        }

        formError.textContent = message;
        formError.hidden = !message;
    }

    function markerIcon(type) {
        var size = iconSizes[type];

        if (size) {
            return window.L.divIcon({
                className: 'hh-admin-map-marker hh-admin-map-marker--' + type,
                html: '<span><img src="/assets/hnt/maps/icons/' + type + '.webp" alt=""></span>',
                iconSize: [size, size],
                iconAnchor: [size / 2, size / 2]
            });
        }

        return window.L.divIcon({
            className: 'hh-admin-map-marker hh-admin-map-marker--fallback',
            html: '<span>' + (type === 'compound' ? 'C' : '•') + '</span>',
            iconSize: [26, 26],
            iconAnchor: [13, 13]
        });
    }

    function clamp(value, maximum) {
        return Math.max(0, Math.min(maximum, Number(value)));
    }

    function markerLabel(marker) {
        return marker.label || marker.label_de || marker.label_en || marker.type.charAt(0).toUpperCase() + marker.type.slice(1);
    }

    function updateTooltip(reference) {
        var tooltip = document.createElement('span');
        var tooltipTitle = document.createElement('strong');
        var tooltipMeta = document.createElement('span');

        tooltipTitle.textContent = '#' + reference.marker.id + ' · ' + markerLabel(reference.marker);
        tooltipMeta.textContent = reference.marker.type + ' · ' + reference.marker.status;
        tooltip.appendChild(tooltipTitle);
        tooltip.appendChild(tooltipMeta);
        reference.layer.unbindTooltip();
        reference.layer.bindTooltip(tooltip, {direction: 'top'});
    }

    function applyFilter(reference) {
        var visible = !filterElement?.value || reference.marker.type === filterElement.value;

        if (visible && !map.hasLayer(reference.layer)) {
            reference.layer.addTo(map);
        } else if (!visible && map.hasLayer(reference.layer)) {
            map.removeLayer(reference.layer);
        }
    }

    function ensureFilterOption(type) {
        if (!filterElement || Array.from(filterElement.options).some(function (option) { return option.value === type; })) {
            return;
        }

        var option = document.createElement('option');
        option.value = type;
        option.textContent = type.charAt(0).toUpperCase() + type.slice(1);
        filterElement.appendChild(option);
    }

    function updateMarkerCount() {
        if (countElement) {
            countElement.textContent = new Intl.NumberFormat('de-DE').format(markerReferences.length);
        }
    }

    function addMarkerReference(marker) {
        var layer = window.L.marker([Number(marker.y), Number(marker.x)], {
            draggable: true,
            icon: markerIcon(marker.type)
        });
        var reference = {marker: marker, layer: layer};
        var dragStartPosition = null;

        updateTooltip(reference);
        layer.on('click', function () {
            openEditor(reference);
        });
        layer.on('dragstart', function () {
            dragStartPosition = layer.getLatLng();
            setStatus('Ungespeicherte Änderung', 'dirty');
        });
        layer.on('dragend', function () {
            savePosition(reference, dragStartPosition);
        });

        markerReferences.push(reference);
        updateMarkerCount();
        ensureFilterOption(marker.type);
        applyFilter(reference);

        return reference;
    }

    function errorMessage(response, result) {
        if (result && result.errors) {
            var fields = Object.keys(result.errors);

            if (fields.length && result.errors[fields[0]].length) {
                return result.errors[fields[0]][0];
            }
        }

        return result?.message || 'Die Änderung konnte nicht gespeichert werden.';
    }

    function jsonRequest(url, method, payload) {
        return fetch(url, {
            method: method,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload || {})
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (result) {
                if (!response.ok) {
                    throw new Error(errorMessage(response, result));
                }

                return result;
            });
        });
    }

    function savePosition(reference, previousLatLng) {
        var position = reference.layer.getLatLng();
        var x = clamp(position.lng, width);
        var y = clamp(position.lat, height);

        reference.layer.setLatLng([y, x]);
        reference.layer.dragging.disable();
        reference.layer.getElement()?.classList.add('is-dirty');
        setStatus('Speichert…', 'saving');

        jsonRequest(reference.marker.position_url, 'PATCH', {x: x, y: y}).then(function (result) {
            reference.marker.x = Number(result.x);
            reference.marker.y = Number(result.y);
            reference.layer.setLatLng([reference.marker.y, reference.marker.x]);
            reference.layer.getElement()?.classList.remove('is-dirty');
            setStatus('Gespeichert', 'saved');
        }).catch(function (error) {
            reference.layer.setLatLng(previousLatLng);
            reference.layer.getElement()?.classList.remove('is-dirty');
            setStatus('Fehler: ' + error.message + ' Position zurückgesetzt.', 'error');
        }).finally(function () {
            reference.layer.dragging.enable();
        });
    }

    function setFormValues(marker) {
        form.elements.type.value = marker.type || 'compound';
        form.elements.status.value = marker.status || 'approved';
        form.elements.label_de.value = marker.label_de || '';
        form.elements.label_en.value = marker.label_en || '';
        form.elements.x.value = Number(marker.x).toFixed(6);
        form.elements.y.value = Number(marker.y).toFixed(6);
    }

    function showDialog() {
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }
    }

    function openEditor(reference) {
        if (addMode) {
            stopAddMode();
        }

        selectedReference = reference;
        formMode.textContent = 'Marker bearbeiten';
        formTitle.textContent = '#' + reference.marker.id + ' · ' + markerLabel(reference.marker);
        deleteButton.hidden = false;
        showFormError('');
        setFormValues(reference.marker);
        showDialog();
    }

    function openCreateEditor(latlng) {
        var x = clamp(latlng.lng, width);
        var y = clamp(latlng.lat, height);

        if (draftLayer) {
            map.removeLayer(draftLayer);
        }

        draftLayer = window.L.marker([y, x], {icon: markerIcon('compound')}).addTo(map);
        selectedReference = null;
        formMode.textContent = 'Neuen Marker erstellen';
        formTitle.textContent = 'Position festlegen';
        deleteButton.hidden = true;
        showFormError('');
        setFormValues({type: 'compound', status: 'approved', x: x, y: y});
        showDialog();
    }

    function stopAddMode() {
        addMode = false;
        mapElement.classList.remove('is-adding');

        if (addButton) {
            addButton.textContent = 'Marker hinzufügen';
            addButton.classList.remove('is-active');
        }

        if (draftLayer) {
            map.removeLayer(draftLayer);
            draftLayer = null;
        }
    }

    function closeEditor() {
        if (dialog.open) {
            dialog.close();
        }

        if (!selectedReference) {
            stopAddMode();
        }

        selectedReference = null;
        showFormError('');
    }

    function formPayload() {
        return {
            type: form.elements.type.value,
            status: form.elements.status.value,
            label_de: form.elements.label_de.value || null,
            label_en: form.elements.label_en.value || null,
            x: clamp(form.elements.x.value, width),
            y: clamp(form.elements.y.value, height)
        };
    }

    (Array.isArray(config.markers) ? config.markers : []).forEach(addMarkerReference);

    filterElement?.addEventListener('change', function () {
        markerReferences.forEach(applyFilter);
    });

    addButton?.addEventListener('click', function () {
        if (addMode) {
            stopAddMode();
            setStatus('Bereit', '');
            return;
        }

        addMode = true;
        mapElement.classList.add('is-adding');
        addButton.textContent = 'Add-Modus abbrechen';
        addButton.classList.add('is-active');
        setStatus('Klicke auf die Karte, um die Position zu setzen.', 'dirty');
    });

    map.on('click', function (event) {
        if (addMode && !dialog.open) {
            openCreateEditor(event.latlng);
        }
    });

    form?.elements.type.addEventListener('change', function () {
        if (!selectedReference && draftLayer) {
            draftLayer.setIcon(markerIcon(form.elements.type.value));
        }
    });

    form?.addEventListener('submit', function (event) {
        event.preventDefault();
        showFormError('');
        setStatus('Speichert…', 'saving');

        var reference = selectedReference;
        var url = reference ? reference.marker.update_url : config.storeUrl;
        var method = reference ? 'PATCH' : 'POST';

        jsonRequest(url, method, formPayload()).then(function (result) {
            if (reference) {
                reference.marker = result.marker;
                reference.layer.setLatLng([Number(result.marker.y), Number(result.marker.x)]);
                reference.layer.setIcon(markerIcon(result.marker.type));
                updateTooltip(reference);
                ensureFilterOption(result.marker.type);
                applyFilter(reference);
            } else {
                if (draftLayer) {
                    map.removeLayer(draftLayer);
                    draftLayer = null;
                }

                addMarkerReference(result.marker);
                stopAddMode();
            }

            dialog.close();
            selectedReference = null;
            setStatus('Gespeichert', 'saved');
        }).catch(function (error) {
            showFormError(error.message);
            setStatus('Fehler', 'error');
        });
    });

    deleteButton?.addEventListener('click', function () {
        if (!selectedReference || !window.confirm('Marker wirklich löschen?')) {
            return;
        }

        var reference = selectedReference;
        showFormError('');
        setStatus('Speichert…', 'saving');

        jsonRequest(reference.marker.delete_url, 'DELETE').then(function () {
            map.removeLayer(reference.layer);
            markerReferences = markerReferences.filter(function (item) { return item !== reference; });
            updateMarkerCount();
            dialog.close();
            selectedReference = null;
            setStatus('Gespeichert', 'saved');
        }).catch(function (error) {
            showFormError(error.message);
            setStatus('Fehler', 'error');
        });
    });

    document.querySelectorAll('[data-admin-map-cancel]').forEach(function (button) {
        button.addEventListener('click', closeEditor);
    });

    dialog?.addEventListener('cancel', function (event) {
        event.preventDefault();
        closeEditor();
    });

    map.fitBounds(bounds, {padding: [16, 16]});
})();
