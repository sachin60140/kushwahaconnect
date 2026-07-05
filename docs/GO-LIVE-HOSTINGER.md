# KushwahaConnect Hostinger Go-Live Guide

This guide is for Hostinger Cloud/shared hosting with SSH access.

## Important Hosting Rule

Laravel must be served from the `public` directory. Do not expose the full project root as the website document root. Laravel's deployment guide warns that serving from the project root can expose sensitive files.

Best setup:

- Project code: `/home/YOUR_USER/domains/kushwahaconnect.com/kushwahaconnect`
- Website document root: `/home/YOUR_USER/domains/kushwahaconnect.com/kushwahaconnect/public`

If hPanel does not allow changing the document root to `public`, use the shared-hosting fallback below.

## Production Requirements

- PHP 8.2 or newer
- PHP extensions: Ctype, cURL, DOM, Fileinfo, Filter, Hash, Mbstring, OpenSSL, PCRE, PDO, Session, Tokenizer, XML
- Composer
- MySQL database and database user from hPanel
- Node.js only if building assets on the server

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
The deploy script copies `kushwahaconnect/public/build` to `public_html/build`
automatically when `public_html` is beside the project folder. If you deploy
with Hostinger Git without running the script, manually copy the `public/build`
folder to `public_html/build` after each frontend build.

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
