import './package-promotion';
import './payment-proof';
import './catalog-photos';
import '../css/service-print-actions.css';
import './payment-proof-viewer';
import { installPaymentWorkflow } from './payment-workflow';
import './record-form-validation';
import './form-discard';
import './promotion-form';
import { installRecordDialogMotion } from './record-dialog-motion';
import { installRecordDrawers } from './record-drawers';
import { filterOptionIcon, installSharedRecordControls } from './shared-record-controls';
import './case-record-filters';
import './panel-skeleton';
import './record-results-navigation';
import './chart-hover-tooltip';
import './system-admin-reference';
import './bootstrap';
import './idle-session';
import './login-throttle';
import './remember-email';
import './password-reset';
import './password-welcome';
import './package-wizard';
import './service-confirm';
import './service-form-feedback';
import './catalog-text-assistance';
import './catalog-filter-sort';
import './catalog-record-search';
import './worklist-controls';
import { installDashboardPaymentSearch } from './dashboard-payment-search';
import { installStaffIntakeModal } from './staff-intake-modal';
import '../css/staff-intake-modal.css';
import './report-filter-controls';
import './addon-price-preview';
import '../css/branch-sync.css';

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
    if (document.documentElement.dataset.rowActionMenusReady === '1') return;
    document.documentElement.dataset.rowActionMenusReady = '1';
    const menuSelector = '[data-row-menu]';
    const triggerSelector = '[data-row-menu-trigger]';
    const itemSelector = '[data-row-menu-item]';

    const positionDropdown = (menu) => {
        const trigger = menu.querySelector(triggerSelector);
        const dropdown = menu.querySelector('.row-action-dropdown');
        if (!trigger || !dropdown) return;
        const rect = trigger.getBoundingClientRect();
        const gap = 6;
        // Keep forms in their record context while escaping table clipping and
        // transformed ancestors. A portal would break Service's submit owner.
        if (typeof dropdown.showPopover === 'function') dropdown.setAttribute('popover', 'manual');
        dropdown.style.setProperty('position', 'fixed', 'important');
        dropdown.style.setProperty('inset', 'auto', 'important');
        dropdown.style.setProperty('margin', '0', 'important');
        dropdown.style.setProperty('display', 'block', 'important');
        dropdown.style.setProperty('z-index', '10050', 'important');
        dropdown.style.setProperty('pointer-events', 'auto', 'important');
        dropdown.style.setProperty('top', (rect.bottom + gap) + 'px', 'important');
        dropdown.style.setProperty('right', Math.max(12, window.innerWidth - rect.right) + 'px', 'important');
        if (typeof dropdown.showPopover === 'function' && !dropdown.matches(':popover-open')) dropdown.showPopover();
        const { height: dropdownHeight, width: dropdownWidth } = dropdown.getBoundingClientRect();
        const right = Math.min(Math.max(12, window.innerWidth - dropdownWidth - 12), Math.max(12, window.innerWidth - rect.right));
        dropdown.style.setProperty('right', right + 'px', 'important');
        let top = rect.bottom + gap;
        if (rect.bottom + gap + dropdownHeight > window.innerHeight - 12 && rect.top - gap - dropdownHeight >= 12) {
            top = rect.top - gap - dropdownHeight;
        }
        dropdown.style.setProperty('top', Math.max(12, Math.min(top, window.innerHeight - dropdownHeight - 12)) + 'px', 'important');
    };

    const clearDropdownPosition = (menu) => {
        const dropdown = menu.querySelector('.row-action-dropdown');
        if (!dropdown) return;
        if (typeof dropdown.hidePopover === 'function' && dropdown.matches(':popover-open')) dropdown.hidePopover();
        ['position','inset','margin','display','z-index','pointer-events','top','right','bottom','left'].forEach(property => dropdown.style.removeProperty(property));
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
            // Let a submit button finish its default activation before hiding
            // its form. Edit/modal click handlers also finish in this event.
            window.setTimeout(() => closeAllMenus(), 0);
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
    document.addEventListener('scroll', event => {
        if (event.target.closest?.('.row-action-dropdown')) return;
        closeAllMenus();
    }, true);
    window.addEventListener('resize', () => closeAllMenus());
}

