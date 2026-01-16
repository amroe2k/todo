# Todo Talenta Digital - Dokumentasi Aplikasi

## 📋 Deskripsi

Aplikasi Todo Management System adalah sistem manajemen tugas, catatan, dan pengguna berbasis web yang dibangun dengan PHP, MySQL, dan Bootstrap. Aplikasi ini menyediakan fitur-fitur lengkap untuk mengelola todo list, sticky notes, dan user management dengan antarmuka yang modern dan responsif.

## 🚀 Fitur Utama

### 1. **Dashboard**

- Tampilan ringkasan statistik (Total Tasks, Completed, Pending, Overdue)
- Activity log dengan pagination
- Recent activity dari semua modul (todos, notes, users)
- Smooth scroll ke user information section
- Welcome toast notification

### 2. **User Management**

- **CRUD Operations**: Create, Read, Update, Delete users
- **User Approval System**: Admin dapat approve/reject user baru
- **Role Management**: Admin dan User roles
- **Excel Import**: Import multiple users dari file Excel
- **View As User**: Admin dapat melihat aplikasi sebagai user lain
- **Password Management**:
  - Change password dengan validasi
  - Generate random password
  - Password strength indicator
- **Filter & Pagination**: Filter berdasarkan role dan status

### 3. **Todo List Management**

- **CRUD Operations**: Create, Read, Update, Delete, Archive
- **Priority Levels**: High (red), Medium (yellow), Low (green)
- **Status Management**: Pending, In Progress, Completed
- **Due Date**: Set dan track deadline
- **Filter System**: Filter by status dan priority
- **Rich Description**: Support untuk Quill editor formatting
- **Checkbox Toggle**: Quick status update
- **Complex Delete Confirmation**: Multiple step confirmation dengan animasi

### 4. **Sticky Notes**

- **Drag & Drop**: Posisi notes dapat diubah dengan drag
- **Color Customization**: 8 pilihan warna untuk notes
- **Rich Text Editor**: Quill editor dengan bullets, numbering, checklist
- **Archive System**: Archive dan restore notes
- **View Modal**: Preview full content notes
- **Auto-save Position**: Posisi tersimpan otomatis
- **Archived Notes Management**: View, restore, delete archived notes

### 5. **Authentication & Security**

- **Login System**: Secure authentication dengan password hashing
- **Registration**: User self-registration dengan admin approval
- **Password Validation**:
  - Minimum 8 karakter
  - Harus mengandung huruf besar, kecil, dan angka
- **Session Management**: Secure session handling
- **CSRF Protection**: Token-based form protection
- **Auto Logout**: Expired session handling

### 6. **UI/UX Features**

- **Page Transition Loading**: Smooth animations saat navigasi
  - Opacity 0.5 dengan backdrop-filter blur
  - 360° spinner rotation
  - 0.8s delay untuk smooth experience
- **Smooth Scrolling**: Anchor scrolling dengan offset navbar
- **Toast Notifications**: SweetAlert2 dengan custom styling
- **Responsive Design**: Mobile-friendly interface
- **Dark Mode Support**: Themed components
- **Animation Effects**: 6 custom keyframe animations
  - bounceIn, bounceInRight, fadeIn
  - shake, pulse, wobble

### 7. **Activity Logging**

- Auto-logging untuk semua aktivitas user
- Automatic cleanup saat mencapai limit (100 entries)
- Display recent activity dengan pagination
- Timestamp untuk setiap aktivitas

## 🛠️ Teknologi yang Digunakan

### Backend

- **PHP**: 7.4+
- **MySQL**: 5.7+ / MariaDB 10.3+
- **PDO**: Database abstraction layer
- **Password Hashing**: PHP password_hash() dengan bcrypt

### Frontend

- **Bootstrap**: 5.3.0-alpha1
- **jQuery**: 3.6.0
- **jQuery UI**: 1.13.1 (untuk drag & drop)
- **SweetAlert2**: v11 (notifications & dialogs)
- **Quill Editor**: 1.3.7 (rich text editor)
- **Font Awesome**: 6.4.0 (icons)
- **Bootstrap Icons**: Latest (additional icons)

### Struktur File

