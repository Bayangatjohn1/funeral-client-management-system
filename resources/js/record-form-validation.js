// Keep native constraints visible beside the existing Branch/User fields.
const recordForms = '#branchCreateForm,#branchCreateStandaloneForm,#branchEditForm,#userCreateForm,#userEditForm';
const labels = { first_name: 'First name', middle_name: 'Middle name', last_name: 'Last name', name: 'Name',
    email: 'Email address', password: 'Password', current_password: 'Your current password',
    branch_name: 'Branch name', address: 'Address', branch_id: 'Branch', reason: 'Reason for System Admin access',
    contact_number: 'Contact Number', confirmed: 'System Admin access confirmation' };

document.addEventListener('invalid', event => {
    const input = event.target;
    const form = input.closest(recordForms);
    if (!form) return;
    event.preventDefault();
    const label = labels[input.name] || 'This selection';
    let message = input.validationMessage;
    if (!input.validity.customError) {
        if (input.validity.valueMissing) message = `${label} is required.`;
        else if (input.validity.tooShort) message = `${label} must be at least ${input.minLength} characters.`;
        else if (input.validity.tooLong) message = `${label} must be ${input.maxLength} characters or fewer.`;
        else if (input.validity.typeMismatch && input.type === 'email') message = 'Please enter a valid email address.';
    }
    let error = [...form.querySelectorAll('[data-field-error]')].find(node => node.dataset.fieldError === input.name);
    if (!error) {
        error = document.createElement('p');
        error.className = 'form-error'; error.dataset.fieldError = input.name;
        (input.closest('.password-field-shell')?.parentElement || input.parentElement).append(error);
    }
    error.textContent = message; error.classList.remove('hidden');
    error.id ||= `${form.id}-${input.name}-error`;
    input.setAttribute('aria-invalid', 'true');
    input.setAttribute('aria-describedby', error.id);
}, true);

document.addEventListener('input', event => {
    const input = event.target;
    const form = input.closest(recordForms);
    if (!form || !input.validity?.valid) return;
    input.removeAttribute('aria-invalid');
    [...form.querySelectorAll('[data-field-error]')].filter(node => node.dataset.fieldError === input.name).forEach(error => {
        error.textContent = ''; error.classList.add('hidden');
    });
});
