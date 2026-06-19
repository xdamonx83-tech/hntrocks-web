document.addEventListener('DOMContentLoaded', function () {
    const appWindow = document.getElementById('hntPreviewApp') || document.querySelector('.app-window');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const profileTrigger = document.getElementById('profileTrigger');
    const profileMenu = document.getElementById('profileMenu');
    const notificationShell = document.getElementById('hntNotificationShell');
    const notificationOpenTriggers = Array.from(document.querySelectorAll('[data-hnt-notifications-open]'));
    const notificationCloseTriggers = Array.from(document.querySelectorAll('[data-hnt-notifications-close]'));
    const friendRequestShell = document.getElementById('hntFriendRequestShell');
    const friendRequestOpenTriggers = Array.from(document.querySelectorAll('[data-hnt-friend-requests-open]'));
    const friendRequestCloseTriggers = Array.from(document.querySelectorAll('[data-hnt-friend-requests-close]'));
    const messageShell = document.getElementById('hntMessageShell');
    const messageOpenTriggers = Array.from(document.querySelectorAll('[data-hnt-messages-open]'));
    const messageCloseTriggers = Array.from(document.querySelectorAll('[data-hnt-messages-close]'));
    const mobileMenuShell = document.querySelector('[data-hnt-mobile-menu-shell]');
    const mobileMenuBackdrop = document.querySelector('.hnt-mobile-menu-backdrop');
    const mobileMenuOpenTriggers = Array.from(document.querySelectorAll('[data-hnt-mobile-menu-open]'));
    const mobileMenuCloseTriggers = Array.from(document.querySelectorAll('[data-hnt-mobile-menu-close]'));
    const chatTabsShell = document.querySelector('[data-hnt-chat-tabs-shell]');
    const chatTabsStorageKey = 'hntPreviewOpenChatTabsV1';
    const momentsList = document.getElementById('momentsList');
    const composerModal = document.getElementById('composerModal');
    const lfgCreateModal = document.getElementById('hntLfgCreateModal');
    const lfgWizardModals = Array.from(document.querySelectorAll('[data-hnt-lfg-wizard-modal]'));
    const postModal = document.getElementById('postModal');
    const reportModal = document.getElementById('hntReportModal');
    const likesModal = document.getElementById('hntLikesModal');
    const likesModalBody = likesModal ? likesModal.querySelector('[data-hnt-likes-body]') : null;
    const likesModalTitle = likesModal ? likesModal.querySelector('[data-hnt-likes-title]') : null;
    const likesModalSubtitle = likesModal ? likesModal.querySelector('[data-hnt-likes-subtitle]') : null;
    const lightboxModal = document.getElementById('hntLightbox');
    const lightboxImage = lightboxModal ? lightboxModal.querySelector('[data-hnt-lightbox-image]') : null;
    const lightboxCaption = lightboxModal ? lightboxModal.querySelector('[data-hnt-lightbox-caption]') : null;
    const lightboxCount = lightboxModal ? lightboxModal.querySelector('[data-hnt-lightbox-count]') : null;
    let lightboxItems = [];
    let lightboxIndex = 0;
    const reportForm = reportModal ? reportModal.querySelector('[data-hnt-report-form]') : null;
    const reportStatus = reportModal ? reportModal.querySelector('[data-hnt-report-status]') : null;
    const reportType = reportModal ? reportModal.querySelector('[data-hnt-report-type]') : null;
    const reportId = reportModal ? reportModal.querySelector('[data-hnt-report-id]') : null;
    const reportLabel = reportModal ? reportModal.querySelector('[data-hnt-report-label]') : null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
    const previewI18n = window.HNT_PREVIEW_I18N || {};
    const previewLocale = window.HNT_PREVIEW_LOCALE || document.documentElement.lang || 'de';

    function t(key, fallback, replacements) {
        let value = previewI18n[key] || fallback || key;
        if (replacements && typeof replacements === 'object') {
            Object.keys(replacements).forEach(function (replaceKey) {
                const replaceValue = replacements[replaceKey];
                value = String(value)
                    .replace(new RegExp(':' + replaceKey, 'g'), String(replaceValue))
                    .replace(new RegExp('\\{' + replaceKey + '\\}', 'g'), String(replaceValue));
            });
        }
        return String(value);
    }

    function formatNumber(value) {
        const numericCount = Number(value || 0);
        try {
            return new Intl.NumberFormat(previewI18n.preview_locale_number || previewLocale || 'de-DE').format(numericCount);
        } catch (error) {
            return String(numericCount);
        }
    }

    function formatBadgeCount(value) {
        const count = Math.max(0, Number.parseInt(value, 10) || 0);
        return count > 99 ? '99+' : String(count);
    }

    function setPreviewBadge(selector, count) {
        const numericCount = Math.max(0, Number.parseInt(count, 10) || 0);
        document.querySelectorAll(selector).forEach(function (node) {
            node.textContent = formatBadgeCount(numericCount);
            node.dataset.count = String(numericCount);
            node.hidden = numericCount <= 0;
        });
    }

    function updatePreviewNotificationBadge(count) {
        if (typeof count === 'undefined' || count === null) return;
        setPreviewBadge('[data-hnt-notification-count]', count);
        document.querySelectorAll('[data-hnt-notifications-unread-count]').forEach(function (node) {
            node.textContent = String(Math.max(0, Number.parseInt(count, 10) || 0));
        });
    }

    function updatePreviewFriendRequestBadge(count) {
        if (typeof count === 'undefined' || count === null) return;
        const numericCount = Math.max(0, Number.parseInt(count, 10) || 0);
        setPreviewBadge('[data-hnt-friend-request-count]', numericCount);
        document.querySelectorAll('[data-hnt-friend-requests-count]').forEach(function (node) {
            node.textContent = String(numericCount);
        });

        if (friendRequestShell) {
            const empty = friendRequestShell.querySelector('[data-hnt-friend-requests-empty]');
            const hasItems = Boolean(friendRequestShell.querySelector('[data-hnt-friend-request-item]'));
            if (empty) empty.hidden = hasItems;
        }
    }

    const openComposer = document.getElementById('openComposer');
    const composerOpenTriggers = Array.from(document.querySelectorAll('[data-hnt-composer-open]'));
    const closeComposerButtons = document.querySelectorAll('#composerModal .modal-close, #composerModal .modal-close-secondary');
    const postModalCloseButtons = document.querySelectorAll('.post-modal-close');
    const postModalContent = postModal ? postModal.querySelector('[data-hnt-post-modal-content]') : null;
    const postModalSubtitle = postModal ? postModal.querySelector('[data-hnt-post-modal-subtitle]') : null;
    const postModalCommentForm = postModal ? postModal.querySelector('[data-hnt-comment-form]') : null;
    const postModalCommentTextarea = postModal ? postModal.querySelector('[data-hnt-comment-textarea]') : null;
    const postModalCommentStatus = postModal ? postModal.querySelector('[data-hnt-comment-status]') : null;
    let currentPostModalUrl = '';
    let currentPostModalStoreUrl = '';
    let currentPostModalPostId = '';

    function cssEscape(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') return window.CSS.escape(value);
        return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char] || char;
        });
    }

    const emojiStorageKey = 'hntPreviewEmojiUsageV1:' + String(window.HNT_PREVIEW_USER_ID || 'guest');
    const emojiCategories = [
        { key: 'recent', icon: '＋', labelKey: 'preview_emoji_recent', fallback: 'Oft genutzt', emojis: [] },
        { key: 'smileys', icon: '😀', labelKey: 'preview_emoji_smileys', fallback: 'Smileys & Personen', emojis: ['😀','😃','😄','😁','😆','😅','🤣','😂','🙂','🙃','😉','😊','😇','🥰','😍','🤩','😘','😗','😚','😙','🥲','😋','😛','😜','🤪','😝','🤑','🤗','🤭','🫢','🫣','🤫','🤔','🫡','🤐','🤨','😐','😑','😶','🫥','😶‍🌫️','😏','😒','🙄','😬','😮‍💨','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕','🤢','🤮','🤧','🥵','🥶','🥴','😵','😵‍💫','🤯','🤠','🥳','🥸','😎','🤓','🧐','😕','🫤','😟','🙁','☹️','😮','😯','😲','😳','🥺','🥹','😦','😧','😨','😰','😥','😢','😭','😱','😖','😣','😞','😓','😩','😫','🥱','😤','😡','😠','🤬','😈','👿','💀','☠️','💩','🤡','👻','👽','🤖'] },
        { key: 'gestures', icon: '👍', labelKey: 'preview_emoji_gestures', fallback: 'Gesten', emojis: ['👍','👎','👌','🤌','🤏','✌️','🤞','🫰','🤟','🤘','🤙','👈','👉','👆','🖕','👇','☝️','🫵','👋','🤚','🖐️','✋','🖖','👏','🙌','🫶','🤲','🤝','🙏','✍️','💪','🦾','🧠','👀','👁️','🫡','🏆','🥇','🥈','🥉','🎯','🔥','💯','⚔️','🩸','💀','🕯️'] },
        { key: 'nature', icon: '🐺', labelKey: 'preview_emoji_nature', fallback: 'Tiere & Natur', emojis: ['🐶','🐺','🦊','🐱','🦁','🐯','🐴','🫎','🦌','🐗','🐭','🐹','🐰','🦇','🐻','🐦','🐦‍⬛','🦅','🦆','🦉','🐸','🐍','🦎','🐊','🐢','🦂','🕷️','🕸️','🦟','🪰','🌲','🌳','🌿','🍂','🍁','🍄','🌙','☀️','⭐','⚡','🔥','💧','🌧️','🌫️'] },
        { key: 'food', icon: '🍎', labelKey: 'preview_emoji_food', fallback: 'Essen & Trinken', emojis: ['🍏','🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍒','🍑','🍍','🥥','🥝','🍅','🥑','🥔','🥕','🌽','🌶️','🫑','🥒','🥬','🥦','🧄','🧅','🥜','🍞','🥐','🥨','🧀','🥩','🍗','🍖','🥓','🍔','🍟','🍕','🌭','🌮','🌯','🥪','🍺','🍻','🥃','☕'] },
        { key: 'activity', icon: '🏀', labelKey: 'preview_emoji_activity', fallback: 'Aktivität', emojis: ['⚽','🏀','🏈','⚾','🥎','🎾','🏐','🏉','🥏','🎱','🪀','🏓','🏸','🥅','🏒','🏑','🏏','⛳','🏹','🎣','🥊','🥋','🎮','🕹️','🎲','♟️','🎰','🎯','🎖️','🏆','🥇','🥈','🥉'] },
        { key: 'objects', icon: '💡', labelKey: 'preview_emoji_objects', fallback: 'Objekte', emojis: ['💡','🔦','🕯️','🪓','🔪','🧨','⚔️','🛡️','🔫','🏹','⛓️','🪤','🧰','🧲','⚙️','🗝️','🔒','🔓','📌','📍','📎','🧾','📦','🎁','💰','🪙','💎','🔮','🩹','💊','🧪','🧭','⏱️','⏰','📱','💻','🎧','📷','🎥'] },
        { key: 'symbols', icon: '✳️', labelKey: 'preview_emoji_symbols', fallback: 'Symbole', emojis: ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟','☮️','✝️','☪️','🕉️','☸️','✡️','🔯','🕎','☯️','☦️','🛐','⛎','♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓','✅','☑️','✔️','❌','❎','⚠️','🚫','🔞','♻️','🔰','⭐','✨','⚡','🔥','💯'] }
    ];
    let activeEmojiTextarea = null;
    let activeEmojiTrigger = null;
    let activeEmojiCategory = 'recent';
    let emojiPickerNode = null;

    function loadEmojiUsage() {
        try {
            const parsed = JSON.parse(window.localStorage.getItem(emojiStorageKey) || '{}');
            return {
                recent: Array.isArray(parsed.recent) ? parsed.recent.filter(Boolean).slice(0, 24) : [],
                counts: parsed.counts && typeof parsed.counts === 'object' ? parsed.counts : {}
            };
        } catch (error) {
            return { recent: [], counts: {} };
        }
    }

    function saveEmojiUsage(emoji) {
        if (!emoji) return;
        const usage = loadEmojiUsage();
        usage.counts[emoji] = (Number(usage.counts[emoji]) || 0) + 1;
        usage.recent = [emoji].concat(usage.recent.filter(function (item) { return item !== emoji; })).slice(0, 24);
        try {
            window.localStorage.setItem(emojiStorageKey, JSON.stringify(usage));
        } catch (error) {}
    }

    function emojiRecentList() {
        const usage = loadEmojiUsage();
        const ranked = Object.keys(usage.counts || {}).sort(function (a, b) {
            const diff = (Number(usage.counts[b]) || 0) - (Number(usage.counts[a]) || 0);
            if (diff !== 0) return diff;
            return usage.recent.indexOf(a) - usage.recent.indexOf(b);
        });
        const combined = [];
        ranked.concat(usage.recent).forEach(function (emoji) {
            if (emoji && combined.indexOf(emoji) === -1) combined.push(emoji);
        });
        return combined.slice(0, 36);
    }

    function emojiCategoryByKey(key) {
        return emojiCategories.find(function (category) { return category.key === key; }) || emojiCategories[0];
    }

    function resolveEmojiTextarea(trigger) {
        const target = trigger ? trigger.getAttribute('data-hnt-emoji-target') : '';
        if (target === 'composer' && composerModal) return composerModal.querySelector('[data-hnt-composer-textarea]');
        if (target === 'post-comment') return postModalCommentTextarea || (postModal ? postModal.querySelector('[data-hnt-comment-textarea]') : null);
        const scope = trigger ? trigger.closest('form, .composer-input-shell, .post-modal-input, [data-hnt-comment-reply-form]') : null;
        return scope ? scope.querySelector('textarea') : null;
    }

    function insertTextAtCursor(textarea, text) {
        if (!textarea || !text) return;
        const start = typeof textarea.selectionStart === 'number' ? textarea.selectionStart : textarea.value.length;
        const end = typeof textarea.selectionEnd === 'number' ? textarea.selectionEnd : textarea.value.length;
        const before = textarea.value.slice(0, start);
        const after = textarea.value.slice(end);
        textarea.value = before + text + after;
        const next = start + text.length;
        textarea.focus();
        try {
            textarea.setSelectionRange(next, next);
        } catch (error) {}
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function positionEmojiPicker(trigger) {
        if (!emojiPickerNode || !trigger) return;

        const rect = trigger.getBoundingClientRect();
        const modal = trigger.closest('.composer-modal, .post-detail-modal');
        const modalRect = modal ? modal.getBoundingClientRect() : null;
        const inputShell = trigger.closest('.composer-input-shell, .post-modal-input') || trigger;
        const inputRect = inputShell.getBoundingClientRect();
        const gap = 12;
        const viewportPadding = 12;
        const availableWidth = Math.max(280, window.innerWidth - (viewportPadding * 2));
        const pickerWidth = Math.min(420, availableWidth, Math.max(320, inputRect.width || 320));
        const pickerMaxHeight = Math.min(430, Math.max(260, window.innerHeight - (viewportPadding * 2)));

        emojiPickerNode.style.width = pickerWidth + 'px';
        emojiPickerNode.style.maxHeight = pickerMaxHeight + 'px';

        const measuredHeight = Math.min(
            pickerMaxHeight,
            Math.max(emojiPickerNode.offsetHeight || 0, emojiPickerNode.scrollHeight || 0, 120)
        );
        const clampLeftMin = modalRect ? Math.max(viewportPadding, modalRect.left + 16) : viewportPadding;
        const clampLeftMax = modalRect ? Math.min(window.innerWidth - pickerWidth - viewportPadding, modalRect.right - pickerWidth - 16) : window.innerWidth - pickerWidth - viewportPadding;
        const clampTopMin = modalRect ? Math.max(viewportPadding, modalRect.top + 16) : viewportPadding;
        const clampTopMax = window.innerHeight - measuredHeight - viewportPadding;

        let left = inputRect.right - pickerWidth;
        if (modalRect && inputRect.width >= pickerWidth) {
            left = inputRect.right - pickerWidth;
        } else if (modalRect) {
            left = rect.right - pickerWidth;
        }

        let top = inputRect.top - measuredHeight - gap;
        const belowTop = inputRect.bottom + gap;
        const canOpenBelow = belowTop + measuredHeight <= window.innerHeight - viewportPadding;
        if (top < clampTopMin && canOpenBelow) {
            top = belowTop;
        }
        if (top < clampTopMin) {
            top = clampTopMin;
        }
        if (top > clampTopMax) {
            top = Math.max(clampTopMin, clampTopMax);
        }

        left = Math.max(clampLeftMin, Math.min(left, Math.max(clampLeftMin, clampLeftMax)));

        emojiPickerNode.style.left = left + 'px';
        emojiPickerNode.style.top = top + 'px';
    }

    function renderEmojiPicker() {
        if (!emojiPickerNode) return;
        const category = emojiCategoryByKey(activeEmojiCategory);
        const emojis = activeEmojiCategory === 'recent' ? emojiRecentList() : category.emojis;
        const tabs = emojiCategories.map(function (item) {
            const label = t(item.labelKey, item.fallback);
            return '<button type="button" class="hnt-emoji-tab ' + (item.key === activeEmojiCategory ? 'is-active' : '') + '" data-hnt-emoji-category="' + item.key + '" aria-label="' + escapeHtml(label) + '" title="' + escapeHtml(label) + '">' + item.icon + '</button>';
        }).join('');
        const buttons = emojis.map(function (emoji) {
            return '<button type="button" class="hnt-emoji-choice" data-hnt-emoji-value="' + escapeHtml(emoji) + '" aria-label="' + escapeHtml(emoji) + '">' + emoji + '</button>';
        }).join('');
        const emptyRecent = activeEmojiCategory === 'recent' && !buttons;
        const body = emptyRecent
            ? '<p class="hnt-emoji-empty">' + escapeHtml(t('preview_emoji_empty_recent', 'Noch keine Emojis genutzt.')) + '</p>'
            : '<div class="hnt-emoji-grid">' + buttons + '</div>';
        emojiPickerNode.innerHTML = '<div class="hnt-emoji-picker-tabs">' + tabs + '</div><div class="hnt-emoji-picker-section"><strong>' + escapeHtml(t(category.labelKey, category.fallback)) + '</strong>' + body + '</div>';
    }

    function ensureEmojiPicker() {
        if (emojiPickerNode) return emojiPickerNode;
        emojiPickerNode = document.createElement('div');
        emojiPickerNode.className = 'hnt-emoji-picker';
        emojiPickerNode.setAttribute('role', 'dialog');
        emojiPickerNode.setAttribute('aria-label', t('preview_emoji_picker_title', 'Emoji auswählen'));
        emojiPickerNode.hidden = true;
        document.body.appendChild(emojiPickerNode);
        emojiPickerNode.addEventListener('click', function (event) {
            event.stopPropagation();
            const categoryButton = event.target.closest('[data-hnt-emoji-category]');
            if (categoryButton) {
                event.preventDefault();
                activeEmojiCategory = categoryButton.dataset.hntEmojiCategory || 'recent';
                renderEmojiPicker();
                positionEmojiPicker(activeEmojiTrigger);
                return;
            }
            const emojiButton = event.target.closest('[data-hnt-emoji-value]');
            if (emojiButton) {
                event.preventDefault();
                const emoji = emojiButton.dataset.hntEmojiValue || emojiButton.textContent || '';
                insertTextAtCursor(activeEmojiTextarea, emoji);
                saveEmojiUsage(emoji);
                renderEmojiPicker();
                positionEmojiPicker(activeEmojiTrigger);
            }
        });
        return emojiPickerNode;
    }

    function openEmojiPicker(trigger) {
        const textarea = resolveEmojiTextarea(trigger);
        if (!textarea) return;
        activeEmojiTextarea = textarea;
        activeEmojiTrigger = trigger;
        activeEmojiCategory = 'recent';
        const picker = ensureEmojiPicker();
        renderEmojiPicker();
        picker.hidden = false;
        picker.classList.add('open');
        if (trigger) trigger.setAttribute('aria-expanded', 'true');
        positionEmojiPicker(trigger);
    }

    function closeEmojiPicker() {
        if (!emojiPickerNode || emojiPickerNode.hidden) return;
        emojiPickerNode.hidden = true;
        emojiPickerNode.classList.remove('open');
        if (activeEmojiTrigger) activeEmojiTrigger.setAttribute('aria-expanded', 'false');
        activeEmojiTrigger = null;
    }


    function normalizeHashtag(value) {
        return String(value || '')
            .replace(/^#+/, '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9_-]/g, '')
            .replace(/^[-_]+|[-_]+$/g, '')
            .slice(0, 50);
    }

    function extractHashtags(value) {
        const text = String(value || '');
        let matches = [];

        try {
            matches = text.match(/(?<![\p{L}\p{N}_])#[\p{L}\p{N}_][\p{L}\p{N}_-]{0,49}/gu) || [];
        } catch (error) {
            matches = text.match(/(^|\s)#[A-Za-z0-9_][A-Za-z0-9_-]{0,49}/g) || [];
        }

        const seen = new Set();
        return matches.map(function (raw) {
            return normalizeHashtag(raw.trim());
        }).filter(function (tag) {
            if (!tag || seen.has(tag)) return false;
            seen.add(tag);
            return true;
        }).slice(0, 8);
    }

    function updateHashtagPreview(input) {
        const scope = input.closest('label, .composer-input-shell, .hh-moment-create-form, form') || input.parentElement;
        const preview = scope ? scope.querySelector('[data-hnt-hashtag-preview]') : null;
        if (!preview) return;

        const tags = extractHashtags(input.value);
        if (!tags.length) {
            preview.hidden = true;
            preview.innerHTML = '';
            return;
        }

        preview.hidden = false;
        preview.innerHTML = tags.map(function (tag) {
            return '<a class="hnt-hashtag-preview-chip" href="/hashtags/' + encodeURIComponent(tag) + '">#' + escapeHtml(tag) + '</a>';
        }).join('');
    }

    function initHashtagDetection(context) {
        const root = context || document;
        root.querySelectorAll('[data-hnt-hashtag-input]').forEach(function (input) {
            if (input.dataset.hntHashtagReady === '1') return;
            input.dataset.hntHashtagReady = '1';
            input.addEventListener('input', function () { updateHashtagPreview(input); });
            updateHashtagPreview(input);
        });
    }

    function anyModalOpen() {
        return Boolean(
            (composerModal && composerModal.classList.contains('open')) ||
            (lfgWizardModals.some(function (modal) { return modal.classList.contains('open'); })) ||
            (postModal && postModal.classList.contains('open')) ||
            (reportModal && reportModal.classList.contains('open')) ||
            (likesModal && likesModal.classList.contains('open')) ||
            (lightboxModal && lightboxModal.classList.contains('open'))
        );
    }

    function syncModalBodyState() {
        document.body.classList.toggle('modal-open', anyModalOpen());
    }

    function closeNavDropdowns(exceptGroup) {
        document.querySelectorAll('.nav-dropdown.open').forEach(function (openGroup) {
            if (exceptGroup && openGroup === exceptGroup) return;
            openGroup.classList.remove('open');
            const openButton = openGroup.querySelector('.nav-dropdown-toggle');
            if (openButton) openButton.setAttribute('aria-expanded', 'false');
        });
    }

    function toggleProfileMenu(forceClose) {
        if (!profileMenu || !profileTrigger) return;
        const isOpen = profileMenu.classList.contains('open');
        const shouldOpen = forceClose ? false : !isOpen;
        profileMenu.classList.toggle('open', shouldOpen);
        profileMenu.setAttribute('aria-hidden', String(!shouldOpen));
        profileTrigger.setAttribute('aria-expanded', String(shouldOpen));
    }

    document.querySelectorAll('.nav-dropdown-toggle').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.stopPropagation();
            const group = button.closest('.nav-dropdown');
            if (!group) return;
            const willOpen = !group.classList.contains('open');
            closeNavDropdowns(group);
            group.classList.toggle('open', willOpen);
            button.setAttribute('aria-expanded', String(willOpen));
            if (willOpen) toggleProfileMenu(true);
        });
    });

    document.querySelectorAll('.nav-submenu-close').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const group = button.closest('.nav-dropdown');
            if (!group) return;
            group.classList.remove('open');
            const toggle = group.querySelector('.nav-dropdown-toggle');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.nav-dropdown')) {
            closeNavDropdowns();
        }
    });


    function setMobileMenuOpen(isOpen) {
        if (!mobileMenuShell) return;
        mobileMenuShell.classList.toggle('open', Boolean(isOpen));
        mobileMenuShell.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        if (mobileMenuBackdrop) {
            mobileMenuBackdrop.hidden = !isOpen;
            mobileMenuBackdrop.classList.toggle('open', Boolean(isOpen));
        }
        mobileMenuOpenTriggers.forEach(function (trigger) {
            trigger.setAttribute('aria-expanded', String(Boolean(isOpen)));
            trigger.classList.toggle('active', Boolean(isOpen));
        });
        document.body.classList.toggle('hnt-mobile-menu-open', Boolean(isOpen));
        if (isOpen) {
            closeNavDropdowns();
            toggleProfileMenu(true);
            const firstFocusable = mobileMenuShell.querySelector('a, button, input, [tabindex]:not([tabindex="-1"])');
            if (firstFocusable) window.setTimeout(function () { firstFocusable.focus({ preventScroll: true }); }, 20);
        }
    }

    function setPreviewSidebarCollapsed(collapsed) {
        if (!appWindow || !sidebarToggle) return;
        appWindow.classList.toggle('sidebar-collapsed', Boolean(collapsed));
        sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
    }

    function isNotificationShellOpen() {
        return Boolean(notificationShell && notificationShell.classList.contains('open'));
    }

    function isFriendRequestShellOpen() {
        return Boolean(friendRequestShell && friendRequestShell.classList.contains('open'));
    }

    function isMessageShellOpen() {
        return Boolean(messageShell && messageShell.classList.contains('open'));
    }

    function closeNotificationShellPanelOnly() {
        if (!notificationShell || !appWindow) return;
        if (!isFriendRequestShellOpen()) appWindow.classList.remove('notifications-shell-open');
        notificationShell.classList.remove('open');
        notificationShell.setAttribute('aria-hidden', 'true');
        notificationOpenTriggers.forEach(function (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
        });
    }

    function closeFriendRequestShellPanelOnly() {
        if (!friendRequestShell || !appWindow) return;
        if (!isNotificationShellOpen()) appWindow.classList.remove('notifications-shell-open');
        friendRequestShell.classList.remove('open');
        friendRequestShell.setAttribute('aria-hidden', 'true');
        friendRequestOpenTriggers.forEach(function (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
        });
    }

    function closeMessageShellPanelOnly() {
        if (!messageShell || !appWindow) return;
        appWindow.classList.remove('messages-shell-open');
        messageShell.classList.remove('open');
        messageShell.setAttribute('aria-hidden', 'true');
        messageOpenTriggers.forEach(function (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
        });
    }

    function setNotificationShellOpen(isOpen) {
        if (!notificationShell || !appWindow) return;
        if (isOpen) {
            closeFriendRequestShellPanelOnly();
            closeMessageShellPanelOnly();
        }
        appWindow.classList.toggle('notifications-shell-open', Boolean(isOpen));
        notificationShell.classList.toggle('open', Boolean(isOpen));
        notificationShell.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        notificationOpenTriggers.forEach(function (trigger) {
            trigger.setAttribute('aria-expanded', String(Boolean(isOpen)));
        });
        if (isOpen) {
            setMobileMenuOpen(false);
            setPreviewSidebarCollapsed(true);
            closeNavDropdowns();
            toggleProfileMenu(true);
            refreshNotificationShell(true);
            const firstFocusable = notificationShell.querySelector('button, a, input, [tabindex]:not([tabindex="-1"])');
            if (firstFocusable) window.setTimeout(function () { firstFocusable.focus({ preventScroll: true }); }, 20);
        } else if (!isMessageShellOpen() && !isFriendRequestShellOpen()) {
            setPreviewSidebarCollapsed(false);
        }
    }

    function setFriendRequestShellOpen(isOpen) {
        if (!friendRequestShell || !appWindow) return;
        if (isOpen) {
            closeNotificationShellPanelOnly();
            closeMessageShellPanelOnly();
        }
        appWindow.classList.toggle('notifications-shell-open', Boolean(isOpen));
        friendRequestShell.classList.toggle('open', Boolean(isOpen));
        friendRequestShell.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        friendRequestOpenTriggers.forEach(function (trigger) {
            trigger.setAttribute('aria-expanded', String(Boolean(isOpen)));
        });
        if (isOpen) {
            setMobileMenuOpen(false);
            setPreviewSidebarCollapsed(true);
            closeNavDropdowns();
            toggleProfileMenu(true);
            refreshFriendRequestShell(true);
            const firstFocusable = friendRequestShell.querySelector('button, a, input, [tabindex]:not([tabindex="-1"])');
            if (firstFocusable) window.setTimeout(function () { firstFocusable.focus({ preventScroll: true }); }, 20);
        } else if (!isMessageShellOpen() && !isNotificationShellOpen()) {
            setPreviewSidebarCollapsed(false);
        }
    }

    function setMessageShellOpen(isOpen) {
        if (!messageShell || !appWindow) return;
        if (isOpen) {
            closeNotificationShellPanelOnly();
            closeFriendRequestShellPanelOnly();
        }
        appWindow.classList.toggle('messages-shell-open', Boolean(isOpen));
        messageShell.classList.toggle('open', Boolean(isOpen));
        messageShell.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        messageOpenTriggers.forEach(function (trigger) {
            trigger.setAttribute('aria-expanded', String(Boolean(isOpen)));
        });
        if (isOpen) {
            setMobileMenuOpen(false);
            setPreviewSidebarCollapsed(true);
            closeNavDropdowns();
            toggleProfileMenu(true);
            refreshMessageShell(true);
            const firstFocusable = messageShell.querySelector('a, button, input, [tabindex]:not([tabindex="-1"])');
            if (firstFocusable) window.setTimeout(function () { firstFocusable.focus({ preventScroll: true }); }, 20);
        } else if (!isNotificationShellOpen() && !isFriendRequestShellOpen()) {
            setPreviewSidebarCollapsed(false);
        }
    }

    if (sidebarToggle && appWindow) {
        sidebarToggle.addEventListener('click', function () {
            const collapsed = !appWindow.classList.contains('sidebar-collapsed');
            setPreviewSidebarCollapsed(collapsed);
            if (!collapsed) {
                setNotificationShellOpen(false);
                setFriendRequestShellOpen(false);
                setMessageShellOpen(false);
            }
        });
    }

    mobileMenuOpenTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            setNotificationShellOpen(false);
            setFriendRequestShellOpen(false);
            setMessageShellOpen(false);
            setMobileMenuOpen(!(mobileMenuShell && mobileMenuShell.classList.contains('open')));
        });
    });

    mobileMenuCloseTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            setMobileMenuOpen(false);
        });
    });

    if (mobileMenuBackdrop) {
        mobileMenuBackdrop.addEventListener('click', function () {
            setMobileMenuOpen(false);
        });
    }

    notificationOpenTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            if (!notificationShell || !appWindow) return;
            event.preventDefault();
            setNotificationShellOpen(!notificationShell.classList.contains('open'));
        });
    });

    notificationCloseTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            setNotificationShellOpen(false);
        });
    });

    friendRequestOpenTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            if (!friendRequestShell || !appWindow) return;
            event.preventDefault();
            setFriendRequestShellOpen(!friendRequestShell.classList.contains('open'));
        });
    });

    friendRequestCloseTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            setFriendRequestShellOpen(false);
        });
    });

    messageOpenTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            if (!messageShell || !appWindow) return;
            event.preventDefault();
            setMessageShellOpen(!messageShell.classList.contains('open'));
        });
    });

    messageCloseTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            setMessageShellOpen(false);
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && mobileMenuShell && mobileMenuShell.classList.contains('open')) {
            setMobileMenuOpen(false);
            return;
        }
        if (event.key === 'Escape' && notificationShell && notificationShell.classList.contains('open')) {
            setNotificationShellOpen(false);
            return;
        }
        if (event.key === 'Escape' && friendRequestShell && friendRequestShell.classList.contains('open')) {
            setFriendRequestShellOpen(false);
            return;
        }
        if (event.key === 'Escape' && messageShell && messageShell.classList.contains('open')) {
            setMessageShellOpen(false);
        }
    });

    const liveBadgesConfig = window.HNT_PREVIEW_LIVE_BADGES || {};
    const liveBadgesEndpoint = liveBadgesConfig.endpoint || '';
    const liveNotificationsEndpoint = liveBadgesConfig.notificationsEndpoint || '';
    const liveMessagesEndpoint = liveBadgesConfig.messagesEndpoint || '';
    const liveFriendRequestsEndpoint = liveBadgesConfig.friendRequestsEndpoint || '';
    const liveBadgesInterval = Math.max(5000, Number(liveBadgesConfig.interval || 15000));
    const liveShellInterval = Math.max(3000, Number(liveBadgesConfig.shellInterval || 5000));
    const liveChatTabInterval = Math.max(2500, Number(liveBadgesConfig.chatTabInterval || 4500));
    let liveBadgesInFlight = false;
    let liveNotificationsInFlight = false;
    let liveMessagesInFlight = false;
    let liveFriendRequestsInFlight = false;

    async function refreshNotificationShell(force) {
        if (!liveNotificationsEndpoint || liveNotificationsInFlight) return;
        if (!force && (!isNotificationShellOpen() || document.hidden)) return;
        liveNotificationsInFlight = true;
        try {
            const response = await fetch(liveNotificationsEndpoint, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) return;
            const payload = await response.json();
            const list = notificationShell ? notificationShell.querySelector('[data-hnt-notification-shell-scroll]') : null;
            if (list && typeof payload.html === 'string') list.innerHTML = payload.html;
            updatePreviewNotificationBadge(payload.unread_count);
            document.querySelectorAll('[data-hnt-notifications-total-count]').forEach(function (node) {
                node.textContent = String(Math.max(0, Number.parseInt(payload.total_count, 10) || 0));
            });
            const readAll = notificationShell ? notificationShell.querySelector('[data-hnt-notification-read-all]') : null;
            if (readAll) readAll.hidden = (Math.max(0, Number.parseInt(payload.unread_count, 10) || 0) <= 0);
        } catch (error) {
            // Silent retry on the next interval/open.
        } finally {
            liveNotificationsInFlight = false;
        }
    }

    async function refreshMessageShell(force) {
        if (!liveMessagesEndpoint || liveMessagesInFlight) return;
        if (!force && (!isMessageShellOpen() || document.hidden)) return;
        liveMessagesInFlight = true;
        try {
            const response = await fetch(liveMessagesEndpoint, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) return;
            const payload = await response.json();
            const list = messageShell ? messageShell.querySelector('[data-hnt-message-shell-scroll]') : null;
            if (list && typeof payload.html === 'string') list.innerHTML = payload.html;
            updatePreviewMessageBadge(payload.unread_count);
            document.querySelectorAll('[data-hnt-messages-total-count]').forEach(function (node) {
                node.textContent = String(Math.max(0, Number.parseInt(payload.total_count, 10) || 0));
            });
        } catch (error) {
            // Silent retry on the next interval/open.
        } finally {
            liveMessagesInFlight = false;
        }
    }

    async function refreshFriendRequestShell(force) {
        if (!liveFriendRequestsEndpoint || liveFriendRequestsInFlight) return;
        if (!force && (!isFriendRequestShellOpen() || document.hidden)) return;
        liveFriendRequestsInFlight = true;
        try {
            const response = await fetch(liveFriendRequestsEndpoint, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) return;
            const payload = await response.json();
            const list = friendRequestShell ? friendRequestShell.querySelector('[data-hnt-friend-requests-list]') : null;
            if (list && typeof payload.html === 'string') list.innerHTML = payload.html;
            updatePreviewFriendRequestBadge(payload.count);
            document.querySelectorAll('[data-hnt-friend-requests-visible-count]').forEach(function (node) {
                node.textContent = String(Math.max(0, Number.parseInt(payload.visible_count, 10) || 0));
            });
        } catch (error) {
            // Silent retry on the next interval/open.
        } finally {
            liveFriendRequestsInFlight = false;
        }
    }

    async function refreshPreviewLiveBadges() {
        if (!liveBadgesEndpoint || liveBadgesInFlight || document.hidden) return;
        liveBadgesInFlight = true;
        try {
            const response = await fetch(liveBadgesEndpoint, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) return;
            const payload = await response.json();
            updatePreviewNotificationBadge(payload.notifications_unread);
            updatePreviewMessageBadge(payload.messages_unread);
            updatePreviewFriendRequestBadge(payload.friend_request_count);
            if (isNotificationShellOpen()) refreshNotificationShell(false);
            if (isMessageShellOpen()) refreshMessageShell(false);
            if (isFriendRequestShellOpen()) refreshFriendRequestShell(false);
        } catch (error) {
            // Keep the preview shell quiet. The next interval/focus event retries.
        } finally {
            liveBadgesInFlight = false;
        }
    }

    window.hntPreviewRefreshLiveBadges = refreshPreviewLiveBadges;
    if (liveBadgesEndpoint) {
        refreshPreviewLiveBadges();
        window.setInterval(refreshPreviewLiveBadges, liveBadgesInterval);
        window.addEventListener('focus', refreshPreviewLiveBadges);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) refreshPreviewLiveBadges();
        });
        document.addEventListener('hnt:preview-live-badges-refresh', refreshPreviewLiveBadges);
    }

    if (liveNotificationsEndpoint || liveMessagesEndpoint || liveFriendRequestsEndpoint) {
        window.setInterval(function () {
            refreshNotificationShell(false);
            refreshMessageShell(false);
            refreshFriendRequestShell(false);
        }, liveShellInterval);
        window.addEventListener('focus', function () {
            refreshNotificationShell(false);
            refreshMessageShell(false);
            refreshFriendRequestShell(false);
        });
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                refreshNotificationShell(false);
                refreshMessageShell(false);
                refreshFriendRequestShell(false);
            }
        });
    }

    document.addEventListener('submit', async function (event) {
        const friendRequestForm = event.target.closest('[data-hnt-friend-request-action]');
        if (!friendRequestForm) return;

        event.preventDefault();
        const requestId = friendRequestForm.getAttribute('data-hnt-friend-request-id');
        const button = friendRequestForm.querySelector('button[type="submit"]');
        if (button) button.disabled = true;

        try {
            const response = await fetch(friendRequestForm.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: new FormData(friendRequestForm)
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload && payload.message ? payload.message : 'Friend request could not be updated.');

            if (requestId) {
                document.querySelectorAll('[data-hnt-friend-request-item="' + cssEscape(requestId) + '"]').forEach(function (item) {
                    item.remove();
                });
            }

            if (typeof payload.friend_request_count !== 'undefined') {
                updatePreviewFriendRequestBadge(payload.friend_request_count);
            } else {
                const current = Number.parseInt((document.querySelector('[data-hnt-friend-requests-count]') || {}).textContent || '0', 10) || 0;
                updatePreviewFriendRequestBadge(Math.max(0, current - 1));
            }

            document.dispatchEvent(new CustomEvent('hnt:preview-live-badges-refresh'));
            refreshFriendRequestShell(true);
        } catch (error) {
            friendRequestForm.submit();
        } finally {
            if (button) button.disabled = false;
        }
    });

    document.addEventListener('submit', async function (event) {
        const readAllForm = event.target.closest('[data-hnt-notification-read-all]');
        const readForm = event.target.closest('[data-hnt-notification-read]');
        const form = readAllForm || readForm;
        if (!form) return;

        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        if (button) button.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: new FormData(form)
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload && payload.message ? payload.message : 'Notification could not be updated.');

            updatePreviewNotificationBadge(payload.unread_count);

            if (readAllForm) {
                notificationShell && notificationShell.querySelectorAll('[data-hnt-notification-item].is-unread').forEach(function (item) {
                    item.classList.remove('is-unread');
                    item.classList.add('is-read');
                    const unreadLabel = item.querySelector('.hnt-notification-shell-title-row span');
                    if (unreadLabel) unreadLabel.remove();
                });
                readAllForm.hidden = true;
            } else if (readForm) {
                const item = readForm.closest('[data-hnt-notification-item]');
                if (item) {
                    item.classList.remove('is-unread');
                    item.classList.add('is-read');
                    const unreadLabel = item.querySelector('.hnt-notification-shell-title-row span');
                    if (unreadLabel) unreadLabel.remove();
                }
                if (payload.action_url) {
                    window.location.href = payload.action_url;
                }
            }

            document.dispatchEvent(new CustomEvent('hnt:preview-live-badges-refresh'));
            if (!payload.action_url) refreshNotificationShell(true);
        } catch (error) {
            form.submit();
        } finally {
            if (button) button.disabled = false;
        }
    });

    function readStoredChatTabs() {
        if (!chatTabsShell) return [];
        try {
            const parsed = JSON.parse(window.sessionStorage.getItem(chatTabsStorageKey) || '[]');
            return Array.isArray(parsed) ? parsed.filter(function (item) { return item && item.id && item.url; }) : [];
        } catch (error) {
            return [];
        }
    }

    function writeStoredChatTabs() {
        if (!chatTabsShell) return;
        const tabs = Array.from(chatTabsShell.querySelectorAll('[data-hnt-chat-tab]')).map(function (tab) {
            return {
                id: tab.getAttribute('data-conversation-id') || tab.getAttribute('data-hnt-chat-tab'),
                url: tab.getAttribute('data-hnt-chat-tab-url') || ''
            };
        }).filter(function (item) { return item.id && item.url; });

        try {
            window.sessionStorage.setItem(chatTabsStorageKey, JSON.stringify(tabs));
        } catch (error) {
            // Optional persistence only.
        }
    }

    function scrollChatTabMessages(tab) {
        const messages = tab ? tab.querySelector('[data-hnt-chat-tab-messages]') : null;
        if (messages) messages.scrollTop = messages.scrollHeight;
    }

    function isNearChatTabBottom(list) {
        if (!list) return true;
        return (list.scrollHeight - list.scrollTop - list.clientHeight) < 64;
    }

    function chatTabMessagesUrl(tab) {
        if (!tab) return '';
        const explicitUrl = tab.getAttribute('data-hnt-chat-tab-messages-url');
        if (explicitUrl) return explicitUrl;
        const tabUrl = tab.getAttribute('data-hnt-chat-tab-url') || '';
        return tabUrl ? tabUrl.replace(/\/?$/, '/messages') : '';
    }

    async function refreshChatTabMessages(tab, force) {
        if (!tab || document.hidden) return;
        if (tab.dataset.hntChatTabRefreshing === '1') return;
        if (!force && tab.classList.contains('is-minimized')) return;
        const list = tab.querySelector('[data-hnt-chat-tab-messages]');
        const url = chatTabMessagesUrl(tab);
        if (!list || !url) return;

        tab.dataset.hntChatTabRefreshing = '1';
        const shouldStickToBottom = isNearChatTabBottom(list);

        try {
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) return;
            const payload = await response.json();
            if (typeof payload.html === 'string') {
                list.innerHTML = payload.html;
                if (shouldStickToBottom) scrollChatTabMessages(tab);
            }
            updatePreviewMessageBadge(payload.unread_messages);
            if (isMessageShellOpen()) refreshMessageShell(false);
        } catch (error) {
            // Silent retry on the next interval.
        } finally {
            delete tab.dataset.hntChatTabRefreshing;
        }
    }

    function refreshOpenChatTabs(force) {
        if (!chatTabsShell || document.hidden) return;
        chatTabsShell.querySelectorAll('[data-hnt-chat-tab]').forEach(function (tab) {
            refreshChatTabMessages(tab, Boolean(force));
        });
    }

    function activateChatTab(tab) {
        if (!chatTabsShell || !tab) return;
        tab.classList.remove('is-minimized');
        chatTabsShell.appendChild(tab);
        scrollChatTabMessages(tab);
        writeStoredChatTabs();
    }

    function renderChatTab(html, url) {
        if (!chatTabsShell) return null;
        const template = document.createElement('template');
        template.innerHTML = String(html || '').trim();
        const tab = template.content.firstElementChild;
        if (!tab) return null;

        const conversationId = tab.getAttribute('data-conversation-id') || tab.getAttribute('data-hnt-chat-tab');
        const existing = conversationId ? chatTabsShell.querySelector('[data-hnt-chat-tab="' + cssEscape(conversationId) + '"]') : null;
        tab.setAttribute('data-hnt-chat-tab-url', url || '');

        const wasMinimized = existing && existing.classList.contains('is-minimized');

        if (existing) {
            existing.replaceWith(tab);
            if (wasMinimized) tab.classList.add('is-minimized');
        } else {
            chatTabsShell.appendChild(tab);
        }

        scrollChatTabMessages(tab);
        writeStoredChatTabs();
        return tab;
    }

    function updatePreviewMessageBadge(count) {
        if (typeof count === 'undefined' || count === null) return;
        const value = Math.max(0, Number.parseInt(count, 10) || 0);
        setPreviewBadge('[data-hnt-message-count]', value);
        document.querySelectorAll('[data-hnt-messages-unread-count]').forEach(function (node) {
            node.textContent = String(value);
        });
    }

    async function openChatTab(url, trigger) {
        if (!chatTabsShell || !url) return;

        const existingId = trigger ? trigger.getAttribute('data-hnt-chat-conversation-id') : '';
        if (existingId) {
            const existing = chatTabsShell.querySelector('[data-hnt-chat-tab="' + cssEscape(existingId) + '"]');
            if (existing) {
                activateChatTab(existing);
                return;
            }
        }

        if (trigger) trigger.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            let payload = await response.json();
            if (!response.ok) {
                throw new Error((payload && payload.message) || t('message_chat_open_failed', 'Chat could not be opened.'));
            }

            if (!payload.html && payload.conversation_id) {
                const showUrl = payload.show_url ? String(payload.show_url).replace(/\/$/, '') : (window.location.origin + '/messages/' + encodeURIComponent(payload.conversation_id));
                const chatTabUrl = payload.chat_tab_url || (showUrl + '/chat-tab');
                const tabResponse = await fetch(chatTabUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                const tabPayload = await tabResponse.json();
                if (!tabResponse.ok) {
                    throw new Error((tabPayload && tabPayload.message) || t('message_chat_open_failed', 'Chat could not be opened.'));
                }
                payload = tabPayload;
                url = chatTabUrl;
            }

            const tab = renderChatTab(payload.html, url);
            if (tab) {
                updatePreviewMessageBadge(payload.unread_messages);
                document.dispatchEvent(new CustomEvent('hnt:preview-live-badges-refresh'));
                if (trigger) {
                    trigger.classList.remove('is-unread');
                    const unread = trigger.querySelector('.hnt-message-shell-title-row em');
                    if (unread) unread.remove();
                }
            }
        } catch (error) {
            if (trigger && trigger.href) {
                window.location.href = trigger.href;
            } else {
                console.error(error);
            }
        } finally {
            if (trigger) trigger.removeAttribute('aria-busy');
        }
    }

    function appendOwnChatMessage(form, payload) {
        const tab = form.closest('[data-hnt-chat-tab]');
        const list = tab ? tab.querySelector('[data-hnt-chat-tab-messages]') : null;
        if (!tab || !list) return;

        const empty = tab.querySelector('[data-hnt-chat-tab-empty]');
        if (empty) empty.remove();

        const wrapper = document.createElement('div');
        wrapper.className = 'hnt-chat-tab__message is-own';
        wrapper.innerHTML = '<div class="hnt-chat-tab__bubble-wrap"><p class="hnt-chat-tab__bubble">' + escapeHtml((payload && payload.body) || '') + '</p><span class="hnt-chat-tab__time">' + escapeHtml((payload && payload.created_at_label) || '') + '</span></div>';
        list.appendChild(wrapper);
        scrollChatTabMessages(tab);
    }

    document.addEventListener('click', function (event) {
        const opener = event.target.closest('[data-hnt-chat-tab-open]');
        if (opener) {
            event.preventDefault();
            openChatTab(opener.getAttribute('data-hnt-chat-tab-url') || opener.href, opener);
            return;
        }

        const close = event.target.closest('[data-hnt-chat-tab-close]');
        if (close) {
            event.preventDefault();
            const tab = close.closest('[data-hnt-chat-tab]');
            if (tab) tab.remove();
            writeStoredChatTabs();
            return;
        }

        const minimize = event.target.closest('[data-hnt-chat-tab-minimize]');
        if (minimize) {
            event.preventDefault();
            const tab = minimize.closest('[data-hnt-chat-tab]');
            if (tab) tab.classList.add('is-minimized');
            writeStoredChatTabs();
            return;
        }

        const header = event.target.closest('[data-hnt-chat-tab-toggle]');
        if (header && !event.target.closest('[data-hnt-chat-tab-full]')) {
            const tab = header.closest('[data-hnt-chat-tab]');
            if (tab && tab.classList.contains('is-minimized')) {
                event.preventDefault();
                activateChatTab(tab);
            }
        }
    });

    document.addEventListener('submit', async function (event) {
        const form = event.target.closest('[data-hnt-chat-tab-form]');
        if (!form) return;

        event.preventDefault();
        const input = form.querySelector('input[name="body"]');
        const button = form.querySelector('button[type="submit"]');
        const body = String(input ? input.value : '').trim();
        if (!body) return;

        if (button) button.disabled = true;

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: formData
            });

            const payload = await response.json();
            if (!response.ok) {
                throw new Error((payload && payload.message) || t('message_send_failed', 'Message could not be sent.'));
            }

            appendOwnChatMessage(form, payload);
            if (input) input.value = '';
            updatePreviewMessageBadge(payload.unread_messages);
            refreshMessageShell(true);
            const tab = form.closest('[data-hnt-chat-tab]');
            if (tab) window.setTimeout(function () { refreshChatTabMessages(tab, true); }, 250);
            document.dispatchEvent(new CustomEvent('hnt:preview-live-badges-refresh'));
        } catch (error) {
            form.submit();
        } finally {
            if (button) button.disabled = false;
            if (input) input.focus();
        }
    });

    readStoredChatTabs().forEach(function (item) {
        openChatTab(item.url);
    });

    if (chatTabsShell) {
        window.setInterval(function () {
            refreshOpenChatTabs(false);
        }, liveChatTabInterval);
        window.addEventListener('focus', function () { refreshOpenChatTabs(true); });
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) refreshOpenChatTabs(true);
        });
    }

    if (profileTrigger && profileMenu) {
        profileTrigger.addEventListener('click', function (event) {
            event.stopPropagation();
            toggleProfileMenu(false);
        });
        profileTrigger.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleProfileMenu(false);
            }
        });
    }

    document.addEventListener('click', function (event) {
        if (profileMenu && profileTrigger && !profileMenu.contains(event.target) && !profileTrigger.contains(event.target)) {
            toggleProfileMenu(true);
        }
    });

    document.querySelectorAll('[data-moments-prev], [data-moments-next]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!momentsList) return;
            const direction = button.hasAttribute('data-moments-prev') ? -1 : 1;
            momentsList.scrollBy({ left: direction * 140, behavior: 'smooth' });
        });
    });


    function initLfgCreateModal() {
        const modals = lfgWizardModals.length ? lfgWizardModals : (lfgCreateModal ? [lfgCreateModal] : []);
        modals.forEach(initLfgWizardModal);
    }

    function initLfgWizardModal(lfgModal) {
        if (!lfgModal) return;

        const form = lfgModal.querySelector('[data-hnt-lfg-create-form]');
        const steps = Array.from(lfgModal.querySelectorAll('[data-hnt-lfg-create-step]'));
        const dots = Array.from(lfgModal.querySelectorAll('[data-hnt-lfg-create-dot]'));
        const nextButtons = Array.from(lfgModal.querySelectorAll('[data-hnt-lfg-create-next]'));
        const prevButtons = Array.from(lfgModal.querySelectorAll('[data-hnt-lfg-create-prev]'));
        const closeButtons = Array.from(lfgModal.querySelectorAll('[data-hnt-lfg-create-close]'));
        const submitButton = lfgModal.querySelector('[data-hnt-lfg-create-submit]');
        const fileInput = lfgModal.querySelector('[data-hnt-lfg-cover-input]');
        const coverStage = lfgModal.querySelector('[data-hnt-lfg-cover-stage]');
        const coverPicker = coverStage ? coverStage.querySelector('.hnt-lfg-cover-picker') : null;
        const preview = lfgModal.querySelector('[data-hnt-lfg-cover-preview]');
        const previewImg = preview ? preview.querySelector('img') : null;
        const removeCover = lfgModal.querySelector('[data-hnt-lfg-cover-remove]');
        const removeCoverInput = lfgModal.querySelector('[data-hnt-lfg-cover-remove-input]');
        const openSelector = lfgModal.getAttribute('data-hnt-lfg-open-selector') || '[data-hnt-lfg-create-open]';
        let currentStep = 0;
        let previewUrl = '';

        function setElementHidden(element, isHidden) {
            if (!element) return;
            element.hidden = Boolean(isHidden);
            element.setAttribute('aria-hidden', isHidden ? 'true' : 'false');
            element.style.display = isHidden ? 'none' : '';
        }

        function syncCoverPickerState(hasCover) {
            const coverSelected = Boolean(hasCover);
            if (coverStage) {
                coverStage.classList.toggle('has-cover', coverSelected);
            }
            if (coverPicker) {
                coverPicker.hidden = coverSelected;
                coverPicker.setAttribute('aria-hidden', coverSelected ? 'true' : 'false');
            }
        }

        function setRemoveCoverState(shouldRemove) {
            if (removeCoverInput) {
                removeCoverInput.value = shouldRemove ? '1' : '0';
            }
        }

        function setStep(index) {
            currentStep = Math.max(0, Math.min(steps.length - 1, index));
            const isLastStep = currentStep >= steps.length - 1;
            steps.forEach(function (step, stepIndex) {
                const active = stepIndex === currentStep;
                setElementHidden(step, !active);
                step.classList.toggle('is-active', active);
            });
            dots.forEach(function (dot, dotIndex) {
                dot.classList.toggle('is-active', dotIndex === currentStep);
                dot.classList.toggle('is-done', dotIndex < currentStep);
            });
            prevButtons.forEach(function (button) {
                setElementHidden(button, currentStep === 0);
            });
            nextButtons.forEach(function (button) {
                setElementHidden(button, isLastStep);
            });
            setElementHidden(submitButton, !isLastStep);
        }

        function openModal() {
            lfgModal.classList.add('open');
            lfgModal.setAttribute('aria-hidden', 'false');
            setStep(0);
            syncModalBodyState();
        }

        function closeModal() {
            lfgModal.classList.remove('open');
            lfgModal.setAttribute('aria-hidden', 'true');
            syncModalBodyState();
        }

        function canLeaveStep() {
            if (!form) return true;
            if (currentStep === 1) {
                const title = form.querySelector('[name="title"]');
                if (title && !title.value.trim()) {
                    title.focus();
                    title.reportValidity?.();
                    return false;
                }
            }
            return true;
        }

        function clearCoverPreview() {
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = '';
            if (previewImg) previewImg.removeAttribute('src');
            if (preview) preview.hidden = true;
            if (fileInput) fileInput.value = '';
            setRemoveCoverState(true);
            syncCoverPickerState(false);
        }

        document.querySelectorAll(openSelector).forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                openModal();
            });
        });

        nextButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (!canLeaveStep()) return;
                setStep(currentStep + 1);
            });
        });

        prevButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                setStep(currentStep - 1);
            });
        });

        closeButtons.forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

        lfgModal.addEventListener('click', function (event) {
            if (event.target === lfgModal) closeModal();
        });

        if (fileInput && preview && previewImg) {
            fileInput.addEventListener('change', function () {
                const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                if (!file) {
                    clearCoverPreview();
                    return;
                }
                if (previewUrl) URL.revokeObjectURL(previewUrl);
                previewUrl = URL.createObjectURL(file);
                previewImg.src = previewUrl;
                preview.hidden = false;
                setRemoveCoverState(false);
                syncCoverPickerState(true);
            });
        }

        if (removeCover) {
            removeCover.addEventListener('click', clearCoverPreview);
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                if (currentStep < steps.length - 1) {
                    event.preventDefault();
                    if (!canLeaveStep()) return;
                    setStep(currentStep + 1);
                    return;
                }
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = form.dataset.hntLfgSubmittingLabel || t('preview_lfg_creating', 'Wird erstellt ...');
                }
            });
        }

        syncCoverPickerState(Boolean(preview && !preview.hidden));
        setStep(0);
        if (lfgModal.classList.contains('open')) {
            syncModalBodyState();
        }
    }

    function openComposerModal() {
        if (!composerModal) return;
        composerModal.classList.add('open');
        composerModal.setAttribute('aria-hidden', 'false');
        syncModalBodyState();
        const composerTextarea = composerModal.querySelector('textarea');
        window.setTimeout(function () {
            if (composerTextarea) composerTextarea.focus();
        }, 180);
    }

    function closeComposerModal() {
        if (!composerModal) return;
        composerModal.classList.remove('open');
        composerModal.setAttribute('aria-hidden', 'true');
        closeEmojiPicker();
        syncModalBodyState();
    }


    function initComposerEnhancements() {
        if (!composerModal) return;

        const form = composerModal.querySelector('[data-hnt-composer-form]');
        const fileInput = composerModal.querySelector('[data-hnt-composer-file-input]');
        const mediaPreview = composerModal.querySelector('[data-hnt-composer-media-preview]');
        const inputShell = composerModal.querySelector('[data-hnt-composer-input-shell]');
        const visibilityInput = composerModal.querySelector('[data-hnt-composer-visibility-input]');
        const visibilityLabel = composerModal.querySelector('[data-hnt-composer-visibility-label]');
        const visibilityCopy = composerModal.querySelector('[data-hnt-composer-visibility-copy]');
        const feelingInput = composerModal.querySelector('[data-hnt-composer-feeling-input]');
        const pollOptions = composerModal.querySelector('[data-hnt-composer-poll-options]');
        const addPollOption = composerModal.querySelector('[data-hnt-composer-add-poll-option]');
        let selectedMediaFiles = [];
        let previewUrls = [];

        function revokePreviewUrls() {
            previewUrls.forEach(function (url) {
                URL.revokeObjectURL(url);
            });
            previewUrls = [];
        }

        function syncComposerFileInput() {
            if (!fileInput) return;

            if (selectedMediaFiles.length === 0) {
                fileInput.value = '';
                return;
            }

            if (typeof DataTransfer === 'undefined') return;

            const transfer = new DataTransfer();
            selectedMediaFiles.forEach(function (file) {
                transfer.items.add(file);
            });
            fileInput.files = transfer.files;
        }

        function renderComposerMediaPreviews() {
            if (!mediaPreview || !inputShell) return;

            revokePreviewUrls();
            mediaPreview.innerHTML = '';
            inputShell.classList.toggle('has-media-previews', selectedMediaFiles.length > 0);
            mediaPreview.hidden = selectedMediaFiles.length === 0;

            selectedMediaFiles.forEach(function (file, index) {
                const url = URL.createObjectURL(file);
                previewUrls.push(url);

                const item = document.createElement('div');
                item.className = 'composer-media-preview-item';
                item.dataset.index = String(index);

                const isVideo = file.type && file.type.indexOf('video/') === 0;
                if (isVideo) {
                    const video = document.createElement('video');
                    video.src = url;
                    video.muted = true;
                    video.playsInline = true;
                    video.preload = 'metadata';
                    item.appendChild(video);
                    const label = document.createElement('span');
                    label.className = 'composer-media-type';
                    label.textContent = t('feed_video', 'Video');
                    item.appendChild(label);
                } else {
                    const image = document.createElement('img');
                    image.src = url;
                    image.alt = file.name || t('preview_feed_medium_alt', 'Bildvorschau');
                    item.appendChild(image);
                }

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'composer-media-remove';
                removeButton.dataset.hntComposerRemoveMedia = String(index);
                removeButton.setAttribute('aria-label', t('remove_media', 'Medium entfernen'));
                removeButton.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" d="M3 6h18M8 6V4h8v2M9 10v8M15 10v8M6 6l1 16h10l1-16"/></svg>';
                item.appendChild(removeButton);
                mediaPreview.appendChild(item);
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                selectedMediaFiles = Array.from(fileInput.files || []);
                renderComposerMediaPreviews();
            });
        }

        if (mediaPreview) {
            mediaPreview.addEventListener('click', function (event) {
                const removeButton = event.target.closest('[data-hnt-composer-remove-media]');
                if (!removeButton) return;

                event.preventDefault();
                const index = Number(removeButton.dataset.hntComposerRemoveMedia);
                if (!Number.isInteger(index)) return;

                selectedMediaFiles.splice(index, 1);
                syncComposerFileInput();
                renderComposerMediaPreviews();
            });
        }

        composerModal.querySelectorAll('[data-hnt-composer-toggle-panel]').forEach(function (button) {
            button.addEventListener('click', function () {
                const target = button.dataset.hntComposerTogglePanel;
                if (!target) return;

                composerModal.querySelectorAll('[data-hnt-composer-panel]').forEach(function (panel) {
                    const active = panel.dataset.hntComposerPanel === target && panel.hidden;
                    panel.hidden = !active;
                });

                composerModal.querySelectorAll('[data-hnt-composer-toggle-panel]').forEach(function (toggle) {
                    toggle.classList.toggle('is-active', toggle === button && !composerModal.querySelector('[data-hnt-composer-panel="' + target + '"]').hidden);
                });
            });
        });

        composerModal.querySelectorAll('[data-hnt-composer-feeling-choice]').forEach(function (button) {
            button.addEventListener('click', function () {
                const value = button.dataset.value || 'none';
                if (feelingInput) feelingInput.value = value;
                composerModal.querySelectorAll('[data-hnt-composer-feeling-choice]').forEach(function (choice) {
                    choice.classList.toggle('is-active', choice === button);
                });
            });
        });

        composerModal.querySelectorAll('[data-hnt-composer-visibility-choice]').forEach(function (button) {
            button.addEventListener('click', function () {
                const value = button.dataset.value || 'public';
                const label = button.dataset.label || 'Community';
                const copy = button.dataset.copy || label;

                if (visibilityInput) visibilityInput.value = value;
                if (visibilityLabel) visibilityLabel.textContent = label;
                if (visibilityCopy) visibilityCopy.textContent = copy;

                composerModal.querySelectorAll('[data-hnt-composer-visibility-choice]').forEach(function (choice) {
                    choice.classList.toggle('is-active', choice === button);
                });

                const dropdown = button.closest('.nav-dropdown');
                if (dropdown) {
                    dropdown.classList.remove('open');
                    const toggle = dropdown.querySelector('.nav-dropdown-toggle');
                    if (toggle) toggle.setAttribute('aria-expanded', 'false');
                }
            });
        });

        function syncPollAddState() {
            if (!pollOptions || !addPollOption) return;
            addPollOption.disabled = pollOptions.querySelectorAll('input').length >= 6;
        }

        if (addPollOption && pollOptions) {
            addPollOption.addEventListener('click', function () {
                const currentCount = pollOptions.querySelectorAll('input').length;
                if (currentCount >= 6) return;

                const input = document.createElement('input');
                input.type = 'text';
                input.name = 'poll_options[]';
                input.maxLength = 180;
                input.placeholder = t('preview_composer_poll_answer_placeholder', 'Antwort :number', { number: String(currentCount + 1) });
                pollOptions.appendChild(input);
                syncPollAddState();
                input.focus();
            });

            syncPollAddState();
        }

        if (form) {
            form.addEventListener('reset', function () {
                selectedMediaFiles = [];
                window.setTimeout(function () {
                    syncComposerFileInput();
                    renderComposerMediaPreviews();
                }, 0);
            });
        }
    }

    document.addEventListener('click', function (event) {
        const emojiTrigger = event.target.closest('[data-hnt-emoji-trigger]');
        if (emojiTrigger) {
            event.preventDefault();
            event.stopPropagation();
            const picker = ensureEmojiPicker();
            if (!picker.hidden && activeEmojiTrigger === emojiTrigger) {
                closeEmojiPicker();
            } else {
                openEmojiPicker(emojiTrigger);
            }
            return;
        }

        if (emojiPickerNode && !emojiPickerNode.hidden && !event.target.closest('.hnt-emoji-picker')) {
            closeEmojiPicker();
        }
    });

    window.addEventListener('resize', function () {
        if (emojiPickerNode && !emojiPickerNode.hidden) positionEmojiPicker(activeEmojiTrigger);
    });
    window.addEventListener('scroll', function () {
        if (emojiPickerNode && !emojiPickerNode.hidden) positionEmojiPicker(activeEmojiTrigger);
    }, true);


    function setPostModalLoading() {
        if (postModalContent) {
            postModalContent.innerHTML = '<div class="hnt-comment-modal-loading">' + escapeHtml(t('preview_post_modal_loading', 'Kommentare werden geladen ...')) + '</div>';
        }
        if (postModalSubtitle) postModalSubtitle.textContent = t('preview_post_modal_loading_subtitle', 'Beitrag und Kommentare werden geladen.');
        if (postModalCommentForm) postModalCommentForm.hidden = true;
        if (postModalCommentStatus) {
            postModalCommentStatus.hidden = true;
            postModalCommentStatus.textContent = '';
            postModalCommentStatus.classList.remove('is-error');
        }
    }

    function updatePostCommentCount(postId, count) {
        if (!postId || typeof count === 'undefined') return;
        document.querySelectorAll('[data-hnt-post-comment-count="' + cssEscape(String(postId)) + '"]').forEach(function (counter) {
            counter.textContent = String(count);
        });
    }

    function updateModalCommentCount(count) {
        if (typeof count === 'undefined' || !postModal) return;
        postModal.querySelectorAll('[data-hnt-modal-comment-count]').forEach(function (counter) {
            counter.textContent = String(count);
        });
    }

    function formatSimpleLikeCount(count) {
        const numericCount = Number(count) || 0;
        try {
            return formatNumber(numericCount);
        } catch (error) {
            return String(numericCount);
        }
    }

    function renderSimpleLikeLabel(type, count) {
        const numericCount = Number(count) || 0;
        return formatSimpleLikeCount(numericCount);
    }

    function syncSimpleLikeState(type, id, reacted, count) {
        if (!type || !id) return;
        const escapedType = cssEscape(String(type));
        const escapedId = cssEscape(String(id));

        document.querySelectorAll('[data-hnt-simple-like-form][data-like-target-type="' + escapedType + '"][data-like-target-id="' + escapedId + '"]').forEach(function (form) {
            const button = form.querySelector('[data-hnt-simple-like-button]');
            if (!button) return;
            button.classList.toggle('active', Boolean(reacted));
            button.dataset.liked = reacted ? '1' : '0';
            button.disabled = false;
            button.classList.remove('is-loading');
            button.setAttribute('aria-pressed', reacted ? 'true' : 'false');
        });

        document.querySelectorAll('[data-hnt-simple-like-count][data-like-target-type="' + escapedType + '"][data-like-target-id="' + escapedId + '"]').forEach(function (counter) {
            counter.textContent = renderSimpleLikeLabel(String(type), count);
        });

        document.querySelectorAll('[data-hnt-like-summary-count][data-like-target-type="' + escapedType + '"][data-like-target-id="' + escapedId + '"]').forEach(function (counter) {
            counter.textContent = formatSimpleLikeCount(count);
            const button = counter.closest('[data-hnt-likes-open]');
            if (button) {
                const isEmpty = (Number(count) || 0) <= 0;
                button.classList.toggle('is-empty', isEmpty);
                button.disabled = isEmpty;
            }
        });
    }

    function submitSimpleLike(form) {
        if (!form || !form.action) return;
        const type = form.dataset.likeTargetType || '';
        const id = form.dataset.likeTargetId || '';
        const button = form.querySelector('[data-hnt-simple-like-button]');
        const formData = new FormData(form);

        if (!formData.has('type')) formData.append('type', 'like');

        if (button) {
            button.disabled = true;
            button.classList.add('is-loading');
        }

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok) {
                        throw new Error(payload && payload.message ? payload.message : 'Like konnte nicht gespeichert werden.');
                    }
                    return payload;
                });
            })
            .then(function (payload) {
                syncSimpleLikeState(type, id, Boolean(payload && payload.reacted), payload && typeof payload.count !== 'undefined' ? payload.count : 0);
            })
            .catch(function (error) {
                if (button) {
                    button.disabled = false;
                    button.classList.remove('is-loading');
                }
                window.alert(error && error.message ? error.message : t('preview_like_failed', 'Like konnte nicht gespeichert werden.'));
            });
    }




    function renderOptionalActionCount(count) {
        const numericCount = Number(count) || 0;
        return numericCount > 0 ? formatSimpleLikeCount(numericCount) : '';
    }

    function getAbsoluteShareUrl(rawUrl) {
        try {
            return new URL(rawUrl || window.location.href, window.location.origin).href;
        } catch (error) {
            return window.location.href;
        }
    }

    function setNativeShareButtonBusy(button, busy) {
        if (!button) return;
        button.disabled = Boolean(busy);
        button.classList.toggle('is-loading', Boolean(busy));
    }

    function flashNativeShareCopied(button) {
        if (!button) return;
        button.classList.add('is-copied');
        button.setAttribute('title', t('preview_share_link_copied', 'Link kopiert'));
        button.setAttribute('aria-label', t('preview_share_link_copied', 'Link kopiert'));
        window.setTimeout(function () {
            button.classList.remove('is-copied');
            button.setAttribute('title', t('preview_share_button_title', 'Teilen'));
            button.setAttribute('aria-label', t('preview_share_post_aria', 'Beitrag teilen'));
        }, 1800);
    }

    function openNativeShare(button) {
        if (!button) return;

        const shareUrl = getAbsoluteShareUrl(button.dataset.shareUrl || window.location.href);
        const shareTitle = button.dataset.shareTitle || document.title || 'HNT.rocks';
        const shareText = button.dataset.shareText || t('preview_share_default_text', 'Schau dir diesen Beitrag auf HNT.rocks an.');
        const sharePayload = {
            title: shareTitle,
            text: shareText,
            url: shareUrl
        };

        setNativeShareButtonBusy(button, true);

        if (navigator.share) {
            Promise.resolve(navigator.share(sharePayload))
                .catch(function (error) {
                    if (!error || error.name === 'AbortError') {
                        return;
                    }
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        return navigator.clipboard.writeText(shareUrl).then(function () {
                            flashNativeShareCopied(button);
                        });
                    }
                    window.prompt(t('preview_share_prompt', 'Link zum Teilen kopieren:'), shareUrl);
                })
                .finally(function () {
                    setNativeShareButtonBusy(button, false);
                });
            return;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(shareUrl)
                .then(function () {
                    flashNativeShareCopied(button);
                })
                .catch(function () {
                    window.prompt(t('preview_share_prompt', 'Link zum Teilen kopieren:'), shareUrl);
                })
                .finally(function () {
                    setNativeShareButtonBusy(button, false);
                });
            return;
        }

        setNativeShareButtonBusy(button, false);
        window.prompt(t('preview_share_prompt', 'Link zum Teilen kopieren:'), shareUrl);
    }

    function syncShareState(id, shared, count) {
        if (!id) return;
        const escapedId = cssEscape(String(id));

        document.querySelectorAll('[data-hnt-share-count][data-share-target-id="' + escapedId + '"]').forEach(function (counter) {
            counter.textContent = renderOptionalActionCount(count);
        });

        document.querySelectorAll('[data-hnt-share-form][data-share-target-id="' + escapedId + '"]').forEach(function (form) {
            const button = form.querySelector('[data-hnt-share-button]');
            if (!button) return;
            button.disabled = false;
            button.classList.remove('is-loading');
            button.classList.toggle('active', Boolean(shared));
            if (shared) {
                button.setAttribute('aria-label', t('preview_share_shared_aria', 'Beitrag geteilt'));
                button.setAttribute('title', 'Geteilt');
            }
        });
    }

    function submitShare(form) {
        if (!form || !form.action) return;
        const id = form.dataset.shareTargetId || '';
        const button = form.querySelector('[data-hnt-share-button]');
        const formData = new FormData(form);

        if (button) {
            button.disabled = true;
            button.classList.add('is-loading');
        }

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok) {
                        throw new Error(payload && payload.message ? payload.message : t('preview_share_failed', 'Beitrag konnte nicht geteilt werden.'));
                    }
                    return payload;
                });
            })
            .then(function (payload) {
                syncShareState(id, Boolean(payload && payload.shared), payload && typeof payload.count !== 'undefined' ? payload.count : 0);
            })
            .catch(function (error) {
                if (button) {
                    button.disabled = false;
                    button.classList.remove('is-loading');
                }
                window.alert(error && error.message ? error.message : t('preview_share_failed', 'Beitrag konnte nicht geteilt werden.'));
            });
    }

    function syncBookmarkState(id, bookmarked) {
        if (!id) return;
        const escapedId = cssEscape(String(id));

        document.querySelectorAll('[data-hnt-bookmark-form][data-bookmark-target-id="' + escapedId + '"]').forEach(function (form) {
            const button = form.querySelector('[data-hnt-bookmark-button]');
            if (!button) return;
            button.disabled = false;
            button.classList.remove('is-loading');
            button.classList.toggle('active', Boolean(bookmarked));
            button.dataset.bookmarked = bookmarked ? '1' : '0';
            button.setAttribute('aria-label', bookmarked ? t('preview_bookmark_remove_aria', 'Beitrag nicht mehr speichern') : t('preview_bookmark_save_aria', 'Beitrag speichern'));
            button.setAttribute('title', bookmarked ? t('preview_saved', 'Gespeichert') : t('preview_save', 'Speichern'));
        });
    }

    function submitBookmark(form) {
        if (!form || !form.action) return;
        const id = form.dataset.bookmarkTargetId || '';
        const button = form.querySelector('[data-hnt-bookmark-button]');
        const formData = new FormData(form);

        if (button) {
            button.disabled = true;
            button.classList.add('is-loading');
        }

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok) {
                        throw new Error(payload && payload.message ? payload.message : t('preview_bookmark_failed', 'Beitrag konnte nicht gespeichert werden.'));
                    }
                    return payload;
                });
            })
            .then(function (payload) {
                syncBookmarkState(id, Boolean(payload && payload.bookmarked));
            })
            .catch(function (error) {
                if (button) {
                    button.disabled = false;
                    button.classList.remove('is-loading');
                }
                window.alert(error && error.message ? error.message : t('preview_bookmark_failed', 'Beitrag konnte nicht gespeichert werden.'));
            });
    }

    function closeLikesModal() {
        if (!likesModal) return;
        likesModal.classList.remove('open');
        likesModal.setAttribute('aria-hidden', 'true');
        syncModalBodyState();
    }

    function renderLikesUsers(users) {
        if (!likesModalBody) return;
        const list = Array.isArray(users) ? users : [];
        if (!list.length) {
            likesModalBody.innerHTML = '<div class="hnt-likes-empty"><strong>' + escapeHtml(t('preview_likes_empty_title', 'Noch keine Likes')) + '</strong><span>' + escapeHtml(t('preview_likes_empty_text', 'Hier erscheinen Hunter, sobald sie liken.')) + '</span></div>';
            return;
        }

        likesModalBody.innerHTML = '<div class="hnt-likes-list">' + list.map(function (user) {
            const profileUrl = user && user.profile_url ? String(user.profile_url) : '#';
            const avatar = user && user.avatar ? String(user.avatar) : '';
            const name = user && user.name ? String(user.name) : 'HNT Hunter';
            const username = user && user.username ? String(user.username) : '';
            const reactedAt = user && user.reacted_at ? String(user.reacted_at) : '';

            return '<a class="hnt-likes-user" href="' + escapeHtml(profileUrl) + '">' +
                '<span class="avatar avatar-sm hnt-avatar-shell">' + (avatar ? '<img src="' + escapeHtml(avatar) + '" alt="' + escapeHtml(name) + '">' : '') + '</span>' +
                '<span class="hnt-likes-user-copy"><strong>' + escapeHtml(name) + '</strong><small>' + escapeHtml([username, reactedAt].filter(Boolean).join(' · ')) + '</small></span>' +
                '<span class="hnt-likes-user-heart" aria-hidden="true"><svg viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="2" d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg></span>' +
                '</a>';
        }).join('') + '</div>';
    }

    function openLikesModal(button) {
        if (!likesModal || !likesModalBody || !button) return;
        const url = button.dataset.likesUrl || '';
        if (!url || button.disabled) return;

        likesModal.classList.add('open');
        likesModal.setAttribute('aria-hidden', 'false');
        if (likesModalTitle) likesModalTitle.textContent = button.dataset.likesTitle || t('preview_likes_title', 'Gefällt mir');
        if (likesModalSubtitle) likesModalSubtitle.textContent = 'Diese Hunter haben geliked.';
        likesModalBody.innerHTML = '<div class="hnt-likes-loading">' + escapeHtml(t('preview_likes_loading', 'Likes werden geladen ...')) + '</div>';
        syncModalBodyState();

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok) {
                        throw new Error(payload && payload.message ? payload.message : t('preview_likes_load_failed', 'Likes konnten nicht geladen werden.'));
                    }
                    return payload;
                });
            })
            .then(function (payload) {
                const total = payload && typeof payload.total !== 'undefined' ? Number(payload.total) || 0 : 0;
                if (likesModalSubtitle) {
                    likesModalSubtitle.textContent = total === 1 ? t('preview_likes_count_one', '1 Hunter hat geliked.') : t('preview_likes_count_many', ':count Hunter haben geliked.', { count: formatNumber(total) });
                }
                renderLikesUsers(payload && payload.users ? payload.users : []);
            })
            .catch(function (error) {
                likesModalBody.innerHTML = '<div class="hnt-likes-empty is-error"><strong>' + escapeHtml(t('preview_error_title', 'Fehler')) + '</strong><span>' + escapeHtml(error && error.message ? error.message : t('preview_likes_load_failed', 'Likes konnten nicht geladen werden.')) + '</span></div>';
            });
    }


    function getPostModalCommentTextarea() {
        return postModal ? postModal.querySelector('[data-hnt-comment-textarea]') : postModalCommentTextarea;
    }

    function getCommentFormStatus(form) {
        if (!form) return null;
        return form.querySelector('[data-hnt-comment-status], [data-hnt-comment-reply-status]');
    }

    function setCommentFormStatus(form, message, isError) {
        const status = getCommentFormStatus(form);
        if (!status) return;
        status.textContent = message || '';
        status.hidden = !message;
        status.classList.toggle('is-error', Boolean(isError));
    }

    function commentFormHasTextOrMedia(form) {
        if (!form) return false;
        const textarea = form.querySelector('[data-hnt-comment-textarea], [data-hnt-comment-reply-textarea]');
        const fileInput = form.querySelector('[data-hnt-comment-media-input]');
        const hasText = textarea && textarea.value.trim().length > 0;
        const hasMedia = fileInput && fileInput.files && fileInput.files.length > 0;
        return Boolean(hasText || hasMedia);
    }

    function updateCommentSubmitState(form) {
        if (!form) return;
        const submit = form.querySelector('[data-hnt-comment-submit], [data-hnt-comment-reply-submit]');
        if (!submit || submit.classList.contains('is-loading')) return;
        submit.disabled = false;
    }

    function setCommentMediaFiles(input, files) {
        if (!input) return;
        const selectedFiles = Array.from(files || []).slice(0, 4);

        if (typeof DataTransfer === 'undefined') {
            if (!selectedFiles.length) input.value = '';
            return;
        }

        const transfer = new DataTransfer();
        selectedFiles.forEach(function (file) {
            transfer.items.add(file);
        });
        input.files = transfer.files;
    }

    function renderCommentMediaPreview(form) {
        if (!form) return;
        const input = form.querySelector('[data-hnt-comment-media-input]');
        const preview = form.querySelector('[data-hnt-comment-media-preview]');
        if (!input || !preview) return;

        const files = Array.from(input.files || []).slice(0, 4);
        preview.innerHTML = '';
        preview.hidden = files.length === 0;

        files.forEach(function (file, index) {
            const item = document.createElement('span');
            item.className = 'hnt-comment-media-preview-item';

            const image = document.createElement('img');
            image.alt = t('preview_comment_image_preview', 'Bildvorschau');
            image.src = URL.createObjectURL(file);
            image.addEventListener('load', function () {
                URL.revokeObjectURL(image.src);
            }, { once: true });

            const name = document.createElement('span');
            name.textContent = file.name || t('preview_comment_image_preview', 'Bildvorschau');

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.setAttribute('data-hnt-comment-media-remove', String(index));
            remove.setAttribute('aria-label', t('preview_comment_clear_images', 'Bildauswahl entfernen'));
            remove.innerHTML = '<i class="ph ph-x" aria-hidden="true"></i>';

            item.appendChild(image);
            item.appendChild(name);
            item.appendChild(remove);
            preview.appendChild(item);
        });

        if (files.length > 0) {
            const clear = document.createElement('button');
            clear.type = 'button';
            clear.className = 'hnt-comment-media-clear';
            clear.setAttribute('data-hnt-comment-media-clear', '1');
            clear.textContent = t('preview_comment_clear_images', 'Bildauswahl entfernen');
            preview.appendChild(clear);
        }
    }

    function clearCommentMediaPreview(form) {
        if (!form) return;
        const input = form.querySelector('[data-hnt-comment-media-input]');
        const preview = form.querySelector('[data-hnt-comment-media-preview]');
        if (input) input.value = '';
        if (preview) {
            preview.innerHTML = '';
            preview.hidden = true;
        }
        updateCommentSubmitState(form);
    }

    function handleCommentMediaSelection(input) {
        if (!input) return;
        const form = input.closest('[data-hnt-comment-form], [data-hnt-comment-reply-form]');
        const files = Array.from(input.files || []);

        if (files.length > 4) {
            setCommentMediaFiles(input, files.slice(0, 4));
            setCommentFormStatus(form, t('preview_comment_media_limit', 'Maximal 4 Bilder.'), true);
        } else {
            setCommentFormStatus(form, '', false);
        }

        renderCommentMediaPreview(form);
        updateCommentSubmitState(form);
    }

    function initCommentMediaInputs(scope) {
        const root = scope || document;
        root.querySelectorAll('[data-hnt-comment-form], [data-hnt-comment-reply-form]').forEach(function (form) {
            if (form.dataset.hntCommentMediaReady === '1') return;
            form.dataset.hntCommentMediaReady = '1';
            renderCommentMediaPreview(form);
            updateCommentSubmitState(form);
        });
    }

    function prefillPostModalComment(commentTemplate, focusComposer) {
        const textarea = getPostModalCommentTextarea();
        const template = typeof commentTemplate === 'string' ? commentTemplate.trim() : '';

        if (!textarea || template === '') return;

        textarea.value = template;
        textarea.dispatchEvent(new Event('input', { bubbles: true }));

        if (focusComposer) {
            window.setTimeout(function () {
                textarea.focus();

                if (typeof textarea.setSelectionRange === 'function') {
                    const length = textarea.value.length;
                    textarea.setSelectionRange(length, length);
                }
            }, 80);
        }
    }

    function loadPostModalContent(url, storeUrl, postId, focusComposer, commentTemplate) {
        if (!postModal || !postModalContent || !url) return;

        currentPostModalUrl = url;
        currentPostModalStoreUrl = storeUrl || currentPostModalStoreUrl;
        currentPostModalPostId = postId || currentPostModalPostId;

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) throw new Error(t('preview_comments_load_failed', 'Kommentare konnten nicht geladen werden.'));
                return response.json();
            })
            .then(function (payload) {
                postModalContent.innerHTML = payload && payload.html ? String(payload.html) : '<div class="hnt-comment-empty"><strong>' + escapeHtml(t('preview_post_modal_empty', 'Keine Kommentare gefunden')) + '</strong></div>';
                if (postModalSubtitle) postModalSubtitle.textContent = t('preview_post_modal_ready_subtitle', 'Echter Beitrag mit echten Kommentaren.');
                if (postModalCommentForm) {
                    postModalCommentForm.action = payload && payload.store_url ? payload.store_url : currentPostModalStoreUrl;
                    postModalCommentForm.dataset.postId = payload && payload.post_id ? String(payload.post_id) : String(currentPostModalPostId || '');
                    postModalCommentForm.hidden = false;
                }
                if (payload && payload.post_id) currentPostModalPostId = String(payload.post_id);
                if (payload && typeof payload.count !== 'undefined') {
                    updatePostCommentCount(currentPostModalPostId, payload.count);
                    updateModalCommentCount(payload.count);
                }
                initReadMore(postModalContent);
                initMediaCarousels(postModalContent);
                initVideoPlayers(postModalContent);
                initCommentMediaInputs(postModal);
                prefillPostModalComment(commentTemplate, focusComposer);
            })
            .catch(function (error) {
                postModalContent.innerHTML = '<div class="hnt-comment-modal-loading is-error">' + escapeHtml(error && error.message ? error.message : t('preview_comments_load_failed', 'Kommentare konnten nicht geladen werden.')) + '</div>';
                if (postModalSubtitle) postModalSubtitle.textContent = t('preview_post_modal_error_subtitle', 'Beim Laden ist ein Fehler aufgetreten.');
            });
    }

    function openPostModal(button) {
        if (!postModal || !button) return;
        const url = button.dataset.postModalUrl || button.getAttribute('href') || '';
        const storeUrl = button.dataset.postCommentStoreUrl || '';
        const postId = button.dataset.postId || '';
        const commentTemplate = button.dataset.postCommentTemplate || button.getAttribute('data-post-comment-template') || '';

        postModal.classList.add('open');
        postModal.setAttribute('aria-hidden', 'false');
        syncModalBodyState();
        setPostModalLoading();
        prefillPostModalComment(commentTemplate, true);
        loadPostModalContent(url, storeUrl, postId, true, commentTemplate);
    }

    function closePostModal() {
        if (!postModal) return;
        postModal.classList.remove('open');
        postModal.setAttribute('aria-hidden', 'true');
        closeEmojiPicker();
        if (postModalCommentStatus) {
            postModalCommentStatus.hidden = true;
            postModalCommentStatus.textContent = '';
            postModalCommentStatus.classList.remove('is-error');
        }
        syncModalBodyState();
    }

    function renderLightbox() {
        if (!lightboxModal || !lightboxImage || !lightboxItems.length) return;
        const item = lightboxItems[lightboxIndex];
        lightboxImage.src = item.src;
        lightboxImage.alt = item.alt || 'Feed Bild';
        if (lightboxCaption) {
            lightboxCaption.textContent = item.alt || '';
            lightboxCaption.hidden = !item.alt;
        }
        if (lightboxCount) {
            lightboxCount.textContent = String(lightboxIndex + 1) + ' / ' + String(lightboxItems.length);
            lightboxCount.hidden = lightboxItems.length <= 1;
        }
        lightboxModal.querySelectorAll('[data-hnt-lightbox-prev], [data-hnt-lightbox-next]').forEach(function (button) {
            button.hidden = lightboxItems.length <= 1;
        });
    }

    function openLightbox(trigger) {
        if (!lightboxModal || !lightboxImage || !trigger) return;
        const scope = trigger.closest('[data-hnt-lightbox-scope]') || document;
        const triggers = Array.from(scope.querySelectorAll('[data-hnt-lightbox-trigger]'));
        lightboxItems = triggers.map(function (node) {
            return {
                src: node.dataset.hntLightboxSrc || node.getAttribute('href') || '',
                alt: node.dataset.hntLightboxAlt || node.querySelector('img')?.getAttribute('alt') || ''
            };
        }).filter(function (item) { return item.src; });
        lightboxIndex = Math.max(0, triggers.indexOf(trigger));
        if (!lightboxItems.length) return;
        lightboxModal.classList.add('open');
        lightboxModal.setAttribute('aria-hidden', 'false');
        renderLightbox();
        syncModalBodyState();
    }

    function closeLightbox() {
        if (!lightboxModal) return;
        lightboxModal.classList.remove('open');
        lightboxModal.setAttribute('aria-hidden', 'true');
        if (lightboxImage) lightboxImage.src = '';
        syncModalBodyState();
    }

    function moveLightbox(direction) {
        if (!lightboxItems.length) return;
        lightboxIndex = (lightboxIndex + direction + lightboxItems.length) % lightboxItems.length;
        renderLightbox();
    }


    function setReportBusy(button, busy) {
        if (!button) return;
        button.disabled = busy;
        button.classList.toggle('is-loading', busy);
        if (busy) {
            button.dataset.originalText = button.textContent;
            button.textContent = 'Sendet ...';
        } else if (button.dataset.originalText) {
            button.textContent = button.dataset.originalText;
            delete button.dataset.originalText;
        }
    }

    function openReportModal(button) {
        if (!reportModal || !reportForm || !button || button.dataset.reportReported === '1') return;

        reportForm.reset();
        if (reportStatus) {
            reportStatus.hidden = true;
            reportStatus.textContent = '';
            reportStatus.classList.remove('is-error');
        }
        if (reportType) reportType.value = button.dataset.reportType || '';
        if (reportId) reportId.value = button.dataset.reportId || '';
        if (reportLabel) reportLabel.textContent = button.dataset.reportLabel || t('preview_report_default_label', 'Hilf uns, problematische Inhalte schneller zu prüfen.');
        reportModal.dataset.reportSourceId = button.dataset.reportId || '';
        reportModal.dataset.reportSourceType = button.dataset.reportType || '';
        reportModal.classList.add('open');
        reportModal.setAttribute('aria-hidden', 'false');
        syncModalBodyState();
        const select = reportModal.querySelector('select[name="reason"]');
        window.setTimeout(function () {
            if (select) select.focus();
        }, 180);
    }

    function closeReportModal() {
        if (!reportModal) return;
        reportModal.classList.remove('open');
        reportModal.setAttribute('aria-hidden', 'true');
        syncModalBodyState();
    }

    function markReported(type, id) {
        if (!type || !id) return;
        document.querySelectorAll('[data-hnt-report-open]').forEach(function (button) {
            if (button.dataset.reportType !== String(type) || button.dataset.reportId !== String(id)) return;
            button.dataset.reportReported = '1';
            button.classList.add('is-reported');
            button.disabled = true;
            button.setAttribute('title', 'Bereits gemeldet');
            button.setAttribute('aria-label', t('preview_report_already_reported_aria', 'Beitrag bereits gemeldet'));
        });
    }

    function setCommentEditStatus(form, message, isError) {
        const status = form ? form.querySelector('[data-hnt-comment-edit-status]') : null;
        if (!status) return;
        status.textContent = message || '';
        status.hidden = !message;
        status.classList.toggle('is-error', Boolean(isError));
    }

    function setCommentEditBusy(form, busy) {
        if (!form) return;
        const textarea = form.querySelector('[data-hnt-comment-edit-textarea]');
        const submit = form.querySelector('[data-hnt-comment-edit-submit]');
        const cancel = form.querySelector('[data-hnt-comment-edit-cancel]');
        if (textarea) textarea.disabled = busy;
        if (submit) {
            submit.disabled = busy;
            submit.classList.toggle('is-loading', busy);
        }
        if (cancel) cancel.disabled = busy;
    }

    function setCommentEditMode(item, editing) {
        if (!item) return;
        const body = item.querySelector('[data-hnt-comment-body]');
        const form = item.querySelector('[data-hnt-comment-edit-form]');
        const textarea = item.querySelector('[data-hnt-comment-edit-textarea]');
        if (!form) return;

        item.classList.toggle('is-editing', editing);
        form.hidden = !editing;
        if (body) body.hidden = editing;
        setCommentEditStatus(form, '', false);

        if (editing && textarea) {
            window.setTimeout(function () {
                textarea.focus();
                textarea.selectionStart = textarea.value.length;
                textarea.selectionEnd = textarea.value.length;
            }, 40);
        }
    }

    function submitCommentEdit(form) {
        if (!form || !form.action) return;
        const item = form.closest('[data-hnt-comment-item]');
        const body = item ? item.querySelector('[data-hnt-comment-body]') : null;
        const textarea = form.querySelector('[data-hnt-comment-edit-textarea]');
        const formData = new FormData(form);

        setCommentEditBusy(form, true);
        setCommentEditStatus(form, '', false);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok) {
                        const message = payload && payload.message ? payload.message : t('preview_comment_save_failed', 'Kommentar konnte nicht gespeichert werden.');
                        throw new Error(message);
                    }
                    return payload;
                });
            })
            .then(function (payload) {
                if (body && payload && payload.body_html) body.innerHTML = String(payload.body_html);
                if (textarea && payload && typeof payload.body !== 'undefined') textarea.value = String(payload.body);
                setCommentEditMode(item, false);
            })
            .catch(function (error) {
                setCommentEditStatus(form, error && error.message ? error.message : t('preview_comment_save_failed', 'Kommentar konnte nicht gespeichert werden.'), true);
            })
            .finally(function () {
                setCommentEditBusy(form, false);
            });
    }

    function deleteComment(button) {
        if (!button || !button.dataset.deleteUrl) return;
        const item = button.closest('[data-hnt-comment-item]');
        const confirmed = window.confirm(t('preview_comment_delete_confirm', 'Kommentar wirklich löschen?'));
        if (!confirmed) return;

        const formData = new FormData();
        formData.append('_method', 'DELETE');

        button.disabled = true;
        button.classList.add('is-loading');

        fetch(button.dataset.deleteUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok) {
                        const message = payload && payload.message ? payload.message : t('preview_comment_delete_failed', 'Kommentar konnte nicht gelöscht werden.');
                        throw new Error(message);
                    }
                    return payload;
                });
            })
            .then(function (payload) {
                if (payload && typeof payload.comment_count !== 'undefined') {
                    updatePostCommentCount(payload.post_id || currentPostModalPostId, payload.comment_count);
                    updateModalCommentCount(payload.comment_count);
                }

                if (currentPostModalUrl) {
                    loadPostModalContent(currentPostModalUrl, currentPostModalStoreUrl, currentPostModalPostId, false);
                } else if (item) {
                    item.remove();
                }
            })
            .catch(function (error) {
                button.disabled = false;
                button.classList.remove('is-loading');
                window.alert(error && error.message ? error.message : t('preview_comment_delete_failed', 'Kommentar konnte nicht gelöscht werden.'));
            });
    }



    function toggleCommentReplyForm(button) {
        if (!button) return;
        const item = button.closest('[data-hnt-comment-item]');
        const form = item ? item.querySelector(':scope > .hnt-comment-modal-main > [data-hnt-comment-reply-form]') : null;
        if (!form) return;

        const isOpen = !form.hidden;
        form.hidden = isOpen;
        button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');

        if (!isOpen) {
            const textarea = form.querySelector('[data-hnt-comment-reply-textarea]');
            const status = form.querySelector('[data-hnt-comment-reply-status]');
            if (status) {
                status.hidden = true;
                status.textContent = '';
                status.classList.remove('is-error');
            }
            window.setTimeout(function () {
                if (textarea) textarea.focus();
            }, 40);
        }
    }

    function setCommentReplyBusy(form, busy) {
        const textarea = form ? form.querySelector('[data-hnt-comment-reply-textarea]') : null;
        const submit = form ? form.querySelector('[data-hnt-comment-reply-submit]') : null;
        const fileInput = form ? form.querySelector('[data-hnt-comment-media-input]') : null;
        const mediaButton = form ? form.querySelector('[data-hnt-comment-media-trigger]') : null;
        if (textarea) textarea.disabled = Boolean(busy);
        if (fileInput) fileInput.disabled = Boolean(busy);
        if (mediaButton) mediaButton.disabled = Boolean(busy);
        if (submit) {
            submit.disabled = Boolean(busy);
            submit.classList.toggle('is-loading', Boolean(busy));
        }
    }

    function setCommentReplyStatus(form, message, isError) {
        const status = form ? form.querySelector('[data-hnt-comment-reply-status]') : null;
        if (!status) return;
        status.textContent = message || '';
        status.hidden = !message;
        status.classList.toggle('is-error', Boolean(isError));
    }

    function submitCommentReply(form) {
        if (!form || !form.action) return;
        if (!commentFormHasTextOrMedia(form)) {
            setCommentReplyStatus(form, t('preview_comment_body_or_media_required', 'Kommentar braucht Text oder Bild.'), true);
            return;
        }

        const formData = new FormData(form);
        setCommentReplyBusy(form, true);
        setCommentReplyStatus(form, '', false);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok) {
                        const message = payload && payload.message ? payload.message : t('preview_comment_send_failed', 'Kommentar konnte nicht gesendet werden.');
                        throw new Error(message);
                    }
                    return payload;
                });
            })
            .then(function (payload) {
                const textarea = form.querySelector('[data-hnt-comment-reply-textarea]');
                if (textarea) textarea.value = '';
                clearCommentMediaPreview(form);
                if (payload && typeof payload.comment_count_delta !== 'undefined') {
                    const current = Number((postModal && postModal.querySelector('[data-hnt-modal-comment-count]') || {}).textContent || 0) || 0;
                    updateModalCommentCount(current + Number(payload.comment_count_delta || 0));
                    updatePostCommentCount(currentPostModalPostId, current + Number(payload.comment_count_delta || 0));
                }
                if (currentPostModalUrl) {
                    loadPostModalContent(currentPostModalUrl, currentPostModalStoreUrl, currentPostModalPostId, false);
                } else {
                    setCommentReplyStatus(form, t('preview_comment_sent', 'Kommentar wurde gepostet.'), false);
                }
            })
            .catch(function (error) {
                setCommentReplyStatus(form, error && error.message ? error.message : t('preview_comment_send_failed', 'Kommentar konnte nicht gesendet werden.'), true);
            })
            .finally(function () {
                setCommentReplyBusy(form, false);
            });
    }

    function closePostOptionMenus(exceptMenu) {
        document.querySelectorAll('[data-hnt-post-options].open').forEach(function (menu) {
            if (exceptMenu && menu === exceptMenu) return;
            menu.classList.remove('open');
            const toggle = menu.querySelector('[data-hnt-post-options-toggle]');
            const submenu = menu.querySelector('.hnt-post-options-menu');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
            if (submenu) submenu.setAttribute('aria-hidden', 'true');
        });
    }

    function setPostEditStatus(form, message, isError) {
        const status = form ? form.querySelector('[data-hnt-post-edit-status]') : null;
        if (!status) return;
        status.textContent = message || '';
        status.hidden = !message;
        status.classList.toggle('is-error', Boolean(isError));
    }

    function resetReadMore(textBlock) {
        if (!textBlock) return;
        if (textBlock.nextElementSibling && textBlock.nextElementSibling.classList.contains('hnt-read-more-toggle')) {
            textBlock.nextElementSibling.remove();
        }
        textBlock.classList.remove('is-collapsible', 'is-collapsed', 'is-expanded');
        textBlock.style.removeProperty('--hnt-read-more-height');
        textBlock.removeAttribute('data-hnt-read-more-ready');
    }

    function escapeAttribute(value) {
        return String(value || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function renderInternalLinkPreviews(article, previews) {
        const container = article ? article.querySelector('[data-hnt-internal-link-previews]') : null;
        if (!container || !Array.isArray(previews)) return;
        container.innerHTML = previews.map(function (preview) {
            return '<a class="hnt-internal-link-preview" href="' + escapeAttribute(preview.url || '#') + '">' +
                '<span class="hnt-internal-link-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 2a2 2 0 012 2v1h4a1 1 0 011 1v2a5 5 0 01-5 5h-.4a5.5 5.5 0 01-3.1 2.7V18h3a1 1 0 110 2h-8a1 1 0 110-2h3v-2.3A5.5 5.5 0 015.4 13H5a5 5 0 01-5-5V6a1 1 0 011-1h4V4a2 2 0 012-2h4zm5 5h-3v4a3 3 0 003-3V7zM5 7H2v1a3 3 0 003 3V7z"/></svg></span>' +
                '<span class="hnt-internal-link-copy"><span>' + escapeAttribute(preview.label || '') + '</span><strong>' + escapeAttribute(preview.title || '') + '</strong><em>' + escapeAttribute(preview.description || '') + '</em><small>' + escapeAttribute(preview.display_url || '') + '</small></span>' +
                '</a>';
        }).join('');
    }

    function openPostEdit(button) {
        const article = button ? button.closest('[data-hnt-preview-post]') : null;
        const form = article ? article.querySelector('[data-hnt-post-edit-form]') : null;
        const bodyWrap = article ? article.querySelector('[data-hnt-post-body-wrap]') : null;
        const bodyNode = article ? article.querySelector('[data-hnt-post-body]') : null;
        const textarea = form ? form.querySelector('[data-hnt-post-edit-textarea]') : null;
        if (!article || !form || !textarea) return;

        closePostOptionMenus();
        if (bodyNode) {
            textarea.value = bodyNode.dataset.rawBody || textarea.value || bodyNode.textContent || '';
            bodyNode.hidden = true;
        }
        if (bodyWrap) bodyWrap.hidden = false;
        form.hidden = false;
        setPostEditStatus(form, '', false);
        window.setTimeout(function () {
            textarea.focus();
            textarea.selectionStart = textarea.value.length;
            textarea.selectionEnd = textarea.value.length;
        }, 40);
    }

    function closePostEdit(buttonOrForm) {
        const form = buttonOrForm ? buttonOrForm.closest('[data-hnt-post-edit-form]') : null;
        const article = form ? form.closest('[data-hnt-preview-post]') : null;
        const bodyWrap = article ? article.querySelector('[data-hnt-post-body-wrap]') : null;
        const bodyNode = article ? article.querySelector('[data-hnt-post-body]') : null;
        if (!form) return;
        form.hidden = true;
        if (bodyNode) bodyNode.hidden = false;
        if (bodyWrap && bodyNode && !(bodyNode.dataset.rawBody || '').trim()) bodyWrap.hidden = true;
        setPostEditStatus(form, '', false);
    }

    function setPostEditBusy(form, busy) {
        const textarea = form ? form.querySelector('[data-hnt-post-edit-textarea]') : null;
        const submit = form ? form.querySelector('[data-hnt-post-edit-submit]') : null;
        const cancel = form ? form.querySelector('[data-hnt-post-edit-cancel]') : null;
        if (textarea) textarea.disabled = busy;
        if (cancel) cancel.disabled = busy;
        if (submit) {
            submit.disabled = busy;
            submit.classList.toggle('is-loading', busy);
        }
    }

    function submitPostEdit(form) {
        if (!form || !form.action) return;
        const article = form.closest('[data-hnt-preview-post]');
        const bodyWrap = article ? article.querySelector('[data-hnt-post-body-wrap]') : null;
        const bodyNode = article ? article.querySelector('[data-hnt-post-body]') : null;
        const textarea = form.querySelector('[data-hnt-post-edit-textarea]');
        const formData = new FormData(form);

        setPostEditBusy(form, true);
        setPostEditStatus(form, '', false);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok) throw new Error(payload && payload.message ? payload.message : t('preview_post_save_failed', 'Beitrag konnte nicht gespeichert werden.'));
                    return payload;
                });
            })
            .then(function (payload) {
                if (bodyNode) {
                    bodyNode.innerHTML = payload && payload.body_html ? String(payload.body_html) : '';
                    bodyNode.dataset.rawBody = payload && typeof payload.body !== 'undefined' ? String(payload.body) : (textarea ? textarea.value : '');
                    bodyNode.hidden = false;
                    resetReadMore(bodyNode);
                    initReadMore(bodyWrap || article || document);
                }
                if (bodyWrap) bodyWrap.hidden = !(bodyNode && (bodyNode.dataset.rawBody || '').trim());
                if (article && payload && payload.internal_link_previews) renderInternalLinkPreviews(article, payload.internal_link_previews);
                if (article) {
                    const translationPanel = article.querySelector('[data-hnt-post-translation]');
                    const translationButton = article.querySelector('[data-hnt-post-translate]');
                    if (translationPanel) {
                        translationPanel.hidden = true;
                        delete translationPanel.dataset.loaded;
                    }
                    if (translationButton) {
                        setPostTranslationButton(translationButton, translationButton.dataset.showLabel || t('translation_action_short', 'Translate'), false);
                    }
                }
                form.hidden = true;
            })
            .catch(function (error) {
                setPostEditStatus(form, error && error.message ? error.message : t('preview_post_save_failed', 'Beitrag konnte nicht gespeichert werden.'), true);
            })
            .finally(function () {
                setPostEditBusy(form, false);
            });
    }

    function deletePost(form) {
        const article = form ? form.closest('[data-hnt-preview-post]') : null;
        if (!form || !form.action) return;
        if (!window.confirm(t('preview_post_delete_confirm', 'Beitrag wirklich löschen?'))) return;

        const submit = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        if (submit) {
            submit.disabled = true;
            submit.classList.add('is-loading');
        }

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok) throw new Error(payload && payload.message ? payload.message : t('preview_post_delete_failed', 'Beitrag konnte nicht gelöscht werden.'));
                    return payload;
                });
            })
            .then(function () {
                if (article) article.remove();
            })
            .catch(function () {
                form.submit();
            });
    }

    function setPostTranslationPanel(article, metaText, bodyHtml, isError) {
        const panel = article ? article.querySelector('[data-hnt-post-translation]') : null;
        if (!panel) return;

        const meta = panel.querySelector('[data-hnt-post-translation-meta]');
        const body = panel.querySelector('[data-hnt-post-translation-body]');

        if (meta) {
            meta.textContent = metaText || '';
            meta.hidden = !metaText;
        }

        if (body) {
            body.innerHTML = bodyHtml || '';
            resetReadMore(body);
        }

        panel.hidden = false;
        panel.classList.toggle('is-error', Boolean(isError));

        if (body && bodyHtml && !isError) {
            initReadMore(panel);
        }
    }

    function setPostTranslationButton(button, label, busy) {
        if (!button) return;
        button.textContent = label || button.dataset.showLabel || t('translation_action_short', 'Translate');
        button.disabled = Boolean(busy);
        button.classList.toggle('is-loading', Boolean(busy));
    }

    function togglePostTranslation(button) {
        const article = button ? button.closest('[data-hnt-preview-post]') : null;
        const panel = article ? article.querySelector('[data-hnt-post-translation]') : null;
        if (!article || !panel) return;

        const showLabel = button.dataset.showLabel || t('translation_action_short', 'Translate');
        const hideLabel = button.dataset.hideLabel || t('translation_hide', 'Übersetzung ausblenden');
        const loadingLabel = button.dataset.loadingLabel || t('translation_loading', 'Wird übersetzt ...');
        const errorLabel = button.dataset.errorLabel || t('translation_error', 'Übersetzung fehlgeschlagen');

        if (!panel.hidden && panel.dataset.loaded === '1') {
            panel.hidden = true;
            setPostTranslationButton(button, showLabel, false);
            return;
        }

        if (panel.dataset.loaded === '1') {
            panel.hidden = false;
            setPostTranslationButton(button, hideLabel, false);
            return;
        }

        const url = button.dataset.translationUrl || '';
        if (!url) return;

        const formData = new FormData();
        formData.append('locale', button.dataset.translationTarget || previewLocale || 'de');

        setPostTranslationButton(button, loadingLabel, true);
        setPostTranslationPanel(article, loadingLabel, '', false);

        fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                return response.json().then(function (payload) {
                    if (!response.ok || !payload || payload.ok === false) {
                        throw new Error(payload && payload.message ? payload.message : errorLabel);
                    }
                    return payload;
                });
            })
            .then(function (payload) {
                const meta = payload.meta_label || payload.provider_label || '';
                setPostTranslationPanel(article, meta, payload.translated_html || '', false);
                panel.dataset.loaded = '1';
                setPostTranslationButton(button, hideLabel, false);
            })
            .catch(function (error) {
                const message = error && error.message ? error.message : errorLabel;
                setPostTranslationPanel(article, message, '', true);
                setPostTranslationButton(button, showLabel, false);
            });
    }

    if (openComposer) {
        openComposer.addEventListener('click', openComposerModal);
    }
    composerOpenTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            setMobileMenuOpen(false);
            openComposerModal();
        });
    });

    closeComposerButtons.forEach(function (button) {
        button.addEventListener('click', closeComposerModal);
    });

    postModalCloseButtons.forEach(function (button) {
        button.addEventListener('click', closePostModal);
    });

    if (likesModal) {
        likesModal.querySelectorAll('[data-hnt-likes-close]').forEach(function (button) {
            button.addEventListener('click', closeLikesModal);
        });

        likesModal.addEventListener('click', function (event) {
            if (event.target === likesModal) closeLikesModal();
        });
    }


    document.addEventListener('click', function (event) {
        const postCommentButton = event.target.closest('[data-post-modal-open]');
        if (!postCommentButton) return;
        event.preventDefault();
        openPostModal(postCommentButton);
    });

    document.addEventListener('click', function (event) {
        const likesButton = event.target.closest('[data-hnt-likes-open]');
        if (!likesButton) return;
        event.preventDefault();
        openLikesModal(likesButton);
    });

    document.addEventListener('click', function (event) {
        const editOpen = event.target.closest('[data-hnt-comment-edit-open]');
        if (editOpen) {
            event.preventDefault();
            setCommentEditMode(editOpen.closest('[data-hnt-comment-item]'), true);
            return;
        }

        const editCancel = event.target.closest('[data-hnt-comment-edit-cancel]');
        if (editCancel) {
            event.preventDefault();
            setCommentEditMode(editCancel.closest('[data-hnt-comment-item]'), false);
            return;
        }

        const replyToggle = event.target.closest('[data-hnt-comment-reply-toggle]');
        if (replyToggle) {
            event.preventDefault();
            toggleCommentReplyForm(replyToggle);
            return;
        }

        const deleteButton = event.target.closest('[data-hnt-comment-delete]');
        if (deleteButton) {
            event.preventDefault();
            deleteComment(deleteButton);
        }
    });

    document.addEventListener('click', function (event) {
        const mediaTrigger = event.target.closest('[data-hnt-comment-media-trigger]');
        if (mediaTrigger) {
            event.preventDefault();
            const form = mediaTrigger.closest('[data-hnt-comment-form], [data-hnt-comment-reply-form]');
            const input = form ? form.querySelector('[data-hnt-comment-media-input]') : null;
            if (input && !input.disabled) input.click();
            return;
        }

        const clearButton = event.target.closest('[data-hnt-comment-media-clear]');
        if (clearButton) {
            event.preventDefault();
            clearCommentMediaPreview(clearButton.closest('[data-hnt-comment-form], [data-hnt-comment-reply-form]'));
            return;
        }

        const removeButton = event.target.closest('[data-hnt-comment-media-remove]');
        if (!removeButton) return;

        event.preventDefault();
        const form = removeButton.closest('[data-hnt-comment-form], [data-hnt-comment-reply-form]');
        const input = form ? form.querySelector('[data-hnt-comment-media-input]') : null;
        const removeIndex = Number.parseInt(removeButton.dataset.hntCommentMediaRemove || '-1', 10);
        if (!input || removeIndex < 0) return;

        const nextFiles = Array.from(input.files || []).filter(function (_file, index) {
            return index !== removeIndex;
        });
        setCommentMediaFiles(input, nextFiles);
        renderCommentMediaPreview(form);
        updateCommentSubmitState(form);
    });

    document.addEventListener('change', function (event) {
        const input = event.target.closest('[data-hnt-comment-media-input]');
        if (!input) return;
        handleCommentMediaSelection(input);
    });

    document.addEventListener('input', function (event) {
        const textarea = event.target.closest('[data-hnt-comment-textarea], [data-hnt-comment-reply-textarea]');
        if (!textarea) return;
        const form = textarea.closest('[data-hnt-comment-form], [data-hnt-comment-reply-form]');
        if (commentFormHasTextOrMedia(form)) setCommentFormStatus(form, '', false);
        updateCommentSubmitState(form);
    });

    document.addEventListener('submit', function (event) {
        const likeForm = event.target.closest('[data-hnt-simple-like-form]');
        if (!likeForm) return;
        event.preventDefault();
        submitSimpleLike(likeForm);
    });

    document.addEventListener('click', function (event) {
        const translateButton = event.target.closest('[data-hnt-post-translate]');
        if (!translateButton) return;
        event.preventDefault();
        togglePostTranslation(translateButton);
    });

    document.addEventListener('click', function (event) {
        const nativeShareButton = event.target.closest('[data-hnt-native-share-button]');
        if (!nativeShareButton) return;
        event.preventDefault();
        openNativeShare(nativeShareButton);
    });

    document.addEventListener('submit', function (event) {
        const shareForm = event.target.closest('[data-hnt-share-form]');
        if (shareForm) {
            event.preventDefault();
            submitShare(shareForm);
            return;
        }

        const bookmarkForm = event.target.closest('[data-hnt-bookmark-form]');
        if (bookmarkForm) {
            event.preventDefault();
            submitBookmark(bookmarkForm);
        }
    });

    document.addEventListener('submit', function (event) {
        const replyForm = event.target.closest('[data-hnt-comment-reply-form]');
        if (replyForm) {
            event.preventDefault();
            submitCommentReply(replyForm);
            return;
        }

        const editForm = event.target.closest('[data-hnt-comment-edit-form]');
        if (!editForm) return;
        event.preventDefault();
        submitCommentEdit(editForm);
    });

    document.addEventListener('click', function (event) {
        const lightboxTrigger = event.target.closest('[data-hnt-lightbox-trigger]');
        if (lightboxTrigger) {
            event.preventDefault();
            openLightbox(lightboxTrigger);
            return;
        }

        const optionsToggle = event.target.closest('[data-hnt-post-options-toggle]');
        if (optionsToggle) {
            event.preventDefault();
            event.stopPropagation();
            const menu = optionsToggle.closest('[data-hnt-post-options]');
            if (!menu) return;
            const willOpen = !menu.classList.contains('open');
            closePostOptionMenus(menu);
            menu.classList.toggle('open', willOpen);
            optionsToggle.setAttribute('aria-expanded', String(willOpen));
            const submenu = menu.querySelector('.hnt-post-options-menu');
            if (submenu) submenu.setAttribute('aria-hidden', String(!willOpen));
            return;
        }

        const optionsClose = event.target.closest('[data-hnt-post-options-close]');
        if (optionsClose) {
            event.preventDefault();
            closePostOptionMenus();
            return;
        }

        const postEditOpen = event.target.closest('[data-hnt-post-edit-open]');
        if (postEditOpen) {
            event.preventDefault();
            openPostEdit(postEditOpen);
            return;
        }

        const postEditCancel = event.target.closest('[data-hnt-post-edit-cancel]');
        if (postEditCancel) {
            event.preventDefault();
            closePostEdit(postEditCancel);
            return;
        }

        if (!event.target.closest('[data-hnt-post-options]')) {
            closePostOptionMenus();
        }
    });

    document.addEventListener('submit', function (event) {
        const postEditForm = event.target.closest('[data-hnt-post-edit-form]');
        if (postEditForm) {
            event.preventDefault();
            submitPostEdit(postEditForm);
            return;
        }

        const postDeleteForm = event.target.closest('[data-hnt-post-delete-form]');
        if (postDeleteForm) {
            event.preventDefault();
            deletePost(postDeleteForm);
        }
    });

    document.addEventListener('click', function (event) {
        const reportButton = event.target.closest('[data-hnt-report-open]');
        if (!reportButton) return;
        event.preventDefault();
        openReportModal(reportButton);
    });

    document.querySelectorAll('[data-hnt-report-close]').forEach(function (button) {
        button.addEventListener('click', closeReportModal);
    });

    document.querySelectorAll('[data-hnt-lightbox-close]').forEach(function (button) {
        button.addEventListener('click', closeLightbox);
    });
    document.querySelectorAll('[data-hnt-lightbox-prev]').forEach(function (button) {
        button.addEventListener('click', function () { moveLightbox(-1); });
    });
    document.querySelectorAll('[data-hnt-lightbox-next]').forEach(function (button) {
        button.addEventListener('click', function () { moveLightbox(1); });
    });

    if (composerModal) {
        composerModal.addEventListener('click', function (event) {
            if (event.target === composerModal) closeComposerModal();
        });
    }

    if (postModal) {
        postModal.addEventListener('click', function (event) {
            if (event.target === postModal) closePostModal();
        });
    }


    if (reportModal) {
        reportModal.addEventListener('click', function (event) {
            if (event.target === reportModal) closeReportModal();
        });
    }

    if (lightboxModal) {
        lightboxModal.addEventListener('click', function (event) {
            if (event.target === lightboxModal) closeLightbox();
        });
    }

    if (reportForm) {
        reportForm.addEventListener('submit', function (event) {
            event.preventDefault();

            const submitButton = reportForm.querySelector('[data-hnt-report-submit]');
            const formData = new FormData(reportForm);
            setReportBusy(submitButton, true);

            if (reportStatus) {
                reportStatus.hidden = true;
                reportStatus.textContent = '';
                reportStatus.classList.remove('is-error');
            }

            fetch(reportForm.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: formData
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok) {
                            const message = payload && payload.message ? payload.message : 'Meldung konnte nicht gesendet werden.';
                            throw new Error(message);
                        }
                        return payload;
                    });
                })
                .then(function (payload) {
                    const message = payload && payload.message ? payload.message : 'Danke, deine Meldung wurde gesendet.';
                    if (reportStatus) {
                        reportStatus.textContent = message;
                        reportStatus.hidden = false;
                    }

                    const report = payload && payload.report ? payload.report : null;
                    markReported(report ? report.type : reportType && reportType.value, report ? report.id : reportId && reportId.value);
                    window.setTimeout(closeReportModal, 1150);
                })
                .catch(function (error) {
                    if (reportStatus) {
                        reportStatus.textContent = error && error.message ? error.message : 'Meldung konnte nicht gesendet werden.';
                        reportStatus.classList.add('is-error');
                        reportStatus.hidden = false;
                    } else {
                        reportForm.submit();
                    }
                })
                .finally(function () {
                    setReportBusy(submitButton, false);
                });
        });
    }


    function setCommentBusy(busy) {
        if (!postModalCommentForm) return;
        const submitButton = postModalCommentForm.querySelector('[data-hnt-comment-submit]');
        const fileInput = postModalCommentForm.querySelector('[data-hnt-comment-media-input]');
        const mediaButton = postModalCommentForm.querySelector('[data-hnt-comment-media-trigger]');
        if (submitButton) {
            submitButton.disabled = busy;
            submitButton.classList.toggle('is-loading', busy);
        }
        if (postModalCommentTextarea) postModalCommentTextarea.disabled = busy;
        if (fileInput) fileInput.disabled = busy;
        if (mediaButton) mediaButton.disabled = busy;
    }

    if (postModalCommentForm) {
        postModalCommentForm.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!postModalCommentForm.action || postModalCommentForm.action === '#') return;

            if (!commentFormHasTextOrMedia(postModalCommentForm)) {
                if (postModalCommentStatus) {
                    postModalCommentStatus.textContent = t('preview_comment_body_or_media_required', 'Kommentar braucht Text oder Bild.');
                    postModalCommentStatus.classList.add('is-error');
                    postModalCommentStatus.hidden = false;
                }
                return;
            }

            const formData = new FormData(postModalCommentForm);
            setCommentBusy(true);
            if (postModalCommentStatus) {
                postModalCommentStatus.hidden = true;
                postModalCommentStatus.textContent = '';
                postModalCommentStatus.classList.remove('is-error');
            }

            fetch(postModalCommentForm.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: formData
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok) {
                            const message = payload && payload.message ? payload.message : t('preview_comment_send_failed', 'Kommentar konnte nicht gesendet werden.');
                            throw new Error(message);
                        }
                        return payload;
                    });
                })
                .then(function () {
                    if (postModalCommentTextarea) postModalCommentTextarea.value = '';
                    clearCommentMediaPreview(postModalCommentForm);
                    if (postModalCommentStatus) {
                        postModalCommentStatus.textContent = t('preview_comment_sent', 'Kommentar wurde gepostet.');
                        postModalCommentStatus.hidden = false;
                    }
                    if (currentPostModalUrl) {
                        loadPostModalContent(currentPostModalUrl, currentPostModalStoreUrl, currentPostModalPostId, false);
                    }
                })
                .catch(function (error) {
                    if (postModalCommentStatus) {
                        postModalCommentStatus.textContent = error && error.message ? error.message : t('preview_comment_send_failed', 'Kommentar konnte nicht gesendet werden.');
                        postModalCommentStatus.classList.add('is-error');
                        postModalCommentStatus.hidden = false;
                    } else {
                        postModalCommentForm.submit();
                    }
                })
                .finally(function () {
                    setCommentBusy(false);
                });
        });
    }


    function initReadMore(scope) {
        const root = scope || document;
        root.querySelectorAll('[data-hnt-read-more]:not([data-hnt-read-more-ready])').forEach(function (textBlock) {
            textBlock.setAttribute('data-hnt-read-more-ready', '1');

            window.requestAnimationFrame(function () {
                const maxCollapsedHeight = 118;
                if (textBlock.scrollHeight <= maxCollapsedHeight + 8) return;

                textBlock.classList.add('is-collapsible', 'is-collapsed');
                textBlock.style.setProperty('--hnt-read-more-height', maxCollapsedHeight + 'px');

                const toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'hnt-read-more-toggle';
                toggle.textContent = 'Mehr lesen';
                toggle.setAttribute('aria-expanded', 'false');

                toggle.addEventListener('click', function () {
                    const expanded = textBlock.classList.toggle('is-expanded');
                    textBlock.classList.toggle('is-collapsed', !expanded);
                    toggle.textContent = expanded ? 'Weniger lesen' : 'Mehr lesen';
                    toggle.setAttribute('aria-expanded', String(expanded));
                });

                textBlock.insertAdjacentElement('afterend', toggle);
            });
        });
    }

    function initMediaCarousels(scope) {
        const root = scope || document;
        root.querySelectorAll('[data-hnt-media-carousel]:not([data-hnt-carousel-ready])').forEach(function (carousel) {
            const slides = Array.from(carousel.querySelectorAll('[data-hnt-media-slide]'));
            const previousButton = carousel.querySelector('[data-hnt-media-prev]');
            const nextButton = carousel.querySelector('[data-hnt-media-next]');
            const currentCounter = carousel.querySelector('[data-hnt-media-current]');
            let currentIndex = 0;

            carousel.setAttribute('data-hnt-carousel-ready', '1');

            function renderSlide(nextIndex) {
                if (!slides.length) return;

                currentIndex = (nextIndex + slides.length) % slides.length;

                slides.forEach(function (slide, index) {
                    const active = index === currentIndex;
                    slide.classList.toggle('is-active', active);
                    slide.setAttribute('aria-hidden', String(!active));

                    if (!active) {
                        slide.querySelectorAll('video').forEach(function (video) {
                            video.pause();
                        });
                    }
                });

                if (currentCounter) currentCounter.textContent = String(currentIndex + 1);
            }

            if (previousButton) {
                previousButton.addEventListener('click', function () {
                    renderSlide(currentIndex - 1);
                });
            }

            if (nextButton) {
                nextButton.addEventListener('click', function () {
                    renderSlide(currentIndex + 1);
                });
            }

            renderSlide(0);
        });
    }

    function formatVideoTime(value) {
        if (!Number.isFinite(value) || value < 0) return '0:00';
        const minutes = Math.floor(value / 60);
        const seconds = Math.floor(value % 60);
        return String(minutes) + ':' + String(seconds).padStart(2, '0');
    }

    function setVideoIcon(player, playing) {
        if (!player) return;
        player.querySelectorAll('[data-hnt-video-icon]').forEach(function (icon) {
            if (icon.classList && icon.classList.contains('ph')) {
                icon.classList.toggle('ph-play', !playing);
                icon.classList.toggle('ph-pause', playing);
            } else {
                const path = playing ? 'M7 5h4v14H7V5zm6 0h4v14h-4V5z' : 'M8 5v14l11-7z';
                icon.innerHTML = '<path fill="currentColor" d="' + path + '"/>';
            }
        });
        const center = player.querySelector('.hnt-video-center-play');
        if (center) center.classList.toggle('is-hidden', playing);
    }

    function initVideoPlayers(scope) {
        const root = scope || document;
        root.querySelectorAll('[data-hnt-video-player]:not([data-hnt-video-ready])').forEach(function (player) {
            const video = player.querySelector('[data-hnt-video]');
            const seek = player.querySelector('[data-hnt-video-seek]');
            const time = player.querySelector('[data-hnt-video-time]');
            const mute = player.querySelector('[data-hnt-video-mute]');
            const fullscreen = player.querySelector('[data-hnt-video-fullscreen]');
            if (!video) return;

            player.setAttribute('data-hnt-video-ready', '1');
            video.controls = false;

            function updateProgress() {
                if (seek && Number.isFinite(video.duration) && video.duration > 0) {
                    seek.value = String((video.currentTime / video.duration) * 100);
                }
                if (time) time.textContent = formatVideoTime(video.currentTime);
            }

            player.querySelectorAll('[data-hnt-video-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (video.paused) {
                        video.play().catch(function () {});
                    } else {
                        video.pause();
                    }
                });
            });

            video.addEventListener('click', function () {
                if (video.paused) video.play().catch(function () {});
                else video.pause();
            });

            video.addEventListener('play', function () { setVideoIcon(player, true); });
            video.addEventListener('pause', function () { setVideoIcon(player, false); });
            video.addEventListener('ended', function () { setVideoIcon(player, false); });
            video.addEventListener('loadedmetadata', updateProgress);
            video.addEventListener('timeupdate', updateProgress);

            if (seek) {
                seek.addEventListener('input', function () {
                    if (!Number.isFinite(video.duration) || video.duration <= 0) return;
                    video.currentTime = (Number(seek.value) / 100) * video.duration;
                });
            }

            if (mute) {
                mute.addEventListener('click', function () {
                    video.muted = !video.muted;
                    mute.classList.toggle('is-active', !video.muted);
                });
            }

            if (fullscreen) {
                fullscreen.addEventListener('click', function () {
                    if (player.requestFullscreen) player.requestFullscreen().catch(function () {});
                });
            }

            setVideoIcon(player, false);
            updateProgress();
        });
    }

    document.addEventListener('click', function (event) {
        const emptyComposerButton = event.target.closest('#openComposerEmpty');
        if (emptyComposerButton) {
            event.preventDefault();
            openComposerModal();
            return;
        }

        const loadMoreLink = event.target.closest('[data-hnt-feed-load-more-link]');
        if (!loadMoreLink) return;

        const feedContent = document.querySelector('.hnt-preview-feed-content');
        const loadMoreWrapper = loadMoreLink.closest('[data-hnt-feed-load-more]');
        if (!feedContent || !loadMoreWrapper) return;

        event.preventDefault();

        const url = new URL(loadMoreLink.href, window.location.origin);
        url.searchParams.set('fragment', '1');

        loadMoreLink.classList.add('is-loading');
        loadMoreLink.textContent = t('preview_feed_load_more_loading', 'Lade ...');
        loadMoreLink.setAttribute('aria-busy', 'true');

        fetch(url.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) throw new Error('Feed konnte nicht nachgeladen werden.');
                return response.json();
            })
            .then(function (payload) {
                const html = payload && payload.html ? String(payload.html) : '';
                const fragment = document.createElement('div');
                fragment.innerHTML = html;

                loadMoreWrapper.remove();

                Array.from(fragment.childNodes).forEach(function (node) {
                    feedContent.appendChild(node);
                });

                initReadMore(feedContent);
                initMediaCarousels(feedContent);
                initVideoPlayers(feedContent);
            })
            .catch(function () {
                window.location.href = loadMoreLink.href;
            });
    });



    function initCupSubmissionUploads() {
        document.querySelectorAll('[data-hnt-cup-submission-form]:not([data-hnt-cup-upload-ready])').forEach(function (form) {
            const modal = document.querySelector('[data-hnt-cup-upload-modal]');
            const title = modal ? modal.querySelector('[data-hnt-cup-upload-title]') : null;
            const text = modal ? modal.querySelector('[data-hnt-cup-upload-text]') : null;
            const bar = modal ? modal.querySelector('[data-hnt-cup-upload-bar]') : null;
            const percent = modal ? modal.querySelector('[data-hnt-cup-upload-percent]') : null;
            const timeLabel = modal ? modal.querySelector('[data-hnt-cup-upload-time]') : null;
            const resultBox = modal ? modal.querySelector('[data-hnt-cup-upload-result]') : null;
            const closeButton = modal ? modal.querySelector('[data-hnt-cup-upload-close]') : null;
            const redirectButton = modal ? modal.querySelector('[data-hnt-cup-upload-redirect]') : null;
            const submitButton = form.querySelector('[data-hnt-cup-submit-button]') || form.querySelector('button[type="submit"]');
            const fileInput = form.querySelector('[data-hnt-cup-screenshot-input], input[type="file"][name="screenshot"]');
            const fileNameLabel = form.querySelector('[data-hnt-cup-file-name]');
            const filePicker = form.querySelector('.hnt-cup-file-picker');
            let activeXhr = null;
            let checkTimer = null;
            let checkStartedAt = 0;
            let estimatedSeconds = 45;
            let lastRedirectUrl = form.dataset.successRedirect || window.location.href;

            form.setAttribute('data-hnt-cup-upload-ready', '1');

            function updateCupFileLabel() {
                if (!fileInput || !fileNameLabel) return;
                const hasFile = Boolean(fileInput.files && fileInput.files.length);
                fileNameLabel.textContent = hasFile ? fileInput.files[0].name : t('preview_cup_no_screenshot', 'Kein Screenshot ausgewählt');
                if (filePicker) filePicker.classList.toggle('has-file', hasFile);
            }

            if (fileInput) {
                fileInput.addEventListener('change', updateCupFileLabel);
                updateCupFileLabel();
            }

            if (!modal) return;

            function escapeHtml(value) {
                return String(value == null ? '' : value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function setStep(activeStep) {
                modal.querySelectorAll('[data-hnt-cup-upload-step]').forEach(function (step) {
                    const order = ['upload', 'check', 'done'];
                    const activeIndex = order.indexOf(activeStep);
                    const stepIndex = order.indexOf(step.dataset.hntCupUploadStep || '');
                    const isActive = step.dataset.hntCupUploadStep === activeStep;
                    step.classList.toggle('active', isActive);
                    step.classList.toggle('done', stepIndex > -1 && activeIndex > stepIndex);
                });
            }

            function setProgress(value) {
                const safeValue = Math.max(0, Math.min(100, Math.round(Number(value) || 0)));
                if (bar) bar.style.width = safeValue + '%';
                if (percent) percent.textContent = safeValue + '%';
            }

            function setTimeLabel(message) {
                if (timeLabel) timeLabel.textContent = message || '';
            }

            function clearCheckTimer() {
                if (checkTimer) {
                    window.clearInterval(checkTimer);
                    checkTimer = null;
                }
            }

            function startCheckingTimer() {
                clearCheckTimer();
                checkStartedAt = Date.now();
                estimatedSeconds = Math.max(20, Number(form.dataset.analysisEstimate || 45) || 45);
                setTimeLabel(t('preview_cup_checking_estimated', 'Prüfung läuft · geschätzt ca. :seconds Sekunden', { seconds: estimatedSeconds }));

                checkTimer = window.setInterval(function () {
                    const elapsed = Math.max(0, Math.round((Date.now() - checkStartedAt) / 1000));
                    const remaining = Math.max(0, estimatedSeconds - elapsed);
                    const checkPercent = Math.min(96, 58 + Math.round((Math.min(elapsed, estimatedSeconds) / estimatedSeconds) * 38));
                    setProgress(checkPercent);

                    if (remaining > 0) {
                        setTimeLabel(t('preview_cup_checking_remaining', 'Prüfung läuft · ca. :seconds Sekunden verbleibend', { seconds: remaining }));
                    } else {
                        setTimeLabel(t('preview_cup_checking_longer', 'Prüfung läuft etwas länger · bitte nicht schließen'));
                    }
                }, 1000);
            }

            function openUploadModal() {
                modal.hidden = false;
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
                if (closeButton) closeButton.hidden = true;
                if (redirectButton) redirectButton.hidden = true;
            }

            function closeUploadModal() {
                if (activeXhr && activeXhr.readyState !== XMLHttpRequest.DONE) return;
                clearCheckTimer();
                modal.hidden = true;
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            }

            function setBusy(busy) {
                form.classList.toggle('is-uploading', Boolean(busy));
                if (submitButton) {
                    submitButton.disabled = Boolean(busy);
                    submitButton.classList.toggle('is-loading', Boolean(busy));
                }
            }

            function resetUploadUi() {
                openUploadModal();
                clearCheckTimer();
                modal.classList.remove('is-error', 'is-done', 'is-review');
                if (title) title.textContent = t('preview_cup_upload_title', 'Screenshot wird hochgeladen');
                if (text) text.textContent = t('preview_cup_upload_text', 'Bitte warte, bis Upload und Prüfung abgeschlossen sind.');
                if (resultBox) {
                    resultBox.hidden = true;
                    resultBox.innerHTML = '';
                }
                setStep('upload');
                setProgress(0);
                setTimeLabel(t('preview_cup_estimated_total', 'Geschätzte Gesamtdauer: ca. 45 Sekunden'));
            }

            function showUploadChecking() {
                if (title) title.textContent = t('preview_cup_check_title', 'Screenshot wird geprüft');
                if (text) text.textContent = t('preview_cup_check_text', 'Der Upload ist fertig. HNT.rocks prüft jetzt Screenshot, Ergebnis und Wertung.');
                setStep('check');
                setProgress(58);
                startCheckingTimer();
            }

            function renderResult(payload) {
                if (!resultBox) return;
                const result = payload && payload.result ? payload.result : {};
                const details = Array.isArray(result.details) ? result.details : [];
                const detailHtml = details.length
                    ? '<div class="hnt-cup-upload-result-grid">' + details.map(function (item) {
                        return '<div><span>' + escapeHtml(item.label || '') + '</span><strong>' + escapeHtml(item.value || '') + '</strong></div>';
                    }).join('') + '</div>'
                    : '';

                resultBox.innerHTML = '' +
                    '<div class="hnt-cup-upload-result-head">' +
                        '<span>' + escapeHtml(result.status || t('preview_cup_result_fallback', 'Ergebnis')) + '</span>' +
                        '<strong>' + escapeHtml(result.score || result.summary || '') + '</strong>' +
                    '</div>' +
                    (result.summary ? '<p>' + escapeHtml(result.summary) + '</p>' : '') +
                    detailHtml;
                resultBox.hidden = false;
            }

            function showUploadDone(payload) {
                clearCheckTimer();
                activeXhr = null;
                setBusy(false);
                modal.classList.add('is-done');
                modal.classList.toggle('is-review', Boolean(payload && payload.result && payload.result.type === 'warning'));
                if (title) title.textContent = payload && payload.result && payload.result.title ? String(payload.result.title) : t('preview_cup_saved_title', 'Einreichung gespeichert');
                if (text) text.textContent = payload && payload.message ? String(payload.message) : t('preview_cup_saved_text', 'Deine Einreichung wurde verarbeitet.');
                setStep('done');
                setProgress(100);
                setTimeLabel(t('preview_cup_done_time', 'Prüfung abgeschlossen · Ergebnis liegt vor'));
                renderResult(payload);
                lastRedirectUrl = payload && payload.redirect_url ? String(payload.redirect_url) : (form.dataset.successRedirect || window.location.href);
                if (redirectButton) redirectButton.hidden = false;
                if (closeButton) closeButton.hidden = false;
            }

            function showUploadError(message) {
                activeXhr = null;
                clearCheckTimer();
                setBusy(false);
                openUploadModal();
                modal.classList.add('is-error');
                modal.classList.remove('is-done', 'is-review');
                if (title) title.textContent = t('preview_cup_error_title', 'Einreichung fehlgeschlagen');
                if (text) text.textContent = message || t('preview_cup_error_text', 'Die Einreichung konnte nicht verarbeitet werden.');
                setStep('upload');
                setProgress(0);
                setTimeLabel(t('preview_cup_error_time', 'Keine Einreichung gespeichert'));
                if (resultBox) {
                    resultBox.hidden = false;
                    resultBox.innerHTML = '<div class="hnt-cup-upload-result-head"><span>' + escapeHtml(t('preview_error_title', 'Fehler')) + '</span><strong>' + escapeHtml(t('preview_cup_try_again', 'Bitte erneut versuchen')) + '</strong></div>';
                }
                if (closeButton) closeButton.hidden = false;
                if (redirectButton) redirectButton.hidden = true;
            }

            function parseJsonResponse(xhr) {
                try {
                    return JSON.parse(xhr.responseText || '{}');
                } catch (error) {
                    return null;
                }
            }

            function extractErrorMessage(payload, fallback) {
                if (payload && payload.message) return String(payload.message);
                if (payload && payload.errors && typeof payload.errors === 'object') {
                    const firstKey = Object.keys(payload.errors)[0];
                    const firstValue = firstKey ? payload.errors[firstKey] : null;
                    if (Array.isArray(firstValue) && firstValue.length) return String(firstValue[0]);
                    if (firstValue) return String(firstValue);
                }
                return fallback;
            }

            if (closeButton) {
                closeButton.addEventListener('click', function () {
                    closeUploadModal();
                });
            }

            if (redirectButton) {
                redirectButton.addEventListener('click', function () {
                    window.location.href = lastRedirectUrl || form.dataset.successRedirect || window.location.href;
                });
            }

            form.addEventListener('submit', function (event) {
                if (typeof form.checkValidity === 'function' && !form.checkValidity()) return;
                if (!fileInput || !fileInput.files || !fileInput.files.length) return;

                event.preventDefault();

                const formData = new FormData(form);
                const xhr = new XMLHttpRequest();
                activeXhr = xhr;

                resetUploadUi();
                setBusy(true);

                xhr.open(String(form.method || 'POST').toUpperCase(), form.action);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                if (csrfToken) xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);

                xhr.upload.addEventListener('progress', function (progressEvent) {
                    if (!progressEvent.lengthComputable) return;
                    const uploadPercent = Math.round((progressEvent.loaded / Math.max(1, progressEvent.total)) * 100);
                    setProgress(Math.min(55, Math.round(uploadPercent * 0.55)));
                    if (uploadPercent >= 100 && !checkTimer) showUploadChecking();
                });

                xhr.addEventListener('load', function () {
                    const payload = parseJsonResponse(xhr);
                    if (xhr.status >= 200 && xhr.status < 300 && payload && payload.ok) {
                        if (payload.estimated_total_seconds) form.dataset.analysisEstimate = String(payload.estimated_total_seconds);
                        showUploadDone(payload);
                        return;
                    }

                    showUploadError(extractErrorMessage(payload, 'Die Einreichung konnte nicht verarbeitet werden.'));
                });

                xhr.addEventListener('error', function () {
                    showUploadError('Netzwerkfehler beim Upload. Bitte versuche es erneut.');
                });

                xhr.addEventListener('abort', function () {
                    showUploadError('Upload abgebrochen.');
                });

                xhr.send(formData);
            });
        });
    }

    initComposerEnhancements();
    initHashtagDetection(document);
    initLfgCreateModal();
    initCupSubmissionUploads();
    initReadMore(document);
    initMediaCarousels(document);
    initVideoPlayers(document);
    initCommentMediaInputs(document);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            toggleProfileMenu(true);
            closeNavDropdowns();
            closePostOptionMenus();
            closeComposerModal();
            lfgWizardModals.forEach(function (modal) {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            });
            syncModalBodyState();
            closePostModal();
            closeReportModal();
            closeLightbox();
        }
        if (lightboxModal && lightboxModal.classList.contains('open') && event.key === 'ArrowLeft') {
            moveLightbox(-1);
        }
        if (lightboxModal && lightboxModal.classList.contains('open') && event.key === 'ArrowRight') {
            moveLightbox(1);
        }
    });
});
