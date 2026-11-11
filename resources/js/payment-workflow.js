export function installPaymentWorkflow() {
    if (document.documentElement.dataset.paymentWorkflowReady) return;
    document.documentElement.dataset.paymentWorkflowReady = '1';
    installCorrectionSearch();
    let drawer, returnFocus, row, loadRequest;
    let companion;
    let reviewHistory;
    const clearQueueHistorySelection = panel => {
        const selected = panel.querySelector('[aria-current="true"]');
        selected?.removeAttribute('aria-current');
        selected?.removeAttribute('tabindex');
    };
    const closeReviewHistory = (restoreFocus = true) => {
        if (!reviewHistory) return;
        const {host, panel, trigger, standalone} = reviewHistory;
        reviewHistory.request?.abort();
        if (standalone) { host.close(); host.remove(); }
        else { panel.remove(); host.classList.remove('pw-review-with-history', 'pw-queue-with-request'); }
        trigger?.setAttribute('aria-expanded', 'false');
        reviewHistory = null;
        if (restoreFocus && trigger?.isConnected) trigger.focus({preventScroll:true});
    };
    const openReviewHistory = trigger => {
        if (reviewHistory?.historyFirst && trigger.hasAttribute('data-review-history-open')) {
            const template = trigger.closest('#workflowContent').querySelector('[data-review-history-template]');
            if (!template) return;
            const dialog = document.createElement('dialog');
            dialog.className = 'pw-history-standalone';
            dialog.setAttribute('aria-label', 'Payment history comparison');
            dialog.append(template.content.firstElementChild.cloneNode(true));
            const closeButton = dialog.querySelector('[data-review-history-close]');
            closeButton.onclick = () => dialog.close();
            dialog.addEventListener('close', () => {
                dialog.remove();
                trigger.setAttribute('aria-expanded', 'false');
                trigger.focus({preventScroll:true});
            }, {once:true});
            document.body.append(dialog);
            trigger.setAttribute('aria-expanded', 'true');
            dialog.showModal();
            closeButton.focus({preventScroll:true});
            return;
        }
        if (reviewHistory?.trigger === trigger) { closeReviewHistory(); return; }
        closeReviewHistory(false);
        const template = trigger.closest('#workflowContent')?.querySelector('[data-review-history-template]');
        if (!template) return;
        const panel = template.content.firstElementChild.cloneNode(true);
        let host = trigger.closest('.pw-dialog');
        const standalone = !host;
        if (standalone) {
            host = document.createElement('dialog');
            host.className = 'pw-history-standalone';
            host.setAttribute('aria-label', 'Payment history comparison');
            document.body.append(host);
            host.addEventListener('cancel', event => { event.preventDefault(); closeReviewHistory(); });
        } else host.classList.add('pw-review-with-history');
        host.append(panel);
        reviewHistory = {host, panel, trigger, standalone};
        trigger.setAttribute('aria-expanded', 'true');
        panel.querySelector('[data-review-history-close]').onclick = () => closeReviewHistory();
        if (standalone) host.showModal();
        panel.querySelector('[data-review-history-close]').focus({preventScroll:true});
    };
    const motion = () => window.recordDialogMotion;
    const money = value => new Intl.NumberFormat('en-PH', { style:'currency', currency:'PHP' }).format(Number(value) || 0);
    let errorId = 0;
    const clearFieldError = field => {
        const id = field.dataset.pwError;
        if (!id) return;
        document.getElementById(id)?.remove();
        const descriptions = (field.getAttribute('aria-describedby') || '').split(' ').filter(value => value && value !== id);
        if (descriptions.length) field.setAttribute('aria-describedby', descriptions.join(' '));
        else field.removeAttribute('aria-describedby');
        field.removeAttribute('aria-invalid');
        delete field.dataset.pwError;
    };
    const fieldError = (field, message, anchor = field) => {
        clearFieldError(field);
        const error = document.createElement('p');
        error.id = `pw-error-${++errorId}`;
        error.className = 'pw-field-error';
        error.textContent = message;
        error.setAttribute('role', 'alert');
        anchor.after(error);
        field.dataset.pwError = error.id;
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'), error.id].filter(Boolean).join(' '));
        return field;
    };
    const focusError = field => {
        field.focus({preventScroll:true});
        field.scrollIntoView({block:'nearest'});
    };
    const confirm = async (title, copy, action, danger = false, besideDetails = false) => {
        const focused = document.activeElement;
        const parent = !besideDetails && drawer?.open ? drawer : null;
        const standalone = besideDetails ? companion?.panel.querySelector('.pw-content') : !parent && document.getElementById('workflowContent');
        const scroll = (parent || standalone)?.querySelector('.pw-body')?.scrollTop || 0;
        if (parent) await motion().close(parent);
        if (standalone) standalone.hidden = true;
        const box = document.createElement('dialog');
        box.className = 'pw-confirm';
        if (besideDetails) box.classList.add('pw-confirm-shared-scrim');
        box.setAttribute('aria-label', title);
        box.innerHTML = '<i class="bi bi-exclamation-triangle" aria-hidden="true"></i><h2></h2><p></p><footer><button type="button" data-keep>Keep Editing</button><button type="button" data-confirm></button></footer>';
        box.querySelector('h2').textContent = title;
        box.querySelector('p').textContent = copy;
        const primary = box.querySelector('[data-confirm]');
        primary.textContent = action;
        primary.className = danger ? 'pw-danger' : 'pw-primary';
        document.body.append(box);
        motion().open(box);
        box.querySelector('[data-keep]').focus();
        const accepted = await new Promise(resolve => {
            box.querySelector('[data-keep]').onclick = () => resolve(false);
            primary.onclick = () => resolve(true);
            box.addEventListener('cancel', event => { event.preventDefault(); resolve(false); });
        });
        await motion().close(box);
        box.remove();
        if (standalone) standalone.hidden = false;
        if (parent) motion().open(parent);
        const body = (parent || standalone)?.querySelector('.pw-body');
        if (body) body.scrollTop = scroll;
        if (besideDetails && focused?.isConnected) focused.focus({preventScroll:true});
        return accepted;
    };
    const loadContent = async (url, signal) => {
        const response = await fetch(url, { signal, headers:{'X-Requested-With':'XMLHttpRequest'} });
        if (!response.ok) {
            const validation = [409, 422].includes(response.status) ? await response.json().catch(() => null) : null;
            throw new Error(validation?.message || 'Unable to open request.');
        }
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const content = page.querySelector('#workflowContent');
        if (!content) throw new Error('Your session may have expired. Refresh the page.');
        return document.importNode(content, true);
    };
    const selectCorrectionFromHistory = async (link, reviewNote = null) => {
        const history = reviewHistory;
        const currentContent = history.host.querySelector(':scope > #workflowContent');
        if (currentContent?.querySelector('[data-workflow-decision][data-submitting]')) return;

        history.request?.abort();
        const request = new AbortController();
        history.request = request;
        const body = history.panel.querySelector('.pw-audit-trail');
        const status = body.querySelector('[data-correction-history-status]');
        const requestBody = currentContent?.querySelector('.pw-body');
        const openingLabel = currentContent?.querySelector('[data-correction-opening]');
        const openingText = reviewNote === null ? `Opening request #${link.dataset.correctionRequestNumber}…` : 'Refreshing request…';
        const url = link.href;
        status.hidden = false;
        status.textContent = openingText;
        if (openingLabel) {
            openingLabel.textContent = openingText;
            openingLabel.hidden = false;
        }
        currentContent?.classList.add('pw-request-switching');
        requestBody?.setAttribute('aria-busy', 'true');
        try {
            const nextContent = await loadContent(url, request.signal);
            if (request.signal.aborted) return;
            const nextBody = nextContent.querySelector('[data-correction-history-template]')?.content.querySelector('.pw-audit-trail')?.cloneNode(true);
            if (!nextBody) throw new Error('Correction history is unavailable for this request.');

            const scrollTop = body.scrollTop;
            const restoreFocus = body.contains(document.activeElement);
            nextContent.classList.add('pw-request-switched');
            if (currentContent) currentContent.replaceWith(nextContent);
            else history.host.append(nextContent);
            body.replaceWith(nextBody);
            nextBody.scrollTop = scrollTop;
            history.host.classList.add('pw-queue-with-request');
            const note = nextContent.querySelector('[name=review_reason]');
            if (note && reviewNote !== null) note.value = reviewNote;
            setup(drawer);
            if (restoreFocus) nextBody.querySelector('[aria-current="true"]')?.focus({preventScroll:true});
        } catch (error) {
            if (!request.signal.aborted) status.textContent = error.message || 'Unable to open request. Try again.';
        } finally {
            if (history.request === request && currentContent?.isConnected) {
                currentContent.classList.remove('pw-request-switching');
                if (openingLabel) openingLabel.hidden = true;
                requestBody?.removeAttribute('aria-busy');
            }
        }
    };
    const closeCompanion = async () => {
        const current = companion;
        if (!current || current.closing) return false;
        current.closing = true;
        const form = current.panel.querySelector('[data-correction-form]');
        if (form?.dataset.submitting) { current.closing = false; return false; }
        if (form?.dataset.dirty && !await confirm('Discard request?', 'This request has not been sent. Your changes will not be saved.', 'Discard Request', true, true)) {
            current.closing = false;
            return false;
        }
        if (reviewHistory?.host === current.host) closeReviewHistory(false);
        current.request.abort();
        current.panel.remove();
        current.host.classList.remove('pw-with-request');
        companion = null;
        const focus = [current.trigger, current.host.querySelector('[data-pm-correction-picker-toggle]'), current.host.querySelector('[data-pm-close-transactions-modal]')]
            .find(element => element?.getClientRects().length);
        focus?.focus({preventScroll:true});
        return true;
    };
    const openCompanion = async (host, trigger, reviewNote = null) => {
        const url = trigger.href;
        if (companion?.host === host && companion.url === url && reviewNote === null) {
            companion.panel.querySelector('[data-workflow-close]')?.focus({preventScroll:true});
            return;
        }
        const returnTrigger = companion?.panel.contains(trigger) ? companion.trigger : trigger;
        if (companion && !await closeCompanion()) return;
        const panel = document.createElement('section');
        panel.className = 'pw-companion';
        const title = trigger.hasAttribute('data-payment-correction-create') ? 'Request Payment Correction' : 'Payment Correction Request';
        panel.setAttribute('aria-label', title);
        panel.innerHTML = '<div class="pw-content"><header class="pw-head"><h2></h2><button type="button" data-workflow-close aria-label="Close request">×</button></header><div class="pw-body" role="status">Loading request…</div></div>';
        panel.querySelector('h2').textContent = title;
        const request = new AbortController();
        companion = {host, panel, trigger:returnTrigger, request, url};
        host.append(panel);
        host.classList.add('pw-with-request');
        panel.querySelector('button').focus({preventScroll:true});
        try {
            const content = await loadContent(url, request.signal);
            if (request.signal.aborted) return;
            const restoreFocus = panel.contains(document.activeElement);
            panel.replaceChildren(content);
            setup(panel);
            const note = panel.querySelector('[name=review_reason]');
            if (note && reviewNote !== null) note.value = reviewNote;
            if (restoreFocus) panel.querySelector('[data-workflow-close]').focus({preventScroll:true});
        } catch (error) {
            if (request.signal.aborted) return;
            const message = panel.querySelector('.pw-body');
            message.setAttribute('role', 'alert');
            message.textContent = error.message;
        }
    };
    document.addEventListener('pm-close-correction-panel', event => {
        if (reviewHistory?.host === event.target) {
            closeReviewHistory();
            return;
        }
        if (companion?.host === event.target) closeCompanion().then(closed => {
            if (closed) event.detail.afterClose?.();
        });
    });
    const close = async () => {
        const form = (drawer?.open ? drawer : document.getElementById('workflowContent'))?.querySelector('[data-correction-form]');
        if (form?.dataset.dirty && !await confirm('Discard request?', 'This request has not been sent. Your changes will not be saved.', 'Discard Request', true)) return;
        loadRequest?.abort();
        if (drawer?.open && !await motion().close(drawer)) return;
        closeReviewHistory(false);
        row?.classList.remove('pw-active-row');
        document.body.classList.remove('record-drawer-open');
        returnFocus?.focus({preventScroll:true});
        if (!drawer) location.href = document.getElementById('workflowContent')?.dataset.workflowBackUrl || '/payments/history?tab=corrections';
    };
    const closeQueueRequest = () => {
        if (!reviewHistory?.historyFirst) return false;
        const current = drawer.querySelector(':scope > #workflowContent');
        if (!current) return false;
        if (current.querySelector('[data-workflow-decision][data-submitting]')) return true;
        reviewHistory.request?.abort();
        const panel = reviewHistory.panel;
        const selection = panel.querySelector('[aria-current="true"] [data-workflow-history-link]');
        clearQueueHistorySelection(panel);
        panel.querySelector('[data-correction-history-status]').hidden = true;
        drawer.classList.remove('pw-queue-with-request');
        current.remove();
        (selection ?? panel.querySelector('[data-review-history-close]'))?.focus({preventScroll:true});
        return true;
    };
    const dismissDrawer = () => {
        if (closeQueueRequest()) return;
        if (reviewHistory && !reviewHistory.historyFirst) closeReviewHistory();
        else close();
    };
    const open = async (url, trigger, {reviewNote = '', historyFirst = false} = {}) => {
        const insideDrawer = drawer?.contains(trigger);
        closeReviewHistory(false);
        loadRequest?.abort();
        loadRequest = new AbortController();
        if (!drawer) {
            drawer = document.createElement('dialog');
            drawer.className = 'pw-dialog is-record-drawer';
            drawer.dataset.paymentDrawer = '';
            drawer.setAttribute('aria-label', 'Payment correction');
            document.body.append(drawer);
            drawer.addEventListener('cancel', event => { event.preventDefault(); dismissDrawer(); });
            drawer.addEventListener('click', event => {
                if (event.target !== drawer) return;
                if (reviewHistory) { dismissDrawer(); return; }
                const bounds = drawer.getBoundingClientRect();
                if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) close();
            });
        }
        if (trigger && !insideDrawer) {
            returnFocus = trigger;
            row?.classList.remove('pw-active-row');
            row = trigger.closest('tr');
            row?.classList.add('pw-active-row');
        }
        const title = historyFirst ? 'Correction Request History' : 'Payment correction';
        drawer.innerHTML = '<section class="pw-content"><header class="pw-head"><h2></h2><button type="button" data-workflow-close aria-label="Close"><i class="bi bi-x-lg" aria-hidden="true"></i></button></header><div class="pw-body" role="status">Loading…</div></section>';
        drawer.querySelector('h2').textContent = title;
        drawer.setAttribute('aria-label', title);
        if (!drawer.open) motion().open(drawer);
        document.body.classList.add('record-drawer-open');
        try {
            const request = loadRequest;
            const content = await loadContent(url, request.signal);
            if (request.signal.aborted) return;
            const historyPanel = historyFirst && content.querySelector('[data-correction-history-template]')?.content.firstElementChild.cloneNode(true);
            if (historyPanel) {
                historyPanel.classList.add('pw-queue-history');
                clearQueueHistorySelection(historyPanel);
                drawer.replaceChildren(historyPanel);
                reviewHistory = {host:drawer, panel:historyPanel, historyFirst:true};
                const closeButton = historyPanel.querySelector('[data-review-history-close]');
                closeButton.onclick = dismissDrawer;
                closeButton.focus({preventScroll:true});
                return;
            }
            drawer.replaceChildren(content);
            setup(drawer);
            const note = drawer.querySelector('[name=review_reason]');
            if (note) note.value = reviewNote;
            drawer.querySelector('[data-workflow-close]')?.focus({preventScroll:true});
        } catch (error) {
            if (error.name === 'AbortError') return;
            drawer.innerHTML = '<section class="pw-content"><header class="pw-head"><h2>Unable to open request</h2><button data-workflow-close aria-label="Close">×</button></header><div class="pw-body"><p role="alert"></p></div></section>';
            drawer.querySelector('[role=alert]').textContent = error.message;
        }
        refreshNotices();
    };
    function setup(root) {
        root.querySelectorAll('.pw-content label').forEach(label => {
            const control = label.querySelector('input,select,textarea');
            if (!control || !label.querySelector(':scope > small')) return;
            const caption = document.createElement('span');
            while (label.firstChild && label.firstChild !== control) caption.append(label.firstChild);
            label.prepend(caption);
        });
        root.querySelectorAll('.pw-content label').forEach(label => {
            const control = label.querySelector('input,select,textarea');
            if (!control || !label.querySelector(':scope > small')) return;
            const caption = document.createElement('span');
            while (label.firstChild && label.firstChild !== control) caption.append(label.firstChild);
            label.prepend(caption);
        });
        root.querySelectorAll('[data-workflow-decision]').forEach(form => form.dataset.approveAction = form.action);
        if (root === drawer) {
            const decision = root.querySelector('#pwDecisionForm');
            if (decision) { decision.id = 'pwDecisionForm-drawer'; root.querySelectorAll('[form="pwDecisionForm"]').forEach(button => button.setAttribute('form', decision.id)); }
        }
        root.querySelectorAll('[data-correction-form]').forEach(form => {
            if (form.dataset.ready) return;
            form.dataset.ready = '1';
            form.noValidate = true;
            const currentDetails = JSON.parse(form.dataset.paymentDetails || '{}');
            Object.entries(currentDetails).forEach(([name,value]) => { const field=form.elements.namedItem(name);if(field && !name.startsWith('legacy_'))field.value=value??''; });
            const proof=form.elements.evidence, clearProof=form.querySelector('[data-proof-clear]');
            proof.addEventListener('change',()=>{clearProof.hidden=!proof.files.length;proof.setCustomValidity(proof.files[0]?.size>5*1024*1024?'Supporting files must not exceed 5 MB.':'');});
            clearProof.onclick=()=>{proof.value='';proof.setCustomValidity('');clearProof.hidden=true;form.dataset.dirty='1';};
            const fields = () => [...form.querySelectorAll('[name="changes[]"]:checked')].map(field => field.value);
            const submit = form.querySelector('[data-correction-request-submit]');
            const impact = form.querySelector('[data-correction-impact]');
            const explanation = form.querySelector('[data-void-explanation]');
            const keepPayment = form.elements.evidence_reference;
            const voidRecord = form.querySelector('[data-void-record]').textContent;
            const show = (element, visible) => {
                element.hidden = !visible;
                element.querySelectorAll('input,select,textarea').forEach(field => field.disabled = !visible);
            };
            let previousType;
            const sync = () => {
                const typeValue = form.elements.correction_type.value;
                const correction = form.elements.correction_type.value === 'CORRECT';
                show(form.querySelector('[data-correction-only]'), correction);
                show(form.querySelector('[data-duplicate-reference]'), !correction);
                keepPayment.required = !correction;
                submit.disabled = !correction && !keepPayment.value;
                explanation.hidden = correction || !keepPayment.value;
                explanation.textContent = !correction && keepPayment.value
                    ? `If approved, ${voidRecord} will be voided and removed from the case’s total payments. ${keepPayment.value} will stay as the valid payment. No refund will be issued.` : '';
                if (typeValue !== previousType) {
                    submit.textContent = correction ? 'Send Correction Request' : 'Send Void Request';
                    submit.classList.toggle('pw-primary', correction);
                    submit.classList.toggle('pw-warning', !correction);
                    previousType = typeValue;
                }
                form.querySelectorAll('[data-change-field]').forEach(element => show(element, correction && fields().includes(element.dataset.changeField)));
                const methodEnabled = correction && fields().includes('payment_details');
                const cashless = methodEnabled && form.elements.payment_method.value === 'cashless';
                form.querySelectorAll('[data-cashless]').forEach(element => show(element, cashless));
                const type = form.elements.cashless_type.value;
                form.elements.cashless_type.required = cashless;
                form.querySelectorAll('[data-types]').forEach(element => show(element, cashless && element.dataset.types.split(' ').includes(type)));
                show(form.querySelector('[data-other-bank]'), cashless && type === 'bank_transfer' && form.elements.bank_name.value === 'Other Bank');
                form.elements.bank_name.required = cashless && type === 'bank_transfer';
                form.elements.other_bank_name.required = cashless && type === 'bank_transfer' && form.elements.bank_name.value === 'Other Bank';
                form.elements.reference_number.required = cashless && type !== 'card';
                form.elements.payment_channel.required = cashless && type === 'other';
                const current = Number(form.dataset.currentBalance), original = Number(form.dataset.originalAmount);
                const cents = value => Math.round(Number(value) * 100);
                const afterReversal = cents(form.dataset.caseTotal) - cents(form.dataset.totalPaid) + cents(original);
                const changes = fields();
                const amountField = form.elements.amount;
                const amount = !correction ? 0 : changes.includes('amount') ? amountField.valueAsNumber : original;
                const target = form.elements.funeral_case_id;
                const moving = correction && changes.includes('funeral_case_id');
                if (!correction && !keepPayment.value) impact.textContent = 'Select the valid payment record to keep.';
                else if (correction && !changes.length) impact.textContent = 'Select the details you need to correct.';
                else if (correction && (!Number.isFinite(amount) || amount <= 0 || (changes.includes('amount') && !amountField.validity.valid))) {
                    impact.textContent = 'Enter a valid corrected amount to see the estimate.';
                } else if (moving && !target.value) impact.textContent = 'Select the destination case to see the estimate.';
                else if (moving && target.value !== form.dataset.originalCase) {
                    impact.textContent = cents(amount) > cents(form.dataset.targetBalance) ? 'The amount exceeds the destination case’s remaining balance.'
                        : `Original case: ${money(current)} → ${money(Math.max(0, afterReversal) / 100)}. Destination case: ${money(form.dataset.targetBalance)} → ${money((cents(form.dataset.targetBalance) - cents(amount)) / 100)}.`;
                } else {
                    impact.textContent = correction && cents(amount) > afterReversal ? 'The amount exceeds the case’s remaining balance after reversing the original payment.'
                        : `Case balance: ${money(current)} → ${money(Math.max(0, afterReversal - cents(amount)) / 100)}.`;
                }
            };
            const update = event => {
                form.dataset.dirty = '1';
                clearFieldError(event.target);
                if (event.target.name === 'changes[]') clearFieldError(form.querySelector('[name="changes[]"]'));
                sync();
            };
            form.addEventListener('input', update);
            form.addEventListener('change', update);
            let timer, searchRequest;
            const search = form.querySelector('[data-correction-case-search]');
            search.addEventListener('input', () => {
                clearTimeout(timer); searchRequest?.abort(); form.elements.funeral_case_id.value = '';
                const results = form.querySelector('[data-correction-case-results]'); results.replaceChildren();
                if (search.value.trim().length < 2) return;
                timer = setTimeout(async () => {
                    searchRequest = new AbortController();
                    try {
                        const response = await fetch(search.dataset.url + '?' + new URLSearchParams({q:search.value.trim()}), {signal:searchRequest.signal,headers:{Accept:'application/json'}});
                        if (!response.ok) throw new Error('Case search unavailable');
                        const items = await response.json();
                        items.forEach(item => {
                            const button = document.createElement('button'); button.type='button'; button.setAttribute('role','option');
                            button.textContent = `${item.label} · Balance ${money(item.balance)}`;
                            button.onclick = () => { form.elements.funeral_case_id.value=String(item.id); form.dataset.targetBalance=item.balance; search.value=item.label; results.replaceChildren(); clearFieldError(search); sync(); };
                            results.append(button);
                        });
                        if (!items.length) results.textContent = 'No matching cases.';
                    } catch (error) { if (error.name !== 'AbortError') results.textContent = 'Case search unavailable. Try again.'; }
                },300);
            });
            sync();
        });
    }
    document.addEventListener('click', event => {
        const historyTrigger = event.target.closest('[data-review-history-open]');
        if (historyTrigger) { event.preventDefault(); openReviewHistory(historyTrigger); return; }
        const filterClose = event.target.closest('[data-correction-filter-close]');
        if (filterClose) {
            const panel = filterClose.closest('details');
            panel.open = false;
            panel.querySelector('summary').focus();
        }
        document.querySelectorAll('[data-correction-toolbar] details[open]').forEach(panel => {
            if (!panel.contains(event.target) && !event.target.closest('.filter-select-menu')) panel.open = false;
        });
        const refresh = event.target.closest('[data-workflow-refresh]');
        if (refresh && companion?.panel.contains(refresh)) {
            event.preventDefault();
            openCompanion(companion.host, refresh, companion.panel.querySelector('[name=review_reason]')?.value ?? '');
            return;
        }
        if (refresh && drawer?.open) {
            event.preventDefault();
            const reviewNote = drawer.querySelector('[name=review_reason]')?.value ?? '';
            if (reviewHistory?.historyFirst) selectCorrectionFromHistory(refresh, reviewNote);
            else open(refresh.href, returnFocus, {reviewNote});
            return;
        }
        const link = event.target.closest('[data-payment-workflow]');
        if (link?.hasAttribute('href') && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey) {
            if (link.hasAttribute('data-workflow-history-link') && reviewHistory?.historyFirst && reviewHistory.panel.contains(link)) {
                event.preventDefault();
                selectCorrectionFromHistory(link);
                return;
            }
            const details = link.closest('.pm-transactions-backdrop.open');
            event.preventDefault();
            event.stopPropagation();
            if (details) openCompanion(details, link);
            else open(link.href, link, {historyFirst:link.hasAttribute('data-workflow-payment-history')});
            return;
        }
        const record = event.target.closest('[data-workflow-row]');
        if (record && !event.target.closest('button,input,select,a')) { open(record.dataset.workflowRow,record,{historyFirst:true}); return; }
        if (event.target.closest('[data-workflow-close]')) {
            event.preventDefault();
            if (companion?.panel.contains(event.target)) closeCompanion();
            else if (!closeQueueRequest()) close();
        }
        const reject = event.target.closest('[data-workflow-reject]');
        const approve = event.target.closest('[data-confirm-title]');
        if (approve?.form?.elements.review_reason) {
            const form = approve.form;
            form.action = form.dataset.approveAction;
            form.elements.review_reason.required = true;
            if (form.elements.reason) form.elements.reason.required = false;
        }
        if (reject) {
            const content = reject.closest('#workflowContent'); const form = content.querySelector('[data-workflow-decision]');
            const block = form.querySelector('[data-rejection-fields]');
            if (block.hidden) { block.hidden=false; block.querySelector('textarea').required=true; form.elements.review_reason.required=false; block.querySelector('textarea').focus(); reject.textContent='Confirm Rejection'; }
            else { form.action=reject.dataset.workflowReject; form.requestSubmit(); }
        }
    });
    document.addEventListener('change', event => {
        if (event.target.matches('[data-correction-submit]')) event.target.form.requestSubmit();
    });
    document.addEventListener('keydown', event => {
        const filter = event.target.closest('[data-correction-toolbar] details[open]');
        if (event.key === 'Escape' && filter && !filter.querySelector('.is-open')) {
            filter.open = false;
            filter.querySelector('summary').focus();
        }
        if ((event.key === 'Enter' || event.key === ' ') && event.target.matches('[data-workflow-row]')) { event.preventDefault();open(event.target.dataset.workflowRow,event.target,{historyFirst:true}); }
    });
    document.addEventListener('submit', async event => {
        const form=event.target;
        if (form.matches('[data-workflow-reminder]')) {
            event.preventDefault();
            const button = form.querySelector('button');
            if (button.disabled) return;
            const footer = form.closest('.pw-footer');
            const feedback = footer.querySelector('[data-reminder-feedback]');
            const last = footer.querySelector('[data-reminder-last]');
            button.disabled = true;
            feedback.textContent = 'Sending reminder…';
            try {
                const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {Accept: 'application/json'}});
                const data = await response.json();
                feedback.textContent = data.message || 'Unable to send reminder. Try again.';
                if (response.ok && data.sent_at) {
                    last.textContent = `Last manual reminder · ${data.sent_at}`;
                    last.hidden = false;
                }
            } catch {
                feedback.textContent = 'Unable to confirm that the reminder was sent. Try again.';
            } finally {
                button.disabled = false;
            }
            return;
        }
        if (!form.matches('[data-correction-form],[data-workflow-decision]')) return;
        event.preventDefault();
        if (form.dataset.submitting) return;
        const request = form.matches('[data-correction-form]');
        form.querySelector('[data-workflow-error]').hidden = true;
        form.querySelectorAll('[data-pw-error]').forEach(clearFieldError);
        if (request) {
            const changes = form.querySelector('.pw-options');
            const selected = changes.querySelectorAll('input:checked:not(:disabled)');
            if (form.elements.correction_type.value === 'CORRECT' && !selected.length) {
                focusError(fieldError(changes.querySelector('input'), 'Select at least one detail to correct.', changes));
                return;
            }
            if ([...selected].some(field => field.value === 'funeral_case_id') && !form.elements.funeral_case_id.value) {
                focusError(fieldError(form.querySelector('[data-correction-case-search]'), 'Select the correct case from the search results.'));
                return;
            }
            const invalid = [...form.querySelectorAll(':invalid')].filter(field => field.willValidate && field.getClientRects().length);
            invalid.forEach(field => fieldError(field, field.validationMessage));
            if (invalid.length) { focusError(invalid[0]); return; }
        }
        const reject = form.action.includes('/reject');
        const voidRequest = request && form.elements.correction_type.value === 'VOID_ONLY';
        const title = request ? (voidRequest ? 'Send duplicate void request?' : 'Send correction request?') : reject ? 'Reject this request?' : event.submitter?.dataset.confirmTitle || 'Confirm decision?';
        const copy = voidRequest ? form.querySelector('[data-void-explanation]').textContent
            : request ? 'The payment stays as recorded until the Branch Admin approves your request.'
            : reject ? 'The payment stays as recorded. The requester will see your reason.' : event.submitter?.dataset.confirmCopy || 'The payment stays as recorded.';
        const action = request ? (voidRequest ? 'Send Void Request' : 'Send Correction Request') : reject ? 'Reject Request' : event.submitter?.textContent.trim() || 'Confirm';
        if (!await confirm(title, copy, action, reject, companion?.panel.contains(form))) return;
        form.dataset.submitting='1';
        const button=event.submitter; const label=button?.textContent;
        if(button){button.disabled=true;button.textContent='Processing…';}
        try {
            const response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
            if (!response.ok) {
                const data=await response.json().catch(()=>({message:response.status===419?'Your session expired. Sign in again before submitting.':'The request could not be processed. Please try again.'}));
                if (data.errors?.preview_token) form.querySelector('[data-workflow-refresh]').hidden = false;
                let firstError;
                let unmappedError;
                for (const [name, messages] of Object.entries(data.errors || {})) {
                    const group = request && (name === 'changes' || name.startsWith('changes.') || name === 'correction_type');
                    const anchor = group ? form.querySelector(name === 'correction_type' ? '.pw-request-choices' : '.pw-options') : null;
                    const field = anchor?.querySelector('input') ?? (name === 'funeral_case_id' ? form.querySelector('[data-correction-case-search]') : form.elements.namedItem(name));
                    if (field?.getClientRects?.().length && !field.disabled) {
                        fieldError(field, messages[0], anchor || field);
                        firstError ??= field;
                    } else unmappedError ??= messages[0];
                }
                if (firstError && !unmappedError) { focusError(firstError); return; }
                if (unmappedError) throw new Error(unmappedError);
                throw new Error(data.message || 'The request could not be processed. Review the fields.');
            }
            form.dataset.dirty='';
            if(response.redirected) location.assign(response.url);
            else { const data=await response.json(); location.assign(data.redirect); }
        } catch(error) {
            const message=form.querySelector('[data-workflow-error]');if(message){message.hidden=false;message.textContent=error.message;message.scrollIntoView({block:'nearest'});}
        } finally { delete form.dataset.submitting;if(button){button.disabled=false;button.textContent=label;} }
    });
    const shownNotices = new Set();
    const noticeTones = {
        requested: 'info', approved: 'success', rejected: 'error',
        overdue: 'warning', overdue_reminder: 'warning', reminded: 'warning',
    };
    const noticeText = item => [item.title, item.branch, item.reference].filter(Boolean).join(' · ');
    const sidebarCount = document.querySelector('[data-sidebar-correction-count]');
    const seenKey = sidebarCount ? `payment-correction-sidebar-seen:${sidebarCount.dataset.user}` : null;
    let seenRequest = 0;
    function updateSidebarCount(pending, latest) {
        if (!sidebarCount) return;
        const onMonitoring = sidebarCount.dataset.current === 'true';
        try {
            seenRequest = Math.max(seenRequest, Number(localStorage.getItem(seenKey)) || 0);
            if (onMonitoring && latest > seenRequest) localStorage.setItem(seenKey, String(latest));
        } catch { /* The current page can still acknowledge the count. */ }
        if (onMonitoring) seenRequest = Math.max(seenRequest, latest);
        sidebarCount.dataset.latestPendingId = latest;
        sidebarCount.dataset.attention = String(pending > 0 && !onMonitoring && latest > seenRequest);
        sidebarCount.textContent = pending;
        sidebarCount.hidden = pending === 0;
        sidebarCount.setAttribute('aria-label', `${pending} pending correction requests`);
    }
    const syncSidebarCount = () => {
        if (sidebarCount) updateSidebarCount(Number(sidebarCount.textContent), Number(sidebarCount.dataset.latestPendingId));
    };
    syncSidebarCount();
    window.addEventListener('storage', event => { if (event.key === seenKey) syncSidebarCount(); });
    let noticesRequest;
    async function refreshNotices() {
        const host = document.querySelector('[data-workflow-notices-url]');
        const url = host?.dataset.workflowNoticesUrl
            ?? document.querySelector('[data-workflow-toast-url]')?.dataset.workflowToastUrl;
        if (!url || document.hidden) return;
        noticesRequest?.abort();
        const request = new AbortController();
        noticesRequest = request;
        try {
            const response = await fetch(url, { signal: request.signal, cache: 'no-store', headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            if (request.signal.aborted) return;
            updateSidebarCount(data.pending, data.latest_pending_id);
            document.querySelectorAll('[data-workflow-pending]').forEach(count => {
                count.textContent = data.pending;
                count.dataset.attention = String(data.pending > 0);
                count.setAttribute('aria-label', `${data.pending} pending requests`);
            });
            if (host) updateBell(host, data);
            if (document.hidden) return;
            for (const item of [...data.items].reverse()) {
                const tone = noticeTones[item.event];
                const key = `payment-correction-toast:${sidebarCount?.dataset.user ?? ''}:${item.id}:${item.created_at}`;
                if (item.read || !tone || shownNotices.has(key) || !window.showPanelToast) continue;
                const storageName = tone === 'warning' ? 'localStorage' : 'sessionStorage';
                try {
                    if (window[storageName].getItem(key)) continue;
                } catch { /* In-memory tracking covers browsers without storage access. */ }
                window.showPanelToast(noticeText(item), tone, item.url);
                shownNotices.add(key);
                try { window[storageName].setItem(key, '1'); } catch { /* Already tracked on this page. */ }
            }
        } catch { /* Durable notices remain available on the next refresh. */ }
    }
    function updateBell(host, data) {
        host.querySelector('[data-workflow-unread]').textContent = data.unread ? `${data.unread} unread` : 'All read';
        const list = host.querySelector('[data-workflow-notice-list]');
        list.replaceChildren(...data.items.map(item => {
            const entry = document.createElement('div');
            entry.className = `pw-notice-entry tone-${item.tone || 'info'}${item.read ? ' is-read' : ''}`;
            entry.dataset.notificationEntry = '';
            entry.dataset.notificationKey = item.key;
            entry.dataset.unread = String(!item.read);
            const link = document.createElement('a');
            link.href = item.url;
            link.dataset.notificationOpen = '';
            if (item.event !== 'overdue') link.dataset.paymentWorkflow = '';
            const title = document.createElement('span');
            title.textContent = noticeText(item);
            const date = document.createElement('small');
            date.textContent = item.at;
            link.append(title, date);
            const dismiss = document.createElement('button');
            dismiss.type = 'button';
            dismiss.className = 'topbar-notification-dismiss';
            dismiss.dataset.notificationDismiss = '';
            dismiss.setAttribute('aria-label', `Dismiss ${item.title}`);
            dismiss.title = 'Dismiss notification';
            dismiss.innerHTML = '<i class="bi bi-x" aria-hidden="true"></i>';
            entry.append(link, dismiss);
            return entry;
        }));
        if (!data.items.length) list.textContent = 'No payment correction notifications yet.';
        const bell = host.closest('[data-notification]')?.querySelector('[data-notification-toggle]');
        if (!bell) return;
        let badge = bell.querySelector('.topbar-notification__count');
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'topbar-notification__count';
            badge.setAttribute('aria-hidden', 'true');
            bell.append(badge);
        }
        const reminderTotal = Number(bell.dataset.reminders) || 0;
        const total = reminderTotal + data.unread;
        bell.dataset.unreadTotal = String(total);
        badge.hidden = !total;
        badge.textContent = Math.min(99, total);
        bell.setAttribute('aria-label', total ? `Notifications, ${total} unread` : 'Notifications');
        const summary = host.closest('[data-notification]')?.querySelector('[data-notification-summary]');
        if (summary) summary.textContent = total ? `${total} unread update${total === 1 ? '' : 's'}` : 'All caught up';
        window.dispatchEvent(new Event('notification:tone-sync'));
    }
    setup(document);
    const deepLink = document.querySelector('[data-open-correction-url]');
    if (deepLink) {
        const url = new URL(location.href);
        url.searchParams.delete('open_correction');
        history.replaceState(history.state, '', url);
        open(deepLink.dataset.openCorrectionUrl, document.querySelector('[data-pm-section-action]'));
    } else {
        refreshNotices();
    }
    setInterval(refreshNotices,30000);
    document.querySelector('[data-notification-toggle]')?.addEventListener('click', refreshNotices);
    document.addEventListener('visibilitychange',()=>{if(!document.hidden)refreshNotices();});
    window.addEventListener('pageshow', event => { if (event.persisted) { syncSidebarCount(); refreshNotices(); } });
}

