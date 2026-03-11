# 🚀 Deploy to Render - Step by Step

This guide will help you deploy the PHP Menu Scanner System to Render's free tier.

## 📋 Prerequisites

- GitHub account
- Render account (sign up at https://render.com)
- Git installed on your computer

---

## Step 1: Push to GitHub

### Initialize Git Repository

```bash
# Navigate to project folder
cd C:\Users\XERXES\menu-scanner

# Initialize git repository
git init

# Add all files
git add .

# Create initial commit
git commit -m "Initial commit: PHP Menu Scanner System"

# Add your GitHub repository as remote
git remote add origin https://github.com/Sammy24389/menu-scanner.git

# Push to GitHub
git branch -M main
git push -u origin main
```

### If You Already Have Git

```bash
cd C:\Users\XERXES\menu-scanner
git add .
git commit -m "Add Render deployment configuration"
git push origin main
```

---

## Step 2: Create Render Web Service

### 2.1 Sign in to Render

1. Go to https://render.com
2. Sign up/Sign in with GitHub

### 2.2 Create New Web Service

1. Click **"New +"** → **"Web Service"**
2. Connect your GitHub repository:
   - Click **"Connect a repository"**
   - Select `Sammy24389/menu-scanner` from the list
3. Configure the service:

| Setting | Value |
|---------|-------|
| **Name** | `menu-scanner` (or your choice) |
| **Region** | Oregon (closest to you) |
| **Branch** | `main` |
| **Root Directory** | (leave blank) |
| **Runtime** | `PHP` |
| **Build Command** | `chmod +x render-build.sh && ./render-build.sh` |
| **Start Command** | `php -S 0.0.0.0:$PORT -t public` |
| **Instance Type** | **Free** |

4. Click **"Advanced"** and add environment variable:
   - Key: `USE_SQLITE`
   - Value: `true`

5. Click **"Create Web Service"**

---

## Step 3: Configure Persistent Storage

Render's free tier requires disks for persistent data.

### 3.1 Add Data Disk (for SQLite database)

1. In your Render dashboard, go to the service
2. Click **"Disks"** tab
3. Click **"Add Disk"**
4. Configure:
   - **Name:** `data`
   - **Mount Path:** `/opt/render/project/src/data`
   - **Size:** `1 GB` (free tier limit)
5. Click **"Add Disk"**

### 3.2 Add Uploads Disk (for menu images)

1. Click **"Add Disk"** again
2. Configure:
   - **Name:** `uploads`
   - **Mount Path:** `/opt/render/project/src/uploads`
   - **Size:** `1 GB`
3. Click **"Add Disk"**

---

## Step 4: Deploy

1. Render will automatically start building your service
2. Watch the logs in the **"Logs"** tab
3. Wait for **"Live"** status (usually 2-5 minutes)
4. Copy your service URL (e.g., `https://menu-scanner.onrender.com`)

---

## Step 5: Access Your Application

### Admin Panel
```
https://your-app.onrender.com/admin/login.php
```

**Credentials:**
- Username: `admin`
- Password: `admin123`

### Customer Menu
```
https://your-app.onrender.com/public/index.php?table=550e8400-e29b-41d4-a716-446655440001
```

---

## Step 6: Update QR Codes

Update the base URL in `admin/tables.php`:

```php
// Change from localhost to your Render URL
$baseUrl = 'https://your-app.onrender.com/public/index.php';
```

Commit and push:
```bash
git add .
git commit -m "Update base URL for Render"
git push origin main
```

---

## 🔧 Troubleshooting

### Build Fails

**Error: Permission denied**
```bash
# Fix locally and push
git add render-build.sh
git config core.filemode false
git commit --amend --chmod=+x render-build.sh
git push --force origin main
```

### Database Not Working

Make sure `USE_SQLITE=true` is set in environment variables:
1. Render Dashboard → Your Service → **Environment**
2. Add variable if missing

### Images Not Uploading

Check that the uploads disk is mounted:
1. Render Dashboard → Your Service → **Disks**
2. Verify mount path: `/opt/render/project/src/uploads`

### 502 Bad Gateway

- Wait 1-2 minutes (Render spins up on first request for free tier)
- Check logs for errors

---

## 📊 Free Tier Limits

| Resource | Limit |
|----------|-------|
| **Bandwidth** | 100 GB/month |
| **Disk Storage** | 1 GB per disk |
| **CPU** | Shared (0.1-0.5 vCPU) |
| **Memory** | 512 MB |
| **Uptime** | May sleep after 15 min inactivity |

⚠️ **Note:** Free tier services may sleep after inactivity. First request after sleep takes 30-60 seconds to wake up.

---

## 🆙 Upgrade Options

For production use, consider upgrading:

| Plan | Price | Benefits |
|------|-------|----------|
| **Starter** | $7/month | No sleep, more resources |
| **Standard** | $25/month | Production-ready |

---

## 📝 Post-Deployment Checklist

- [ ] Change admin password from default
- [ ] Update QR code base URL
- [ ] Test customer menu on mobile
- [ ] Test call waitstaff functionality
- [ ] Upload test menu image
- [ ] Create actual tables and QR codes
- [ ] Add real menu items
- [ ] Set up waitstaff accounts

---

## 🔗 Useful Links

- Render Dashboard: https://dashboard.render.com
- Render Docs: https://render.com/docs
- PHP on Render: https://render.com/docs/php
- Your Service Logs: Dashboard → Your Service → Logs

---

**Need help?** Check the logs in Render dashboard for detailed error messages.
