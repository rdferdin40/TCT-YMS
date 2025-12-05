# TCT Yard Management System (YMS)

A modern, production-ready Yard Management System built for logistics operations in Laredo, Texas and surrounding areas. Designed for cross-docks, 3PL providers, freight forwarders, and warehouses handling Mexico-US freight flows.

![PHP Version](https://img.shields.io/badge/PHP-8.2+-blue.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)
![License](https://img.shields.io/badge/license-proprietary-red.svg)

## Features

### Core Modules

- **Interactive Yard Map**: Grid-based visual representation with drag-and-drop trailer movement
- **Gate Check-In/Out**: Complete arrival and departure processing with driver/carrier capture
- **Trailer Lifecycle Tracking**: Configurable state machine (Arrived → Staged → At Door → Loading → Loaded → Departed)
- **Dock Door Management**: Real-time door status, assignments, and utilization tracking
- **Move Tasking System**: Create, assign, and track spotter move tasks
- **Spotter Mobile App**: Mobile-optimized interface for yard spotters
- **Analytics Dashboard**: Real-time KPIs with ApexCharts visualizations
- **Reports & Exports**: Yard inventory, gate activity, dwell time, move history

### Key Capabilities

- **Role-Based Access Control**: Admin, Supervisor, Dispatcher, Spotter, Viewer roles
- **Configurable Status Workflows**: Define your own trailer statuses and transitions
- **Dwell Time Alerts**: Visual warnings for trailers exceeding thresholds
- **CSV Import/Export**: Bulk data import for carriers, trailers, yard layout
- **Dark Mode**: Modern theme with light/dark toggle
- **Single-Tenant Deployment**: Each customer gets isolated instance

## Technology Stack

- **Backend**: PHP 8.2 (Custom MVC, no framework)
- **Database**: MySQL 8.x / MariaDB
- **Frontend**: Tailwind CSS + Alpine.js
- **Charts**: ApexCharts
- **Icons**: Tabler Icons
- **Web Server**: Apache 2.4

## Requirements

- Ubuntu 24.04 LTS (or compatible Linux)
- PHP 8.2+
- MySQL 8.0+ or MariaDB 10.6+
- Apache 2.4 with mod_rewrite
- 2GB RAM minimum
- 20GB disk space

### PHP Extensions Required

- pdo, pdo_mysql
- mbstring
- json
- openssl
- fileinfo

## Installation

### Quick Install

```bash
# Clone repository
git clone https://github.com/your-repo/tct-yms.git
cd tct-yms

# Run installer
php scripts/install.php
```

The installer will guide you through:
1. Database configuration
2. Application URL setup
3. Admin user creation
4. Initial data seeding

### Manual Installation

1. **Configure Environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

2. **Create Database**
   ```sql
   CREATE DATABASE tct_yms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Run Schema**
   ```bash
   mysql -u root -p tct_yms < database/schema.sql
   ```

4. **Run Seeders**
   ```bash
   mysql -u root -p tct_yms < database/seeders/seed.sql
   ```

5. **Configure Apache**
   ```apache
   <VirtualHost *:80>
       ServerName yourdomain.com
       DocumentRoot /var/www/tct-yms/public

       <Directory /var/www/tct-yms/public>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

6. **Set Permissions**
   ```bash
   chmod -R 755 storage/
   chmod -R 755 public/uploads/
   chown -R www-data:www-data storage/ public/uploads/
   ```

## Configuration

### Environment Variables (.env)

```env
# Application
APP_NAME="Your Yard Name"
APP_URL=https://yourdomain.com
APP_DEBUG=false

# Database
DB_HOST=localhost
DB_DATABASE=tct_yms
DB_USERNAME=your_user
DB_PASSWORD=your_password

# Session
SESSION_LIFETIME=120
SESSION_SECURE=true

# Features
FEATURE_DARK_MODE=1
FEATURE_CSV_IMPORT=1
FEATURE_PHOTO_UPLOAD=1
```

### Admin Panel Configuration

Most settings are configurable via the admin panel:
- Yard layout (zones, rows, slots, doors)
- Trailer statuses and colors
- Dwell time thresholds
- User roles and permissions
- Feature flags

## User Roles

| Role | Description | Key Permissions |
|------|-------------|-----------------|
| Admin | Full system access | All permissions |
| Supervisor | Operations manager | Tasks, reports, door management |
| Dispatcher | Gate operations | Check-in/out, trailer assignments |
| Spotter | Yard worker | View/complete assigned tasks |
| Viewer | Read-only | View yard map and dashboards |

## API Endpoints

Internal AJAX endpoints (authenticated):

```
GET  /api/trailers           - List trailers
GET  /api/trailers/{id}      - Get trailer details
GET  /api/dock-doors         - List dock doors
GET  /api/yard-slots         - List yard slots
GET  /api/dashboard/stats    - Dashboard statistics
POST /yard-map/move          - Move trailer (drag-drop)
```

## Directory Structure

```
tct-yms/
├── app/
│   ├── Controllers/        # HTTP controllers
│   ├── Models/             # Database models
│   ├── Middleware/         # Auth, CSRF, Role middleware
│   ├── Helpers/            # Global helper functions
│   ├── Database.php        # Database connection
│   └── Router.php          # HTTP router
├── config/
│   ├── app.php             # Application config
│   └── database.php        # Database config
├── database/
│   ├── schema.sql          # Database schema
│   └── seeders/            # Initial data
├── public/
│   ├── index.php           # Entry point
│   ├── .htaccess           # Apache rewrites
│   └── uploads/            # User uploads
├── resources/
│   └── views/              # PHP templates
├── routes/
│   └── web.php             # Route definitions
├── storage/
│   ├── logs/               # Application logs
│   └── cache/              # Cache files
├── scripts/
│   └── install.php         # Installation script
├── .env.example            # Environment template
└── bootstrap.php           # Application bootstrap
```

## Backup & Updates

### Automated Backups

Add to crontab:
```bash
# Daily database backup at 2 AM
0 2 * * * mysqldump -u user -p'password' tct_yms > /backups/yms_$(date +\%Y\%m\%d).sql

# Weekly file backup
0 3 * * 0 tar -czf /backups/yms_files_$(date +\%Y\%m\%d).tar.gz /var/www/tct-yms/
```

### Updating

```bash
cd /var/www/tct-yms
git fetch origin
git checkout v1.x.x  # specific version tag
# Run any new migrations if needed
```

## Security Checklist

- [ ] Use HTTPS with valid SSL certificate
- [ ] Set strong admin password (12+ characters)
- [ ] Configure firewall (allow only 80, 443, 22)
- [ ] Disable directory listing
- [ ] Keep PHP and MySQL updated
- [ ] Regular backups (test restore process)
- [ ] Monitor logs for suspicious activity
- [ ] Change default MySQL port if exposed

## Troubleshooting

### Common Issues

**500 Internal Server Error**
- Check Apache error log: `tail -f /var/log/apache2/error.log`
- Verify PHP version: `php -v`
- Check file permissions on storage/

**Database Connection Failed**
- Verify MySQL is running: `systemctl status mysql`
- Check credentials in .env
- Test connection: `mysql -u user -p database_name`

**Session Issues**
- Check session directory permissions
- Verify session configuration in php.ini

## Support

For technical support:
- Documentation: [docs/](docs/)
- Issues: Open a GitHub issue
- Email: support@yourcompany.com

## License

Proprietary software. All rights reserved.

---

Built with ❤️ for the logistics industry in South Texas
