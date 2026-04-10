# Quick Start Guide

Get the Railway MCC PHP dashboard up and running in minutes.

---

## 5-Minute Setup

### Option 1: PHP Built-in Server (Recommended)

```bash
cd /path/to/MCC
php -S localhost:8000
```

Then open: `http://localhost:8000/index.php`

---

### Option 2: Python Local Server

#### macOS/Linux:
```bash
cd /path/to/MCC
python3 -m http.server 8000
```

#### Windows (PowerShell):
```powershell
cd path\to\MCC
python -m http.server 8000
```

Then open: `http://localhost:8000/index.php`

---

### Option 3: Node.js Server

```bash
# Install globally (once)
npm install -g http-server

# Run in project folder
cd /path/to/MCC
http-server -p 8000
```

Open: `http://localhost:8000/index.php`

---

### Option 4: VS Code Live Server Extension

1. Install "Live Server" extension
2. Right-click `index.php`
3. Click "Open with Live Server"
4. Automatically opens at `http://127.0.0.1:5500` ✅

---

## 🌐 Production Deployment

### Shared Hosting (cPanel/Plesk)
1. Upload all files via FTP
2. Navigate to domain in browser
3. Works immediately ✅

### Apache Server
1. Ensure `.htaccess` is with other files
2. Enable `mod_rewrite` in Apache
3. Upload and access via domain

### Nginx Server
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/mcc;
    
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    # Cache static assets
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

---

## File Structure Quick Check

```
✓ index.php                    ← Dashboard
✓ organisation.php             ← Organisation setup
✓ organisation_list.php        ← Organisation list
✓ organisation_reports.php     ← Reports setup
✓ organisation_pdf.php         ← Print/PDF page
✓ stations.php                 ← Station setup
✓ connection.php               ← DB connection
✓ assets/css/style.css         ← Styling
✓ assets/js/script.js          ← JavaScript
✓ .htaccess                    ← Apache config
✓ README.md                    ← Documentation
```

If all files are present ✅, ready to deploy!

---

## Testing the Dashboard

### Test All Pages
- [ ] Dashboard loads from `index.php`
- [ ] All sidebar links work
- [ ] Organisation setup flow works
- [ ] Organisation reports setup supports multi-report save
- [ ] Organisation PDF opens and prints correctly
- [ ] Responsive on mobile view (F12)
- [ ] Print functionality works

### Mobile Testing
1. Open browser DevTools (F12)
2. Click device icon (top-left)
3. Select "iPhone 12" or "Pixel 4"
4. Test navigation and forms

---

## 🔧 Common Issues & Solutions

### Issue: Page missing styles
**Solution**: Check browser console (F12). Ensure `assets/css/style.css` path is correct.

### Issue: Sidebar not responsive
**Solution**: Refresh page (Ctrl+R or Cmd+R). Clear browser cache.

### Issue: Forms not working
**Solution**: Backend integration needed. Currently forms are UI-only.

### Issue: "File not found" error
**Solution**: Ensure you're using a local server, not opening `file://` directly.

### Issue: Cannot export CSV
**Solution**: Open browser console to see errors. Requires proper file permissions.

---

## 📱 Browser Compatibility

| Browser | Version | Status |
|---------|---------|--------|
| Chrome  | 90+     | ✅ Full Support |
| Firefox | 88+     | ✅ Full Support |
| Safari  | 14+     | ✅ Full Support |
| Edge    | 90+     | ✅ Full Support |
| IE 11   | -       | ❌ Not Supported |

---

## Database Migration (Important)

If you enabled Chemical/Machine report fields, run:

```sql
ALTER TABLE Mcc_parameters
MODIFY category VARCHAR(150) NOT NULL;
```

And ensure `Mcc_reports.report_type` enum includes:
- Normal Report
- Intensive Report
- Chemical Report
- Machine Report
- Attendance Report

---

## Next Steps

### 1. Choose Backend Framework
- **PHP**: Native, easy integration
- **Node.js**: JavaScript full-stack
- **Python**: Django/Flask
- **Rails**: Ruby framework

### 2. Create Database
See `DATABASE_SCHEMA.md` for table structure

### 3. Connect API Endpoints
Example AJAX call ready in forms:
```javascript
// In inspection_form.html
document.getElementById('inspectionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    // Connect to: /api/inspections/create
});
```

### 4. Add Authentication Hardening
- Login page improvements
- Session management
- Password hashing

### 5. Deploy Together
- Upload HTML files
- Upload PHP/Node backend
- Configure database
- Set up SSL/HTTPS

---

## 📊 Sample Data Included

Pre-populated in all tables:
- **Zones**: A, B, C, D
- **Stations**: Central, Northern, Eastern, South, West
- **Users**: John Doe, Jane Smith, Mike Johnson, Sarah Davis
- **Trains**: TR-001 to TR-008
- **Contractors**: 3 sample contractors

Use these for UI testing before backend integration.

---

## 🎓 Learning Path

1. **Copy the files** ✅
2. **Open in browser** ✅
3. **Explore all pages** ✅
4. **Test responsive design** ✅
5. **Review HTML structure** ✅
6. **Customize CSS** ✅
7. **Set up backend** 🟡
8. **Connect API** 🟡
9. **Add authentication** 🟡
10. **Deploy to production** 🟡

---

## 🆘 Getting Help

### Read Documentation
1. `README.md` - Full project overview
2. HTML file comments - Form structure
3. `assets/css/style.css` - Styling explanation
4. `assets/js/script.js` - JavaScript functions

### Debug Issues
1. Open DevTools (F12)
2. Check Console for errors
3. Check Network tab
4. Inspect HTML elements

### Report Issues
1. Check browser console
2. Note error messages
3. Try different browser
4. Clear cache and reload

---

## ✨ Useful Tips

**Tip 1**: Use Inspector (F12) to see responsive design break points
**Tip 2**: All forms have validation hints in placeholder text
**Tip 3**: Sample data helps understand table structure
**Tip 4**: CSS variables make theme customization easy
**Tip 5**: Forms are ready for AJAX integration

---

## 🎯 Success Checklist

- [ ] Files copied to project folder
- [ ] Opened in web browser successfully
- [ ] Dashboard displays properly
- [ ] Can navigate between all pages
- [ ] Sidebar responsive on mobile
- [ ] Forms look clean and organized
- [ ] Tables display sample data
- [ ] Print preview works
- [ ] CSV export saves file
- [ ] Ready to integrate backend

---

**You're all set! 🎉**

Start exploring the dashboard and customize as needed.

Next: Integrate with your backend framework.

Questions? Check the README.md file.

---

Created: April 1, 2026
