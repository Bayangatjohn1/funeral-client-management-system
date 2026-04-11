import './bootstrap';

import Alpine from 'alpinejs';

const THEME_KEY = 'app-theme';

function getStoredTheme() {
    const stored = window.localStorage.getItem(THEME_KEY);
    return stored === 'dark' || stored === 'light' ? stored : null;
}

function resolveTheme() {
    return getStoredTheme() ?? 'light';
}

function updateThemeButtons(theme) {
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        const label = button.querySelector('[data-theme-label]');
        const nextTheme = theme === 'dark' ? 'light' : 'dark';

        button.dataset.themeState = theme;
        button.setAttribute('aria-pressed', String(theme === 'dark'));
        button.setAttribute('title', `Switch to ${nextTheme} mode`);

        if (label) {
            label.textContent = theme === 'dark' ? 'Dark' : 'Light';
        }
    });
}

function setTheme(theme, persist) {
    document.documentElement.setAttribute('data-theme', theme);

    if (persist) {
        window.localStorage.setItem(THEME_KEY, theme);
    }

    updateThemeButtons(theme);
    document.dispatchEvent(new CustomEvent('themechange', { detail: { theme } }));
}

function animateThemeToggle() {
    document.documentElement.classList.add('theme-changing');
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.classList.add('is-switching');
    });

    window.setTimeout(() => {
        document.documentElement.classList.remove('theme-changing');
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.classList.remove('is-switching');
        });
    }, 360);
}

function applyTheme(theme, persist = true) {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    if (currentTheme === theme) {
        updateThemeButtons(theme);
        return;
    }

    const commit = () => {
        setTheme(theme, persist);
        animateThemeToggle();
    };

    if (typeof document.startViewTransition === 'function') {
        document.startViewTransition(commit);
        return;
    }

    commit();
}

function initTheme() {
    setTheme(resolveTheme(), false);

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-theme-toggle]');
        if (!toggle) return;

        const currentTheme = document.documentElement.getAttribute('data-theme') || resolveTheme();
        const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
        applyTheme(nextTheme);
    });

}

function initRowActionMenus() {
    const menuSelector = '[data-row-menu]';
    const triggerSelector = '[data-row-menu-trigger]';
    const itemSelector = '[data-row-menu-item]';

    const closeAllMenus = (except = null) => {
        document.querySelectorAll(menuSelector).forEach((menu) => {
            if (except && menu === except) return;
            menu.classList.remove('is-open');
            const trigger = menu.querySelector(triggerSelector);
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
        });
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest(triggerSelector);
        if (trigger) {
            const menu = trigger.closest(menuSelector);
            if (!menu) return;

            const isOpen = menu.classList.contains('is-open');
            closeAllMenus(menu);

            if (isOpen) {
                menu.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
            } else {
                menu.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
            }
            return;
        }

        const item = event.target.closest(itemSelector);
        if (item) {
            closeAllMenus();
            return;
        }

        if (!event.target.closest(menuSelector)) {
            closeAllMenus();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllMenus();
        }
    });
}

function initTableToolbarBehavior() {
    const forms = document.querySelectorAll('form[data-table-toolbar]');
    if (!forms.length) return;

    forms.forEach((form) => {
        const debounceMs = Number(form.dataset.searchDebounce || 400);
        const searchInputs = form.querySelectorAll('[data-table-search]');
        const sortInputs = form.querySelectorAll('[data-table-sort]');
        const autoSubmitInputs = form.querySelectorAll('[data-table-auto-submit]');
        let debounceTimer = null;
        let navigationIntentAt = 0;

        const clearDebounce = () => {
            if (debounceTimer) {
                window.clearTimeout(debounceTimer);
                debounceTimer = null;
            }
        };

        const submitForm = () => {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        };

        const markNavigationIntent = () => {
            navigationIntentAt = Date.now();
            clearDebounce();
        };

        const hasRecentNavigationIntent = () => {
            if (!navigationIntentAt) return false;
            return Date.now() - navigationIntentAt < debounceMs + 150;
        };

        document.addEventListener('pointerdown', (event) => {
            if (!(event.target instanceof Element)) return;
            if (form.contains(event.target)) return;
            markNavigationIntent();
        }, true);

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            if (!(event.target instanceof Element)) return;
            if (form.contains(event.target)) return;
            markNavigationIntent();
        }, true);

        window.addEventListener('pagehide', markNavigationIntent);

        searchInputs.forEach((input) => {
            input.addEventListener('input', () => {
                clearDebounce();
                navigationIntentAt = 0;

                debounceTimer = window.setTimeout(() => {
                    // Prevent accidental submits after navigation intent (e.g. sidebar click).
                    if (hasRecentNavigationIntent()) return;
                    if (!document.contains(input)) return;
                    if (document.activeElement !== input) return;
                    submitForm();
                }, debounceMs);
            });

            input.addEventListener('blur', clearDebounce);
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    clearDebounce();
                }
            });
        });

        sortInputs.forEach((select) => {
            select.addEventListener('change', () => {
                navigationIntentAt = 0;
                clearDebounce();
                submitForm();
            });
        });

        autoSubmitInputs.forEach((input) => {
            input.addEventListener('change', () => {
                navigationIntentAt = 0;
                clearDebounce();
                submitForm();
            });
        });

        form.addEventListener('submit', markNavigationIntent);
    });
}

initTheme();
initRowActionMenus();
initTableToolbarBehavior();

window.Alpine = Alpine;

Alpine.start();
