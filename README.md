# Shopify Metafield Inspector

A self-hosted Laravel application that connects to your Shopify stores, scans metafields, and surfaces quality issues: missing definitions, empty values, unused fields, duplicate namespaces, and more.

## Requirements

- Docker + Docker Compose
- A domain with DNS pointed to your VPS (for real HTTPS and Shopify OAuth)
  OR [ngrok](https://ngrok.com) for local development

---

## 1. Create a Shopify App

1. Go to [dev.shopify.com/dashboard](https://dev.shopify.com/dashboard) → **Apps** → **Create app**
2. Select **Create app manually**
3. In the **Configuration** section:
   - **App URL**: `https://yourdomain.com`
   - **Allowed redirect URL**: `https://yourdomain.com/shopify/callback`
4. Go to **API access** → set visibility to **Unlisted** (so you can install on any store)
5. Copy your **Client ID** and **Client Secret**
6. Set scopes
7. Set callbackurl to https://yourdomain.com/shopify/callback

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

## 3. Start

```bash
docker compose up -d
```

On first boot, the application automatically:
- Waits for MySQL to be ready
- Generates `APP_KEY` if not set
- Runs all database migrations
- Obtains a Let's Encrypt SSL certificate (VPS with a real domain)

---

## 4. First Access

Open `https://yourdomain.com`, register your account, then connect your Shopify store.

---

## Local Development with ngrok

Shopify requires HTTPS even for local OAuth. Use ngrok to expose your local instance:

```bash
# 1. Start the stack
docker compose up -d

# 2. Expose port 80 via ngrok
ngrok http 80

# 3. Copy the HTTPS URL (e.g. https://abc123.ngrok-free.app)

# 4. Update .env
APP_URL=https://abc123.ngrok-free.app
APP_DOMAIN=abc123.ngrok-free.app
SHOPIFY_REDIRECT_URI=https://abc123.ngrok-free.app/shopify/callback

# 5. Update your Shopify App configuration with the new URLs

# 6. Restart the app service to apply .env changes
docker compose restart app
```

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
