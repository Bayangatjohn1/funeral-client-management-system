# Password recovery deployment

All roles can recover through their account email. A valid emailed reset token proves inbox ownership; successful reset marks that address verified. Reset retains MFA and recovery codes, revokes sessions and remember tokens, and records a security audit. It does not sign the user in or reactivate disabled accounts.

Configure a verified Brevo sender and SMTP credentials in the server `.env`, never in source control:

```dotenv
APP_URL=https://your-domain.example
APP_DEBUG=false
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=your-brevo-smtp-login
MAIL_PASSWORD=your-brevo-smtp-key
MAIL_FROM_ADDRESS=verified-sender@your-domain.example
MAIL_FROM_NAME="Sabangan Caguioa"
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

Run migrations, refresh the configuration cache, and supervise `php artisan queue:work --tries=1`. Asynchronous workers keep SMTP latency out of the public response. Do not use sync queues in production: delivery timing can reveal whether an account exists. Log and failover-to-log mailers are unavailable for recovery because reset links are secrets.

Test delivery only to an approved recipient after sender verification. Check inbox/spam, expiry, reuse rejection and worker failures. Jobs contain encrypted email addresses, not tokens. Restrict queue and failed-job storage; monitor failures without logging mail contents, reset URLs or credentials. Failed deliveries require a fresh request after the cooldown.

For lost email access, an authorized administrator must verify identity against established personnel records before an administrative password reset. Deliver the temporary password privately; the recipient must change it. This retains MFA. For a lost authenticator use a saved recovery code. Lost email plus lost MFA requires a documented identity-verification process; no public bypass is provided. Owner recovery follows the organization's authorized bootstrap procedure; this feature grants no new Owner-management permission.

Deployment requires correct registered emails, HTTPS, a verified sender/domain, running queue workers and delivery monitoring. No real mail delivery was performed by automated tests.
