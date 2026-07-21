# BastionTech Portable Email Funnel Module

A lightweight PHP/MySQL module for signup-based welcome and follow-up email sequences on client websites, designed for IONOS hosting. It is **not** a SaaS product, billing platform, client self-service portal, imported-list sender, or bulk eblast tool.

## Install on IONOS

1. Upload `funnel-module/` into the client site, for example `/htdocs/funnel-module`.
2. Import `install.sql` in phpMyAdmin or the IONOS MySQL import tool.
3. Copy `config.example.php` to `includes/config.php` and fill in database, site URL, SMTP, admin email, cron token, and batch limit values.
4. Install PHPMailer by uploading Composer's `vendor/` folder or PHPMailer's `src/` files to `funnel-module/PHPMailer/src/`.
5. Make `logs/` writable by PHP.

## Create the first admin user

Generate a hash locally or in a temporary protected PHP script:

```php
echo password_hash('CHANGE_ME', PASSWORD_DEFAULT);
```

Insert the admin in MySQL:

```sql
INSERT INTO admin_users (email, password_hash, name) VALUES ('you@example.com', 'PASTE_HASH', 'BastionTech Admin');
```

Passwords are verified with `password_verify`; plaintext passwords are never stored.

## Add a client, sequence, and templates

1. Insert one client record in `clients` with the client name and optional from/SMTP overrides.
2. Log in at `/funnel-module/admin/login.php`.
3. Add a sequence and set it active when ready.
4. Add templates by `day_number` (for example 1 through 7, 14, or 21). The number of templates is not hardcoded.
5. Keep only templates that should send marked `active`.

## Connect a client form

Use `examples/client-form.html` as a vanilla JavaScript example. Forms must submit POST data to:

```text
/funnel-module/public/subscribe.php
```

Required fields: `name`, `email`, and `sequence_id`. Optional honeypot field: `website`.

## Cron job on IONOS

Run once daily with your secret token:

```text
https://example.com/funnel-module/cron/process-sequences.php?token=YOUR_SECRET
```

Optionally cap the run:

```text
https://example.com/funnel-module/cron/process-sequences.php?token=YOUR_SECRET&limit=25
```

The processor sends at most one scheduled sequence email per subscriber per run, skips unsubscribed subscribers, only sends active sequences/templates, and checks `email_send_log` before sending.

## Testing

Use `/admin/test-email.php` to send any template to the configured admin email. Subscribe through the example form to verify Day 1 immediate sending and unsubscribe links.

## Moving to another client project

Copy the full `funnel-module/` directory, import `install.sql` into the new database, copy/update `includes/config.php`, create the admin user, and add the new client's sequence/templates.

## Recommended limits

- Start with 1 active sequence per client.
- Use 7 to 21 signup-based emails.
- Do not use purchased, scraped, or imported lists.
- Run daily cron only.
- Keep batch limits at 25, 50, or 100 depending on hosting and SMTP limits.

## Important files

- `install.sql` — database schema.
- `includes/config.php` — local runtime configuration (copy from `config.example.php`).
- `includes/db.php` — PDO database connection using prepared statements.
- `includes/auth.php` — admin login with `password_hash` / `password_verify`.
- `includes/mailer.php` — PHPMailer SMTP sending and merge tags.
- `public/subscribe.php` — POST-only signup endpoint.
- `public/unsubscribe.php` — unsubscribe confirmation endpoint.
- `cron/process-sequences.php` — token-protected daily processor.