```
todo/
├── config/
│   └── database.php          # Database configuration
├── css/
│   └── style.css             # Consolidated styles (~1450 lines)
├── database/
│   └── db_todo.sql           # Database schema
├── includes/
│   ├── auth.php              # Authentication class
│   └── functions.php         # Helper functions
├── js/
│   ├── change-password.js    # Change password logic
│   ├── dashboard.js          # Dashboard interactions
│   ├── notes.js              # Notes management (~830 lines)
│   ├── register.js           # Registration form
│   ├── todos.js              # Todos management (~480 lines)
│   ├── users.js              # User management (~485 lines)
│   └── utils.js              # Shared utilities
├── ssl/                      # SSL certificates
├── view/
│   ├── change-password.php   # Change password page
│   ├── dashboard.php         # Main dashboard
│   ├── footer.php            # Footer template
│   ├── login.php             # Login page
│   ├── logout.php            # Logout handler
│   ├── navbar.php            # Navigation bar
│   ├── notes.php             # Notes management
│   ├── register.php          # Registration page
│   ├── todos.php             # Todos management
│   └── users.php             # User management
├── .env                      # Environment variables
├── .htaccess                 # Apache configuration
├── favicon.svg               # Application icon
├── index.php                 # Application entry point
└── README.md                 # Documentation (this file)
```

## 📦 Instalasi

### Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7+ / MariaDB 10.3+
- Apache/Nginx dengan mod_rewrite enabled
- Extension PHP: PDO, PDO_MySQL, mbstring, openssl

### Langkah Instalasi

1. **Clone atau Download Repository**

   ```bash
   git clone https://github.com/amroe2k/todo.git
   cd todo
   ```

2. **Konfigurasi Database**

   - Buat database MySQL baru:
     ```sql
     CREATE DATABASE db_todo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     ```
   - Import schema database:
     ```bash
     mysql -u username -p db_todo < database/db_todo.sql
     ```

3. **Konfigurasi Environment**

   - Copy file `.env.example` menjadi `.env`:
     ```bash
     cp .env.example .env
     ```
   - Edit file `.env` dengan kredensial database Anda:
     ```
     DB_HOST=localhost
     DB_NAME=db_todo
     DB_USER=your_username
     DB_PASS=your_password
     ```

4. **Set Permissions**

   ```bash
   chmod 755 -R .
   chmod 644 .env
   ```

5. **Akses Aplikasi**
   - Buka browser dan akses: `http://localhost/todo/`
   - Atau jika menggunakan virtual host: `https://todo.test/`

### Default Demo Accounts

Setelah import database, gunakan kredensial berikut untuk testing:

#### Admin Account

- **Username**: admin
- **Password**: password
- **Role**: Administrator
- **Access**: Full application access (User Management, Settings, etc.)

#### Regular User Account

- **Username**: user
- **Password**: user123
- **Role**: User
- **Access**: Todo, Notes, Change Password

> **⚠️ PENTING**: Ganti password kedua akun ini segera setelah login pertama kali untuk keamanan!

## 🎨 Struktur Database

### Tabel: `users`

```sql
- id (INT, PK, AUTO_INCREMENT)
- username (VARCHAR 50, UNIQUE)
- email (VARCHAR 100, UNIQUE)
- password (VARCHAR 255, hashed)
- role (ENUM: admin, user)
- is_approved (TINYINT, 0=pending, 1=approved)
- is_active (TINYINT, 0=inactive, 1=active)
- created_at (TIMESTAMP)
```

### Tabel: `todos`

```sql
- id (INT, PK, AUTO_INCREMENT)
- user_id (INT, FK → users.id)
- task (VARCHAR 255)
- description (TEXT)
- priority (ENUM: low, medium, high)
- status (ENUM: pending, in_progress, completed)
- due_date (DATE, nullable)
- is_archived (TINYINT, default 0)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### Tabel: `notes`

```sql
- id (INT, PK, AUTO_INCREMENT)
- user_id (INT, FK → users.id)
- title (VARCHAR 255)
- content (TEXT)
- color (VARCHAR 7, hex color)
- position_x (INT, default 0)
- position_y (INT, default 0)
- is_archived (TINYINT, default 0)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### Tabel: `activity_log`

```sql
- id (INT, PK, AUTO_INCREMENT)
- user_id (INT, FK → users.id)
- action (VARCHAR 255)
- description (TEXT)
- module (VARCHAR 50: todos, notes, users)
- created_at (TIMESTAMP)
```

## 🔐 Security Features

1. **Password Hashing**: Menggunakan `password_hash()` dengan algoritma bcrypt
2. **SQL Injection Prevention**: Prepared statements dengan PDO
3. **XSS Protection**: `htmlspecialchars()` untuk output sanitization
4. **CSRF Protection**: Session-based token validation
5. **Input Validation**: Server-side dan client-side validation
6. **Session Security**:
   - Regenerate session ID setelah login
   - HttpOnly cookies
   - Secure cookies (untuk HTTPS)
7. **Role-Based Access Control**: Admin dan User permissions
8. **Auto-logout**: Session timeout handling

## 📱 Responsive Design

Aplikasi fully responsive dengan breakpoints:

- **Desktop**: ≥ 1200px (optimal experience)
- **Tablet**: 768px - 1199px
- **Mobile**: < 768px (simplified UI)

### Mobile Optimizations

