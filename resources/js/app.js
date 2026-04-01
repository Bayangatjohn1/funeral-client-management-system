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

initTheme();

window.Alpine = Alpine;

Alpine.start();
