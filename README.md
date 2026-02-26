# Doctor Store

 A Laravel application that connects to your Shopify stores, scans metafields, and surfaces quality issues: missing definitions, empty values, unused fields, duplicate namespaces, and more.

## Requirements

- Docker + Docker Compose
- A domain with DNS pointed to your VPS (for real HTTPS and Shopify OAuth)
  OR [ngrok](https://ngrok.com) for local development

---

## 1. Create a Shopify App

1. Go to [dev.shopify.com/dashboard](https://dev.shopify.com/dashboard) → **Apps** → **Create app**
2. Select **Create app manually**
3. In the **Configuration** section, enter your base URL:
   - **App URL**: `https://yourdomain.com` *(for local dev with ngrok, use a placeholder — you'll update this after getting your ngrok URL)*
   - **Allowed redirect URL**: `https://yourdomain.com/shopify/callback`
4. Go to **API access** → set visibility to **Unlisted** (so you can install on any store)
5. Copy your **Client ID** and **Client Secret**
6. In **Versions**, create a new version and:
   - Set the required scopes
   - Set the callback URL to `https://yourdomain.com/shopify/callback`

> **Local dev with ngrok:** You won't know your ngrok URL until you start the stack (step 4). Use a placeholder URL here and come back to update the App URL, Allowed redirect URL, and version callback URL once you have your ngrok address.

---

## 2. Configure

```bash
cp .env.example .env
```

Edit `.env` with your editor and fill in:

```env
APP_URL=https://yourdomain.com
APP_DOMAIN=yourdomain.com          # used by Caddy for SSL

DB_PASSWORD=your_secure_password   # change this!
DB_ROOT_PASSWORD=your_root_password

SHOPIFY_CLIENT_ID=your_client_id
SHOPIFY_CLIENT_SECRET=your_client_secret
SHOPIFY_REDIRECT_URI=https://yourdomain.com/shopify/callback
```

---

## 3. Generate an APP_KEY

The app needs a permanent encryption key. Start the stack once to get a generated key:

```bash
docker compose up -d
docker compose exec app php artisan key:generate --show
```

Copy the output (e.g. `base64:xxxx...`) and add it to your `.env`:

```env
APP_KEY=base64:xxxx...
```

Then restart so it takes effect:

```bash
docker compose restart app
```

> If `APP_KEY` is left empty, a temporary key is generated on every container start — which invalidates all sessions on each restart.

---

## 4. Start

```bash
docker compose up -d
```

On first boot, the application automatically:
- Waits for MySQL to be ready
- Runs all database migrations
- Obtains a Let's Encrypt SSL certificate (VPS with a real domain)

---

## 5. First Access

Open `https://yourdomain.com`, register your account, then connect your Shopify store.

---

---

## Local Development with ngrok

Caddy cannot obtain a Let's Encrypt certificate for `localhost`, so HTTPS won't work out of the box locally. Since Shopify requires a real HTTPS callback URL for OAuth, you need ngrok to expose your local instance.

> **Just want to browse the app without Shopify OAuth?** Set `APP_DOMAIN=localhost` in `.env`, run `docker compose up -d`, and open `https://localhost`. Your browser will warn about the self-signed certificate — click through it.

### Full local setup with ngrok

```bash
# 1. Start the stack
docker compose up -d

# 2. In a separate terminal, expose port 8080 via ngrok
#    (port 8089 is nginx directly — bypasses Caddy, which would cause a redirect loop)
ngrok http 8089
# ngrok will print your unique URL, e.g. https://abc123.ngrok-free.app
# Important: use your actual subdomain — not the generic ngrok-free.app

# 3. Update .env with your specific ngrok URL
APP_URL=https://abc123.ngrok-free.app
APP_DOMAIN=abc123.ngrok-free.app          # must be the full subdomain
SHOPIFY_REDIRECT_URI=https://abc123.ngrok-free.app/shopify/callback

# 4. Update your Shopify App configuration with the new URLs
#    (App URL and Allowed redirect URL)

# 5. Restart the app service to apply the .env changes
docker compose restart app

# 6. Visit https://abc123.ngrok-free.app
```

> **Note:** Free ngrok URLs change every time you restart ngrok. Each time you get a new URL, repeat steps 3–5 and update your Shopify App configuration.

---

## Useful Commands

```bash
# View all logs
docker compose logs -f

# View only queue worker logs
docker compose logs -f queue

# Run artisan commands
docker compose exec app php artisan tinker
docker compose exec app php artisan migrate

# Check service status
docker compose ps

# Stop all services
docker compose down

# Stop and delete all data (including database)
docker compose down -v
```

## Updating

```bash
git pull
docker compose build --no-cache
docker compose up -d
```

Migrations run automatically on restart.