function initTableToolbarBehavior() {
    initFilterSelectAffordances();

    const forms = document.querySelectorAll('form[data-table-toolbar]');
    if (!forms.length) return;

    forms.forEach((form) => {
        if (form.dataset.tableToolbarReady === '1') return;
        form.dataset.tableToolbarReady = '1';
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
        'form[method="GET" i] select:not([multiple]):not([data-native-select])',
        '.table-system-toolbar select.table-toolbar-select',
        '.table-system-toolbar select.table-toolbar-sort',
        'form[data-table-toolbar] select.table-toolbar-select',
        'form[data-table-toolbar] select.table-toolbar-sort',
        '.sales-filter select.filter-select',
        '.rp-field select.form-select',
        '.audit-filter-control.is-select select.audit-select',
        '.admin-filter-control select.input-custom',
        '.aoc-select-wrap select.form-select',
        '.pf-control-wrap select.pf-select',
        '.ba-branch-select-wrap select.ba-branch-select',
        '.ba-period-select-wrap select.ba-period-select',
        '.eb-branch-select-wrap select.eb-branch-select',
        '.eb-period-select-wrap select.eb-period-select',
        '.reports-module-control-wrap select.reports-module-control',
        '.reports-field-control select.reports-input',
        '.reports-analytics-branch select.reports-analytics-select',
        '.owner-sales-field-control select.owner-sales-control',
        '.topbar-filter-bar select.filter-select',
        '.payments-filter-control.has-dropdown select',
        '.case-compact-date-filter select:not([data-native-select])',
        '.case-compact-sort-filter select',
        '.case-compact-branch select',
        '.pm-field.has-icon select.pm-control',
        '.intake-package-select select#regular_package_choice',
    ].join(',');

    document.querySelectorAll(selector).forEach((select) => {
        if (!(select instanceof HTMLSelectElement) || select.dataset.filterSelectReady === '1') return;
        select.dataset.filterSelectReady = '1';

        let wrap = select.closest('.table-toolbar-select-wrap, .audit-filter-control.is-select, .admin-filter-control, .sales-filter-control, .owner-sales-field-control, .rp-field, .aoc-select-wrap, .pf-control-wrap, .ba-period-select-wrap, .ba-branch-select-wrap, .eb-period-select-wrap, .eb-branch-select-wrap, .reports-module-control-wrap, .reports-field-control, .reports-analytics-branch, .payments-filter-control.has-dropdown, .case-compact-select-wrap, .case-compact-date-filter, .case-compact-sort-filter, .case-compact-branch, .pm-field.has-icon, .global-filter-select-wrap, .case-page-size-control');

        if (!wrap && select.parentElement) {
            const isTableToolbarSelect = select.classList.contains('table-toolbar-select') || select.classList.contains('table-toolbar-sort');
            wrap = document.createElement('span');
            wrap.className = isTableToolbarSelect ? 'table-toolbar-select-wrap' : 'global-filter-select-wrap';
            select.parentElement.insertBefore(wrap, select);
            wrap.appendChild(select);
        }

        if (!wrap) return;
        wrap.setAttribute('data-filter-select-wrap', '');

        let icon = wrap.querySelector('.table-toolbar-select-icon, .payments-filter-dropdown-icon, .admin-filter-chevron, .reports-module-chevron, .ba-branch-select-chev, .eb-branch-select-chev, .eb-chev, .case-compact-date-chev, .case-compact-sort-chev, .case-compact-select-chev, .pm-sel-chev, .pf-control-icon, .aoc-select-icon, .reports-analytics-select-chev, .case-page-size-chevron, [data-filter-select-icon]');
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
        // Case filters live in a scrolling popover. A child dropdown needs its
        // own top-layer surface to escape that scroller's clipping and fields.
        const intakePackageDropdown = wrap.classList.contains('intake-package-select');
        const caseDropdown = !!wrap.closest('.case-record-filters [data-case-more-panel], .pm-more-panel, .worklist-filter-panel');
        const floatingDropdown = caseDropdown || intakePackageDropdown;
        if (floatingDropdown) menu.setAttribute('popover', 'manual');
        const positionFloatingDropdown = () => {
            const rect = select.getBoundingClientRect();
            if (intakePackageDropdown) {
                const gutter = 8;
                const gap = 6;
                const below = Math.max(0, window.innerHeight - rect.bottom - gutter - gap);
                const above = Math.max(0, rect.top - gutter - gap);
                const openAbove = below < Math.min(menu.scrollHeight, 240) && above > below;
                const height = Math.max(1, Math.min(240, openAbove ? above : below));
                const width = Math.min(rect.width, window.innerWidth - 2 * gutter);
                const left = Math.max(gutter, Math.min(rect.left, window.innerWidth - width - gutter));
                const top = openAbove ? rect.top - gap - Math.min(menu.scrollHeight, height) : rect.bottom + gap;
                Object.assign(menu.style, { position: 'fixed', inset: 'auto', margin: '0', top: `${top}px`, left: `${left}px`, width: `${width}px`, minWidth: '0', maxHeight: `${height}px` });
                return;
            }
            const panelRect = wrap.closest('.case-compact-drawer, .pm-more-panel, .worklist-filter-panel').getBoundingClientRect();
            const gutter = 12;
            const gap = 8;
            const width = Math.min(240, window.innerWidth - gutter * 2);
            const height = Math.max(1, Math.min(320, window.innerHeight - gutter * 2));
            // Anchor to the outside edge of the entire Filter panel, beside
            // the selected field. Flip left only when the right cannot fit.
            const right = panelRect.right + gap;
            const leftSide = panelRect.left - gap - width;
            const left = right + width <= window.innerWidth - gutter
                ? right
                : leftSide >= gutter ? leftSide
                : Math.max(gutter, window.innerWidth - width - gutter);
            const top = Math.max(gutter, Math.min(rect.top, window.innerHeight - Math.min(menu.scrollHeight, height) - gutter));
            Object.entries({position:'fixed', inset:'auto', margin:'0', top:`${top}px`, left:`${left}px`, width:`${width}px`, minWidth:'0', maxHeight:`${height}px`}).forEach(([key, value]) => {
                menu.style.setProperty(key.replace(/[A-Z]/g, letter => `-${letter.toLowerCase()}`), value, 'important');
            });
        };
        if (floatingDropdown) {
            document.addEventListener('scroll', event => {
                if (!menu.hidden && !menu.contains(event.target)) setOpen(false);
            }, true);
            window.addEventListener('resize', () => { if (!menu.hidden) setOpen(false); });
        }

        const optionLabel = (option) => option.dataset.menuLabel || option.textContent?.trim() || option.value || 'Option';
        const syncMenu = () => {
            menu.innerHTML = '';

            if (wrap.matches('.case-compact-sort-filter, .pm-sort-control, .worklist-sort-control')) {
                const heading = document.createElement('div');
                heading.className = 'filter-select-menu-heading';
                heading.textContent = 'Sort by';
                menu.appendChild(heading);
            }

            Array.from(select.options).forEach((option) => {
                if (option.disabled) return;

                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'filter-select-menu__item';
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', option.selected ? 'true' : 'false');
                item.dataset.value = option.value;
                const label = document.createElement('span');
                label.textContent = optionLabel(option);
                const check = document.createElement('i');
                check.className = 'bi bi-check2 filter-select-menu__check';
                check.setAttribute('aria-hidden', 'true');
                const menuIcon = select.hasAttribute('data-plain-options') ? '' : filterOptionIcon(select, option);
                if (/^[a-z0-9-]+$/.test(menuIcon)) {
                    const optionIcon = document.createElement('i');
                    optionIcon.className = 'bi bi-' + menuIcon;
                    optionIcon.setAttribute('aria-hidden', 'true');
                    item.append(optionIcon);
                }
                item.append(label, check);

                if (option.selected) item.classList.add('is-selected');

                item.addEventListener('click', (event) => {
                    // A top-layer option can sit outside the visual Filter
                    // panel's DOM hit area. Contain the pointer activation so
                    // page-level outside-click handlers do not close the main
                    // manual-Apply Filter after a pending choice is made.
                    event.preventDefault();
                    event.stopPropagation();
                    const detailsFilter = wrap.closest('.pm-more-filters, [data-worklist-filter]');
                    const caseFilterPanel = wrap.closest('.case-record-filters [data-case-more-panel]');
                    const keepDetailsOpen = detailsFilter instanceof HTMLDetailsElement && detailsFilter.open;
                    const keepCaseFilterOpen = caseFilterPanel instanceof HTMLElement
                        && !caseFilterPanel.hidden
                        && caseFilterPanel.matches(':popover-open');
                    select.value = option.value;
                    select.dispatchEvent(new Event('input', { bubbles: true }));
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    setOpen(false);

                    // Manual-Apply filters are multi-selection workspaces. A
                    // field choice may run legacy/native change handlers, but
                    // it must never dismiss the parent Filter before Apply.
                    // Restore only the parent that was already open; async
                    // Apply/navigation may replace it and is intentionally not
                    // reopened.
                    window.queueMicrotask(() => {
                        if (keepDetailsOpen && detailsFilter?.isConnected) {
                            detailsFilter.open = true;
                            detailsFilter.querySelector('summary')?.setAttribute('aria-expanded', 'true');
                        }
                        if (keepCaseFilterOpen && caseFilterPanel?.isConnected) {
                            caseFilterPanel.hidden = false;
                            if (!caseFilterPanel.matches(':popover-open')) caseFilterPanel.showPopover();
                            const form = caseFilterPanel.closest('[data-case-filter]');
                            const toggle = form?.querySelector('[data-case-more-toggle]');
                            toggle?.setAttribute('aria-expanded', 'true');
                            toggle?.classList.add('active');
                        }
                    });
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
                        const openMenu = openWrap.querySelector('.filter-select-menu');
                        if (openMenu?.matches(':popover-open')) openMenu.hidePopover();
                        openMenu?.setAttribute('hidden', '');
                    }
                });
                syncMenu();
            }

            wrap.classList.toggle('is-open', isOpen);
            select.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            menu.hidden = !isOpen;
            if (floatingDropdown) {
                if (isOpen) {
                    if (!menu.matches(':popover-open')) menu.showPopover();
                    positionFloatingDropdown();
                } else if (menu.matches(':popover-open')) menu.hidePopover();
            }
        };

        select.setAttribute('aria-haspopup', 'listbox');
        select.setAttribute('aria-expanded', 'false');

        select.addEventListener('pointerdown', (event) => {
            if (select.disabled) return;
            event.preventDefault();
            if (caseDropdown) event.stopPropagation();
            select.focus({ preventScroll:true });
        });
        select.addEventListener('click', (event) => {
            if (select.disabled) return;
            event.preventDefault();
            if (caseDropdown) event.stopPropagation();
            setOpen(!wrap.classList.contains('is-open'));
        });
        select.addEventListener('keydown', (event) => {
            if (['Enter', ' ', 'ArrowDown', 'ArrowUp'].includes(event.key)) {
                event.preventDefault();
                setOpen(true);
                menu.querySelector('[aria-selected="true"], button')?.focus({preventScroll:true});
            }
            if (event.key === 'Escape') setOpen(false);
        });
        menu.addEventListener('keydown', event => {
            const items = [...menu.querySelectorAll('button')];
            const index = items.indexOf(document.activeElement);
            if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
                event.preventDefault();
                const next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1
                    : (index + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
                items[next]?.focus({preventScroll:true});
            } else if (event.key === 'Escape') {
                event.preventDefault(); event.stopPropagation();
                setOpen(false); select.focus({preventScroll:true});
            } else if (event.key === 'Tab') setOpen(false);
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

const caseDateDialogs = new Map();
window.setCaseCustomDateOpen = (form, open) => {
    if (!form) return;
    if (form.matches('.case-record-filters')) {
        const panel = form.querySelector('[data-case-custom-panel]');
        const select = form.querySelector('[data-case-date-preset-select]');
        if (!panel || !select) return;
        if (!panel.dataset.sidePanelReady) {
            panel.dataset.sidePanelReady = '1';
            panel.classList.add('case-custom-date-side-panel');
            panel.setAttribute('popover', 'manual');
            const apply = panel.querySelector('button[type="submit"]');
            apply.type = 'button'; apply.removeAttribute('name'); apply.textContent = 'Use date range';
            apply.addEventListener('click', () => {
                const dates = [...panel.querySelectorAll('input[type="date"]')];
                dates.forEach(input => input.required = true);
                if (dates.every(input => input.reportValidity())) window.setCaseCustomDateOpen(form, false);
            });
            const reset = panel.querySelector('.case-compact-pop-reset');
            reset?.addEventListener('click', event => { event.preventDefault(); window.setCaseCustomDateOpen(form, false); });
            if (reset) reset.textContent = 'Close';
            const dates = panel.querySelectorAll('input[type="date"]');
            dates[0].addEventListener('change', () => dates[1].min = dates[0].value);
            dates[1].min = dates[0].value;
            document.addEventListener('click', event => {
                if (panel.matches(':popover-open') && !panel.contains(event.target) && !event.target.closest('.filter-select-menu') && event.target !== select) window.setCaseCustomDateOpen(form, false);
            });
        }
        if (!open) {
            if (panel.matches(':popover-open')) panel.hidePopover();
            panel.hidden = true;
            panel.querySelectorAll('input').forEach(input => input.required = false);
            return;
        }
        panel.hidden = false;
        if (!panel.matches(':popover-open')) panel.showPopover();
        const bounds = form.querySelector('.case-compact-drawer').getBoundingClientRect();
        const rect = select.getBoundingClientRect();
        const width = Math.min(320, window.innerWidth - 24);
        const left = bounds.right + 8 + width <= window.innerWidth - 12 ? bounds.right + 8 : Math.max(12, bounds.left - width - 8);
        const top = Math.max(12, Math.min(rect.top, window.innerHeight - panel.offsetHeight - 12));
        Object.entries({position:'fixed', inset:'auto', margin:'0', top:`${top}px`, left:`${left}px`, width:`${width}px`}).forEach(([key,value]) => panel.style.setProperty(key,value,'important'));
        panel.querySelector('input')?.focus({preventScroll:true});
        return;
    }
    for (const [owner, state] of caseDateDialogs) {
        if (!owner.isConnected) { state.dialog.remove(); caseDateDialogs.delete(owner); }
    }
    let state = caseDateDialogs.get(form);
    const select = form.querySelector('[data-case-date-preset-select]');
    if (!state && open) {
        const panel = form.querySelector('[data-case-custom-panel]');
        if (!panel || !select) return;
        if (!form.id) form.id = `case-date-form-${Date.now()}`;
        const dialog = document.createElement('dialog');
        dialog.className = 'case-date-dialog';
        dialog.setAttribute('aria-label', 'Custom Date Range');
        const head = document.createElement('div'); head.className = 'case-date-head';
        const title = document.createElement('h2'); title.textContent = 'Custom Date Range';
        const close = document.createElement('button'); close.type = 'button'; close.setAttribute('aria-label', 'Close custom date range'); close.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
        head.append(title, close);
        panel.hidden = false;
        panel.querySelectorAll('input,button').forEach(control => control.setAttribute('form', form.id));
        panel.querySelectorAll('input[type="date"]').forEach((input, index) => {
            input.required = true;
            input.id = `${form.id}-custom-date-${index}`;
            input.parentElement.querySelector('label')?.setAttribute('for', input.id);
        });
        const apply = panel.querySelector('button[type="submit"]'); if (apply) {
            apply.textContent = 'Apply Date Range'; apply.removeAttribute('name');
            if (select.closest('.case-record-filters [data-case-more-panel]')) {
                apply.type = 'button';
                apply.addEventListener('click', () => {
                    const dates = [...panel.querySelectorAll('input[type="date"]')];
                    if (dates.every(input => input.reportValidity())) dialog.close();
                });
            }
        }
        const reset = panel.querySelector('.case-compact-pop-reset');
        const cancel = document.createElement('button'); cancel.type = 'button'; cancel.className = 'case-date-cancel'; cancel.textContent = 'Cancel';
        if (reset) reset.replaceWith(cancel); else panel.querySelector('.case-compact-pop-actions')?.prepend(cancel);
        dialog.append(head, panel); document.body.append(dialog);
        state = {dialog, panel}; caseDateDialogs.set(form, state);
        const dismiss = () => dialog.close(); close.addEventListener('click', dismiss); cancel.addEventListener('click', dismiss);
        dialog.addEventListener('close', () => { panel.querySelectorAll('input[type="date"]').forEach(input => input.required = false); document.body.classList.remove('case-date-open'); select.focus({preventScroll:true}); });
        dialog.addEventListener('click', event => { if (event.target === dialog) { const r = dialog.getBoundingClientRect(); if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) dialog.close(); } });
        const dates = panel.querySelectorAll('input[type="date"]');
        if (dates.length === 2) { dates[0].addEventListener('change', () => dates[1].min = dates[0].value); dates[1].min = dates[0].value; }
        form.addEventListener('submit', () => { if (dialog.open) dialog.close(); });
    }
    if (!state) return;
    if (open && !state.dialog.open) { state.panel.hidden = false; state.panel.querySelectorAll('input[type="date"]').forEach(input => input.required = true); state.dialog.showModal(); document.body.classList.add('case-date-open'); state.panel.querySelector('input')?.focus(); }
    if (!open && state.dialog.open) state.dialog.close();
};

function initCaseCompactFilters() {
    document.querySelectorAll('[data-case-filter]').forEach((form) => {
        if (form.dataset.caseFilterReady === '1') return;
        form.dataset.caseFilterReady = '1';

        const customToggle = form.querySelector('[data-case-custom-toggle]');
        const customPanel = form.querySelector('[data-case-custom-panel]');
        const datePreset = form.querySelector('[data-case-date-preset-select]');
        const searchInput = form.querySelector('[data-case-search-input]');
        const searchClear = form.querySelector('[data-case-search-clear]');
        const searchPanel = form.querySelector('[data-case-search-suggestions]');
        const page = form.closest('.records-page, .master-records-page, .owner-page-shell, .admin-table-page') || document;
        const isStaffRecordsPage = !!form.closest('.records-page');

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
                if (!window.SabanganNavigate?.(url.toString())) window.location.href = url.toString();
            } finally {
                requestAnimationFrame(() => setUpdating(false));
            }
        };

        const setCustomOpen = (open) => {
            window.setCaseCustomDateOpen(form, open);
        };

        customToggle?.addEventListener('click', () => {
            setCustomOpen(customToggle.getAttribute('aria-expanded') !== 'true');
        });

        form.querySelectorAll('.case-compact-branch, .case-compact-date-filter, .case-compact-sort-filter').forEach((wrap) => {
            wrap.addEventListener('click', (event) => {
                if (event.target.closest('.filter-select-menu, .case-compact-popover')) return;

                const select = wrap.querySelector('select');
                if (!(select instanceof HTMLSelectElement) || select.disabled || event.target === select || select.contains(event.target)) return;

                event.preventDefault();
                select.focus({ preventScroll: true });
                const PointerCtor = typeof PointerEvent === 'function' ? PointerEvent : MouseEvent;
                select.dispatchEvent(new PointerCtor('pointerdown', {
                    bubbles: true,
                    cancelable: true,
                    pointerType: 'mouse',
                }));
            });
        });

        datePreset?.addEventListener('change', () => {
            if (isStaffRecordsPage) return;
            if (datePreset.value === 'CUSTOM') {
                setCustomOpen(true);
                return;
            }
            setCustomOpen(false);
            if (datePreset.closest('.case-record-filters [data-case-more-panel]')) return;
            // Preset choices (including All Dates) must reach the server;
            // otherwise the select changes visually while the results stay stale.
            submitFilter();
        });

        form.querySelectorAll('[data-case-auto-submit]').forEach((field) => {
            field.addEventListener('change', () => {
                if (isStaffRecordsPage) return;
                if (!field.reportValidity()) return;
                submitFilter();
            });
        });

        form.addEventListener('submit', (event) => {
            if (isStaffRecordsPage) return;
            event.preventDefault();
            searchPanel && (searchPanel.hidden = true);
            submitFilter(event.submitter || null);
        });

        form.querySelectorAll('[data-case-remove-filter]').forEach((button) => {
            button.addEventListener('click', () => {
                const keys = String(button.dataset.removeKeys || '').split(',').map((key) => key.trim()).filter(Boolean);
                keys.forEach((key) => {
                    form.querySelectorAll(`[name="${CSS.escape(key)}"]`).forEach((field) => {
                        if (field instanceof HTMLInputElement && field.type === 'hidden') return;
                        if (field instanceof HTMLSelectElement) field.selectedIndex = 0;
                        else if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) field.value = '';
                    });
                });
                if (keys.includes('date_preset')) {
                    const preset = form.querySelector('[data-case-date-preset-select]');
                    if (preset) preset.value = '';
                }
                submitFilter();
            });
        });

        form.querySelectorAll('[data-case-clear-filters]').forEach((button) => {
            button.addEventListener('click', () => {
                const filterNames = ['branch_id', 'date_preset', 'date_from', 'date_to', 'payment_status', 'verification_status', 'service_type', 'package_id', 'encoded_by', 'interment_from', 'interment_to'];
                filterNames.forEach((name) => {
                    form.querySelectorAll(`[name="${CSS.escape(name)}"]`).forEach((field) => {
                        if (field instanceof HTMLInputElement && field.type === 'hidden') return;
                        if (field instanceof HTMLSelectElement) field.selectedIndex = 0;
                        else if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) field.value = '';
                    });
                });
                submitFilter();
            });
        });

        form.querySelectorAll('.case-compact-pop-reset, .case-compact-advanced-clear').forEach((link) => {
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
                    if (!window.SabanganNavigate?.(link.href)) window.location.href = link.href;
                } finally {
                    requestAnimationFrame(() => setUpdating(false));
                }
            });
        });

        const searchEvents = new AbortController();
        const searchSignal = searchEvents.signal;
        const appliedSearchFields = new Map(new FormData(form).entries());
        const pendingSearchKeys = ['branch_id','date_preset','date_from','date_to','payment_status','verification_status','service_type','package_id','encoded_by','interment_from','interment_to'];
        let suggestionTimer, suggestionRequest, suggestionVersion = 0;
        const hideSuggestions = () => {
            clearTimeout(suggestionTimer); suggestionRequest?.abort(); suggestionVersion++;
            if (searchPanel) { searchPanel.hidden=true; searchPanel.replaceChildren(); }
            searchInput?.setAttribute('aria-expanded','false');
        };
        const searchUrl = query => {
            const url=buildUrl();
            pendingSearchKeys.forEach(key=>{
                const value=appliedSearchFields.get(key);
                if(value!==undefined && String(value).trim()!=='') url.searchParams.set(key,value); else url.searchParams.delete(key);
            });
            if(query) url.searchParams.set('q',query); else url.searchParams.delete('q');
            url.searchParams.delete('page');
            return url;
        };
        const commitSearch = () => {
            if(!searchInput) return;
            hideSuggestions(); searchInput.value=searchInput.value.trim();
            if(searchClear) searchClear.hidden=!searchInput.value;
            const url=searchUrl(searchInput.value);
            if(isStaffRecordsPage && window.loadCaseRecordSearch) window.loadCaseRecordSearch(url);
            else if(window.loadMasterCaseSearch) window.loadMasterCaseSearch(url);
            else submitFilter();
        };
        const matchesFrom = root => [...root.querySelectorAll('[data-record-search-case]')].map(row=>({code:row.dataset.recordSearchCase,client:row.dataset.recordSearchClient,deceased:row.dataset.recordSearchDeceased}));
        const underlineMatch = (element,text) => {
            const value=String(text||''),query=searchInput.value.trim().toLowerCase();
            if(!query) {element.textContent=value;return;}
            let cursor=0,index;
            while((index=value.toLowerCase().indexOf(query,cursor))!==-1){
                element.append(document.createTextNode(value.slice(cursor,index)));
                const mark=document.createElement('mark');mark.textContent=value.slice(index,index+query.length);element.append(mark);
                cursor=index+query.length;
            }
            element.append(document.createTextNode(value.slice(cursor)));
        };
        const drawMatches = rows => {
            if(!searchPanel) return;
            searchPanel.replaceChildren(); const seen=new Set();
            rows.filter(row=>row.code&&!seen.has(row.code)&&seen.add(row.code)).slice(0,5).forEach(row=>{
                const option=document.createElement('button'); option.type='button';option.className='case-search-match';option.dataset.caseMatchValue=row.code;
                option.setAttribute('aria-label',row.code+', Client: '+row.client+', Deceased: '+row.deceased);
                option.title=row.code+' · '+row.client+' · '+row.deceased;
                for(const [value,kind] of [[row.code,'case-match-code'],[row.client,'case-match-name'],[row.deceased,'case-match-name']]){
                    const span=document.createElement('span');span.className=kind;
                    if(kind==='case-match-name'){const text=document.createElement('span');underlineMatch(text,value);span.append(text);}else underlineMatch(span,value);
                    option.append(span);
                }
                searchPanel.append(option);
            });
            searchPanel.hidden=!searchPanel.children.length;
            searchInput?.setAttribute('aria-expanded',String(!searchPanel.hidden));
        };
        const renderSuggestions = () => {
            hideSuggestions();const query=searchInput?.value.trim();
            if(searchClear) searchClear.hidden=!query;
            if(!query||query.length<2) return;
            drawMatches(matchesFrom(page).filter(row=>[row.code,row.client,row.deceased].some(value=>value.toLowerCase().includes(query.toLowerCase()))));
            const version=suggestionVersion;
            suggestionTimer=setTimeout(async()=>{
                suggestionRequest=new AbortController();
                try {
                    const response=await fetch(searchUrl(query).toString(),{signal:suggestionRequest.signal,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest',Accept:'text/html'}});
                    if(!response.ok) return;
                    const doc=new DOMParser().parseFromString(await response.text(),'text/html');
                    if(version!==suggestionVersion||!form.isConnected||!doc.querySelector('[data-case-filter]')) return;
                    drawMatches(matchesFrom(doc));
                } catch(error) { /* Keep cached matches when suggestions are unavailable. */ }
            },300);
        };
        searchInput?.addEventListener('input',renderSuggestions,{signal:searchSignal});
        searchInput?.addEventListener('keydown',event=>{
            if(event.key==='Enter'){event.preventDefault();commitSearch();}
            if(event.key==='Escape') hideSuggestions();
            if(event.key==='ArrowDown'&&!searchPanel?.hidden){event.preventDefault();searchPanel.querySelector('button')?.focus();}
        },{signal:searchSignal});
        searchPanel?.addEventListener('click',event=>{
            const option=event.target.closest('[data-case-match-value]');if(!option)return;
            searchInput.value=option.dataset.caseMatchValue;commitSearch();
        },{signal:searchSignal});
        searchPanel?.addEventListener('keydown',event=>{
            if(event.key==='Escape'){hideSuggestions();searchInput?.focus();return;}
            if(!['ArrowDown','ArrowUp'].includes(event.key))return;
            event.preventDefault();const options=[...searchPanel.querySelectorAll('button')],index=options.indexOf(document.activeElement)+(event.key==='ArrowDown'?1:-1);
            if(index<0)searchInput?.focus();else options[Math.min(index,options.length-1)]?.focus();
        },{signal:searchSignal});
        searchClear?.addEventListener('click',()=>{searchInput.value='';commitSearch();},{signal:searchSignal});
        form.querySelector('[data-case-search-submit]')?.addEventListener('click',commitSearch,{signal:searchSignal});
        form.addEventListener('submit',hideSuggestions,{signal:searchSignal});
        document.addEventListener('panel-ui:reset',()=>{hideSuggestions();if(!form.isConnected)searchEvents.abort();},{signal:searchSignal});
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
                && !event.target.closest('.filter-select-menu, .case-date-dialog')
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
    if (document.documentElement.dataset.clickableRecordRowsReady === '1') return;
    document.documentElement.dataset.clickableRecordRowsReady = '1';
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
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        if (window.getSelection()?.toString()) return;
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
            if (!window.SabanganNavigate?.(href)) window.location.href = href;
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        if (!(event.target instanceof HTMLElement)) return;

        const row = event.target.closest('[data-clickable-row]');
        if (!row || event.target !== row) return;
        event.preventDefault();

        const trigger = row.querySelector('[data-row-view-trigger]');
        if (trigger instanceof HTMLElement) {
            trigger.click();
            return;
        }

        const href = row.getAttribute('data-row-href');
        if (href) {
            if (!window.SabanganNavigate?.(href)) window.location.href = href;
        }
    });
}

