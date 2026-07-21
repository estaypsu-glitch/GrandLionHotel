# Railway Deployment

This repository is ready for Railway Docker deployments through `railway.json` and the shared startup script in `docker/start-render.sh`.

## First-Time Setup

1. In Railway, create a new project from the GitHub repository.
2. Add a MySQL database service to the same project.
3. Open the app service, go to Variables, and add the required Laravel variables below.
4. Generate a public domain for the app service in Settings > Networking.
5. Redeploy the app service.

## App Service Variables

Railway provides MySQL variables such as `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, and `MYSQLPASSWORD`. The startup script automatically maps them to Laravel's `DB_*` variables when the `DB_*` variables are missing.

Use these variables on the app service:

```env
APP_NAME="The Grand Lion Hotel"
APP_ENV=production
APP_KEY=base64:TX8L3SaJDs3zfnFCdQgTWaSwpWhagYM6aandLfNApIo=
APP_DEBUG=false
FORCE_HTTPS=true
TRUSTED_PROXIES=*
APP_TIMEZONE=Asia/Manila

DB_CONNECTION=mysql

SESSION_DRIVER=database
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public
LOG_CHANNEL=stderr

MAIL_MAILER=log
QR_MERCHANT_NAME="The Grand Lion Hotel"
VITE_APP_NAME="The Grand Lion Hotel"
```

`APP_URL` can be left empty if Railway provides `RAILWAY_PUBLIC_DOMAIN`; the startup script will set it automatically.

## Notes

- Do not set `DB_CONNECTION=sqlite` in production.
- Do not upload or depend on `database/database.sqlite`.
- Switch `MAIL_MAILER` from `log` to real SMTP values when production email sending is ready.
