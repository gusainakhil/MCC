# Railway Mechanized Cleaning Coach (MCC) Management System
## Modern Admin Dashboard UI

A clean, professional, and fully responsive admin dashboard frontend built with HTML5, Bootstrap 5, and vanilla JavaScript.

---

## 📁 Project Structure

```
MCC/
├── index.html                  # Dashboard Overview
├── create_user.html            # User Management
├── zones.html                  # Zone Master Data
├── stations.html               # Station Master Data
├── contracts.html              # Contract Management
├── inspection_form.html        # MCC Inspection Entry Form
├── report.html                 # Reports & Analytics
├── assets/
│   ├── css/
│   │   └── style.css          # Custom styling (production-ready)
│   └── js/
│       └── script.js           # Shared utilities & helpers
└── README.md                   # This file
```

---

## 🎨 Pages Overview

### 1. **Dashboard (index.html)**
- Summary cards: Total Users, Contracts, Inspections, Stations
- Recent inspections table with filtering (Date, Zone, Station)
- Responsive layout with pagination

### 2. **Create User (create_user.html)**
- User registration form
- Fields: Name, Email, Password (with visibility toggle), Role
- Role descriptions and password requirements guide
- Form validation ready

### 3. **Zones (zones.html)**
- Add new zone form
- Zone listing table with Edit/Delete actions
- Modal for editing zones
- Displays station count per zone

### 4. **Stations (stations.html)**
- Add station form with zone dropdown
- Station listing table
- Linked to zones with contract count
- Edit/Delete functionality

### 5. **Contracts (contracts.html)**
- Contract creation form
- Fields: Agreement No, Date, Contractor Name, Zone, Station, Duration
- Active contracts table with full details
- Dynamic zone-to-station linking

### 6. **Inspection Entry Form (inspection_form.html)**
- **3 Major Sections:**
  - Basic Details: Date, Zone, Station, Depot, Supervisor, Auditor
  - Train Details: Train No, Work time (with auto-duration calculation)
  - Coach Details: Total coaches, attended coaches (with auto-calculation)
- Auto-calculated fields: Duration, Coaches not attended, Completion %
- Comments section for additional notes
- Submit & Clear buttons

### 7. **Reports (report.html)**
- Advanced filtering: Date range, Zone, Station
- Summary statistics cards
- Detailed inspection report table (8 rows of sample data)
- **Export Options:**
  - Print (browser print functionality)
  - Export to CSV (working JavaScript implementation)
  - Export to PDF (UI only, requires backend integration)
- Pagination support

---

## 🎯 Features

### ✅ Implemented
- **Responsive Design**: Mobile, tablet, desktop optimized
- **Bootstrap 5**: Latest framework for UI components
- **Sidebar Navigation**: collapsible on mobile with icons
- **Form Validation**: HTML5 validation ready
- **Data Tables**: with sorting, filtering, pagination
- **Cards & Badges**: color-coded status badges
- **Icons**: Bootstrap Icons included
- **Modal Dialogs**: for edit operations
- **Print Friendly**: CSS print media queries
- **CSV Export**: Working implementation
- **Sample Data**: Pre-populated tables for demonstration
- **Auto-calculations**: Coach percentages, time durations

### 🔧 Ready for Backend Integration
- Form structure with `id` and `name` attributes
- Prepared for AJAX calls
- RESTful endpoint hooks in JavaScript
- No hardcoded backend dependencies in HTML
- Clean separation of concerns

---

## 🚀 How to Use

### 1. **Local Testing**
```bash
# Simply open in a browser
open index.html
# or
firefox index.html
# or
right-click → Open with Browser
```

### 2. **Using a Local Server** (Recommended)
```bash
# Python 3
python -m http.server 8000

# Python 2
python -m SimpleHTTPServer 8000

# Node.js (with http-server)
npx http-server

# PHP
php -S localhost:8000
```
Then visit: `http://localhost:8000`

### 3. **Deploy to Production**
- Upload to your web server
- No build process required
- All assets are relative paths
- Works with PHP backend directly

---

## 📱 Responsive Breakpoints

- **Desktop**: `>768px` - Full sidebar with labels
- **Tablet**: `768px-576px` - Collapsed sidebar icons
- **Mobile**: `<576px` - Optimized layout for touch

---

