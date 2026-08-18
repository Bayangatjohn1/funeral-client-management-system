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

    const positionDropdown = (menu) => {
        const trigger = menu.querySelector(triggerSelector);
        const dropdown = menu.querySelector('.row-action-dropdown');
        if (!trigger || !dropdown) return;
        const rect = trigger.getBoundingClientRect();
        dropdown.style.position = 'fixed';
        dropdown.style.top = (rect.bottom + 6) + 'px';
        dropdown.style.right = (window.innerWidth - rect.right) + 'px';
        dropdown.style.left = 'auto';
    };

    const clearDropdownPosition = (menu) => {
        const dropdown = menu.querySelector('.row-action-dropdown');
        if (!dropdown) return;
        dropdown.style.position = '';
        dropdown.style.top = '';
        dropdown.style.right = '';
        dropdown.style.left = '';
    };

    const closeMenu = (menu) => {
        if (!menu) return;
        menu.classList.remove('is-open');
        clearDropdownPosition(menu);
        const trigger = menu.querySelector(triggerSelector);
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
        }
    };

    const closeAllMenus = (except = null) => {
        document.querySelectorAll(menuSelector).forEach((menu) => {
            if (except && menu === except) return;
            closeMenu(menu);
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
                closeMenu(menu);
            } else {
                menu.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
                positionDropdown(menu);
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

    document.addEventListener('panel-ui:reset', () => {
        closeAllMenus();
    });
}

function initTableToolbarBehavior() {
    const forms = document.querySelectorAll('form[data-table-toolbar]');
    if (!forms.length) return;

    forms.forEach((form) => {
        const debounceMs = Number(form.dataset.searchDebounce || 400);
        const usesLocalSearch = form.hasAttribute('data-live-search-suggestions');
        const searchInputs = usesLocalSearch ? [] : form.querySelectorAll('[data-table-search]');
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

function initLiveSearchSuggestions() {
    document.querySelectorAll('form[data-live-search-suggestions]').forEach((form) => {
        if (form.dataset.liveSearchReady === '1') return;
        form.dataset.liveSearchReady = '1';

        const input = form.querySelector('[data-live-search-input]');
        const clear = form.querySelector('[data-live-search-clear]');
        const panel = form.querySelector('[data-live-search-results]');
        const page = form.closest('.admin-table-page') || document;
        if (!(input instanceof HTMLInputElement) || !panel) return;

        const commitOnly = form.hasAttribute('data-live-search-commit-only');
        const rows = () => Array.from(page.querySelectorAll('[data-live-search-row]'));
        const normalize = (value) => String(value || '').toLowerCase().trim();

        const applyLocalFilter = () => {
            const query = normalize(input.value);
            rows().forEach((row) => {
                const isMatch = !query || normalize(row.dataset.liveSearchText || row.textContent).includes(query);
                row.classList.toggle('is-search-hidden', !isMatch);
            });
        };

        const setPanel = (items, query) => {
            panel.innerHTML = '';
            if (!query) {
                panel.hidden = true;
                return;
            }

            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'live-search-empty';
                empty.textContent = 'No quick matches found. Continue typing to search all records.';
                panel.appendChild(empty);
                panel.hidden = false;
                return;
            }

            const seen = new Set();
            const uniqueItems = items.filter((row) => {
                const key = `${row.dataset.liveSearchTitle || row.textContent.trim()}|${row.dataset.liveSearchMeta || ''}`;
                if (seen.has(key)) return false;
                seen.add(key);
                return true;
            });

            uniqueItems.slice(0, 5).forEach((row) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'live-search-option';
                button.innerHTML = `
                    <span class="live-search-option__title"></span>
                    <span class="live-search-option__meta"></span>
                `;
                button.querySelector('.live-search-option__title').textContent = row.dataset.liveSearchTitle || row.textContent.trim();
                button.querySelector('.live-search-option__meta').textContent = row.dataset.liveSearchMeta || 'Open matching record';
                button.addEventListener('click', () => {
                    input.value = row.dataset.liveSearchTitle || input.value;
                    panel.hidden = true;
                    form.dataset.liveSearchSelected = '1';
                    applyLocalFilter();
                });
                panel.appendChild(button);
            });
            panel.hidden = false;
        };

        const render = (showPanel = true) => {
            const query = normalize(input.value);
            if (clear) clear.hidden = query.length === 0;
            if (!showPanel || form.dataset.liveSearchSelected === '1') {
                panel.hidden = true;
                return;
            }
            const matches = rows().filter((row) => normalize(row.dataset.liveSearchText || row.textContent).includes(query));
            setPanel(matches, query);
        };

        clear?.addEventListener('click', () => {
            input.value = '';
            form.dataset.liveSearchSelected = '0';
            panel.hidden = true;
            applyLocalFilter();
            render(false);
        });

        input.addEventListener('input', () => {
            form.dataset.liveSearchSelected = '0';
            if (!commitOnly) {
                applyLocalFilter();
            }
            render();
        });
        input.addEventListener('focus', () => render());
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                panel.hidden = true;
                applyLocalFilter();
                return;
            }
            if (event.key === 'Escape') {
                panel.hidden = true;
            }
        });

        document.addEventListener('pointerdown', (event) => {
            if (!(event.target instanceof Element)) return;
            if (form.contains(event.target)) return;
            panel.hidden = true;
        });

        applyLocalFilter();
        render(false);
    });
}

