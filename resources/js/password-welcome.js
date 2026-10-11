const welcome = document.getElementById('password-welcome-dialog');
if (welcome) {
    const button = welcome.querySelector('[data-dismiss-password-welcome]');
    button.addEventListener('click', () => welcome.close());
    welcome.showModal();
    button.focus();
    // Do not show the confirmation again when returning through browser history.
    window.addEventListener('pagehide', () => { if (welcome.open) welcome.close(); });
}
