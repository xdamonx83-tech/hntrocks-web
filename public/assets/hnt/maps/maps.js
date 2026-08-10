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
    var cashDetailModal = null;
    var cashDetailImage = null;
    var cashDetailError = null;
    var cashDetailLabel = null;
    var cashDetailUpButton = null;
    var cashDetailDownButton = null;
    var cashDetailUpCount = null;
    var cashDetailDownCount = null;
    var cashDetailAnonymousHint = null;
    var cashDetailVoteError = null;
    var cashDetailCommentsCount = null;
    var cashDetailCommentsList = null;
    var cashDetailCommentsStatus = null;
    var cashDetailCommentsLogin = null;
    var cashDetailCommentsLoginLink = null;
    var cashDetailCommentsForm = null;
    var cashDetailCommentsInput = null;
    var cashDetailCommentsSubmit = null;
    var cashDetailMarker = null;
    var cashDetailLastFocus = null;

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

    function closeCashDetailModal() {
        if (!cashDetailModal || cashDetailModal.hidden) {
            return;
        }

        cashDetailModal.hidden = true;
        cashDetailImage.removeAttribute('src');
        cashDetailMarker = null;
        document.body.classList.remove('hnt-map-lightbox-open');
        cashDetailLastFocus?.focus();
        cashDetailLastFocus = null;
    }

    function updateCashDetailVoteState() {
        if (!cashDetailMarker) {
            return;
        }

        var viewerVote = Number(cashDetailMarker.viewer_vote) || null;
        var canVote = Boolean(cashDetailMarker.vote_url);

        cashDetailUpCount.textContent = String(Number(cashDetailMarker.up_count) || 0);
        cashDetailDownCount.textContent = String(Number(cashDetailMarker.down_count) || 0);
        cashDetailUpButton.classList.toggle('is-active', viewerVote === 1);
        cashDetailDownButton.classList.toggle('is-active', viewerVote === -1);
        cashDetailUpButton.setAttribute('aria-pressed', viewerVote === 1 ? 'true' : 'false');
        cashDetailDownButton.setAttribute('aria-pressed', viewerVote === -1 ? 'true' : 'false');
        cashDetailUpButton.disabled = !canVote;
        cashDetailDownButton.disabled = !canVote;
        cashDetailAnonymousHint.hidden = Boolean(config.viewerIsAuthenticated);
    }

    function submitCashDetailVote(value) {
        if (!cashDetailMarker?.vote_url) {
            return;
        }

        var marker = cashDetailMarker;

        cashDetailUpButton.disabled = true;
        cashDetailDownButton.disabled = true;
        cashDetailVoteError.hidden = true;

        fetch(marker.vote_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({value: value})
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('vote_failed');
            }

            return response.json();
        }).then(function (data) {
            if (!data.ok) {
                throw new Error('vote_failed');
            }

            marker.up_count = Number(data.up_count) || 0;
            marker.down_count = Number(data.down_count) || 0;
            marker.viewer_vote = data.viewer_vote;

            if (cashDetailMarker === marker) {
                updateCashDetailVoteState();
            }
        }).catch(function () {
            if (cashDetailMarker === marker) {
                cashDetailVoteError.hidden = false;
                updateCashDetailVoteState();
            }
        });
    }

    function commentRequest(url, method, body) {
        return fetch(url, {
            method: method,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: body === undefined ? undefined : JSON.stringify(body)
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                if (!response.ok || !data.ok) {
                    var validationError = data.errors?.body?.[0];
                    throw new Error(validationError || data.message || config.cashSpotCommentErrorText);
                }

                return data;
            });
        });
    }

    function setCashDetailCommentCount(count) {
        if (!cashDetailCommentsCount) {
            return;
        }

        count = Number(count) || 0;
        cashDetailCommentsCount.textContent = String(count);
        if (cashDetailMarker) {
            cashDetailMarker.comment_count = count;
        }
    }

    function showCashDetailCommentError(message) {
        cashDetailCommentsStatus.textContent = message || config.cashSpotCommentErrorText;
        cashDetailCommentsStatus.hidden = false;
    }

    function renderCashDetailComment(comment) {
        var item = document.createElement('article');
        var avatarLink = document.createElement('a');
        var avatar = document.createElement('img');
        var content = document.createElement('div');
        var meta = document.createElement('div');
        var authorLink = document.createElement('a');
        var time = document.createElement('span');
        var body = document.createElement('div');
        var actions = document.createElement('div');

        item.className = 'hnt-map-cash-comment';
        item.dataset.commentId = String(comment.id);
        avatarLink.className = 'hnt-map-cash-comment-avatar';
        avatarLink.href = comment.user.profile_url;
        avatar.src = comment.user.avatar_url;
        avatar.alt = '';
        avatarLink.appendChild(avatar);
        content.className = 'hnt-map-cash-comment-content';
        meta.className = 'hnt-map-cash-comment-meta';
        authorLink.href = comment.user.profile_url;
        authorLink.textContent = comment.user.name;
        time.textContent = comment.created_at_label;
        body.className = 'hnt-map-cash-comment-body';
        body.innerHTML = comment.body_html;
        actions.className = 'hnt-map-cash-comment-actions';

        function actionButton(icon, label, handler) {
            var button = document.createElement('button');
            button.type = 'button';
            button.title = label;
            button.setAttribute('aria-label', label);
            button.innerHTML = '<i class="ph ' + icon + '" aria-hidden="true"></i>';
            button.addEventListener('click', handler);
            return button;
        }

        if (comment.can_edit && comment.routes.update) {
            actions.appendChild(actionButton('ph-pencil-simple', config.cashSpotCommentEditText, function () {
                var editor = document.createElement('div');
                var input = document.createElement('textarea');
                var editorActions = document.createElement('div');
                var save = document.createElement('button');
                var cancel = document.createElement('button');

                editor.className = 'hnt-map-cash-comment-editor';
                input.maxLength = 2000;
                input.value = comment.body;
                editorActions.className = 'hnt-map-cash-comment-editor-actions';
                save.type = 'button';
                save.textContent = config.cashSpotCommentSaveText;
                cancel.type = 'button';
                cancel.textContent = config.cashSpotCommentCancelText;
                editorActions.appendChild(cancel);
                editorActions.appendChild(save);
                editor.appendChild(input);
                editor.appendChild(editorActions);
                body.replaceWith(editor);
                actions.hidden = true;
                input.focus();

                cancel.addEventListener('click', function () {
                    editor.replaceWith(body);
                    actions.hidden = false;
                });
                save.addEventListener('click', function () {
                    save.disabled = true;
                    commentRequest(comment.routes.update, 'PATCH', {body: input.value}).then(function (data) {
                        var replacement = renderCashDetailComment(data.comment);
                        item.replaceWith(replacement);
                        cashDetailCommentsStatus.textContent = config.cashSpotCommentUpdatedText;
                        cashDetailCommentsStatus.hidden = false;
                    }).catch(function (error) {
                        save.disabled = false;
                        showCashDetailCommentError(error.message);
                    });
                });
            }));
        }

        if (comment.can_delete && comment.routes.delete) {
            actions.appendChild(actionButton('ph-trash', config.cashSpotCommentDeleteText, function () {
                if (!window.confirm(config.cashSpotCommentDeleteConfirmText)) {
                    return;
                }

                commentRequest(comment.routes.delete, 'DELETE').then(function (data) {
                    item.remove();
                    setCashDetailCommentCount(data.comment_count);
                    cashDetailCommentsStatus.textContent = config.cashSpotCommentDeletedText;
                    cashDetailCommentsStatus.hidden = false;
                    if (!cashDetailCommentsList.children.length) {
                        cashDetailCommentsStatus.textContent = config.cashSpotCommentsEmptyText;
                    }
                }).catch(function (error) {
                    showCashDetailCommentError(error.message);
                });
            }));
        }

        meta.appendChild(authorLink);
        meta.appendChild(time);
        content.appendChild(meta);
        content.appendChild(body);
        content.appendChild(actions);
        item.appendChild(avatarLink);
        item.appendChild(content);

        return item;
    }

    function loadCashDetailComments(marker) {
        cashDetailCommentsList.replaceChildren();
        cashDetailCommentsStatus.textContent = config.cashSpotCommentsLoadingText;
        cashDetailCommentsStatus.hidden = false;
        cashDetailCommentsForm.hidden = true;
        cashDetailCommentsLogin.hidden = true;
        setCashDetailCommentCount(marker.comment_count);

        if (!marker.comments_url) {
            showCashDetailCommentError(config.cashSpotCommentErrorText);
            return;
        }

        fetch(marker.comments_url, {
            credentials: 'same-origin',
            headers: {'Accept': 'application/json'}
        }).then(function (response) {
            if (!response.ok) {
                throw new Error(config.cashSpotCommentErrorText);
            }
            return response.json();
        }).then(function (data) {
            if (!data.ok || cashDetailMarker !== marker) {
                return;
            }

            data.comments.forEach(function (comment) {
                cashDetailCommentsList.appendChild(renderCashDetailComment(comment));
            });
            setCashDetailCommentCount(data.comment_count);
            cashDetailCommentsStatus.textContent = data.comments.length ? '' : config.cashSpotCommentsEmptyText;
            cashDetailCommentsStatus.hidden = Boolean(data.comments.length);
            marker.comment_store_url = data.routes.store;
            marker.viewer_can_comment = data.viewer_can_comment;
            cashDetailCommentsForm.hidden = !data.viewer_can_comment;
            cashDetailCommentsLogin.hidden = data.viewer_can_comment;
            cashDetailCommentsLoginLink.href = data.routes.login;
        }).catch(function (error) {
            if (cashDetailMarker === marker) {
                showCashDetailCommentError(error.message);
            }
        });
    }

    function ensureCashDetailModal() {
        if (cashDetailModal) {
            return;
        }

        cashDetailModal = document.createElement('div');
        cashDetailModal.className = 'hnt-map-lightbox hnt-map-cash-detail';
        cashDetailModal.hidden = true;
        cashDetailModal.setAttribute('role', 'dialog');
        cashDetailModal.setAttribute('aria-modal', 'true');
        cashDetailModal.setAttribute('aria-labelledby', 'hntMapCashDetailTitle');

        var panel = document.createElement('div');
        var grip = document.createElement('span');
        var header = document.createElement('header');
        var titleBlock = document.createElement('div');
        var eyebrow = document.createElement('p');
        var eyebrowDot = document.createElement('span');
        var title = document.createElement('h2');
        var closeButton = document.createElement('button');
        var body = document.createElement('div');
        var media = document.createElement('div');
        var details = document.createElement('aside');
        var voteSection = document.createElement('section');
        var voteTitle = document.createElement('h3');
        var voteActions = document.createElement('div');
        var comments = document.createElement('section');
        var commentsHeader = document.createElement('header');
        var commentsTitle = document.createElement('h3');

        panel.className = 'hnt-map-lightbox-panel hnt-map-cash-detail-panel';
        grip.className = 'hnt-map-cash-detail-grip';
        grip.setAttribute('aria-hidden', 'true');
        header.className = 'hnt-map-cash-detail-header';
        titleBlock.className = 'hnt-map-cash-detail-title-block';
        eyebrow.className = 'hnt-map-cash-detail-eyebrow';
        eyebrowDot.setAttribute('aria-hidden', 'true');
        eyebrow.appendChild(eyebrowDot);
        eyebrow.appendChild(document.createTextNode(config.cashSpotEyebrowText));
        title.id = 'hntMapCashDetailTitle';
        title.textContent = config.cashSpotDetailTitle;
        closeButton.type = 'button';
        closeButton.className = 'hnt-map-lightbox-close';
        closeButton.setAttribute('aria-label', config.closeText);
        closeButton.innerHTML = '<i class="ph ph-x" aria-hidden="true"></i>';
        closeButton.addEventListener('click', closeCashDetailModal);

        cashDetailImage = document.createElement('img');
        cashDetailImage.className = 'hnt-map-lightbox-image hnt-map-cash-detail-image';
        cashDetailImage.addEventListener('load', function () {
            cashDetailImage.hidden = false;
            cashDetailError.hidden = true;
        });
        cashDetailImage.addEventListener('error', function () {
            cashDetailImage.hidden = true;
            cashDetailError.hidden = false;
        });

        cashDetailError = document.createElement('p');
        cashDetailError.className = 'hnt-map-lightbox-error';
        cashDetailError.textContent = config.cashScreenshotErrorText;
        cashDetailError.hidden = true;

        cashDetailLabel = document.createElement('p');
        cashDetailLabel.className = 'hnt-map-cash-detail-label';
        voteSection.className = 'hnt-map-cash-detail-votes';
        voteTitle.textContent = config.cashSpotHelpfulText;
        voteActions.className = 'hnt-map-cash-detail-vote-actions';

        cashDetailUpButton = document.createElement('button');
        cashDetailUpButton.type = 'button';
        cashDetailUpButton.className = 'hnt-map-cash-detail-vote';
        cashDetailUpButton.setAttribute('aria-label', config.cashSpotUpvoteText);
        cashDetailUpButton.title = config.cashSpotUpvoteText;
        cashDetailUpButton.innerHTML = '<i class="ph ph-thumbs-up" aria-hidden="true"></i>';
        cashDetailUpCount = document.createElement('span');
        cashDetailUpButton.appendChild(cashDetailUpCount);
        cashDetailUpButton.addEventListener('click', function () { submitCashDetailVote(1); });

        cashDetailDownButton = document.createElement('button');
        cashDetailDownButton.type = 'button';
        cashDetailDownButton.className = 'hnt-map-cash-detail-vote';
        cashDetailDownButton.setAttribute('aria-label', config.cashSpotDownvoteText);
        cashDetailDownButton.title = config.cashSpotDownvoteText;
        cashDetailDownButton.innerHTML = '<i class="ph ph-thumbs-down" aria-hidden="true"></i>';
        cashDetailDownCount = document.createElement('span');
        cashDetailDownButton.appendChild(cashDetailDownCount);
        cashDetailDownButton.addEventListener('click', function () { submitCashDetailVote(-1); });

        cashDetailAnonymousHint = document.createElement('p');
        cashDetailAnonymousHint.className = 'hnt-map-cash-detail-anonymous-hint';
        cashDetailAnonymousHint.textContent = config.cashSpotVoteAnonymousHintText;

        cashDetailVoteError = document.createElement('p');
        cashDetailVoteError.className = 'hnt-map-cash-detail-vote-error';
        cashDetailVoteError.textContent = config.cashSpotVoteErrorText;
        cashDetailVoteError.hidden = true;

        comments.className = 'hnt-map-cash-detail-comments';
        commentsTitle.textContent = config.cashSpotCommentsTitleText;
        cashDetailCommentsCount = document.createElement('span');
        cashDetailCommentsCount.className = 'hnt-map-cash-comments-count';
        commentsHeader.appendChild(commentsTitle);
        commentsHeader.appendChild(cashDetailCommentsCount);
        cashDetailCommentsList = document.createElement('div');
        cashDetailCommentsList.className = 'hnt-map-cash-comments-list';
        cashDetailCommentsStatus = document.createElement('p');
        cashDetailCommentsStatus.className = 'hnt-map-cash-comments-status';
        cashDetailCommentsLogin = document.createElement('p');
        cashDetailCommentsLogin.className = 'hnt-map-cash-comments-login';
        cashDetailCommentsLoginLink = document.createElement('a');
        cashDetailCommentsLoginLink.textContent = config.cashSpotCommentLoginText;
        cashDetailCommentsLogin.appendChild(cashDetailCommentsLoginLink);
        cashDetailCommentsForm = document.createElement('form');
        cashDetailCommentsForm.className = 'hnt-map-cash-comments-form';
        cashDetailCommentsInput = document.createElement('textarea');
        cashDetailCommentsInput.name = 'body';
        cashDetailCommentsInput.maxLength = 2000;
        cashDetailCommentsInput.required = true;
        cashDetailCommentsInput.placeholder = config.cashSpotCommentPlaceholderText;
        cashDetailCommentsSubmit = document.createElement('button');
        cashDetailCommentsSubmit.type = 'submit';
        cashDetailCommentsSubmit.textContent = config.cashSpotCommentSendText;
        cashDetailCommentsForm.appendChild(cashDetailCommentsInput);
        cashDetailCommentsForm.appendChild(cashDetailCommentsSubmit);
        cashDetailCommentsForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!cashDetailMarker?.comment_store_url) {
                return;
            }

            var marker = cashDetailMarker;
            cashDetailCommentsSubmit.disabled = true;
            cashDetailCommentsStatus.hidden = true;
            commentRequest(marker.comment_store_url, 'POST', {body: cashDetailCommentsInput.value}).then(function (data) {
                if (cashDetailMarker !== marker) {
                    return;
                }
                cashDetailCommentsList.appendChild(renderCashDetailComment(data.comment));
                cashDetailCommentsInput.value = '';
                setCashDetailCommentCount(data.comment_count);
            }).catch(function (error) {
                showCashDetailCommentError(error.message);
            }).finally(function () {
                cashDetailCommentsSubmit.disabled = false;
            });
        });
        comments.appendChild(commentsHeader);
        comments.appendChild(cashDetailCommentsList);
        comments.appendChild(cashDetailCommentsStatus);
        comments.appendChild(cashDetailCommentsLogin);
        comments.appendChild(cashDetailCommentsForm);

        titleBlock.appendChild(eyebrow);
        titleBlock.appendChild(title);
        titleBlock.appendChild(cashDetailLabel);
        header.appendChild(titleBlock);
        header.appendChild(closeButton);
        media.appendChild(cashDetailImage);
        media.appendChild(cashDetailError);
        voteActions.appendChild(cashDetailUpButton);
        voteActions.appendChild(cashDetailDownButton);
        voteSection.appendChild(voteTitle);
        voteSection.appendChild(voteActions);
        voteSection.appendChild(cashDetailAnonymousHint);
        voteSection.appendChild(cashDetailVoteError);
        details.appendChild(header);
        details.appendChild(voteSection);
        details.appendChild(comments);
        body.appendChild(media);
        body.appendChild(details);
        panel.appendChild(grip);
        panel.appendChild(body);
        cashDetailModal.appendChild(panel);
        body.className = 'hnt-map-cash-detail-body';
        media.className = 'hnt-map-cash-detail-media';
        details.className = 'hnt-map-cash-detail-info';
        cashDetailModal.addEventListener('click', function (event) {
            if (event.target === cashDetailModal) {
                closeCashDetailModal();
            }
        });
        document.querySelector('.hnt-map-stage').appendChild(cashDetailModal);
    }

    function openCashDetailModal(marker, trigger) {
        ensureCashDetailModal();
        cashDetailMarker = marker;
        cashDetailLastFocus = trigger || document.activeElement;
        cashDetailImage.hidden = true;
        cashDetailError.hidden = true;
        cashDetailVoteError.hidden = true;
        cashDetailLabel.textContent = marker.label;
        cashDetailImage.alt = config.cashScreenshotText + ': ' + marker.label;
        cashDetailImage.src = marker.image_url;
        updateCashDetailVoteState();
        loadCashDetailComments(marker);
        cashDetailModal.hidden = false;
        document.body.classList.add('hnt-map-lightbox-open');
        cashDetailModal.querySelector('.hnt-map-lightbox-close').focus();
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeCashDetailModal();
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
            point.on('click', function () { openCashDetailModal(marker, point.getElement()); });
        }

        point.addTo(layers[marker.type]);
        markerReferences.push({
            marker: marker,
            point: point,
            label: marker.label,
            typeLabel: config.typeLabels[marker.type] || marker.type
        });
    });

    var cashSpotToggle = document.querySelector('[data-map-cash-spot-toggle]');
    var cashSpotHint = document.querySelector('[data-map-cash-spot-hint]');
    var cashSpotModal = document.querySelector('[data-map-cash-spot-modal]');
    var cashSpotForm = document.querySelector('[data-map-cash-spot-form]');
    var cashSpotStatus = document.querySelector('[data-map-cash-spot-status]');
    var cashSpotMode = false;
    var cashSpotDraft = null;

    document.querySelectorAll('[data-map-cash-spot-guest-field]').forEach(function (field) {
        field.hidden = Boolean(config.viewerIsAuthenticated);
        field.querySelectorAll('input').forEach(function (input) {
            input.disabled = Boolean(config.viewerIsAuthenticated);
        });
    });

    function clearCashSpotDraft() {
        if (cashSpotDraft) {
            map.removeLayer(cashSpotDraft);
            cashSpotDraft = null;
        }
    }

    function setCashSpotMode(active) {
        cashSpotMode = active;
        clearCashSpotDraft();
        mapElement.classList.toggle('is-cash-spot-submitting', active);
        cashSpotToggle?.classList.toggle('is-active', active);
        cashSpotToggle?.setAttribute('aria-pressed', active ? 'true' : 'false');
        if (cashSpotHint) {
            cashSpotHint.hidden = !active;
            cashSpotHint.textContent = config.cashSpotSelectText;
        }
    }

    function closeCashSpotModal(cancelMode) {
        if (cashSpotModal) {
            cashSpotModal.hidden = true;
        }
        document.body.classList.remove('hnt-map-lightbox-open');
        if (cashSpotStatus) {
            cashSpotStatus.textContent = '';
        }
        if (cancelMode) {
            cashSpotForm?.reset();
            setCashSpotMode(false);
        }
    }

    function openCashSpotModal(latlng) {
        if (!cashSpotModal || !cashSpotForm) {
            return;
        }

        cashSpotForm.elements.x.value = latlng.lng.toFixed(6);
        cashSpotForm.elements.y.value = latlng.lat.toFixed(6);
        cashSpotModal.hidden = false;
        document.body.classList.add('hnt-map-lightbox-open');
        cashSpotForm.elements.image.focus();
    }

    cashSpotToggle?.addEventListener('click', function () {
        if (!cashSpotMode && typeof setMeasuring === 'function') {
            setMeasuring(false, config.measureIdleText);
        }
        setCashSpotMode(!cashSpotMode);

        if (cashSpotMode && window.matchMedia('(max-width: 768px)').matches) {
            setToolsOpen(false);
            window.setTimeout(function () { map.invalidateSize(); }, 240);
        }
    });

    map.on('click', function (event) {
        if (!cashSpotMode || !bounds.contains(event.latlng)) {
            return;
        }

        clearCashSpotDraft();
        cashSpotDraft = window.L.marker(event.latlng, {icon: markerIcon('cash'), interactive: false}).addTo(map);
        openCashSpotModal(event.latlng);
    });

    document.querySelectorAll('[data-map-cash-spot-cancel]').forEach(function (button) {
        button.addEventListener('click', function () { closeCashSpotModal(true); });
    });

    cashSpotModal?.addEventListener('click', function (event) {
        if (event.target === cashSpotModal) {
            closeCashSpotModal(true);
        }
    });

    cashSpotForm?.addEventListener('submit', function (event) {
        event.preventDefault();
        var submitButton = cashSpotForm.querySelector('[type="submit"]');
        submitButton.disabled = true;
        cashSpotStatus.textContent = config.cashSpotRunningText;

        fetch(config.cashSpotSubmissionUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: new FormData(cashSpotForm),
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (payload) {
                if (!response.ok) {
                    var errors = payload.errors || {};
                    var firstError = Object.keys(errors).length ? errors[Object.keys(errors)[0]][0] : null;
                    throw new Error(firstError || payload.message || config.cashSpotErrorText);
                }
            });
        }).then(function () {
            closeCashSpotModal(true);
            showMapToast(config.cashSpotPendingText);
        }).catch(function (error) {
            cashSpotStatus.textContent = error.message || config.cashSpotErrorText;
        }).finally(function () {
            submitButton.disabled = false;
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && cashSpotModal && !cashSpotModal.hidden) {
            closeCashSpotModal(true);
        }
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
        if (!measuring && cashSpotMode) {
            closeCashSpotModal(true);
        }
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
        closeCashSpotModal(true);
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
