(() => {
    const filterForm = document.querySelector('[data-guide-filter-form]');

    if (filterForm) {
        const applyFilters = () => {
            const params = new URLSearchParams();

            new FormData(filterForm).forEach((value, key) => {
                const normalized = String(value).trim();

                if (!normalized || (key === 'sort' && normalized === 'new')) {
                    return;
                }

                params.set(key, normalized);
            });

            const query = params.toString();
            window.location.assign(filterForm.action + (query ? `?${query}` : ''));
        };

        filterForm.addEventListener('submit', (event) => {
            event.preventDefault();
            applyFilters();
        });

        filterForm.querySelectorAll('select').forEach((select) => {
            select.addEventListener('change', applyFilters);
        });
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const showToast = (message, isError = false) => {
        let toast = document.querySelector('.guide-action-toast');

        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'guide-action-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            document.body.appendChild(toast);
        }

        toast.textContent = message;
        toast.classList.toggle('is-error', isError);
        toast.classList.add('is-visible');

        window.clearTimeout(showToast.timeout);
        showToast.timeout = window.setTimeout(() => {
            toast.classList.remove('is-visible');
        }, 2600);
    };

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-guide-card-bookmark]');

        if (!button || button.disabled) {
            return;
        }

        const url = button.dataset.url;

        if (!url || !csrfToken) {
            showToast('Der Guide konnte gerade nicht gespeichert werden.', true);
            return;
        }

        button.disabled = true;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('bookmark_failed');
            }

            const payload = await response.json();
            const saved = Boolean(payload.saved);
            const icon = button.querySelector('i');

            button.classList.toggle('saved', saved);
            button.setAttribute('aria-pressed', saved ? 'true' : 'false');
            button.setAttribute(
                'aria-label',
                saved ? 'Guide aus gespeicherten Guides entfernen' : 'Guide speichern'
            );

            if (icon) {
                icon.classList.toggle('ph-bookmark-simple', !saved);
                icon.classList.toggle('ph-bookmark-simple-fill', saved);
            }

            showToast(payload.message || (saved ? 'Guide gespeichert.' : 'Guide entfernt.'));
        } catch (error) {
            showToast('Der Guide konnte gerade nicht gespeichert werden.', true);
        } finally {
            button.disabled = false;
        }
    });
})();
