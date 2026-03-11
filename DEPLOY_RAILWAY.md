# PHP Menu Scanner - Railway Deployment

## Deploy to Railway (Free Tier)

Railway offers $5/month credit which is enough for small PHP apps with MySQL.

### Step 1: Push to GitHub (Already Done ✅)

Your code is at: https://github.com/Sammy24389/menu-scanner

### Step 2: Connect Railway to GitHub

1. Go to https://railway.app
2. Click **"Start a New Project"**
3. Click **"Deploy from GitHub repo"**
4. Authorize Railway to access your GitHub
5. Select **`Sammy24389/menu-scanner`**

### Step 3: Add MySQL Database

1. In your Railway project dashboard
2. Click **"+ New"**
3. Select **"Database"** → **"MySQL"**
4. Wait for MySQL to provision (1-2 minutes)

### Step 4: Configure Environment Variables

Railway auto-injects MySQL credentials. Add these in **Variables** tab:

```
USE_SQLITE=false
DB_HOST=${{MYSQL_HOST}}
DB_PORT=${{MYSQL_PORT}}
DB_NAME=${{MYSQL_DATABASE}}
DB_USER=${{MYSQL_USER}}
DB_PASS=${{MYSQL_PASSWORD}}
```

Or Railway will auto-detect the MYSQL_* variables.

### Step 5: Deploy

Railway will automatically:
1. Detect PHP from `composer.json`
2. Install dependencies
3. Start the PHP server

### Step 6: Run Database Setup

Once deployed:

1. Click **"Generate Domain"** in Settings
2. Go to `https://your-app.railway.app/setup.php`
3. This will create the database tables
4. Delete `setup.php` after running (security)

### Step 7: Access Your App

- **Admin:** `https://your-app.railway.app/admin/login.php`
- **Customer Menu:** `https://your-app.railway.app/public/index.php?table=550e8400-e29b-41d4-a716-446655440001`

---

## Troubleshooting

### Build Fails

Make sure `composer.json` is in the root directory (it is).

### Database Connection Error

Check that MySQL service is connected and environment variables are set.

### Images Not Uploading

Railway has ephemeral storage. For production, use:
- Cloudinary (free tier)
- AWS S3
- Or upgrade Railway for persistent disk

---

## Railway Free Tier Limits

- **$5 credit/month** (~500 hours of uptime)
- **1 GB RAM**
- **1 vCPU**
- **5 GB disk**

For a restaurant menu system, this should be plenty!

---

## Alternative: InfinityFree (100% Free, No Credit Card)

If Railway credit runs out:

1. Go to https://infinityfree.net
2. Sign up (no credit card)
3. Create account with subdomain
4. Upload files via FTP
5. Import `database/schema.sql` via phpMyAdmin
6. Update `config/database.php`

**Free Features:**
- Unlimited PHP hosting
- Free MySQL
- 5 GB storage
- Free SSL
