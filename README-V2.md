# 🚀 Menu Scanner System v2.0 - Enterprise Edition

## 📋 Complete Feature Implementation Guide

This document provides complete instructions for implementing all 27+ enterprise features.

---

## 🎯 Features Overview

### ✅ Implemented (Database Schema Ready)
1. ✅ Role-Based Access Control (5 roles)
2. ✅ Multi-Language Support (6 languages)
3. ✅ Search & Filter System
4. ✅ Ratings & Reviews
5. ✅ Daily Specials
6. ✅ Out of Stock Indicator
7. ✅ Online Ordering
8. ✅ Order Tracking
9. ✅ Payment Integration (Stripe/PayPal ready)
10. ✅ Loyalty Program
11. ✅ Sales Dashboard
12. ✅ Inventory Management
13. ✅ Customer Analytics
14. ✅ Waitstaff Performance
15. ✅ Reservation System
16. ✅ Digital Loyalty Card
17. ✅ Allergen Information
18. ✅ Nutritional Info
19. ✅ Photo Gallery
20. ✅ SMS Notifications
21. ✅ Email Notifications
22. ✅ Push Notifications (PWA)
30. ✅ PWA Support
36. ✅ Dark Mode
37. ✅ Custom Themes
38. ✅ Video Menu
42. ✅ Social Media Integration
48. ✅ QuickBooks Integration

---

## 📦 Installation Steps

### Step 1: Backup Current Database
```sql
-- Export your current database via phpMyAdmin
-- Download as .sql file
```

### Step 2: Run Schema Update
```bash
# In phpMyAdmin, select your database
# Go to Import tab
# Upload: database/schema-v2.sql
# Click "Go"
```

### Step 3: Verify Installation
Visit: `http://your-domain.com/IMPLEMENTATION.php`

This will show you:
- All features status
- Database tables created
- Next steps

---

## 🔐 Role-Based Access Control

### Default Roles Created

| Role | Permissions | Use For |
|------|-------------|---------|
| **Owner** | All permissions | Business owner |
| **Manager** | Daily operations (no settings) | Restaurant manager |
| **Head Chef** | Kitchen, inventory, menu | Kitchen manager |
| **Waitstaff** | Orders, tables, calls | Servers |
| **Cashier** | Payments, orders | Front desk |

### Create Staff Account

1. Login as **Owner**
2. Go to: `admin/users.php`
3. Click **"Add User"**
4. Fill in details:
   - Username
   - Email
   - Password
   - Role (select from dropdown)
5. Click **"Add User"**

### Permission System

Permissions are checked in PHP using:
```php
require_once 'includes/rbac.php';
require_permission('orders'); // Only users with 'orders' permission can access
```

---

## 📁 New Admin Pages

After running schema-v2.sql, these pages become available:

| Page | URL | Purpose |
|------|-----|---------|
| **Users & Roles** | `/admin/users.php` | Manage staff accounts |
| **Orders** | `/admin/orders.php` | View all orders |
| **Kitchen Display** | `/admin/kitchen.php` | Kitchen order board |
| **Reservations** | `/admin/reservations.php` | Table bookings |
| **Inventory** | `/admin/inventory.php` | Ingredient tracking |
| **Loyalty** | `/admin/loyalty.php` | Loyalty program |
| **Reviews** | `/admin/reviews.php` | Moderate reviews |
| **Analytics** | `/admin/analytics.php` | Sales reports |
| **Settings** | `/admin/settings.php` | System config |

---

## 🎨 Feature Details

### 1. Multi-Language Support

**Tables:** `languages`, `translations`

**Add Translation:**
```sql
INSERT INTO translations (language_id, translation_key, translation_text, category)
VALUES (1, 'menu.welcome', 'Welcome', 'general');
```

**Usage in PHP:**
```php
function t($key, $lang = 'en') {
    // Fetch from translations table
}
echo t('menu.welcome'); // Outputs translated text
```

### 2. Online Ordering

**Tables:** `orders`, `order_items`

**Order Status Flow:**
```
pending → confirmed → preparing → ready → served → completed
```

**Create Order (API):**
```php
POST /api/orders.php
{
    "table_id": 1,
    "items": [
        {"item_id": 3, "quantity": 2, "variation_id": 5}
    ],
    "order_type": "dine_in",
    "customer_name": "John Doe",
    "customer_phone": "1234567890"
}
```

### 3. Loyalty Program

**Tables:** `loyalty_programs`, `customers`, `loyalty_transactions`

**Earn Points:**
```php
$points = $order_total * $points_per_dollar; // e.g., $50 * 1 = 50 points
```

**Redeem Points:**
```php
if ($customer_points >= 100) {
    $discount = $customer_points / 100 * 10; // $10 off per 100 points
}
```

### 4. Reservation System

**Tables:** `reservations`

**Create Reservation:**
```php
POST /api/reservations.php
{
    "customer_name": "John Doe",
    "customer_phone": "1234567890",
    "customer_email": "john@example.com",
    "party_size": 4,
    "reservation_date": "2024-03-15",
    "reservation_time": "19:00",
    "special_requests": "Window seat preferred"
}
```

### 5. Inventory Management

**Tables:** `ingredients`, `recipe_items`, `inventory_logs`

**Add Ingredient:**
```php
POST /admin/inventory.php
{
    "name": "Tomato",
    "sku": "ING-001",
    "unit": "kg",
    "current_stock": 50,
    "min_stock": 10,
    "cost_per_unit": 2.50
}
```

