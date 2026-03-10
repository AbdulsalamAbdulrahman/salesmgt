# Production Deployment Checklist

## Pre-Deployment

### Server Requirements
- PHP >= 8.2
- MySQL >= 8.0
- Composer
- Node.js & NPM (for building assets)
- SSL Certificate (HTTPS required)

### Files to Upload
Upload all files EXCEPT:
- `/node_modules/`
- `/vendor/` (will be installed on server)
- `.env` (use `.env.production` as template)
- `/storage/logs/*`
- `/storage/framework/cache/*`
- `/storage/framework/sessions/*`
- `/storage/framework/views/*`

## Deployment Steps

### 1. Build Assets Locally
```bash
# IMPORTANT: Run this BEFORE uploading files
npm install
npm run build
```

This creates the `public/build/` folder with compiled CSS/JS assets.

### 2. Upload Files
```bash
# Upload via FTP/SFTP or Git
```

**Upload all files INCLUDING:**
- `public/build/` ← **CRITICAL: Must upload this folder!**
- All other application files

**EXCEPT (don't upload these):**
- `/node_modules/`
- `/vendor/` (will be installed on server)
- `.env` (use `.env.production` as template)
- `/storage/logs/*`
- `/storage/framework/cache/*`
- `/storage/framework/sessions/*`
- `/storage/framework/views/*`

### 3. Configure Environment
```bash
# Copy and edit .env.production to .env
cp .env.production .env
nano .env

# Update these values:
# - APP_KEY (generate new if needed: php artisan key:generate)
# - DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
# - APP_URL (your production domain with https://)
```

### 4. Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

### 5. Run Migrations
```bash
php artisan migrate --force
```

### 6. Create Storage Directories
```bash
# Ensure storage directories exist
mkdir -p storage/app/public
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache
```

### 7. Create Storage Link
```bash
# Method 1: Using artisan (requires symlink permission)
php artisan storage:link

# Method 2: If symlink fails (shared hosting)
# Manually create the link or use:
ln -s ../storage/app/public public/storage

# Method 3: If symlinks not supported at all
# Copy instead of link (you'll need to recopy when uploading new files)
cp -r storage/app/public public/storage
```

### 8. Cache for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 9. Set Permissions (Linux)
```bash
# Make storage and cache writable
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# If using nginx/php-fpm, adjust user:
# chown -R nginx:nginx storage bootstrap/cache
```

### 10. Create Admin User (if fresh install)
```bash
php artisan tinker
```
```php
\App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@jannats-rufaidahsalesapp.com.ng',
    'password' => bcrypt('sagir@rufaidah'),
    'role' => 'admin',
    'is_active' => true,
    'can_manage_inventory' => true,
]);
```

### 11. Setup Queue Worker & Scheduler (Shared Hosting)

For shared hosting without Supervisor, use cron-based queue processing.

**Add these cron jobs in cPanel → Cron Jobs:**

```bash
# Queue Worker - runs every minute, processes jobs for up to 55 seconds
* * * * * cd /home2/jannatsr && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1

# Laravel Scheduler - runs scheduled tasks
* * * * * cd /home2/jannatsr && php artisan schedule:run >> /dev/null 2>&1
```

**Alternative: Use Sync Queue (if cron not reliable)**

If cron-based queue doesn't work well, use synchronous processing by updating `.env`:
```
QUEUE_CONNECTION=sync
```
This processes jobs immediately without a background worker.

---

### For VPS/Dedicated Servers (with root access)

If you have a VPS with root access, use Supervisor instead:

Create `/etc/supervisor/conf.d/salesmgt-worker.conf`:
```ini
[program:salesmgt-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home2/jannatsr/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/home2/jannatsr/storage/logs/worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start salesmgt-worker:*
```

Add scheduler to crontab (`crontab -e`):
```
* * * * * cd /home2/jannatsr && php artisan schedule:run >> /dev/null 2>&1
```

## Post-Deployment Verification

- [ ] Website loads with HTTPS
- [ ] Can login as admin
- [ ] Can create sales
- [ ] Can manage products/inventory
- [ ] Can view reports
- [ ] Email notifications working
- [ ] Queue worker processing jobs
- [ ] Low stock alerts sending

## Rollback Plan

If issues occur:
```bash
php artisan down
# Restore previous files/database
php artisan up
```

## Security Checklist

- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] Strong database password
- [ ] HTTPS enabled
- [ ] File permissions correct
- [ ] .env not publicly accessible
- [ ] Debug bar disabled

## Performance Tips

- Enable OPcache in PHP
- Use Redis for cache/sessions (optional)
- Configure nginx/apache for static file caching
- Enable gzip compression
