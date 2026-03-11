# 🍽️ PHP Menu Scanner System

A complete QR code-based menu system for lounges and restaurants. Customers scan a QR code to view the menu, browse categories, and call waitstaff. Includes a comprehensive admin panel for managing everything.

## ✨ Features

### Customer-Facing
- **QR Code Scanning** - Scan to access table-specific menu
- **Mobile-Optimized Menu** - Beautiful, responsive design
- **Category Navigation** - Easy browsing through menu sections
- **Item Variations** - Size options, extras, and modifiers with pricing
- **Call Waitstaff Button** - Request assistance with one tap
- **Service Request Types** - Waiter, Bill, Complaint, or Other

### Admin Dashboard
- **Dashboard Overview** - Statistics and recent activity
- **Table Management** - Add/edit tables, generate QR codes
- **Category Management** - Organize menu sections
- **Menu Items CRUD** - Full item management with images
- **Variations System** - Add sizes, options, and modifiers
- **Waitstaff Management** - Manage staff with PIN codes
- **Service Calls Dashboard** - Live view of customer requests
- **QR Code Generation** - Download printable QR codes

## 📋 Requirements

- PHP 8.0 or higher
- MySQL 8.0 or higher (configured on port 3308)
- Composer (optional, for QR code library)
- Modern web browser

## 🚀 Installation

### 1. Database Setup

Import the database schema into your MySQL server running on port 3308:

```bash
mysql -u root -P 3308 -p < database/schema.sql
```

Or manually:
1. Open MySQL client on port 3308
2. Run the contents of `database/schema.sql`

### 2. Configure Database Connection

Edit `config/database.php` if needed:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', 3308);
define('DB_NAME', 'menu_scanner');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 3. Install Dependencies (Optional)

For local QR code generation (fallback to API if not installed):

```bash
composer install
```

### 4. Set Up Web Server

#### Using PHP Built-in Server

```bash
# From the project root directory
cd menu-scanner
php -S localhost:8080
```

#### Using Apache/XAMPP

1. Copy `menu-scanner` folder to your htdocs directory
2. Update the base URL in files if needed
3. Access via `http://localhost/menu-scanner`

#### Using Nginx

```nginx
server {
    listen 8080;
    server_name localhost;
    root /path/to/menu-scanner;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### 5. Set Permissions

Ensure the `uploads/` directory is writable:

```bash
# Windows
icacls uploads /grant Users:(OI)(CI)F

# Linux/Mac
chmod -R 755 uploads/
chown -R www-data:www-data uploads/
```

## 🔐 Default Login

- **Username:** `admin`
- **Password:** `admin123`

**⚠️ Change the default password immediately after first login!**

## 📁 Project Structure

```
menu-scanner/
├── admin/                  # Admin dashboard pages
│   ├── index.php          # Dashboard
│   ├── login.php          # Admin login
│   ├── tables.php         # Table management
│   ├── categories.php     # Category management
│   ├── items.php          # Menu items management
│   ├── variations.php     # Item variations
│   ├── waitstaff.php      # Waitstaff management
│   ├── service-calls.php  # Service calls dashboard
│   └── generate-qr.php    # QR code generator
├── api/                    # API endpoints
│   ├── menu.php           # Menu JSON API
│   └── service-call.php   # Service call API
├── assets/
│   ├── css/
│   │   ├── admin.css      # Admin styles
│   │   └── customer.css   # Customer menu styles
│   └── js/
│       ├── admin.js       # Admin JavaScript
│       └── customer.js    # Customer menu JavaScript
├── config/
│   └── database.php       # Database configuration
├── database/
│   └── schema.sql         # Database schema + sample data
├── includes/
│   ├── auth.php           # Authentication functions
│   ├── functions.php      # Helper functions
│   ├── header.php         # Admin header
│   └── footer.php         # Admin footer
├── public/
│   ├── index.php          # Customer menu view
│   └── qr/                # Generated QR codes (optional)
├── uploads/               # Uploaded menu images
├── composer.json          # Composer dependencies
└── README.md              # This file
```

## 🎯 Usage Guide

### For Administrators

1. **Login** to admin panel at `http://localhost:8080/admin/login.php`

2. **Add Tables**
   - Go to Tables → Add Table
   - Enter table number (e.g., T1, Table-001)
   - QR code is auto-generated

3. **Create Categories**
   - Go to Categories → Add Category
   - Add sections like "Appetizers", "Main Course", "Beverages"

4. **Add Menu Items**
   - Go to Menu Items → Add Item
   - Select category, add name, description, price
   - Upload image (optional)
   - Add variations (sizes, options)

5. **Add Waitstaff**
   - Go to Waitstaff → Add Staff
   - Enter name and PIN code
   - Share PIN with staff member

6. **Print QR Codes**
   - Go to Tables
   - Click QR icon to view and print

### For Customers

1. Scan QR code on table
2. Browse menu by category
3. View item details and variations
4. Tap "Call Waiter" when needed
5. Select reason and submit

### For Waitstaff

1. Receive notification from admin dashboard
2. Admin assigns call to staff (optional)
3. Attend to customer at table

## 🔧 Configuration

### Base URL

Update the base URL in `admin/tables.php` and `public/index.php`:

```php
$baseUrl = 'http://localhost:8080/menu-scanner/public/index.php';
```

### MySQL Port

If using a different port, update `config/database.php`:

```php
define('DB_PORT', 3308); // Change to your port
```

### Image Upload Settings

Modify in `includes/functions.php`:

```php
$maxSize = 5 * 1024 * 1024; // Max 5MB
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
```

## 🛠️ Troubleshooting

### Database Connection Failed
- Verify MySQL is running on port 3308
- Check credentials in `config/database.php`
- Ensure database `menu_scanner` exists

### Images Not Uploading
- Check `uploads/` folder permissions
- Verify PHP upload settings in php.ini:
  ```ini
  upload_max_filesize = 5M
  post_max_size = 6M
  ```

### QR Codes Not Generating
- Ensure internet connection for API fallback
- Or install Composer dependencies: `composer install`

### Session Issues
- Check PHP session configuration
- Ensure session directory is writable

## 🔒 Security Best Practices

1. **Change default admin credentials** immediately
2. **Use HTTPS** in production
3. **Regular backups** of database
4. **Update PHP** and dependencies regularly
5. **Restrict admin access** by IP if possible
6. **Use strong passwords** for waitstaff PINs

## 📝 API Endpoints

### GET /api/menu.php
Returns complete menu in JSON format.

**Query Parameters:**
- `table` (optional) - Table UUID for table-specific info

**Response:**
```json
{
  "success": true,
  "timestamp": "2024-01-01T12:00:00+00:00",
  "table": {"id": 1, "number": "T1"},
  "categories": [
    {
      "id": 1,
      "name": "Appetizers",
      "items": [...]
    }
  ]
}
```

### POST /api/service-call.php
Create a service call request.

**Request Body:**
```json
{
  "table_id": 1,
  "table_uuid": "xxx-xxx-xxx",
  "call_type": "waiter",
  "notes": "Need assistance"
}
```

**Response:**
```json
{
  "success": true,
  "call_id": 123,
  "message": "Service call created"
}
```

## 📄 License

MIT License - Feel free to use and modify for your projects.

## 🤝 Support

For issues or questions:
1. Check the troubleshooting section
2. Review database schema for table structures
3. Verify all requirements are met

## 🎉 Credits

Built with:
- Bootstrap 5
- Bootstrap Icons
- PHP 8+
- MySQL 8
- chillerlan/php-qrcode (optional)

---

**Happy serving! 🍻**
