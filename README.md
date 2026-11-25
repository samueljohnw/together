# Together

A small, private shared space for a household: **to-dos** and **threads**, each with its own conversation, file attachments, and photo/video sharing. Built with Laravel, Blade, Tailwind CSS and plain JavaScript.

## Features

- **To-dos and threads.** Add either from the home page. Each one opens a chat window where members reply and attach files.
- **Photos and videos.** Images and videos show as thumbnails in the chat and open in an on-site viewer. Videos play with full controls and seeking. Other files download.
- **Close and re-open.** Closing a to-do or thread (after a confirmation) moves it to **Account › Closed Items**, with its full history. Re-open it from there at any time.
- **Live updates.** An open page checks for the other person's changes every 5 seconds.
- **Push notifications.** Each member can turn on Web Push per device from **Account › Members** to get an alert when someone else adds or replies.
- **Search** across open to-dos and threads, and separately across closed items.
- **Private by default.** Every page requires signing in. There is no public sign-up; members are added from the command line.

## Requirements

- PHP 8.3+ with the `openssl`, `fileinfo` and `pdo_sqlite` extensions
- Composer
- Node.js and npm

SQLite is the default database. Any database Laravel supports will work.

## Getting started

```bash
composer setup                 # installs dependencies, creates .env, generates the app key, migrates, builds assets
php artisan together:add-member you@example.com "Your Name"
php artisan together:add-member partner@example.com "Their Name"
composer dev                   # runs the web server, queue, log viewer and Vite together
```

Then open http://localhost:8000 and sign in.

`together:add-member` asks for a password (at least 8 characters). Each person gets their own login. Everyone who can sign in shares the same household space.

### Demo data

For local development, `php artisan db:seed` creates two demo members, `alex@example.com` and `sam@example.com` (password `password`), with sample to-dos and threads. Don't run it on a real install.

## Push notifications

Web Push needs a VAPID key pair:

```bash
php artisan together:push-keys
```

Copy the printed values into `.env`:

```dotenv
TOGETHER_VAPID_PUBLIC_KEY=...
TOGETHER_VAPID_PRIVATE_KEY=...
TOGETHER_VAPID_SUBJECT=mailto:you@example.com
```

Keep the private key secret. Browsers only allow push on `localhost` or over HTTPS. Each member then enables notifications on each device from **Account › Members**. Notifications are sent after the request finishes, so no queue worker is required for them.

## File uploads

The app accepts attachments up to **20 MB** each, and up to 10 files at once. PHP's defaults are much lower, so raise them in `php.ini`:

```ini
upload_max_filesize = 25M
post_max_size = 210M
```

Behind nginx, also set `client_max_body_size` to match. Attachments are stored privately in `storage/app/private/attachments` and are only served to signed-in members.

Inline viewing supports JPEG, PNG, GIF, WebP and AVIF images, and MP4, WebM, MOV and M4V video. The server checks each file's actual contents before showing it inline. SVGs and everything else are download-only.

## Development

| Task | Command |
| --- | --- |
| Run everything locally | `composer dev` |
| Run the test suite | `composer test` or `php artisan test --compact` |
| Run one test file | `php artisan test --compact tests/Feature/ItemTest.php` |
| Run tests matching a name | `php artisan test --compact --filter="closes an open item"` |
| Format PHP | `vendor/bin/pint --dirty` |
| Build front-end assets | `npm run build` |

### How it fits together

- To-dos and threads share one `items` table (`App\Enums\ItemKind`). Messages belong to an item, and each message is either text or a single attachment.
- The pages are Blade shells that embed their starting data as JSON. `resources/js/app.js` renders the UI and talks to small JSON endpoints (`/items`, `/items/{item}/messages`, `/closed-items/{item}`) that return API resources.
- Routes live in `routes/web.php` and are all behind the `auth` middleware except sign-in.

## Deploying

- Set `APP_ENV=production`, `APP_DEBUG=false` and a real `APP_URL`, and serve over HTTPS (required for push).
- Run `php artisan migrate --force` and `npm run build` on deploy.
- Add members with `php artisan together:add-member` on the server.
- Make sure `storage/` is persistent and writable, because attachments live there.
- Apply the upload limits above to the production PHP and web server.
