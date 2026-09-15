<div align="center">

# StudentCare

**A simple, welcoming space for student counselling.**

Book appointments, connect with counselors, and manage student support in one place.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white)
![MariaDB](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-003545?style=flat-square&logo=mariadb&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=222222)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat-square&logo=html5&logoColor=white)
![CSS](https://img.shields.io/badge/CSS-663399?style=flat-square&logo=css&logoColor=white)

[![Quick Start](https://img.shields.io/badge/Quick_Start-2E6251?style=for-the-badge)](#quick-start)
[![Features](https://img.shields.io/badge/Features-405348?style=for-the-badge)](#features)
[![Demo Accounts](https://img.shields.io/badge/Demo_Accounts-687866?style=for-the-badge)](#demo-accounts)

</div>

## Features

| Workspace | What you can do |
| :--- | :--- |
| 🎓 **Student** | Register, request or cancel appointments, message counselors, and submit feedback after completed sessions. |
| 💬 **Counselor** | Review requests, approve or reject appointments, mark sessions completed, and reply to students. |
| ⚙️ **Admin** | Manage accounts and passwords, update appointments, and view completed sessions and the counselor directory. |

Responsive layouts, consistent navigation, and clear appointment status labels across all dashboards.

## Quick Start

**Requirements:** XAMPP with Apache, PHP 8.2 and the `mysqli` extension, plus MySQL or MariaDB. No npm or Composer installation is needed.

1. Place the project in `C:\xampp\htdocs\student-counselling-system`.
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin` and import [`student_counselling.sql`](student_counselling.sql). It creates the database, tables, relationships, and demo accounts.
4. Check the connection settings in [`php/db.php`](php/db.php):

   | Host | Database | Username | Password |
   | :--- | :--- | :--- | :--- |
   | `localhost` | `student_counselling` | `root` | Empty by default |

5. Visit **`http://localhost/student-counselling-system/`**.

> The SQL import recreates the application's tables. Use it for a fresh demo setup, or back up existing records first.

## Demo Accounts

| Role | Email | Password |
| :--- | :--- | :--- |
| Student | `user@gmail.com` | `user` |
| Counselor | `sarah@gmail.com` | `sarah` |
| Admin | `admin@gmail.com` | `admin` |

**Demo use only:** passwords are deliberately stored as plain text. Use dummy credentials and sample data.

## Project Structure

```text
├── index.html                  # Public home page
├── login.html / register.html  # Account access
├── student.html                # Student workspace
├── counselor.html              # Counselor workspace
├── admin.html                  # Admin workspace
├── css/                        # Shared visual styles
├── js/                         # Dashboard actions and navigation
├── php/                        # Database, sessions, and request handlers
└── student_counselling.sql      # Schema and demo records
```

**Troubleshooting:** use the localhost URL so PHP runs correctly. For database errors, check MySQL and `php/db.php`. After UI updates, press **Ctrl+F5** to refresh cached assets.
