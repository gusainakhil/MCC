# MCC Dashboard - Setup & Configuration Guide

Complete setup instructions for Railway Mechanized Cleaning Coach Management System (PHP version).

---

## 📋 Table of Contents
1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Running Locally](#running-locally)
4. [Production Deployment](#production-deployment)
5. [Backend Integration](#backend-integration)
6. [Troubleshooting](#troubleshooting)

---

## Installation

### System Requirements
- **Modern Web Browser**: Chrome, Firefox, Safari, Edge (2020+)
- **Disk Space**: ~5 MB for all files
- **No external dependencies**: All libraries from CDN

### For Local Development (Optional)
- **Python 3.6+** OR
- **Node.js 12+** OR
- **PHP 7.0+**

### Step 1: Download/Clone Project
```bash
# Option A: Download ZIP and extract
unzip mcc-dashboard.zip
cd MCC

# Option B: Clone repository
git clone <repository-url>
cd MCC
```

### Step 2: Verify File Structure
```bash
# Check all files are present
ls -la

# Expected output:
# index.php
# organisation.php
# organisation_list.php
# organisation_reports.php
# organisation_pdf.php
# stations.php
# connection.php
# assets/
#   css/style.css
#   js/script.js
# .htaccess
# README.md
# QUICKSTART.md
# DATABASE_SCHEMA.md
# SETUP.md
```

---

## Configuration

### Environment Setup

#### Windows Users
```batch
# Create shortcut to run local server
cd C:\path\to\MCC
python -m http.server 8000
```

#### macOS/Linux Users
```bash
# Add to ~/.bashrc or ~/.zshrc for quick access
alias mcc-serve='cd ~/Desktop/MCC && python3 -m http.server 8000'

# Use it anytime with:
mcc-serve
```

### Browser Preferences
Set your default browser for opening files:
- macOS: Right-click → Open With → [Browser]
- Windows: Right-click → Open With → [Browser]
- Linux: `xdg-open index.html`

---

## Running Locally

### Method 1: Direct Browser (No Server)
```bash
# Navigate to folder and open
open index.php          # macOS
xdg-open index.php      # Linux
start index.php         # Windows
```

**Note**: Some features like CSV export may not work without a server.

### Method 2: Python HTTP Server (Recommended)
```bash
cd /path/to/MCC

# Python 3
python3 -m http.server 8000

# Python 2 (legacy)
python -m SimpleHTTPServer 8000
```

Visit: `http://localhost:8000/index.php`

### Method 3: Node.js HTTP Server
```bash
# Install once (globally)
npm install -g http-server

# Run in project folder
cd /path/to/MCC
http-server -p 8000 -c-1
```

Visit: `http://localhost:8000/index.php`

### Method 4: NPX (No Installation)
```bash
cd /path/to/MCC
npx http-server -p 8000
```

### Method 5: PHP Built-in Server
```bash
cd /path/to/MCC
php -S localhost:8000
```

Visit: `http://localhost:8000`

### Method 6: VS Code Live Server
1. Install "Live Server" extension by Ritwick Dey
2. Right-click `index.php`
3. Select "Open with Live Server"
4. Auto-opens at `http://127.0.0.1:5500`
5. Auto-refreshes on file changes ✅

### Method 7: Docker Container
```bash
# Create Dockerfile in project folder:
FROM php:7.4-apache
WORKDIR /var/www/html
COPY . .
RUN a2enmod rewrite

# Build and run:
docker build -t mcc-dashboard .
docker run -p 8000:80 mcc-dashboard
```

Visit: `http://localhost:8000`

---

## Production Deployment

### Important DB Migration for New Report Types

Run this once on upgraded environments:

```sql
ALTER TABLE Mcc_parameters
MODIFY category VARCHAR(150) NOT NULL;
```

Also verify `Mcc_reports.report_type` enum includes `Chemical Report`, `Machine Report`, and `Attendance Report`.

### Shared Hosting (Bluehost, GoDaddy, Hostinger)

#### Step 1: Connect via FTP
```
Host: ftp.yourdomain.com
Username: cPanel username
Password: cPanel password
Port: 21 (or 22 for SFTP)
```

#### Step 2: Upload Files
- Connect with FileZilla or WinSCP
- Navigate to `public_html/` folder
- Upload all files (preserving directory structure)

#### Step 3: Verify
- Visit `https://yourdomain.com`
- Check files appear correctly
- Test navigation and forms

### VPS/Dedicated Server (Linode, DigitalOcean, AWS)

#### Step 1: SSH Access
```bash
ssh user@your-server-ip
```

#### Step 2: Clone Repository
```bash
cd /var/www/
git clone <repo-url> mcc-dashboard
cd mcc-dashboard
```

#### Step 3: Configure Web Server

**Apache** (.htaccess included):
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
# Move files to /var/www/html/mcc
```

**Nginx**:
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/mcc-dashboard;
    
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    # Static file caching
    location ~* \.(js|css|png|jpg|gif|ico|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

#### Step 4: Set Permissions
```bash
sudo chown -R www-data:www-data /var/www/mcc-dashboard
sudo chmod -R 755 /var/www/mcc-dashboard
sudo chmod -R 644 /var/www/mcc-dashboard/*.html
```

### Cloud Platforms

#### AWS S3 + CloudFront
```bash
# Upload to S3
aws s3 sync . s3://mcc-bucket/ --acl public-read

# Create CloudFront distribution pointing to S3
```

#### Netlify
```bash
# Install Netlify CLI
npm install -g netlify-cli

# Deploy
cd /path/to/MCC
netlify init
netlify deploy --prod
```

#### Vercel
```bash
# Install Vercel CLI
npm i -g vercel

# Deploy
vercel
```

#### GitHub Pages
1. Create repository: `username.github.io`
2. Push MCC files to main branch
3. Access at: `https://username.github.io`

---

## Backend Integration

### Step 1: Set Up Backend Framework

#### PHP (Recommended for quick integration)
```php
// api/config.php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'password');
define('DB_NAME', 'mcc_railway_db');

$connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}
?>
```

#### Node.js
```javascript
// server.js
const express = require('express');
const app = express();
const mysql = require('mysql2');

app.use(express.json());

const connection = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: 'password',
    database: 'mcc_railway_db'
});

connection.connect((err) => {
    if (err) throw err;
    console.log('MySQL Connected');
});

app.listen(3000, () => {
    console.log('Server running on port 3000');
});
```

### Step 2: Create API Endpoints

#### Example: Create Inspection
```php
// api/inspections/create.php
<?php
header('Content-Type: application/json');
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $query = "INSERT INTO inspections 
        (inspection_date, zone_id, station_id, depot_name, supervisor_name, 
         auditor_id, train_no, time_work_started, time_work_completed, 
         total_coaches_in_rake, coaches_attended, comments) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param(
        "siiisssssiss",
        $data['inspection_date'],
        $data['zone_id'],
        $data['station_id'],
        $data['depot_name'],
        $data['supervisor_name'],
        $data['auditor_id'],
        $data['train_no'],
        $data['time_work_started'],
        $data['time_work_completed'],
        $data['total_coaches_in_rake'],
        $data['coaches_attended'],
        $data['comments']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $connection->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
}
?>
```

### Step 3: Connect Forms to API

Update form submission in HTML:
```javascript
// In inspection_form.html
document.getElementById('inspectionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    fetch('api/inspections/create.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Inspection created successfully!');
            this.reset();
        } else {
            alert('Error: ' + result.error);
        }
    })
    .catch(error => console.error('Error:', error));
});
```

---

## Troubleshooting

### Issue: CORS Errors
**Solution**: Add CORS headers to .htaccess
```apache
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE"
    Header set Access-Control-Allow-Headers "Content-Type"
</IfModule>
```

### Issue: 404 Errors When Using Rewrite
**Solution**: Check mod_rewrite is enabled
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Issue: Slow Page Load
**Solution**: Enable caching
```apache
# In .htaccess
<FilesMatch "\\.(jpg|jpeg|png|gif|ico|css|js)$">
    Header set Cache-Control "max-age=31536000, public"
</FilesMatch>
```

### Issue: Permission Denied on Upload
**Solution**: Fix file permissions
```bash
sudo chmod 644 *.html
sudo chmod 755 assets/
sudo chmod 644 assets/css/*
sudo chmod 644 assets/js/*
```

### Issue: Database Connection Failed
**Solution**: Verify credentials
```php
// Test connection
echo mysqli_connect_error();
// Check credentials in config.php
```

### Issue: Form Data Not Saving
**Solution**: Check prepared statements
```php
// Use bound parameters, not string concatenation
$stmt->bind_param("s", $variable);
$stmt->execute();
```

---

## Performance Optimization

### Enable GZIP Compression
```apache
# .htaccess
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

### Minimize CSS/JS
```bash
# Using command line tools
npx csso assets/css/style.css -o assets/css/style.min.css
npx uglify-js assets/js/script.js -o assets/js/script.min.js

# Update HTML to use .min files
```

### Lazy Load Images
```html
<img src="..." loading="lazy" alt="description">
```

### Cache Static Files
```apache
# Browser cache (30 days)
<FilesMatch "\.(css|js)$">
    Header set Cache-Control "max-age=2592000"
</FilesMatch>
```

---

## Security Checklist

- [ ] Use HTTPS/SSL in production
- [ ] Hash passwords with bcrypt/argon2
- [ ] Use prepared statements for DB queries
- [ ] Validate input on both client & server
- [ ] Enable Content Security Policy (CSP)
- [ ] Remove debug/error logging from production
- [ ] Set proper file permissions (644 files, 755 dirs)
- [ ] Keep software updated
- [ ] Regular security backups
- [ ] Monitor access logs

---

## Monitoring & Maintenance

### Check Server Health
```bash
# Check CPU usage
top -bn1 | head -20

# Check disk space
df -h

# Check memory usage
free -h

# Check database size
SELECT SUM(data_length + index_length) FROM information_schema.tables;
```

### Regular Backups
```bash
# Daily backup script
mysqldump -u user -p database > /backup/mcc_$(date +%Y%m%d).sql
```

### Monitor Errors
```bash
# Watch Apache error log
tail -f /var/log/apache2/error.log

# Watch PHP error log
tail -f /var/log/php-fpm.log
```

---

## Scaling Considerations

### When to Scale:
- >1000 inspections/day
- >100 concurrent users
- Database size >1 GB
- Response time >2 seconds

### Scaling Options:
1. **Database Optimization**: Indexes, query optimization
2. **Caching**: Redis, Memcached
3. **Load Balancing**: Multiple servers
4. **CDN**: Content delivery for static files
5. **Microservices**: Separate API services

---

## Support & Resources

### Official Documentation
- Bootstrap 5: https://getbootstrap.com/docs/5.0/
- Bootstrap Icons: https://icons.getbootstrap.com/
- MySQL: https://dev.mysql.com/doc/
- PHP: https://www.php.net/manual/

### Community
- Stack Overflow: [bootstrap] [mysql] [php]
- GitHub Issues: Report bugs in repository
- Forum: Bootstrap official forum

### Support Channels
1. Check README.md for overview
2. Review QUICKSTART.md for setup
3. Check DATABASE_SCHEMA.md for DB design
4. Inspect browser console (F12) for errors
5. Check server error logs

---

**Last Updated**: April 1, 2026
**Version**: 1.0
**Status**: Production Ready
