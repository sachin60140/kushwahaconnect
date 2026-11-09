# KushwahaConnect Hostinger Go-Live Guide

This guide is for Hostinger Cloud/shared hosting with SSH access.

## Important Hosting Rule

Laravel must be served from the `public` directory. Do not expose the full project root as the website document root. Laravel's deployment guide warns that serving from the project root can expose sensitive files.

Best setup:

- Project code: `/home/YOUR_USER/domains/kushwahaconnect.com/kushwahaconnect`
- Website document root: `/home/YOUR_USER/domains/kushwahaconnect.com/kushwahaconnect/public`

If hPanel does not allow changing the document root to `public`, use the shared-hosting fallback below.

## Production Requirements

- PHP 8.3 or newer for both the website and the SSH command line (check with `php -v`); Laravel 13 requires it
- PHP extensions: Ctype, cURL, DOM, Fileinfo, Filter, Hash, Mbstring, OpenSSL, PCRE, PDO, Session, Tokenizer, XML
- Composer
- MySQL database and database user from hPanel
- Node.js 20.19+ or 22.12+ only if building assets (Vite 8); the deploy script uses the committed `public/build` by default

## Deployment Steps

1. Upload or clone the project into the domain folder:

```bash
cd ~/domains/kushwahaconnect.com
git clone YOUR_REPOSITORY_URL kushwahaconnect
cd kushwahaconnect
```

2. Create the production `.env`:

```bash
cp .env.production.example .env
nano .env
```

Set these values in `.env`:

- `APP_KEY` may be left blank for the first deploy; the deploy script will generate it.
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` from Hostinger hPanel.
- `MAIL_PASSWORD` for `no_reply@kushwahaconnect.com`.
- `APP_DEBUG=false`.
- `APP_ENV=production`.

3. Run the deploy script:

```bash
bash scripts/hostinger-deploy.sh
```

4. Point the domain document root to:

```text
/home/YOUR_USER/domains/kushwahaconnect.com/kushwahaconnect/public
```

5. Test:

```text
https://kushwahaconnect.com/up
https://kushwahaconnect.com
```

## If Node.js Is Not Available

Build locally before upload:

```bash
npm ci
npm run build
```

Then upload the generated `public/build` folder to the server. The deploy script will accept prebuilt assets when `npm` is not available.

## Shared-Hosting Fallback If Document Root Cannot Be Changed

Use this only if Hostinger forces the domain to use `public_html`.

Recommended layout:

```text
/home/YOUR_USER/domains/kushwahaconnect.com/kushwahaconnect
/home/YOUR_USER/domains/kushwahaconnect.com/public_html
```

Steps:

1. Upload the Laravel project to `kushwahaconnect`.
2. Copy the contents of `kushwahaconnect/public` into `public_html`.
3. Replace `public_html/index.php` with `deployment/hostinger-public-html-index.php`.
4. Keep the `.env`, `vendor`, `storage`, `bootstrap`, `app`, `routes`, and `config` folders outside `public_html`.
5. Run the deploy script from the Laravel project folder.

When using this fallback, browser assets must exist in `public_html/build`.
The deploy script copies `public/build` to the web root automatically, both when
`public_html` is beside the project folder and when the project itself lives in
`public_html`. If you deploy with Hostinger Git without running the script,
copy `public/build` into the web root's `build` folder after each deploy.
Laravel also serves any `/build` file missing from the web root straight from
`public/build`, so a skipped copy no longer blanks the site, but copying keeps
assets served directly by the web server.

## Troubleshooting

**Blank white page after a deploy.** The browser console shows 404s for
`/build/assets/...` files: the web root's `build` folder is older than
`public/build`. From the project folder run:

```bash
cp -R public/build/. build/          # project inside public_html
# or: cp -R public/build/. ../public_html/build/   (project beside public_html)
php artisan optimize:clear && php artisan optimize
```

If it is still blank, purge the Hostinger CDN cache in hPanel.

**`The Process class relies on proc_open` during `composer install`.** Hostinger
disables `proc_open`, which Composer needs to run Laravel's post-install scripts.
The deploy script installs with `--no-scripts`, clears Laravel's cached manifests and
runs `php artisan package:discover` itself. When running Composer by hand, use:

```bash
composer install --no-dev --optimize-autoloader --no-scripts
rm -f bootstrap/cache/config.php bootstrap/cache/services.php bootstrap/cache/packages.php
php artisan package:discover
```

**`Class "...ServiceProvider" not found` (HTTP 500 everywhere).** Laravel's cached
package list in `bootstrap/cache/packages.php` still names a package Composer removed
(e.g. `Laravel\Pail\PailServiceProvider` after a `--no-dev` install). Delete the cached
manifests and rebuild them:

```bash
rm -f bootstrap/cache/config.php bootstrap/cache/services.php bootstrap/cache/packages.php
php artisan package:discover && php artisan optimize
```

## After Launch Checklist

- Visit `/up`; it should return HTTP 200.
- Register one test user.
- Test login/logout.
- Test password reset email.
- Upload one image through admin/settings and verify it loads from `/storage`.
- Submit a business listing and verify it appears in the user dashboard.
- Approve a profile from admin and confirm the email notification is delivered.
- In hPanel, enable SSL and force HTTPS.
- In hPanel, create a daily backup before launch day.

## Useful Commands

```bash
php artisan about
php artisan route:list
php artisan migrate:status
php artisan storage:link
php artisan optimize:clear
php artisan optimize
```

## References

- Laravel deployment docs: https://laravel.com/docs/12.x/deployment
- Hostinger Laravel deployment guide: https://www.hostinger.com/tutorials/how-to-deploy-laravel