function initCaseCompactFilters() {
    document.querySelectorAll('[data-case-filter]').forEach((form) => {
        const customToggle = form.querySelector('[data-case-custom-toggle]');
        const customPanel = form.querySelector('[data-case-custom-panel]');
        const moreToggle = form.querySelector('[data-case-more-toggle]');
        const morePanel = form.querySelector('[data-case-more-panel]');
        const moreIcon = form.querySelector('[data-case-more-icon]');
        const moreText = form.querySelector('[data-case-more-text]');
        const hasAdvancedFilters = moreToggle?.classList.contains('active') || false;

        const setCustomOpen = (open) => {
            if (!customToggle || !customPanel) return;
            customPanel.hidden = !open;
            customToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        const setMoreOpen = (open) => {
            if (!moreToggle || !morePanel) return;
            morePanel.hidden = !open;
            moreToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            moreToggle.classList.toggle('active', open || hasAdvancedFilters);
            if (moreText) {
                moreText.textContent = open ? 'Hide Filters' : 'More Filters';
            }
            if (moreIcon) {
                moreIcon.classList.toggle('bi-chevron-down', !open);
                moreIcon.classList.toggle('bi-chevron-up', open);
            }
        };

        customToggle?.addEventListener('click', () => {
            setCustomOpen(customToggle.getAttribute('aria-expanded') !== 'true');
        });

        moreToggle?.addEventListener('click', () => {
            setMoreOpen(moreToggle.getAttribute('aria-expanded') !== 'true');
        });

        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) return;
            if (customPanel && customToggle && !customPanel.contains(event.target) && !customToggle.contains(event.target)) {
                setCustomOpen(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setCustomOpen(false);
            }
        });
    });
}

function initClickableRecordRows() {
    const interactiveSelector = [
        'a',
        'button',
        'input',
        'select',
        'textarea',
        'label',
        'summary',
        '[role="button"]',
        '[data-row-menu]',
        '[data-row-menu-item]',
        '[data-no-row-click]',
    ].join(',');

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) return;
        if (event.target.closest(interactiveSelector)) return;

        const row = event.target.closest('[data-clickable-row]');
        if (!row) return;

        const trigger = row.querySelector('[data-row-view-trigger]');
        if (trigger instanceof HTMLElement) {
            trigger.click();
            return;
        }

        const href = row.getAttribute('data-row-href');
        if (href) {
            window.location.href = href;
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        if (!(event.target instanceof HTMLElement)) return;

        const row = event.target.closest('[data-clickable-row]');
        if (!row || event.target !== row) return;

        const trigger = row.querySelector('[data-row-view-trigger]');
        if (trigger instanceof HTMLElement) {
            trigger.click();
            return;
        }

        const href = row.getAttribute('data-row-href');
        if (href) {
            window.location.href = href;
        }
    });
}

function initCaseRecordTabTransitions() {
    window.addEventListener('popstate', () => {
        if (document.querySelector('.records-page .case-records-tabs')) {
            window.location.reload();
        }
    });

    document.addEventListener('click', async (event) => {
        const link = event.target.closest('.case-records-tabs a[href]');
        if (!link) return;
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const currentCard = link.closest('.table-system-card');
        const currentPage = link.closest('.records-page');
        if (!currentCard || !currentPage) return;
        const currentTableSection = currentCard.querySelector('.table-system-list');
        if (!currentTableSection) return;

        const targetUrl = new URL(link.href, window.location.href);
        if (targetUrl.origin !== window.location.origin || targetUrl.href === window.location.href) return;

        event.preventDefault();

        currentTableSection.classList.add('case-records-tab-loading');

        try {
            const response = await fetch(targetUrl.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error(`Tab request failed: ${response.status}`);

            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(html, 'text/html');
            const nextCard = nextDocument.querySelector('.records-page .table-system-card');
            if (!nextCard) throw new Error('Case records card not found in response.');

            await new Promise((resolve) => window.setTimeout(resolve, 140));
            currentCard.replaceWith(nextCard);
            window.history.pushState({}, '', targetUrl.href);
            initTableToolbarBehavior();
            initLiveSearchSuggestions();
            initCaseCompactFilters();

            const nextTableSection = nextCard.querySelector('.table-system-list');
            nextTableSection?.classList.add('case-records-tab-enter');
            requestAnimationFrame(() => {
                nextTableSection?.classList.remove('case-records-tab-enter');
            });
        } catch (error) {
            window.location.href = targetUrl.href;
        }
    });
}

initTheme();
initRowActionMenus();
initTableToolbarBehavior();
initLiveSearchSuggestions();
initCaseCompactFilters();
initClickableRecordRows();
initCaseRecordTabTransitions();

window.Alpine = Alpine;

Alpine.start();
