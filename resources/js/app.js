import './bootstrap';

const initializeSiteHeader = () => {
    document.querySelectorAll('[data-site-header]').forEach((header) => {
        if (header.dataset.siteHeaderBound === 'true') {
            return;
        }

        const toggle = header.querySelector('[data-site-header-toggle]');
        const drawer = header.querySelector('[data-site-header-drawer]');

        if (!toggle || !drawer) {
            return;
        }

        const closeDrawer = () => {
            drawer.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        };

        const openDrawer = () => {
            drawer.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
        };

        toggle.addEventListener('click', () => {
            if (drawer.hidden) {
                openDrawer();
                return;
            }

            closeDrawer();
        });

        document.addEventListener('click', (event) => {
            if (!header.contains(event.target)) {
                closeDrawer();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeDrawer();
            }
        });

        drawer.querySelectorAll('a, button').forEach((item) => {
            item.addEventListener('click', () => {
                closeDrawer();
            });
        });

        closeDrawer();
        header.dataset.siteHeaderBound = 'true';
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSiteHeader, { once: true });
} else {
    initializeSiteHeader();
}
