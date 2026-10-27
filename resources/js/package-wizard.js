const packageForm = document.querySelector('[data-package-wizard]');
if (packageForm) {
    const panels = [...packageForm.querySelectorAll('.pkg-main > .pkg-panel')];
    const review = packageForm.querySelector('.pkg-review');
    const other = packageForm.querySelector('[data-custom-list]')?.closest('.pkg-muted-box');
    const nav = packageForm.querySelector('.package-wizard-nav');
    const footer = packageForm.querySelector('.package-wizard-footer');
    if (panels.length === 4 && other && review) {
        const inclusions = document.createElement('section');
        inclusions.className = 'pkg-panel package-wizard-inclusions';
        inclusions.append(other);
        panels[1].after(inclusions);
        const steps = [panels[0], panels[1], inclusions, panels[2], panels[3], review];
        const submit = packageForm.querySelector('[data-package-submit]');
        const reviewActions = review.querySelector('.pkg-review-actions');
        footer.querySelector('div').append(submit);
        reviewActions.hidden = true;
        steps.forEach((panel, index) => {
            panel.dataset.wizardStep = index + 1;
            panel.classList.remove('is-collapsed', 'is-collapsible');
            panel.querySelectorAll('[data-panel-toggle]').forEach(toggle => { toggle.hidden = true; });
        });
        packageForm.classList.add('package-wizard');
        nav.hidden = footer.hidden = false;
        let current = 1;
        const feedback = document.createElement('p');
        feedback.className = 'form-error'; feedback.setAttribute('role', 'alert'); feedback.hidden = true;
        nav.after(feedback);
        const show = (step, focus = true) => {
            current = step;
            steps.forEach((panel, index) => { panel.hidden = index + 1 !== step; });
            nav.querySelectorAll('button').forEach(button => {
                const active = Number(button.dataset.wizardGo) === step;
                button.setAttribute('aria-current', active ? 'step' : 'false');
            });
            footer.querySelector('[data-wizard-progress]').textContent = `Step ${step} of 6`;
            footer.querySelector('[data-wizard-back]').hidden = step === 1;
            footer.querySelector('[data-wizard-next]').hidden = step === 6;
            submit.hidden = step !== 6;
            if (focus) {
                steps[step - 1].tabIndex = -1;
                steps[step - 1].focus({ preventScroll: true });
                steps[step - 1].scrollIntoView({ block: 'start', behavior: 'instant' });
            }
        };
        const valid = (step) => {
            feedback.hidden = true;
            if (step === 2) {
                const enabled = [...steps[1].querySelectorAll('input[type="checkbox"][name^="included_services"]')].filter(input => input.checked);
                const casketEnabled = steps[1].querySelector('input[type="checkbox"][name="included_services[casket][enabled]"]')?.checked;
                const casket = steps[1].querySelector('[data-casket-select]');
                const selectedCasket = casket?.selectedOptions[0];
                if (!enabled.length || (casketEnabled && (!casket?.value || selectedCasket?.dataset.available === '0'))) {
                    show(2); feedback.textContent = !enabled.length ? 'Select at least one included service.' : 'Select the included casket before continuing.'; feedback.hidden = false; return false;
                }
            }
            if (step === 4 && steps[3].querySelector('[data-freebie-card].is-selected[data-catalog-active="0"]')) {
                show(4);
                feedback.textContent = 'Remove or replace the archived freebie before continuing.';
                feedback.hidden = false;
                return false;
            }
            const inputs = steps[step - 1].querySelectorAll('input, select, textarea');
            for (const input of inputs) {
                if (!input.disabled && !input.checkValidity()) { show(step); input.reportValidity(); return false; }
            }
            return true;
        };
        footer.querySelector('[data-wizard-next]').addEventListener('click', () => { if (valid(current)) show(current + 1); });
        footer.querySelector('[data-wizard-back]').addEventListener('click', () => show(current - 1));
        nav.addEventListener('click', event => {
            const button = event.target.closest('[data-wizard-go]');
            if (!button) return;
            const target = Number(button.dataset.wizardGo);
            for (let step = current; step < target; step++) if (!valid(step)) return;
            show(target);
        });
        packageForm.addEventListener('invalid', event => {
            const panel = event.target.closest('[data-wizard-step]');
            if (panel) show(Number(panel.dataset.wizardStep), false);
        }, true);
        submit.addEventListener('click', event => {
            for (let step = 1; step <= 6; step++) {
                if (!valid(step)) { event.preventDefault(); return; }
            }
        });
        packageForm.addEventListener('submit', event => {
            if (current !== 6) { event.preventDefault(); event.stopImmediatePropagation(); if (valid(current)) show(current + 1); }
        }, true);
        packageForm.addEventListener('submit', event => {
            if (!event.defaultPrevented) return;
            const failure = steps.findIndex(panel => [...panel.querySelectorAll('.form-error')].some(error => !error.hidden && !error.classList.contains('hidden') && error.textContent.trim()));
            if (failure >= 0) show(failure + 1);
        });
        const errorStep = steps.findIndex(panel => [...panel.querySelectorAll('.form-error')].some(error => !error.hidden && !error.classList.contains('hidden') && error.textContent.trim()));
        show(errorStep >= 0 ? errorStep + 1 : 1, false);
    }
}