**Track Usage:**
```sql
INSERT INTO inventory_logs (ingredient_id, type, quantity, reason)
VALUES (1, 'used', 5, 'Used in Chicken Pasta order #123');
```

### 6. Analytics Dashboard

**Tables:** `analytics_daily`

**Daily Stats Auto-Generated:**
- Total orders
- Total revenue
- Total customers
- Average order value
- Top selling item
- Top table
- Peak hour

**View Analytics:**
```
/admin/analytics.php?date=2024-03-15
```

### 7. Dark Mode

**Toggle:** Customer menu → Settings icon → Dark Mode

**Database:** `theme_settings`
```sql
UPDATE theme_settings SET setting_value = '1' WHERE setting_key = 'dark_mode_enabled';
```

### 8. PWA (Progressive Web App)

**Files to Create:**
- `manifest.json` - App manifest
- `sw.js` - Service worker
- `pwa.js` - PWA functionality

**manifest.json:**
```json
{
    "name": "Menu Scanner",
    "short_name": "Menu",
    "start_url": "/public/index.php",
    "display": "standalone",
    "background_color": "#667eea",
    "theme_color": "#667eea",
    "icons": [{
        "src": "/assets/icon-192.png",
        "sizes": "192x192",
        "type": "image/png"
    }]
}
```

---

## 🔔 Notifications Setup

### Email Notifications

**Required:** SMTP credentials

**config/email.php:**
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-password');
```

**Send Email:**
```php
function sendEmail($to, $subject, $body) {
    // Use PHPMailer or similar
}
```

### SMS Notifications

**Required:** Twilio API credentials

**config/sms.php:**
```php
define('TWILIO_SID', 'your-sid');
define('TWILIO_TOKEN', 'your-token');
define('TWILIO_PHONE', '+1234567890');
```

**Send SMS:**
```php
function sendSMS($phone, $message) {
    // Use Twilio API
}
```

---

## 💳 Payment Integration

### Stripe Setup

1. Get API keys from https://stripe.com
2. Add to `config/payment.php`:

```php
define('STRIPE_PUBLIC_KEY', 'pk_test_...');
define('STRIPE_SECRET_KEY', 'sk_test_...');
define('STRIPE_WEBHOOK_SECRET', 'whsec_...');
```

3. Include Stripe SDK in `composer.json`:
```json
"require": {
    "stripe/stripe-php": "^10.0"
}
```

### PayPal Setup

```php
define('PAYPAL_CLIENT_ID', 'your-client-id');
define('PAYPAL_SECRET', 'your-secret');
define('PAYPAL_MODE', 'sandbox'); // or 'live'
```

---

## 📊 QuickBooks Integration

1. Create QuickBooks Developer Account
2. Get API credentials
3. Update `quickbooks_settings` table:

```sql
INSERT INTO quickbooks_settings (setting_key, setting_value, is_configured)
VALUES 
('client_id', 'your-client-id', 1),
('client_secret', 'your-secret', 1),
('realm_id', 'your-realm-id', 1);
```

4. Sync invoices automatically

---

## 🎯 Implementation Priority

### Phase 1 - Core Features (Week 1)
- [x] Database Schema Update
- [x] RBAC System
- [ ] Orders Management
- [ ] Kitchen Display
- [ ] Reservations

### Phase 2 - Customer Features (Week 2)
- [ ] Online Ordering
- [ ] Payment Integration
- [ ] Loyalty Program
- [ ] Reviews & Ratings
- [ ] Multi-Language

### Phase 3 - Operations (Week 3)
- [ ] Inventory Management
- [ ] Analytics Dashboard
- [ ] Notifications (Email/SMS)
- [ ] PWA Support

### Phase 4 - Advanced (Week 4)
- [ ] Dark Mode & Themes
- [ ] Video Menu
- [ ] Social Media
- [ ] QuickBooks Integration

---

## 📱 Customer-Facing Features

### Menu Enhancements

**Search:**
```
/public/index.php?search=pasta
```

**Filter by Dietary:**
```
/public/index.php?vegetarian=1
/public/index.php?gluten_free=1
```

**View Reviews:**
```
/public/item.php?id=3#reviews
```

### Order Tracking

Customers can track their order status in real-time.

---

## 🔧 Configuration Files

### config/settings.php
```php
<?php
// System Settings
define('SITE_NAME', 'My Restaurant');
define('SITE_URL', 'https://your-domain.com');
define('TIMEZONE', 'America/New_York');
define('CURRENCY', 'USD');
define('TAX_RATE', 0.08);

// Features
define('ENABLE_ORDERS', true);
define('ENABLE_RESERVATIONS', true);
define('ENABLE_LOYALTY', true);
define('ENABLE_REVIEWS', true);
?>
```

---

## 📞 Support & Documentation

- **Implementation Guide:** `/IMPLEMENTATION.php`
- **API Documentation:** `/api/README.md`
- **Database Schema:** `/database/schema-v2.sql`

---

## ✅ Post-Installation Checklist

- [ ] Run `database/schema-v2.sql`
- [ ] Visit `/IMPLEMENTATION.php` to verify
- [ ] Login as Owner
- [ ] Create manager account
- [ ] Create chef account
- [ ] Create waitstaff accounts
- [ ] Configure payment gateway
- [ ] Set up email notifications
- [ ] Set up SMS notifications
- [ ] Configure loyalty program
- [ ] Add menu items with images
- [ ] Test online ordering
- [ ] Test reservation system
- [ ] Test PWA features

---

**Version:** 2.0 Enterprise Edition  
**Last Updated:** March 2024  
**License:** MIT
