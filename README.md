# TAG-CICC

TAG-CICC is a Laravel and Livewire church management system for City Impact Christian Centre. It covers membership, departments, zones, services, finance, calendar events, leadership, operational reports, visitor analytics, uploads, and SMS communication.

## Production After Pull

Run these commands from the application directory on Hostinger after pulling updates:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If assets look unstyled on the live site, make sure the contents of `public/build` from the repository are present in the live `public_html/build` path used by `public_html/index.php`.

## SMS Environment

Add Beem credentials to the live `.env` before enabling SMS sending:

```env
BEEM_SMS_API_KEY=your_key
BEEM_SMS_SECRET_KEY=your_secret
BEEM_SMS_SENDER_ID=TAGCICC
BEEM_SMS_BASE_URL=https://apisms.beem.africa
BEEM_SMS_CALLBACK_TOKEN=optional-secret-token
```

After changing `.env`, run:

```bash
php artisan optimize:clear
php artisan config:cache
```

Set Beem delivery callback URL to:

```text
https://www.tag-cicc.or.tz/sms/beem/callback
```

If `BEEM_SMS_CALLBACK_TOKEN` is set, send it as a Bearer token, `X-Beem-Token` header, or `?token=` query parameter.

## Scheduled SMS

Create a Hostinger cron job to process scheduled SMS:

```bash
cd /home/u916010174/domains/tag-cicc.or.tz/tag-cicc-app && php artisan sms:send-scheduled
```

Recommended interval: every 5 or 10 minutes.

## Android Install

The system includes a web app manifest and service worker. On Android Chrome, users can open the site and choose Add to Home screen or Install app.

## Final Checks

Before pushing a release:

```bash
php artisan test
npm run build
```