function initCaseRecordTabTransitions() {
    document.addEventListener('click', async (event) => {
        const link = event.target instanceof Element ? event.target.closest('.case-records-tabs a[href]') : null;
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

            currentCard.replaceWith(nextCard);
            window.history.pushState({}, '', targetUrl.href);
            initTableToolbarBehavior();
            initLiveSearchSuggestions();
            initCaseCompactFilters();

        } catch (error) {
            currentTableSection.classList.remove('case-records-tab-loading');
            const message = document.createElement('p');
            message.className = 'flash-error'; message.setAttribute('role', 'alert');
            message.textContent = 'Unable to load case records. Please try the tab again.';
            currentTableSection.prepend(message);
        }
    });
}

function initServiceManagementHub() {
    const hub = document.querySelector('[data-service-hub]');
    // Server-paginated hub owns its tab navigation, filtering and modal lifecycle.
    if (hub?.hasAttribute('data-service-server-navigation')) return;
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
            if (frameUrl.origin === window.location.origin && frameUrl.pathname === window.location.pathname) {
                if (!window.SabanganNavigate?.(frameUrl.toString())) window.location.href = frameUrl.toString();
                return;
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
installRecordDialogMotion();
installRecordDrawers();
installPaymentWorkflow();
installSharedRecordControls();
installDashboardPaymentSearch();
installStaffIntakeModal();
initRowActionMenus();
initFilterSelectAffordances();
initTableToolbarBehavior();
initLiveSearchSuggestions();
initCaseCompactFilters();
initClickableRecordRows();
initCaseRecordTabTransitions();
initServiceManagementHub();

document.addEventListener('panel-ui:reset', initFilterSelectAffordances);
document.addEventListener('panel-ui:reset', initCaseCompactFilters);
document.addEventListener('master-case-records:updated', () => {
    initTableToolbarBehavior();
    initLiveSearchSuggestions();
    initCaseCompactFilters();
});

window.Alpine = Alpine;

Alpine.start();
