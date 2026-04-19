# Render MySQL Deployment

This repository includes a `render.yaml` Blueprint for deploying the app on Render with:

- a web service built from this repository's `Dockerfile`
- a private MySQL 8 service backed by a persistent disk
- a persistent disk mounted to Laravel's `storage/` directory so uploaded proofs and generated files survive restarts

## What the Blueprint Configures

- Web service name: `hotel-reservation-app`
- MySQL service name: `hotel-reservation-mysql`
- Region: `singapore`
- Web plan: `starter`
- MySQL plan: `starter`

If you want different service names, region, or disk sizes, edit `render.yaml` before the first Blueprint sync. Some of these settings are harder to change after creation.

## First-Time Render Setup

1. Push this repository, including `render.yaml`, to GitHub/GitLab/Bitbucket.
2. In Render, create a new Blueprint and select this repository.
3. During Blueprint creation, provide values for the `sync: false` variables:
   - `APP_URL`: your public Render URL or custom domain
   - `APP_KEY`: generate locally with `php artisan key:generate --show`
4. Review the generated services and apply the Blueprint.

The startup script at `docker/start-render.sh` will:

- refuse to boot production with SQLite
- create required Laravel storage directories
- create the `public/storage` symlink when needed
- retry migrations until MySQL is ready

## Important Environment Notes

- `DB_CONNECTION` is forced to `mysql` in `render.yaml`.
- `SESSION_DRIVER` is set to `database` so sessions persist in MySQL.
- `QUEUE_CONNECTION` is set to `sync` so queued mail is processed immediately without a separate worker service.
- `MAIL_MAILER` defaults to `log` in the Blueprint. Switch it to SMTP in Render when you are ready to send real emails.

## Upload Persistence

Uploaded files such as payment proofs and discount IDs are stored on Laravel's `public` disk. The Blueprint mounts a persistent disk at `/var/www/html/storage`, which keeps those uploads across restarts and redeploys.

If you later move uploads to S3 or another object store, update `FILESYSTEM_DISK` and remove the web disk if you no longer need local persistent storage.

## After Deployment

Recommended follow-up settings in Render:

- set real SMTP credentials if email sending is required
- set Google OAuth credentials if Google login is enabled
- replace `MAIL_MAILER=log` with your mail provider values

If you change `QUEUE_CONNECTION` from `sync` to `database`, add a Render worker service that runs the queue worker. Otherwise queued jobs will stay pending.
