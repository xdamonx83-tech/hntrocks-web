(function () {
    'use strict';

    function closeSidebar() {
        document.body.classList.remove('is-admin-sidebar-open');
    }

    function openSidebar() {
        document.body.classList.add('is-admin-sidebar-open');
    }

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-admin-sidebar-toggle]')) {
            document.body.classList.contains('is-admin-sidebar-open') ? closeSidebar() : openSidebar();
            return;
        }

        if (event.target.closest('[data-admin-sidebar-close]')) {
            closeSidebar();
            return;
        }

        if (window.innerWidth < 1024 && event.target.closest('.hnt-admin-nav a')) {
            closeSidebar();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }

        if ((event.ctrlKey || event.metaKey) && event.key === '/') {
            var search = document.querySelector('.hnt-admin-sidebar-search input');
            if (search) {
                event.preventDefault();
                if (window.innerWidth < 1024) {
                    openSidebar();
                }
                window.setTimeout(function () {
                    search.focus();
                    search.select();
                }, 40);
            }
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1024) {
            closeSidebar();
        }
    });
})();