- Touch-friendly buttons dan inputs
- Collapsible navigation menu
- Stacked card layouts
- Optimized modal sizes
- Always-visible action buttons pada notes dan todos

## 🎯 Fitur Khusus

### 1. Activity Log Cleanup

Automatic cleanup untuk mencegah database bloat:

- Maximum 100 entries per user per module
- Auto-delete oldest entries saat limit tercapai
- Maintain performance dengan pagination

### 2. Excel Import untuk Users

Format Excel yang didukung:

```
| Username | Email | Password | Role | Is Approved | Is Active |
|----------|-------|----------|------|-------------|-----------|
| user     | ...   | ...      | user | 1           | 1         |
```

### 3. View As User Feature

Admin dapat:

- Melihat aplikasi dari perspektif user lain
- Testing user experience
- Debugging user-specific issues
- Badge indicator saat viewing as another user

### 4. Position Persistence (Sticky Notes)

- Auto-save posisi saat drag selesai
- AJAX update ke server
- Restore posisi saat page reload
- Smooth dragging dengan containment

## 🐛 Troubleshooting

### Issue: Page transition tidak berfungsi

**Solusi**: Pastikan `navbar.php` sudah di-include dan JavaScript loaded dengan benar.

### Issue: Drag & drop notes tidak work

**Solusi**: Pastikan jQuery UI sudah loaded sebelum `notes.js`.

### Issue: Excel import gagal

**Solusi**:

- Periksa format file Excel
- Pastikan PHP extension `zip` dan `xml` aktif
- Cek permissions pada upload directory

### Issue: Database connection error

**Solusi**:

- Periksa kredensial di file `.env`
- Pastikan MySQL service berjalan
- Test connection dengan `mysql -u username -p`

### Issue: CSS/JS tidak ter-load

**Solusi**:

- Clear browser cache (Ctrl+F5)
- Periksa path file di HTML
- Cek console browser untuk errors

## 🔄 Update & Maintenance

### Update Aplikasi

```bash
git pull origin main
# Review changes
# Backup database sebelum update
mysqldump -u username -p db_todo > backup_$(date +%Y%m%d).sql
# Test di development environment terlebih dahulu
```

### Database Backup

Backup rutin direkomendasikan:

```bash
# Manual backup
mysqldump -u username -p db_todo > backup.sql

# Automated backup (cron job)
0 2 * * * mysqldump -u username -p'password' db_todo > /path/to/backup/db_todo_$(date +\%Y\%m\%d).sql
```

### Clear Cache

```bash
# Clear PHP opcache (jika digunakan)
service php-fpm reload

# Clear session files
rm -rf /var/lib/php/sessions/*
```

## 📊 Performance Tips

1. **Enable OPcache**: Untuk production, aktifkan PHP OPcache
2. **Database Indexing**: Index sudah dibuat pada foreign keys
3. **CDN Usage**: CSS/JS library loaded dari CDN
4. **Lazy Loading**: Pagination untuk large datasets
5. **AJAX**: Reduce full page reloads
6. **Minification**: Minify CSS/JS untuk production

## 🤝 Contributing

Kontribusi sangat diterima! Untuk berkontribusi:

1. Fork repository ini
2. Buat branch baru (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

### Coding Standards

- Follow PSR-12 untuk PHP code
- Use meaningful variable names
- Comment complex logic
- Write clean, readable code
- Test before commit

## 📝 Changelog

### Version 1.0.0 (Current)

- ✅ Initial release
- ✅ User management dengan approval system
- ✅ Todo management dengan archive
- ✅ Sticky notes dengan drag & drop
- ✅ Activity logging
- ✅ Page transition animations
- ✅ Responsive design
- ✅ Code organization (separated CSS/JS)

### Planned Features (v1.1.0)

- 📋 Todo categories/tags
- 📊 Dashboard charts & analytics
- 🔔 Email notifications
- 📱 PWA support
- 🌙 Dark mode toggle
- 🔍 Advanced search & filters
- 📤 Export data (PDF, Excel)
- 🔄 Real-time updates (WebSocket)

## 📄 License

Aplikasi ini dibuat untuk keperluan pembelajaran dan portfolio. Free to use and modify.

## 👨‍💻 Developer

Dikembangkan dengan ❤️ untuk Talenta Digital

**Contact:**

- Email: your.email@example.com
- GitHub: [@yourusername](https://github.com/yourusername)
- Portfolio: https://yourportfolio.com

## 🙏 Acknowledgments

- Bootstrap Team untuk framework CSS
- SweetAlert2 untuk beautiful alerts
- Quill Team untuk rich text editor
- jQuery & jQuery UI Teams
- Font Awesome untuk icon library
- Semua open source contributors

---

**Last Updated**: January 4, 2026

**Version**: 1.0.0

**Status**: ✅ Production Ready
