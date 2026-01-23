# Todo Talenta Digital

<div align="center">

![Todo Talenta Digital](https://img.shields.io/badge/Todo-Talenta%20Digital-0d6efd?style=for-the-badge&logo=checkmarx&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

**A Modern Task & Notes Management Platform with Premium Glassmorphism UI**

[Features](#-features) • [Screenshots](#-screenshots) • [Installation](#-installation) • [Usage](#-usage) • [Tech Stack](#-tech-stack)

</div>

---

## ✨ Features

### 🔐 Authentication System
- **Unified Split-Panel Auth** - Modern sliding transition between Login and Register forms
- **Glassmorphism Design** - Premium frosted-glass aesthetic with animated abstract backgrounds
- **User Approval Workflow** - Admin-controlled registration approval system
- **Real-time Password Strength** - Visual indicator for password security during registration
- **Show/Hide Password Toggle** - Intuitive icon-based password visibility control

### 📊 Dashboard
- **Statistics Overview** - Quick glance at todos, notes, and completion rates
- **Recent Activities** - Latest tasks and notes at your fingertips
- **Quick Actions** - One-click access to create new items
- **Personalized Greeting** - Welcome message with user context

### ✅ Task Management (Todos)
- **Priority Levels** - High, Medium, Low with color-coded indicators
- **Status Tracking** - Active, Completed, and Archived states
- **Due Date Management** - Calendar-based scheduling with overdue alerts
- **Rich Text Descriptions** - Formatted task details
- **Bulk Operations** - Multi-select for mass actions
- **Search & Filter** - Find tasks quickly with advanced filtering

### 📝 Notes Management
- **Sticky Note Interface** - Visual card-based note display
- **Color Categories** - Organize notes with custom colors
- **Archive System** - Keep important notes without clutter
- **Quick Edit** - Inline editing for rapid updates
- **Rich Text Editor** - Full formatting support

### 👥 User Management (Admin)
- **User Listing** - Comprehensive user directory with status indicators
- **Approval System** - Pending, Approved, and Inactive user states
- **Role Management** - Admin and User role assignments
- **View As User** - Admin can impersonate users for support
- **Batch Operations** - Approve, reject, or manage multiple users

### 🎨 Premium UI/UX
- **Glassmorphism Theme** - Modern semi-transparent design throughout
- **Dark Mode Support** - Toggle between light and dark themes with localStorage persistence
- **Persistent Navbar** - Navigation remains visible during page transitions
- **Skeleton Loading** - Elegant content placeholder during navigation
- **Smooth Animations** - Cubic-bezier transitions for premium feel
- **Responsive Design** - Fully optimized for desktop and mobile
- **Bootstrap Icons** - Consistent iconography across the application

---

## 📸 Screenshots

| Login Page | Dashboard |
|:---:|:---:|
| Split-panel glassmorphism auth | Statistics and quick access cards |

| Notes | Tasks |
|:---:|:---:|
| Colorful sticky note interface | Priority-based task management |

---

## 🚀 Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- Composer (for dependencies)

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/amroe2k/todo.git
   cd todo
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env_origin .env
   ```
   Edit `.env` with your database credentials:
   ```env
   DB_HOST=localhost
   DB_NAME=todo_talenta
   DB_USER=your_username
   DB_PASS=your_password
   ```

4. **Import database**
   ```bash
   mysql -u your_username -p your_database < database/todo_talenta.sql
   ```

5. **Set permissions** (Linux/Mac)
   ```bash
   chmod -R 755 .
   chmod -R 777 ssl/
   ```

6. **Access the application**
   ```
   http://localhost/todo/
   ```

---

## 👤 Usage

### Demo Credentials
| Role | Username | Password |
|:---:|:---:|:---:|
| Admin | `admin` | `password` |
| User | `user` | `user123` |

### User Workflow
1. **Register** - Create account via the Register panel
2. **Wait for Approval** - Admin must approve new accounts
3. **Login** - Access dashboard after approval
4. **Manage Tasks** - Create, edit, complete, and archive todos
5. **Organize Notes** - Add colorful notes for quick reference

### Admin Workflow
1. **Review Pending Users** - Approve or reject registrations
2. **Manage Users** - Activate, deactivate, or delete accounts
3. **View As User** - Impersonate users for troubleshooting
4. **Monitor Activity** - Track user engagement and productivity

---

## 🛠 Tech Stack

### Backend
| Technology | Purpose |
|:---|:---|
| **PHP 8.0+** | Server-side logic and API |
| **MySQL 8.0+** | Relational database |
| **PDO** | Secure database abstraction |

### Frontend
| Technology | Purpose |
|:---|:---|
| **Bootstrap 5.3** | Responsive CSS framework |
| **Bootstrap Icons** | Consistent iconography |
| **jQuery 3.6** | DOM manipulation and AJAX |
| **SweetAlert2** | Beautiful alert dialogs |
| **Custom CSS** | Glassmorphism styling |

### Security
| Feature | Implementation |
|:---|:---|
| **Password Hashing** | `password_hash()` with bcrypt |
| **SQL Injection Prevention** | PDO prepared statements |
| **XSS Protection** | `htmlspecialchars()` sanitization |
| **CSRF Protection** | Session-based validation |
| **Honeypot Fields** | Anti-bot registration protection |

---

## 📁 Project Structure

```
todo/
├── config/
│   └── database.php          # Database connection
├── css/
│   ├── auth.css              # Split-panel auth styling
│   ├── style.css             # Global styles & glassmorphism
│   ├── dark-mode.css         # Dark mode theme system
│   ├── dashboard.css         # Dashboard specific styles
│   ├── notes.css             # Notes page styling
│   ├── todos.css             # Tasks page styling
│   ├── users.css             # User management styling
│   ├── change-password.css   # Security settings styling
│   ├── login.css             # Legacy login styles
│   └── register.css          # Legacy register styles
├── database/
│   └── todo_talenta.sql      # Database schema
├── includes/
│   ├── auth.php              # Authentication class
│   └── functions.php         # Utility functions
├── js/
│   ├── utils.js              # Global utilities
│   ├── dashboard.js          # Dashboard logic
│   ├── notes.js              # Notes functionality
│   ├── todos.js              # Tasks functionality
│   ├── users.js              # User management
│   └── change-password.js    # Security settings
├── view/
│   ├── auth.php              # Unified login/register
│   ├── dashboard.php         # Main dashboard
│   ├── navbar.php            # Navigation component
│   ├── footer.php            # Footer component
│   ├── notes.php             # Notes management
│   ├── todos.php             # Tasks management
│   ├── users.php             # User management (admin)
│   ├── change-password.php   # Security settings
│   └── logout.php            # Session termination
├── index.php                 # Application router
├── .env                      # Environment configuration
├── .htaccess                 # Apache configuration
├── composer.json             # PHP dependencies
└── README.md                 # Documentation
```

---

## 🔧 Configuration

### Environment Variables
| Variable | Description | Default |
|:---|:---|:---|
| `DB_HOST` | Database host | `localhost` |
| `DB_NAME` | Database name | `todo_talenta` |
| `DB_USER` | Database username | - |
| `DB_PASS` | Database password | - |

### Apache Configuration
The `.htaccess` file includes:
- URL rewriting for clean routes
- Security headers
- HTTPS enforcement (optional)
- Caching rules

---

## 📄 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

---

## 👨‍💻 Author

**Alfa IT Solutions**

- Website: [Alfa IT Solutions](https://alfaitsolutions.com)
- GitHub: [@amroe2k](https://github.com/amroe2k)

---

## 🙏 Acknowledgments

- [Bootstrap](https://getbootstrap.com/) - CSS Framework
- [Bootstrap Icons](https://icons.getbootstrap.com/) - Icon Library
- [SweetAlert2](https://sweetalert2.github.io/) - Alert Library
- [jQuery](https://jquery.com/) - JavaScript Library

---

<div align="center">

**Made with ❤️ by Alfa IT Solutions**

© 2026 Todo Talenta Digital. All rights reserved.

</div>
