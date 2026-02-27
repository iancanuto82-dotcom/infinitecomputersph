# Deploy on Render with Docker

## 1) Push this repo
Push the current branch to GitHub/GitLab so Render can build from it.

## 2) Create a new Web Service on Render
1. Render Dashboard -> **New +** -> **Web Service**
2. Connect your repo
3. For environment/runtime, choose **Docker**

Render will use the root `Dockerfile` automatically.

## 3) Set required environment variables
At minimum:

```env
APP_NAME=Laravel
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-render-url.onrender.com
APP_KEY=base64:your-generated-key

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password
```

Optional:

```env
RUN_MIGRATIONS=true
```

If `RUN_MIGRATIONS=true`, container startup will run `php artisan migrate --force`.

## 4) Generate `APP_KEY`
Generate locally and paste into Render:

```powershell
php artisan key:generate --show
```

## 5) Deploy
Trigger deploy from Render. The app binds to Render's injected `PORT` automatically.