## 🎨 Customization Guide

### Change Primary Color
Edit `assets/css/style.css`:
```css
:root {
    --primary-color: #0d6efd;  /* Change this hex color */
}
```

### Customize Sidebar Width
```css
:root {
    --sidebar-width: 250px;  /* Adjust width */
}
```

### Add/Remove Navigation Items
Edit the sidebar `<ul>` in HTML files to add/remove menu items.

### Modify Form Fields
Each form has a comment structure making it easy to add/remove fields.

---

## 🔐 Security Notes

⚠️ **Current State**: Frontend only - NO BACKEND
- All data is temporary (stored in browser memory)
- Passwords are NOT hashed
- Sessions are NOT managed
- CSRF protection NOT implemented

### When Integrating Backend:
✅ Implement password hashing (bcrypt/argon2)
✅ Use prepared statements for all queries
✅ Add CSRF tokens to forms
✅ Implement session management
✅ Add input validation & sanitization
✅ Use HTTPS in production
✅ Implement role-based access control (RBAC)

---

## 📊 Sample Data

All pages include realistic sample data for:
- Zones: A, B, C, D
- Stations: Central, Northern, Eastern, South, West
- Users: John Doe, Jane Smith, Mike Johnson, Sarah Davis
- Trains: TR-001 through TR-008
- Contractors: ABC Cleaning Services, XYZ Maintenance Ltd, Premium Clean Co.

---

## 🔗 Navigation Flow

```
Dashboard (Home)
├── Create User → User Management
├── Zones → Zone Master Data
├── Stations → Station Master Data
├── Contracts → Contract Management
├── Inspection Entry → Form Submission
└── Reports → Data Analysis
```

Each page has a consistent sidebar and can navigate to any other page.

---

## 📋 Checklist for Backend Integration

- [ ] Set up PHP/Node.js/Python backend
- [ ] Create MySQL database with required tables
- [ ] Implement user authentication (login/logout)
- [ ] Connect forms via AJAX/Fetch API
- [ ] Add input validation server-side
- [ ] Implement password hashing
- [ ] Set up session management
- [ ] Add error handling & validation messages
- [ ] Implement role-based access control
- [ ] Create API endpoints for CRUD operations
- [ ] Add pagination backend logic
- [ ] Implement report generation
- [ ] Add PDF export functionality
- [ ] Set up logging & audit trails
- [ ] Test security vulnerabilities

---

## 🛠 Technologies Used

- **HTML5**: Semantic markup
- **CSS3**: Custom styling with variables
- **Bootstrap 5.3**: Responsive framework
- **JavaScript (ES6+)**: Client-side functionality
- **Bootstrap Icons**: Icon library
- **Fetch API**: Ready for AJAX calls

---

## 📝 Notes

1. **No Build Process**: This is pure frontend - no compilation needed
2. **No Dependencies**: All libraries loaded from CDN
3. **Cross-browser Compatible**: Works on modern browsers
4. **Print Optimized**: Each page is print-friendly
5. **Accessible**: ARIA labels and semantic HTML

---

## 🐛 Known Limitations

- CSV export works; PDF export requires backend
- Data is not persistent (no database connection)
- Modal dialogs are UI-only (no backend actions)
- File uploads not implemented
- Network requests not connected
- Authentication not implemented

---

## 💡 Tips for Development

1. **Test locally first** before deploying
2. **Use browser DevTools** to debug
3. **Check Console** for any JavaScript errors
4. **Validate HTML** with W3C Validator
5. **Test responsiveness** with mobile emulation
6. **Use a backend framework** (PHP, Node, Django, etc.)

---

## 📞 Support

For questions or issues:
1. Review the code comments in HTML files
2. Check Bootstrap 5 documentation
3. Inspect elements with DevTools
4. Test with sample data provided

---

## 📜 License

Free to use and modify for your Railway MCC Management System project.

---

## 🎓 Learning Resources

- **Bootstrap 5**: https://getbootstrap.com/docs/5.0/
- **Bootstrap Icons**: https://icons.getbootstrap.com/
- **MDN Web Docs**: https://developer.mozilla.org/
- **TailwindCSS** (alternative styling): https://tailwindcss.com/

---

**Happy Coding! 🚀**

Created: January 2026
Last Updated: April 1, 2026
