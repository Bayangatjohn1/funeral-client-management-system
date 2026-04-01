
(() => {
    const f = document.getElementById('intakeWizardForm');
    if (!f) return;

    const panels = [...document.querySelectorAll('.wizard-panel')];
    const tabs = [...document.querySelectorAll('.wizard-tab')];
    const prev = document.getElementById('wizardPrev');
    const next = document.getElementById('wizardNext');
    const save = document.getElementById('saveIntakeRecord');
    const branch = document.getElementById('branch_id');
    const branchError = document.getElementById('branch_error');
    const branchBtns = [...document.querySelectorAll('.branch-toggle')];
    const nextCode = document.getElementById('next_case_code');
    const nextMap = @json($nextCodeMap ?? []);
    const defCode = @json($nextCode ?? 'FC0001');
    const requestDate = document.getElementById('service_requested_at');
    const requestDateDisplay = document.getElementById('service_requested_display');
    const reporterBox = document.getElementById('external_reporter_box');
    const reporterName = document.getElementById('reporter_name');
    const reportedAt = document.getElementById('reported_at');
    const born = document.getElementById('born');
    const died = document.getElementById('died');
    const age = document.getElementById('age');
    const clientAddr = document.getElementById('client_address');
    const deceasedAddr = document.getElementById('deceased_address');
    const wakeDays = document.getElementById('wake_days');
    const funeral = document.getElementById('funeral_service_at');
    const interment = document.getElementById('interment_at');
    const intermentErr = document.getElementById('interment_at_error');
    const senior = document.getElementById('senior_citizen_status');
    const seniorIdWrap = document.getElementById('senior_id_wrap');
    const seniorId = document.getElementById('senior_citizen_id_number');
    const pkgRadios = [...document.querySelectorAll('.package-radio')];
    const pkgCards = [...document.querySelectorAll('.package-card')];
    const pkgAmount = document.getElementById('package_amount');
    const packageError = document.getElementById('package_error');
    const addAmt = document.getElementById('additional_service_amount');
    const taxRate = document.getElementById('tax_rate');
    const taxAmountDisplay = document.getElementById('tax_amount_display');
    const additionalServices = document.getElementById('additional_services');
    const autoDiscountType = document.getElementById('auto_discount_type');
    const autoDiscountAmount = document.getElementById('auto_discount_amount');
    const discountHelpSecondary = document.getElementById('discount_help_text_secondary');
    const mark = document.getElementById('mark_as_paid');
    const payWrap = document.getElementById('payment_form_fields');
    const payTypeRadios = [...document.querySelectorAll('.payment-type-radio')];
    const payTypeCards = [...document.querySelectorAll('.payment-type-card')];
    const paymentTypeError = document.getElementById('payment_type_error');
    const amountPaid = document.getElementById('amount_paid');
    const paidAt = document.getElementById('paid_at');
    const payHint = document.getElementById('payment_amount_hint');
    const summaryPackage = document.getElementById('summary_package_price');
    const summaryAdd = document.getElementById('summary_additional');
    const summarySubtotal = document.getElementById('summary_subtotal');
    const summaryDiscountSource = document.getElementById('summary_discount_source');
    const summaryDiscount = document.getElementById('summary_discount');
    const summaryTax = document.getElementById('summary_tax');
    const summaryTotal = document.getElementById('summary_total');
    const summaryStatus = document.getElementById('summary_payment_status');
    const summaryBalance = document.getElementById('summary_balance');
    const paymentStatusPreview = document.getElementById('payment_status_preview');
    const paymentPaidPreview = document.getElementById('payment_paid_preview');
    const paymentBalancePreview = document.getElementById('payment_balance_preview');
    const reviewClient = document.getElementById('review_client');
    const reviewDeceased = document.getElementById('review_deceased');
    const reviewService = document.getElementById('review_service');
    const reviewPackage = document.getElementById('review_package');
    const reviewBilling = document.getElementById('review_billing');
    const reviewPayment = document.getElementById('review_payment');
    const inclusions = document.getElementById('selected_package_inclusions');
    const freebies = document.getElementById('selected_package_freebies');
    const reviewEditButtons = [...document.querySelectorAll('.review-edit')];
    const seniorPct = {{ (float) ($seniorDiscountPercent ?? config('funeral.senior_discount_percent', 20)) }};
    const isOtherEntryMode = @json($isOtherEntryMode);
    const otherBranchWindowClosed = @json($otherBranchWindowClosed);

    const totalSteps = panels.length;
    const initialStep = Math.max(1, Math.min(totalSteps, Number(@json($initialStep ?? 1))));
    let step = 1;
    // Use local date (not UTC) to avoid â€œfuture dateâ€ false positives in non-UTC timezones.
    const today = new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD

    const num = (value) => Number.parseFloat(value || 0) || 0;
    const fmt = (value) => num(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const pkg = () => pkgRadios.find((radio) => radio.checked);
    const payNow = () => !mark ? false : (mark.type === 'checkbox' ? mark.checked : String(mark.value) === '1');
    const payType = () => payTypeRadios.find((radio) => radio.checked)?.value || document.getElementById('payment_type')?.value || '';
    const list = (value) => String(value || '').split(/\\r?\\n|,|;/).map((item) => item.trim()).filter(Boolean);
    const textOrDash = (value) => String(value || '').trim() || '-';
    const formatDateOnly = (value) => {
        if (!value) return '-';
        const parsed = new Date(`${value}T00:00:00`);
        return Number.isNaN(parsed.getTime()) ? '-' : parsed.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
    };
    const formatDateTime = (value) => {
        if (!value) return '-';
        const parsed = new Date(value);
        return Number.isNaN(parsed.getTime()) ? '-' : parsed.toLocaleString(undefined, { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    };
    const detailRow = (label, value) => `<div class="flex items-start justify-between gap-4"><dt class="text-slate-500">${label}</dt><dd class="text-right font-medium text-slate-700">${value}</dd></div>`;
    const setHidden = (wrap, input, hidden) => {
        if (!wrap || !input) return;
        wrap.classList.toggle('hidden', hidden);
        input.disabled = hidden;
        if (hidden) input.setCustomValidity('');
    };
    const clearFieldMessage = (field) => field.setCustomValidity('');
    const labelFor = (field) => field.dataset.label || field.getAttribute('placeholder') || 'this field';
    const branchCode = () => branchBtns.find((button) => String(button.dataset.branchId) === String(branch.value))?.dataset.branchCode || '-';
    const hasSeniorDiscount = () => senior?.value === '1' && Boolean(String(seniorId?.value || '').trim());
    const autoDiscountMeta = () => {
        if (hasSeniorDiscount()) {
            return {
                type: 'Senior Citizen Discount',
                source: `Senior (${seniorPct}%)`,
                amount: num(pkgAmount?.value) * (seniorPct / 100),
                message: `Senior Citizen discount is applied automatically at ${seniorPct}% of the package price.`,
            };
        }
        if (senior?.value === '1' && !String(seniorId?.value || '').trim()) {
            return {
                type: 'Senior Citizen Discount',
                source: 'Pending Senior ID',
                amount: 0,
                message: 'Senior Citizen ID is required to apply the discount.',
            };
        }
        return {
            type: 'None',
            source: 'None',
            amount: 0,
            message: 'No automatic discount applies when Senior Citizen is marked No.',
        };
    };

    const syncBranch = () => {
        branchBtns.forEach((button) => {
            const active = String(button.dataset.branchId) === String(branch.value);
            button.classList.toggle('bg-slate-700', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('border-slate-200', !active);
        });
        if (nextCode) nextCode.textContent = nextMap[String(branch.value)] || defCode;
        reporterBox?.classList.toggle('hidden', !isOtherEntryMode);
        if (reporterName) reporterName.required = isOtherEntryMode;
        if (reportedAt) reportedAt.required = isOtherEntryMode;
    };

    const syncRequestDate = () => {
        if (!requestDateDisplay || !requestDate?.value) return;
        requestDateDisplay.textContent = formatDateOnly(requestDate.value);
    };

    const syncAge = () => {
        if (!born?.value || !died?.value) {
            if (age) age.value = '';
            return;
        }
        const birth = new Date(`${born.value}T00:00:00`);
        const death = new Date(`${died.value}T00:00:00`);
        if (Number.isNaN(birth.getTime()) || Number.isNaN(death.getTime()) || death < birth) {
            age.value = '';
            return;
        }
        let years = death.getFullYear() - birth.getFullYear();
        if (death.getMonth() < birth.getMonth() || (death.getMonth() === birth.getMonth() && death.getDate() < birth.getDate())) years -= 1;
        age.value = String(years);
    };

    const syncDateConstraints = () => {
        if (died) died.max = today;
        if (funeral) funeral.min = died?.value || '';
        if (paidAt && died?.value) paidAt.min = `${died.value}T00:00`;
    };

    const syncControls = () => {
        if (deceasedAddr && clientAddr && deceasedAddr.dataset.manual !== '1') deceasedAddr.value = clientAddr.value;
        setHidden(seniorIdWrap, seniorId, senior?.value !== '1');
        if (seniorId) seniorId.required = senior?.value === '1';
        if (payWrap && mark?.type === 'checkbox') payWrap.classList.toggle('hidden', !payNow());
        if (amountPaid) {
            amountPaid.disabled = !payNow();
            amountPaid.readOnly = payType() === 'FULL';
        }
        payTypeCards.forEach((card) => {
            const active = card.querySelector('.payment-type-radio')?.checked;
            card.classList.toggle('border-slate-700', active);
            card.classList.toggle('ring-2', active);
        });
        const helpText = autoDiscountMeta().message;
        if (discountHelpSecondary) discountHelpSecondary.textContent = helpText;
    };

    const discount = () => {
        const meta = autoDiscountMeta();
        return {
            amount: meta.amount,
            source: meta.source,
            type: meta.type,
            message: meta.message,
        };
    };

    const totals = () => {
        const packagePrice = num(pkgAmount?.value);
        const additional = num(addAmt?.value);
        const subtotal = packagePrice + additional;
        const disc = discount();
        const taxableBase = Math.max(subtotal - Math.min(disc.amount, subtotal), 0);
        const rate = Math.max(0, Math.min(num(taxRate?.value), 100));
        const tax = Math.max(taxableBase * (rate / 100), 0);
        const total = Math.max(taxableBase + tax, 0);
        if (payNow() && payType() === 'FULL' && amountPaid) amountPaid.value = total > 0 ? total.toFixed(2) : '';
        const paid = payNow() ? num(amountPaid?.value) : 0;
        const balance = Math.max(total - paid, 0);
        const status = !payNow() || paid <= 0 ? 'UNPAID' : paid < total ? 'PARTIAL' : 'PAID';
        return { packagePrice, additional, subtotal, disc, tax, total, paid, balance, status, rate };
    };

    const renderPkg = () => {
        const selected = pkg();
        pkgCards.forEach((card) => {
            const active = card.querySelector('.package-radio')?.checked;
            card.classList.toggle('border-slate-700', active);
            card.classList.toggle('ring-2', active);
        });
        if (pkgAmount) pkgAmount.value = selected?.dataset.price || '';
        if (packageError) packageError.classList.add('hidden');
        inclusions.innerHTML = list(selected?.dataset.inclusions).map((item) => `<li>${item}</li>`).join('') || '<li class="text-slate-400">Select a package to view inclusions.</li>';
        freebies.innerHTML = list(selected?.dataset.freebies).map((item) => `<li>${item}</li>`).join('') || '<li class="text-slate-400">Package freebies and notes will appear here.</li>';
    };

    const renderReview = () => {
        const selected = pkg();
        const t = totals();
        reviewClient.innerHTML = [
            detailRow('Branch', branchCode()),
            detailRow('Client Name', textOrDash(f.elements.client_name.value)),
            detailRow('Relationship', textOrDash(f.elements.client_relationship.value)),
            detailRow('Contact Number', textOrDash(f.elements.client_contact_number.value)),
            detailRow('Address', textOrDash(f.elements.client_address.value)),
            ...(isOtherEntryMode ? [
                detailRow('Reporter Name', textOrDash(f.elements.reporter_name?.value)),
                detailRow('Reporter Contact', textOrDash(f.elements.reporter_contact?.value)),
                detailRow('Reported Date', formatDateTime(f.elements.reported_at?.value)),
            ] : []),
        ].join('');

        reviewDeceased.innerHTML = [
            detailRow('Deceased Name', textOrDash(f.elements.deceased_name.value)),
            detailRow('Address', textOrDash(f.elements.deceased_address.value)),
            detailRow('Birthdate', formatDateOnly(f.elements.born.value)),
            detailRow('Age', textOrDash(f.elements.age.value)),
            detailRow('Date of Death', formatDateOnly(f.elements.died.value)),
            detailRow('Senior Citizen', senior?.value === '1' ? `Yes${seniorId?.value ? ` - ${seniorId.value}` : ''}` : 'No'),
        ].join('');

        reviewService.innerHTML = [
            detailRow('Service Type', textOrDash(f.elements.service_type.value)),
            detailRow('Wake Location', textOrDash(f.elements.wake_location.value)),
            detailRow('Funeral Service Date', formatDateOnly(f.elements.funeral_service_at.value)),
            detailRow('Interment / Burial Date', formatDateTime(f.elements.interment_at.value)),
            detailRow('Place of Interment', textOrDash(f.elements.place_of_cemetery.value)),
            detailRow('Case Status', textOrDash(f.elements.case_status.value)),
            detailRow('Wake Days', textOrDash(f.elements.wake_days.value)),
        ].join('');

        reviewPackage.innerHTML = [
            detailRow('Selected Package', selected?.closest('.package-card')?.querySelector('.package-title')?.textContent?.trim() || '-'),
            detailRow('Package Price', `PHP ${fmt(t.packagePrice)}`),
            detailRow('Inclusions', list(selected?.dataset.inclusions).join(', ') || '-'),
            detailRow('Freebies / Notes', list(selected?.dataset.freebies).join(', ') || '-'),
        ].join('');

        reviewBilling.innerHTML = [
            detailRow('Additional Services', textOrDash(additionalServices?.value)),
            detailRow('Additional Charges', `PHP ${fmt(t.additional)}`),
            detailRow('Discount Type', t.disc.type),
            detailRow('Discount Amount', `PHP ${fmt(t.disc.amount)}`),
            detailRow('Tax Amount', `PHP ${fmt(t.tax)}`),
            detailRow('Total Amount', `PHP ${fmt(t.total)}`),
        ].join('');

        reviewPayment.innerHTML = [
            detailRow('Payment Recorded', payNow() ? 'Yes' : 'No'),
            detailRow('Payment Type', isOtherEntryMode ? 'FULL (Required)' : (payType() || '-')),
            detailRow('Amount Paid', `PHP ${fmt(t.paid)}`),
            detailRow('Remaining Balance', `PHP ${fmt(t.balance)}`),
            detailRow('Payment Date', formatDateTime(paidAt?.value)),
            detailRow('Payment Status', t.status),
        ].join('');
    };

    const render = () => {
        const t = totals();
        summaryPackage.textContent = fmt(t.packagePrice);
        summaryAdd.textContent = fmt(t.additional);
        summarySubtotal.textContent = fmt(t.subtotal);
        summaryDiscountSource.textContent = t.disc.source;
        summaryDiscount.textContent = fmt(t.disc.amount);
        summaryTax.textContent = fmt(t.tax);
        summaryTotal.textContent = fmt(t.total);
        summaryStatus.textContent = t.status;
        summaryBalance.textContent = fmt(t.balance);
        if (taxAmountDisplay) taxAmountDisplay.value = `PHP ${fmt(t.tax)}`;
        if (taxRate) taxRate.value = t.rate.toFixed(2);
        if (autoDiscountType) autoDiscountType.value = t.disc.type;
        if (autoDiscountAmount) autoDiscountAmount.value = `PHP ${fmt(t.disc.amount)}`;
        if (discountHelpSecondary) discountHelpSecondary.textContent = t.disc.message;
        if (paymentStatusPreview) paymentStatusPreview.textContent = t.status;
        if (paymentPaidPreview) paymentPaidPreview.textContent = fmt(t.paid);
        if (paymentBalancePreview) paymentBalancePreview.textContent = fmt(t.balance);
        if (payHint) {
            payHint.textContent = payType() === 'FULL'
                ? 'Full payment must equal the total amount due.'
                : payType() === 'PARTIAL'
                    ? 'Partial payment must be less than the total amount due.'
                    : (mark?.type === 'checkbox' && !payNow()
                        ? 'Leave payment unchecked if the client has not paid yet.'
                        : 'Select full or partial payment and enter the amount received.');
        }
        renderReview();
    };

    const clearMessages = () => {
        branchError?.classList.add('hidden');
        packageError?.classList.add('hidden');
        paymentTypeError?.classList.add('hidden');
        intermentErr?.classList.add('hidden');
        [...f.querySelectorAll('input, select, textarea')].forEach(clearFieldMessage);
    };

    const validatePanelFields = (panel) => {
        for (const field of [...panel.querySelectorAll('input, select, textarea')].filter((element) => element.type !== 'hidden' && !element.disabled)) {
            clearFieldMessage(field);
            const value = typeof field.value === 'string' ? field.value.trim() : field.value;
            if (field.required && !value && field.type !== 'checkbox' && field.type !== 'radio') {
                const message = field.tagName === 'SELECT' || field.type === 'date' || field.type === 'datetime-local'
                    ? `Please select ${labelFor(field).toLowerCase()}.`
                    : `Please enter ${labelFor(field).toLowerCase()}.`;
                field.setCustomValidity(message);
                field.reportValidity();
                return false;
            }
            if (!field.checkValidity()) {
                field.reportValidity();
                return false;
            }
        }
        return true;
    };

    const validate = (targetStep) => {
        clearMessages();
        const panel = panels.find((element) => Number(element.dataset.step) === targetStep);
        if (!panel) return true;
        if (!validatePanelFields(panel)) return false;

        if (targetStep === 1 && !branch.value) {
            branchError.classList.remove('hidden');
            branchError.textContent = 'Please select a branch before proceeding.';
            return false;
        }
        if (targetStep === 2 && died?.value) {
            if (new Date(`${died.value}T00:00:00`) > new Date(`${today}T23:59:59`)) {
                died.setCustomValidity('Date of death cannot be in the future.');
                died.reportValidity();
                return false;
            }
        }
        if (targetStep === 4 && funeral?.value && interment?.value && new Date(interment.value) <= new Date(`${funeral.value}T00:00:00`)) {
            intermentErr.classList.remove('hidden');
            intermentErr.textContent = 'Interment date must be after the funeral service date.';
            return false;
        }
        if (targetStep === 4 && died?.value && funeral?.value && new Date(`${funeral.value}T00:00:00`) < new Date(`${died.value}T00:00:00`)) {
            funeral.setCustomValidity('Funeral service date must be on or after the date of death.');
            funeral.reportValidity();
            return false;
        }
        if (targetStep === 3 && !pkg()) {
            packageError.classList.remove('hidden');
            return false;
        }
        if (targetStep === 6 && payNow()) {
            const t = totals();
            if (!payType()) {
                paymentTypeError?.classList.remove('hidden');
                return false;
            }
            if (!amountPaid?.value) {
                amountPaid.setCustomValidity('Please enter amount paid.');
                amountPaid.reportValidity();
                return false;
            }
            if (!paidAt?.value) {
                paidAt.setCustomValidity('Please select payment date.');
                paidAt.reportValidity();
                return false;
            }
            if (payType() === 'FULL' && t.paid < t.total) {
                amountPaid.setCustomValidity('Full payment must match the total amount due.');
                amountPaid.reportValidity();
                return false;
            }
            if (payType() === 'PARTIAL' && t.paid >= t.total) {
                amountPaid.setCustomValidity('Partial payment must be less than the total amount due.');
                amountPaid.reportValidity();
                return false;
            }
        }
        if (targetStep === 7) {
            const confirmBox = document.getElementById('confirm_review');
            if (confirmBox && !confirmBox.checked) {
                confirmBox.setCustomValidity('Please confirm that the information is correct.');
                confirmBox.reportValidity();
                return false;
            }
        }
        return true;
    };

    const go = (targetStep) => {
        step = Math.max(1, Math.min(totalSteps, targetStep));
        panels.forEach((panel) => panel.classList.toggle('hidden', Number(panel.dataset.step) !== step));
        tabs.forEach((tab) => {
            const active = Number(tab.dataset.step) === step;
            tab.classList.toggle('bg-slate-700', active);
            tab.classList.toggle('text-white', active);
            tab.classList.toggle('text-slate-500', !active);
        });
        prev.disabled = step === 1;
        next.classList.toggle('hidden', step === totalSteps);
        save.classList.toggle('hidden', step !== totalSteps);
        next.textContent = step === totalSteps - 1 ? 'Review' : 'Next';
        if (step === totalSteps) renderReview();
    };

    tabs.forEach((tab) => tab.addEventListener('click', () => {
        const targetStep = Number(tab.dataset.step);
        if (targetStep > step) {
            for (let index = step; index < targetStep; index += 1) {
                if (!validate(index)) return;
            }
        }
        go(targetStep);
    }));

    reviewEditButtons.forEach((button) => button.addEventListener('click', () => go(Number(button.dataset.jumpStep))));

    prev.addEventListener('click', () => go(step - 1));
    next.addEventListener('click', () => {
        if (!validate(step)) return;
        go(step + 1);
    });

    branchBtns.forEach((button) => button.addEventListener('click', () => {
        branch.value = button.dataset.branchId;
        syncBranch();
        render();
    }));

    if (deceasedAddr && clientAddr && String(deceasedAddr.value || '').trim() !== '' && deceasedAddr.value !== clientAddr.value) {
        deceasedAddr.dataset.manual = '1';
    }

    clientAddr?.addEventListener('input', syncControls);
    deceasedAddr?.addEventListener('input', () => {
        deceasedAddr.dataset.manual = deceasedAddr.value ? '1' : '';
    });
    born?.addEventListener('change', () => {
        syncAge();
        syncDateConstraints();
        render();
    });
    died?.addEventListener('change', () => {
        syncAge();
        syncDateConstraints();
        render();
    });
    interment?.addEventListener('change', render);
    wakeDays?.addEventListener('input', () => {
        wakeDays.dataset.manual = wakeDays.value ? '1' : '';
    });

    [senior, seniorId, addAmt, taxRate, amountPaid, paidAt].forEach((element) => {
        element?.addEventListener('input', () => {
            clearFieldMessage(element);
            syncControls();
            render();
        });
        element?.addEventListener('change', () => {
            clearFieldMessage(element);
            syncControls();
            render();
        });
    });

    if (mark?.type === 'checkbox') {
        mark.addEventListener('change', () => {
            syncControls();
            render();
        });
    }

    payTypeRadios.forEach((radio) => radio.addEventListener('change', () => {
        syncControls();
        render();
    }));

    pkgRadios.forEach((radio) => radio.addEventListener('change', () => {
        renderPkg();
        syncControls();
        render();
    }));

    document.querySelectorAll('[data-validate]').forEach((element) => element.addEventListener('input', () => {
        clearFieldMessage(element);
        if (element.dataset.validate === 'letters-spaces') element.value = element.value.replace(/[^A-Za-z\s.'-]/g, '');
        if (element.dataset.validate === 'digits') element.value = element.value.replace(/\D/g, '');
    }));

    f.addEventListener('submit', (event) => {
        if (isOtherEntryMode && otherBranchWindowClosed) {
            event.preventDefault();
            return;
        }
        for (let index = 1; index <= totalSteps - 1; index += 1) {
            if (!validate(index)) {
                event.preventDefault();
                go(index);
                return;
            }
        }
    });

    syncBranch();
    syncRequestDate();
    syncControls();
    syncAge();
    syncDateConstraints();
    renderPkg();
    render();
    go(initialStep);
})();