function installCorrectionSearch() {
    let matchesRequest, resultsRequest, timer;
    const find = () => document.querySelector('[data-correction-toolbar]');
    const matches = form => form.querySelector('[data-correction-matches]');
    const hide = form => {
        clearTimeout(timer);
        matchesRequest?.abort();
        matches(form).hidden = true;
        form.querySelector('[data-correction-search]').setAttribute('aria-expanded', 'false');
    };
    const urlFor = form => {
        const url = new URL(form.action);
        url.search = new URLSearchParams(new FormData(form));
        return url;
    };
    const load = async (url, signal) => {
        const response = await fetch(url, { signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) throw new Error('Correction requests unavailable');
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        if (!page.querySelector('[data-correction-results]')) throw new Error('Correction results unavailable');
        return page;
    };
    const renderMatches = (form, rows, query, emptyMessage = 'No matching correction requests found.') => {
        const panel = matches(form);
        const needle = query.toLowerCase();
        panel.replaceChildren(...rows.slice(0, 5).map(row => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'pm-search-match';
            button.dataset.correctionMatch = row.dataset.requestNumber;
            const values = [row.dataset.requestNumber, row.dataset.paymentNumber, row.dataset.replacementPaymentNumber,
                row.dataset.caseCode, row.dataset.receiptNumber, row.dataset.transactionReference, row.dataset.approvalCode].filter(Boolean);
            button.title = values.join(' · ');
            const details = document.createElement('span');
            details.className = 'pm-match-name';
            values.forEach((value, index) => {
                const field = document.createElement(index === 0 ? 'strong' : 'span');
                if (index === 0) field.className = 'pm-match-code';
                if (index > 1) details.append('·');
                let offset = 0;
                let match;
                while ((match = value.toLowerCase().indexOf(needle, offset)) !== -1) {
                    field.append(value.slice(offset, match));
                    const highlight = document.createElement('mark');
                    offset = match + needle.length;
                    highlight.textContent = value.slice(match, offset);
                    field.append(highlight);
                }
                field.append(value.slice(offset));
                (index === 0 ? button : details).append(field);
            });
            if (details.childElementCount) button.append(details);
            return button;
        }));
        if (!panel.childElementCount && emptyMessage) {
            const status = document.createElement('p');
            status.className = 'pm-search-match pm-search-match--status';
            status.setAttribute('role', 'status');
            status.textContent = emptyMessage;
            panel.append(status);
        }
        panel.hidden = false;
        form.querySelector('[data-correction-search]').setAttribute('aria-expanded', String(!panel.hidden));
    };
    document.addEventListener('input', event => {
        const input = event.target.closest('[data-correction-search]');
        if (!input) return;
        const form = input.form;
        const query = input.value.trim();
        form.querySelector('[data-correction-clear]').hidden = !query;
        hide(form);
        if (query.length < 2) return;
        const isRequestNumber = query.charAt(0) === '#' && /^[0-9]+$/.test(query.slice(1));
        const loaded = [...form.closest('.pw-request-list').querySelectorAll('[data-request-number]')];
        const localMatches = loaded.filter(row => isRequestNumber
            ? row.dataset.requestNumber === query
            : [row.dataset.paymentNumber, row.dataset.replacementPaymentNumber, row.dataset.caseCode, row.dataset.receiptNumber,
                row.dataset.transactionReference, row.dataset.approvalCode]
                .some(value => value?.toLowerCase().includes(query.toLowerCase())));
        renderMatches(form, localMatches, query, localMatches.length ? '' : 'Searching correction requests…');
        timer = setTimeout(async () => {
            const controller = new AbortController();
            matchesRequest = controller;
            const url = new URL(location.href);
            url.searchParams.delete('page');
            url.searchParams.set('correction_q', query);
            try {
                const page = await load(url, controller.signal);
                if (controller.signal.aborted || !input.isConnected || input.value.trim() !== query) return;
                if (!matches(form).contains(document.activeElement)) {
                    const found = [...page.querySelectorAll('[data-request-number]')];
                    renderMatches(form, found, query, found.length ? '' : 'No matching correction requests found.');
                }
            } catch (error) {
                if (error.name !== 'AbortError' && input.isConnected && input.value.trim() === query && !localMatches.length) {
                    renderMatches(form, [], query, 'Search unavailable. Press Search to try again.');
                }
            }
        }, 300);
    });
    const update = async (form, url) => {
        hide(form);
        resultsRequest?.abort();
        const controller = new AbortController();
        resultsRequest = controller;
        const section = form.closest('.pw-request-list');
        const results = section.querySelector('[data-correction-results]');
        const status = section.querySelector('[data-correction-search-status]');
        results.setAttribute('aria-busy', 'true');
        status.textContent = 'Loading correction requests…';
        try {
            const page = await load(url, controller.signal);
            if (controller.signal.aborted || !form.isConnected) return;
            results.replaceWith(page.querySelector('[data-correction-results]'));
            document.querySelector('[data-correction-pagination]').replaceWith(page.querySelector('[data-correction-pagination]'));
            form.querySelector('[data-correction-applied]').replaceWith(page.querySelector('[data-correction-applied]'));
            form.querySelector('.pm-more-filters').replaceWith(page.querySelector('[data-correction-toolbar] .pm-more-filters'));
            const input = form.querySelector('[data-correction-search]');
            input.value = url.searchParams.get('correction_q') ?? '';
            form.querySelector('[data-correction-clear]').hidden = !input.value;
            document.dispatchEvent(new CustomEvent('panel-ui:reset'));
            history.pushState(null, '', url);
            status.textContent = 'Correction requests updated.';
        } catch {
            if (!controller.signal.aborted) location.assign(url);
        } finally {
            if (!controller.signal.aborted) results.removeAttribute('aria-busy');
        }
    };
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!form.matches('[data-correction-toolbar]')) return;
        event.preventDefault();
        const input = form.querySelector('[data-correction-search]');
        input.value = input.value.trim();
        const url = urlFor(form);
        if (!event.submitter?.matches('[data-correction-apply]')) {
            const applied = new URL(location.href).searchParams;
            for (const { name: filter } of form.querySelectorAll('[data-correction-filter]')) {
                if (applied.has(filter)) url.searchParams.set(filter, applied.get(filter));
                else url.searchParams.delete(filter);
            }
        }
        update(form, url);
    });
    document.addEventListener('click', event => {
        const form = find();
        if (!form) return;
        const clear = event.target.closest('[data-correction-clear]');
        const choice = event.target.closest('[data-correction-match]');
        const chip = event.target.closest('[data-correction-remove]');
        const link = event.target.closest('[data-correction-clear-filters], [data-correction-pagination] a[href]');
        const input = form.querySelector('[data-correction-search]');
        if (chip) {
            const url = new URL(location.href);
            url.searchParams.delete(chip.dataset.correctionRemove);
            url.searchParams.delete('page');
            update(form, url);
            return;
        }
        if (link && !event.defaultPrevented && event.button === 0 && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey) {
            event.preventDefault();
            update(form, new URL(link.href));
            return;
        }
        if (clear || choice) {
            input.value = choice?.dataset.correctionMatch ?? '';
            form.querySelector('[data-correction-clear]').hidden = !input.value;
            input.focus();
            form.requestSubmit();
        } else if (!event.target.closest('.shared-record-search')) hide(form);
    });
    document.addEventListener('keydown', event => {
        const form = find();
        if (!form || !form.contains(event.target)) return;
        const input = form.querySelector('[data-correction-search]');
        const panel = matches(form);
        if (panel.hidden || (event.target !== input && !panel.contains(event.target))) return;
        if (event.key === 'Escape') { hide(form); input.focus(); }
        if (!['ArrowUp', 'ArrowDown'].includes(event.key)) return;
        event.preventDefault();
        const options = [input, ...panel.querySelectorAll('button')];
        const index = options.indexOf(event.target) + (event.key === 'ArrowDown' ? 1 : -1);
        options[Math.max(0, Math.min(index, options.length - 1))].focus();
    });
}
