# 🎓 Portalia — Smart Campus Marketplace Information System

Portalia is a premium, web-based campus marketplace information system built using **PHP and MySQL (PDO)**. It is designed to provide students with a secure, mobile-first marketplace to buy, sell, and trade campus goods (such as textbooks, stationery, food, and electronics), while equipping university administrators with a comprehensive, professional management dashboard powered by a customized **AdminHMD** theme.

---

## 🌟 Key Features

### 🛒 Student Marketplace (Mobile-First Interface)
* **Modern Startup Splash Screen**: Engaging welcome page featuring geometric CSS illustrations.
* **Smart Search & Filters**: Instant sticky search bar combined with horizontally scrollable product category filters.
* **Product Detail Gallery**: Dynamic visual galleries showing condition badges, item stock, and seller identities.
* **AJAX Wishlist Engine**: Save favorite products instantly with state-preserving bookmark toggles.
* **Multi-Step Upload Wizard**: Guide students step-by-step through uploading listing photos, adding descriptions, categories, and set pricing.
* **Platform Fee Calculator**: Integrated real-time pricing calculator deducting a 5% platform fee (e.g., inputting `Rp 100.000` shows a `Rp 5.000` fee and `Rp 95.000` net seller earnings).
* **Interactive Buyer-Seller Chat**: Secure direct messaging featuring a simulated seller auto-reply engine (triggered by keywords like *hello, ready, meet, price*).
* **Frictionless Checkout Receipt**: Instantly generates an invoice summary and triggers delivery coordination messages upon purchase.

### 📊 Administrator Panel (AdminHMD Dashboard)
* **Real-time Analytics Metric Cards**: Keep track of registered students, active items, pending approvals, and platform revenue.
* **Platform Revenue Ledger**: Automatically aggregates the 5% maintenance fees from completed transactions.
* **Interactive Live Activity Feed**: Lists recent student registration, upload status updates, and sales.
* **User Directory Moderation**: Manage student accounts, suspension controls (preventing login), or permanent deletions.
* **Product Listing Moderation**: Review pending product requests to approve or reject with custom feedback.
* **Category CRUD Editor**: Create, edit, and delete marketplace categories mapped with Bootstrap icons.
* **Financial Auditing Reports**: Search transaction logs and download complete reports as a standardized CSV file.

---

## 📂 Directory Structure

The project has been structured into clean, isolated modules for a clean separation of concerns:

```text
adminhmd-1.0.0/
├── admin/                    # Administrator Workspace Area
│   ├── categories.php        # Manage product categories (CRUD)
│   ├── index.php             # Admin Dashboard overview & graphs
│   ├── products.php          # Moderation panel for pending product listings
│   ├── reports.php           # Sales monitoring log and CSV export
│   └── users.php             # Student account directory and suspension tools
│
├── assets/                   # Shared CSS, JS, Vendors, and Design tokens
│   ├── css/
│   │   ├── bootstrap.min.css # Core layout stylesheet
│   │   ├── style.css         # Admin custom dashboard styling
│   │   └── portalia.css      # Custom student marketplace stylesheet
│   ├── js/                   # Bootstrapping and layout scripts
│   └── vendors/              # Bootstrap Icons pack
│
├── config/                   # Centralized Configuration (Optional)
│   # Shared database configs can also reside here
│
├── marketplace/              # Student Marketplace Area (Mobile View)
│   ├── chat.php              # Chat inbox and message threads
│   ├── index.php             # Marketplace homepage with recommendations
│   ├── login.php             # Authentication page for students & guest accounts
│   ├── logout.php            # Session termination logic
│   ├── product.php           # Product detailed page & purchase triggers
│   ├── profile.php           # Student dashboard and listing status tabs
│   ├── register.php          # Account creation interface for new students
│   ├── upload.php            # Guided product listing wizard
│   ├── welcome.php           # Starting splash welcome view
│   ├── wishlist.php          # Saved items grid
│   └── wishlist_toggle.php   # Wishlist state AJAX controller
│
├── uploads/                  # User-uploaded product images folder
│
├── database.sql              # Database structure and mock data inserts
├── db.php                    # Central PDO database connection and utility library
├── index.php                 # Root session router (Entry Point)
├── setup.php                 # Dynamic database initializer script
└── DESIGN.md                 # UI/UX design specifications
```

---

## ⚙️ Requirements & Environment

To run this application locally, ensure you have:
* **PHP**: version `7.4` or higher.
* **Database**: MySQL / MariaDB.
* **Web Server**: Apache (integrated in setups like XAMPP, Laragon, or MAMP).

---

## 🚀 Setup & Installation Guide

### Step 1: Clone or Place the Project
Move the entire folder `adminhmd-1.0.0` into your local server's document root directory:
* **XAMPP**: `C:\xampp\htdocs\portalia\`
* **Laragon**: `C:\laragon\www\portalia\`

### Step 2: Initialize the Database (Automatic Setup)
Portalia includes an automatic setup script that initializes the database, configures tables, and inserts initial mock data without needing to open phpMyAdmin manually:
1. Turn on **Apache** and **MySQL** in your local server control panel (XAMPP / Laragon).
2. Open your web browser and navigate to:
   ```text
   http://localhost/portalia/setup.php
   ```
3. Click the **"Initialize Database"** button. The script will output confirmation logs and automatically redirect you to the welcome screen.

> [!NOTE]
> **Manual Database Setup (Alternative)**:
> If you prefer manual installation:
> 1. Open phpMyAdmin and create a database named `portaliadb`.
> 2. Import the [database.sql](file:///d:/School/Pemweb/ProjectUAS/adminhmd-1.0.0/database.sql) file.
> 3. Verify that credentials in `db.php` match your local MySQL configuration (Default: host `localhost`, username `root`, empty password).

### Step 3: Run the Application
Access the central root router from your browser:
```text
http://localhost/portalia/index.php
```
*The router will automatically redirect you based on your login status: guest/anonymous users will land on `marketplace/welcome.php`.*

---

## 🔑 Demo Account Credentials

You can test both user roles with the following preconfigured accounts:

| Role | Email | Password | Details |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@portalia.ac.id` | `password` | Complete access to the Admin panel dashboard |
| **Student (Seller)** | `budi@student.ac.id` | `password` | Has preconfigured active, pending, and sold listings |
| **Student (Buyer)** | `siti@student.ac.id` | `password` | Has active wishlist items |
| **Guest Mode** | *(No credentials)* | *N/A* | Click "Browse as Guest" on the welcome page |

---

## 🔒 Technical Specifications

* **Prepared Statements (PDO)**: Clean parameterized queries are implemented throughout the application to guard against SQL Injection vulnerabilities.
* **XSS Defenses**: All user inputs are sanitized with HTML entity encoders on rendering.
* **Secure Session Handling**: Role-based access validation stops students from entering `/admin/` directories and restricts guests from using chat and upload panels.
* **Image Upload Filters**: Implemented size restrictions (max 5MB) and mime-type verification filters for safer file storage.

---

## 🎨 Attributions & License
* **Dashboard Template**: Built on top of the open-source [AdminHMD](https://themewagon.com/themes/adminhmd/) design layout by Md. Hasan Mahmud, customized for dynamic PHP database rendering.
* **Icons**: [Bootstrap Icons Pack](https://icons.getbootstrap.com/).
