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
    initFilterSelectAffordances();

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

function initFilterSelectAffordances() {
    if (document.body.dataset.filterSelectDismissReady !== '1') {
        document.body.dataset.filterSelectDismissReady = '1';
        document.addEventListener('pointerdown', (event) => {
            if (!(event.target instanceof Element)) return;
            document.querySelectorAll('[data-filter-select-wrap].is-open').forEach((wrap) => {
                if (!wrap.contains(event.target)) {
                    wrap.classList.remove('is-open');
                    wrap.querySelector('select')?.setAttribute('aria-expanded', 'false');
                    wrap.querySelector('.filter-select-menu')?.setAttribute('hidden', '');
                }
            });
        }, true);

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            document.querySelectorAll('[data-filter-select-wrap].is-open').forEach((wrap) => {
                wrap.classList.remove('is-open');
                wrap.querySelector('select')?.setAttribute('aria-expanded', 'false');
                wrap.querySelector('.filter-select-menu')?.setAttribute('hidden', '');
            });
        });
    }

    const selector = [
        '.table-system-toolbar select.table-toolbar-select',
        '.table-system-toolbar select.table-toolbar-sort',
        'form[data-table-toolbar] select.table-toolbar-select',
        'form[data-table-toolbar] select.table-toolbar-sort',
        '.sales-filter select.filter-select',
        '.audit-filter-control.is-select select.audit-select',
        '.admin-filter-control select.input-custom',
        '.ba-branch-select-wrap select.ba-branch-select',
        '.ba-period-select-wrap select.ba-period-select',
        '.eb-branch-select-wrap select.eb-branch-select',
        '.eb-period-select-wrap select.eb-period-select',
        '.reports-module-control-wrap select.reports-module-control',
        '.reports-analytics-branch select.reports-analytics-select',
        '.owner-sales-field-control select.owner-sales-control',
        '.topbar-filter-bar select.filter-select',
        '.payments-filter-control.has-dropdown select',
        '.case-compact-date-filter select',
        '.case-compact-sort-filter select',
        '.case-compact-branch select',
        '.pm-field.has-icon select.pm-control',
    ].join(',');

    document.querySelectorAll(selector).forEach((select) => {
        if (!(select instanceof HTMLSelectElement) || select.dataset.filterSelectReady === '1') return;
        select.dataset.filterSelectReady = '1';

        let wrap = select.closest('.table-toolbar-select-wrap, .audit-filter-control.is-select, .admin-filter-control, .ba-period-select-wrap, .ba-branch-select-wrap, .eb-period-select-wrap, .eb-branch-select-wrap, .reports-module-control-wrap, .reports-analytics-branch, .payments-filter-control.has-dropdown, .case-compact-date-filter, .case-compact-sort-filter, .case-compact-branch, .pm-field.has-icon, .global-filter-select-wrap');

        if (!wrap && select.parentElement) {
            const isTableToolbarSelect = select.classList.contains('table-toolbar-select') || select.classList.contains('table-toolbar-sort');
            wrap = document.createElement('span');
            wrap.className = isTableToolbarSelect ? 'table-toolbar-select-wrap' : 'global-filter-select-wrap';
            select.parentElement.insertBefore(wrap, select);
            wrap.appendChild(select);
        }

        if (!wrap) return;
        wrap.setAttribute('data-filter-select-wrap', '');

        let icon = wrap.querySelector('.table-toolbar-select-icon, .payments-filter-dropdown-icon, .admin-filter-chevron, .reports-module-chevron, .ba-branch-select-chev, .eb-branch-select-chev, .case-compact-date-chev, .case-compact-sort-chev, .case-compact-select-chev, .pm-sel-chev, [data-filter-select-icon]');
        if (!icon && (wrap.classList.contains('table-toolbar-select-wrap') || wrap.classList.contains('global-filter-select-wrap'))) {
            icon = document.createElement('i');
            icon.className = 'bi bi-chevron-down table-toolbar-select-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.setAttribute('data-filter-select-icon', '');
            wrap.appendChild(icon);
        }

        const menu = document.createElement('div');
        menu.className = 'filter-select-menu';
        menu.setAttribute('role', 'listbox');
        menu.hidden = true;
        wrap.appendChild(menu);

        const optionLabel = (option) => option.textContent?.trim() || option.value || 'Option';
        const syncMenu = () => {
            menu.innerHTML = '';

            Array.from(select.options).forEach((option) => {
                if (option.disabled) return;

                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'filter-select-menu__item';
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', option.selected ? 'true' : 'false');
                item.dataset.value = option.value;
                item.textContent = optionLabel(option);

                if (option.selected) item.classList.add('is-selected');

                item.addEventListener('click', () => {
                    select.value = option.value;
                    select.dispatchEvent(new Event('input', { bubbles: true }));
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    setOpen(false);
                });

                menu.appendChild(item);
            });
        };

        const setOpen = (isOpen) => {
            if (isOpen) {
                document.querySelectorAll('[data-filter-select-wrap].is-open').forEach((openWrap) => {
                    if (openWrap !== wrap) {
                        openWrap.classList.remove('is-open');
                        openWrap.querySelector('select')?.setAttribute('aria-expanded', 'false');
                        openWrap.querySelector('.filter-select-menu')?.setAttribute('hidden', '');
                    }
                });
                syncMenu();
            }

            wrap.classList.toggle('is-open', isOpen);
            select.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            menu.hidden = !isOpen;
        };

        select.setAttribute('aria-haspopup', 'listbox');
        select.setAttribute('aria-expanded', 'false');

        select.addEventListener('pointerdown', (event) => {
            if (select.disabled) return;
            event.preventDefault();
            select.focus({ preventScroll: true });
            setOpen(!wrap.classList.contains('is-open'));
        });
        select.addEventListener('keydown', (event) => {
            if (['Enter', ' ', 'ArrowDown', 'ArrowUp'].includes(event.key)) {
                event.preventDefault();
                setOpen(true);
            }
            if (event.key === 'Escape') setOpen(false);
        });
        select.addEventListener('change', () => setOpen(false));
        select.addEventListener('blur', () => window.setTimeout(() => {
            if (!wrap.matches(':focus-within')) setOpen(false);
        }, 120));
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
    if (document.body.dataset.caseFilterDismissReady !== '1') {
        document.body.dataset.caseFilterDismissReady = '1';
        document.addEventListener('click', (event) => {
            const target = event.target instanceof Element ? event.target.closest('[data-case-more-dismiss]') : null;
            if (!target) return;

            const form = target.closest('[data-case-filter]');
            const panel = form?.querySelector('[data-case-more-panel]');
            const toggle = form?.querySelector('[data-case-more-toggle]');
            const icon = form?.querySelector('[data-case-more-icon]');
            const text = form?.querySelector('[data-case-more-text]');
            if (!form || !panel || !toggle) return;

            event.preventDefault();
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            toggle.classList.toggle('active', toggle.dataset.hasActiveFilters === '1');
            if (text) text.textContent = 'More Filters';
            if (icon) {
                icon.classList.add('bi-chevron-down');
                icon.classList.remove('bi-chevron-up');
            }
        });
    }

    document.querySelectorAll('[data-case-filter]').forEach((form) => {
        if (form.dataset.caseFilterReady === '1') return;
        form.dataset.caseFilterReady = '1';

        const customToggle = form.querySelector('[data-case-custom-toggle]');
        const customPanel = form.querySelector('[data-case-custom-panel]');
        const datePreset = form.querySelector('[data-case-date-preset-select]');
        const moreToggle = form.querySelector('[data-case-more-toggle]');
        const morePanel = form.querySelector('[data-case-more-panel]');
        const moreIcon = form.querySelector('[data-case-more-icon]');
        const moreText = form.querySelector('[data-case-more-text]');
        const searchInput = form.querySelector('[data-case-search-input]');
        const searchClear = form.querySelector('[data-case-search-clear]');
        const searchPanel = form.querySelector('[data-case-search-suggestions]');
        const hasAdvancedFilters = moreToggle?.classList.contains('active') || false;
        if (moreToggle) {
            moreToggle.dataset.hasActiveFilters = hasAdvancedFilters ? '1' : '0';
        }
        const page = form.closest('.records-page, .master-records-page, .owner-page-shell, .admin-table-page') || document;
        const isStaffRecordsPage = !!form.closest('.records-page');

        const rowSelector = [
            '.table-system-table tbody tr[data-clickable-row]',
            '.table-system-table tbody tr',
            '.records-worklist-table tbody tr',
        ].join(',');

        const normalize = (value) => String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();

        const getRows = () => Array.from(page.querySelectorAll(rowSelector)).filter((row) => {
            const cells = row.querySelectorAll('td');
            return cells.length > 1 && !row.querySelector('[colspan]');
        });

        const getRowTitle = (row) => {
            const firstPrimary = row.querySelector('.table-primary');
            const code = firstPrimary?.textContent?.trim() || row.cells?.[0]?.textContent?.trim() || '';
            const person = row.cells?.[2]?.querySelector('.table-primary')?.textContent?.trim()
                || row.cells?.[1]?.querySelector('.table-primary')?.textContent?.trim()
                || '';
            return [code, person].filter(Boolean).join(' - ') || row.textContent.trim();
        };

        const getRowMeta = (row) => {
            const metaParts = [];
            const branch = row.cells?.[1]?.textContent?.trim();
            const service = row.cells?.[3]?.querySelector('.table-primary')?.textContent?.trim()
                || row.cells?.[2]?.querySelector('.table-secondary')?.textContent?.trim();
            if (branch) metaParts.push(branch.replace(/\s+/g, ' '));
            if (service) metaParts.push(service);
            return metaParts.join(' • ');
        };

        const setUpdating = (isUpdating) => {
            if (!(page instanceof Element)) return;
            page.classList.toggle('is-updating', isUpdating);
        };

        const replaceSelector = (doc, selector) => {
            const current = page.querySelector(selector);
            const nextPage = doc.querySelector('.records-page, .master-records-page, .owner-page-shell, .admin-table-page') || doc;
            const next = nextPage.querySelector(selector);
            if (current && next) {
                current.replaceWith(next);
                return true;
            }
            return false;
        };

        const replacePageSections = (doc) => {
            if (isStaffRecordsPage) return;

            if (page.classList?.contains('master-records-page')) {
                replaceSelector(doc, '.table-system-card');
                return;
            }

            if (page.classList?.contains('owner-page-shell')) {
                [
                    '.owner-history-filter-panel',
                    '.owner-history-chip-row',
                    '.list-card',
                    '.owner-page-shell > .mt-4',
                ].forEach((selector) => replaceSelector(doc, selector));
            }
        };

        const buildUrl = (submitter = null) => {
            const url = new URL(form.action, window.location.origin);
            const data = submitter ? new FormData(form, submitter) : new FormData(form);
            const selectedPreset = String(data.get('date_preset') || '');

            Array.from(url.searchParams.keys()).forEach((key) => url.searchParams.delete(key));
            data.forEach((value, key) => {
                if (key === 'page') return;
                if (selectedPreset !== 'CUSTOM' && (key === 'date_from' || key === 'date_to')) return;
                if (value !== null && String(value).trim() !== '') {
                    url.searchParams.append(key, value);
                }
            });
            return url;
        };

        const submitFilter = async (submitter = null) => {
            if (isStaffRecordsPage) {
                if (typeof form.requestSubmit === 'function' && !submitter) {
                    form.requestSubmit();
                }
                return;
            }

            const url = buildUrl(submitter);
            setUpdating(true);
            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                    },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error(`Filter request failed: ${response.status}`);
                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                replacePageSections(doc);
                window.history.pushState({}, '', url.toString());
                document.dispatchEvent(new CustomEvent('panel-ui:reset'));
                initCaseCompactFilters();
                initClickableRecordRows();
            } catch (error) {
                window.location.href = url.toString();
            } finally {
                requestAnimationFrame(() => setUpdating(false));
            }
        };

        const setCustomOpen = (open) => {
            if (!customPanel) return;
            customPanel.hidden = !open;
            customToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
            datePreset?.setAttribute('aria-expanded', open ? 'true' : 'false');
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

        datePreset?.addEventListener('change', () => {
            if (isStaffRecordsPage) return;
            if (datePreset.value === 'CUSTOM') {
                setCustomOpen(true);
                return;
            }
            setCustomOpen(false);
            submitFilter();
        });

        form.querySelectorAll('[data-case-auto-submit]').forEach((field) => {
            field.addEventListener('change', () => {
                if (isStaffRecordsPage) return;
                submitFilter();
            });
        });

        form.addEventListener('submit', (event) => {
            if (isStaffRecordsPage) return;
            event.preventDefault();
            searchPanel && (searchPanel.hidden = true);
            submitFilter(event.submitter || null);
        });

        form.querySelectorAll('[data-case-clear-filters], .case-compact-pop-reset, .case-compact-advanced-clear').forEach((link) => {
            link.addEventListener('click', async (event) => {
                if (!(link instanceof HTMLAnchorElement)) return;
                if (isStaffRecordsPage) return;
                event.preventDefault();
                setUpdating(true);
                try {
                    const response = await fetch(link.href, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) throw new Error(`Reset request failed: ${response.status}`);
                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    replacePageSections(doc);
                    window.history.pushState({}, '', link.href);
                    document.dispatchEvent(new CustomEvent('panel-ui:reset'));
                    initCaseCompactFilters();
                    initClickableRecordRows();
                } catch (error) {
                    window.location.href = link.href;
                } finally {
                    requestAnimationFrame(() => setUpdating(false));
                }
            });
        });

        const hideSuggestions = () => {
            if (searchPanel) searchPanel.hidden = true;
        };

        const renderSuggestions = () => {
            if (!(searchInput instanceof HTMLInputElement) || !searchPanel) return;
            const query = normalize(searchInput.value);
            if (searchClear) searchClear.hidden = query.length === 0;

            searchPanel.innerHTML = '';
            if (!query) {
                searchPanel.hidden = true;
                return;
            }

            const seen = new Set();
            const matches = getRows().filter((row) => normalize(row.textContent).includes(query)).filter((row) => {
                const title = getRowTitle(row);
                if (seen.has(title)) return false;
                seen.add(title);
                return true;
            }).slice(0, 5);

            if (!matches.length) {
                const empty = document.createElement('div');
                empty.className = 'case-compact-search-empty';
                empty.textContent = 'No quick matches. Press Enter to search all records.';
                searchPanel.appendChild(empty);
                searchPanel.hidden = false;
                return;
            }

            matches.forEach((row) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'case-compact-search-option';
                button.innerHTML = `
                    <span class="case-compact-search-title"></span>
                    <span class="case-compact-search-meta"></span>
                `;
                button.querySelector('.case-compact-search-title').textContent = getRowTitle(row);
                button.querySelector('.case-compact-search-meta').textContent = getRowMeta(row) || 'Click to apply search';
                button.addEventListener('click', () => {
                    searchInput.value = getRowTitle(row).split(' - ')[0] || searchInput.value;
                    hideSuggestions();
                    submitFilter();
                });
                searchPanel.appendChild(button);
            });
            searchPanel.hidden = false;
        };

        searchInput?.addEventListener('input', renderSuggestions);
        searchInput?.addEventListener('focus', renderSuggestions);
        searchInput?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                hideSuggestions();
                submitFilter();
            }
            if (event.key === 'Escape') {
                hideSuggestions();
            }
        });

        searchClear?.addEventListener('click', () => {
            if (!(searchInput instanceof HTMLInputElement)) return;
            searchInput.value = '';
            hideSuggestions();
            submitFilter();
        });

        moreToggle?.addEventListener('click', () => {
            setMoreOpen(moreToggle.getAttribute('aria-expanded') !== 'true');
        });

        form.querySelectorAll('[data-case-more-dismiss]').forEach((dismiss) => {
            dismiss.addEventListener('click', () => setMoreOpen(false));
        });

        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) return;
            if (searchPanel && searchInput && !searchPanel.contains(event.target) && !searchInput.contains(event.target)) {
                hideSuggestions();
            }
            if (
                customPanel
                && !customPanel.contains(event.target)
                && !customToggle?.contains(event.target)
                && !datePreset?.contains(event.target)
            ) {
                setCustomOpen(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setCustomOpen(false);
                hideSuggestions();
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

function initServiceManagementHub() {
    const hub = document.querySelector('[data-service-hub]');
    if (!hub || hub.dataset.serviceHubInitialized === 'true') return;

    const tabs = [...hub.querySelectorAll('[data-service-tab]')];
    const panels = [...hub.querySelectorAll('[data-service-panel]')];
    const createActions = [...hub.querySelectorAll('[data-service-create]')];
    const contextCopy = hub.querySelector('[data-service-context-copy]');
    const search = hub.querySelector('[data-service-search]');
    const status = hub.querySelector('[data-service-status]');
    const clearSearch = hub.querySelector('[data-service-clear-search]');
    const reset = hub.querySelector('[data-service-reset]');
    const empty = hub.querySelector('[data-service-filter-empty]');
    const viewButtons = [...hub.querySelectorAll('[data-catalog-view]')];
    const viewPanels = [...hub.querySelectorAll('[data-catalog-view-panel]')];
    const modal = document.querySelector('[data-service-modal]');
    const modalFrame = modal?.querySelector('[data-service-modal-frame]');
    const modalTitle = modal?.querySelector('[data-service-modal-title]');
    const modalCopy = modal?.querySelector('[data-service-modal-copy]');
    const pageContent = document.querySelector('.page-content');
    let catalogView = localStorage.getItem('service-catalog-view') || 'cards';
    let modalScrollY = 0;

    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

    const closeModal = () => {
        if (!modal) return;
        modal.hidden = true;
        document.documentElement.classList.remove('service-modal-open');
        document.body.classList.remove('service-modal-open');
        if (pageContent) pageContent.scrollTop = modalScrollY;
        if (modalFrame) modalFrame.src = 'about:blank';
    };

    const openModal = (action, event) => {
        if (!modal || !modalFrame || !action) return false;
        event?.preventDefault();

        const title = action.dataset.serviceModalTitle || 'Catalog Item';
        if (modalTitle) modalTitle.textContent = title;
        if (modalCopy) {
            modalCopy.textContent = title.toLowerCase().startsWith('add')
                ? 'Create a new catalog item without leaving Service Management.'
                : 'Review this catalog item without leaving Service Management.';
        }

        const modalUrl = new URL(action.dataset.serviceModalUrl || action.href, window.location.origin);
        modalUrl.searchParams.set('modal', '1');
        modalFrame.src = modalUrl.toString();
        modalScrollY = pageContent?.scrollTop || window.scrollY || document.documentElement.scrollTop || 0;
        modal.hidden = false;
        document.documentElement.classList.add('service-modal-open');
        document.body.classList.add('service-modal-open');

        return false;
    };

    window.openServiceCatalogModal = openModal;
    window.closeServiceCatalogModal = closeModal;
    hub.dataset.serviceHubInitialized = 'true';

    const sectionCopy = JSON.parse(hub.dataset.serviceCopy || '{}');

    const applyFilters = () => {
        const panel = hub.querySelector('.service-hub-panel.is-active');
        if (!panel) return;

        const query = (search?.value || '').trim().toLowerCase();
        const currentStatus = status?.value || '';
        let shown = 0;

        panel.querySelectorAll('[data-service-row]').forEach((row) => {
            const matchesText = !query || (row.dataset.search || '').includes(query);
            const matchesStatus = !currentStatus || row.dataset.status === currentStatus;
            const visible = matchesText && matchesStatus;
            row.hidden = !visible;
            if (visible && !row.closest('[hidden]')) shown += 1;
        });

        if (clearSearch) clearSearch.hidden = query === '';
        if (empty) empty.hidden = shown > 0 || panel.querySelector('[data-service-empty]');
    };

    const setCatalogView = (view) => {
        catalogView = view === 'table' ? 'table' : 'cards';
        localStorage.setItem('service-catalog-view', catalogView);

        viewButtons.forEach((button) => {
            const selected = button.dataset.catalogView === catalogView;
            button.classList.toggle('is-active', selected);
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });

        viewPanels.forEach((panel) => {
            panel.hidden = panel.dataset.catalogViewPanel !== catalogView;
        });

        applyFilters();
    };

    const activate = (key) => {
        tabs.forEach((tab) => {
            const selected = tab.dataset.serviceTab === key;
            tab.classList.toggle('is-active', selected);
            tab.setAttribute('aria-selected', selected ? 'true' : 'false');
        });
        panels.forEach((panel) => panel.classList.toggle('is-active', panel.dataset.servicePanel === key));
        createActions.forEach((action) => action.classList.toggle('is-active', action.dataset.serviceCreate === key));
        if (contextCopy && sectionCopy[key]) contextCopy.textContent = sectionCopy[key];

        const url = new URL(window.location.href);
        url.searchParams.set('tab', key);
        window.history.replaceState({}, '', url);

        setCatalogView(catalogView);
        applyFilters();
    };

    tabs.forEach((tab) => tab.addEventListener('click', () => activate(tab.dataset.serviceTab)));
    viewButtons.forEach((button) => button.addEventListener('click', () => setCatalogView(button.dataset.catalogView)));
    search?.addEventListener('input', applyFilters);
    status?.addEventListener('change', applyFilters);
    clearSearch?.addEventListener('click', () => {
        search.value = '';
        search.focus();
        applyFilters();
    });
    reset?.addEventListener('click', () => {
        if (search) search.value = '';
        if (status) status.value = '';
        applyFilters();
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest?.('[data-service-modal-url]');
        if (!trigger) return;
        openModal(trigger, event);
    });

    modalFrame?.addEventListener('load', () => {
        try {
            const frameUrl = new URL(modalFrame.contentWindow.location.href);
            if (frameUrl.pathname === window.location.pathname && frameUrl.searchParams.has('tab')) {
                window.location.href = frameUrl.toString();
            }
        } catch (_) {
            // Ignore inaccessible or initial iframe states.
        }
    });

    modal?.querySelectorAll('[data-service-modal-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    modal?.addEventListener('wheel', (event) => {
        if (event.target === modal || event.target?.dataset?.serviceModalClose !== undefined) {
            event.preventDefault();
        }
    }, { passive: false });

    modal?.addEventListener('touchmove', (event) => {
        if (event.target === modal || event.target?.dataset?.serviceModalClose !== undefined) {
            event.preventDefault();
        }
    }, { passive: false });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal && !modal.hidden) closeModal();
    });

    setCatalogView(catalogView);
    activate(hub.dataset.activeTab || 'packages');
}

initTheme();
initRowActionMenus();
initFilterSelectAffordances();
initTableToolbarBehavior();
initLiveSearchSuggestions();
initCaseCompactFilters();
initClickableRecordRows();
initCaseRecordTabTransitions();
initServiceManagementHub();

document.addEventListener('panel-ui:reset', initFilterSelectAffordances);

window.Alpine = Alpine;

Alpine.start();
