# 🎓 Portalia — Smart Campus Marketplace Information System

Portalia is a premium, web-based campus marketplace information system built using **PHP and PostgreSQL (Supabase)**. It is designed to provide students with a secure, mobile-first marketplace to buy, sell, and trade campus goods (such as textbooks, stationery, food, and electronics), while equipping university administrators with a comprehensive, professional management dashboard powered by a customized **AdminHMD** theme.

The application has been successfully migrated to **Supabase PostgreSQL** to offer high performance, cloud database storage, and modern database administration tools.

---

## 🌟 Key Features

### 🛒 Student Marketplace (Mobile-First & Fully Responsive)
* **Modern Startup Splash Screen**: Engaging welcome page featuring geometric CSS illustrations.
* **Smart Search & Filters**: Instant sticky search bar combined with horizontally scrollable product category filters.
* **Product Detail Gallery**: Dynamic visual galleries showing condition badges, item stock, and seller identities.
* **AJAX Wishlist Engine**: Save favorite products instantly with state-preserving bookmark toggles.
* **Multi-Step Upload Wizard**: Guide students step-by-step through uploading listing photos, adding descriptions, categories, and set pricing.
* **Platform Fee Calculator**: Integrated real-time pricing calculator deducting a 5% platform fee (e.g., inputting `Rp 100.000` shows a `Rp 5.000` fee and `Rp 95.000` net seller earnings).
* **Interactive Buyer-Seller Chat**: Secure direct messaging featuring a simulated seller auto-reply engine (triggered by keywords like *hello, ready, meet, price*).
* **Frictionless Checkout Receipt**: Instantly generates an invoice summary and triggers delivery coordination messages upon purchase.
* **Product Rating & Review System**: Empower students to leave feedback and ratings (1 to 5 stars) on purchased items. Reviews are displayed publicly on product detail pages.

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
portaliaMarketplace/
├── admin/                    # Administrator Dashboard Files
│   ├── categories.php        # Manage product categories (CRUD)
│   ├── index.php             # Admin Dashboard overview & statistics
│   ├── products.php          # Moderation panel for pending product listings
│   ├── reports.php           # Sales monitoring log and CSV export
│   └── users.php             # Student account directory and suspension tools
│
├── assets/                   # Shared CSS, JS, Images, and Design tokens
│   ├── css/
│   │   ├── bootstrap.min.css # Core layout stylesheet
│   │   ├── style.css         # Admin custom dashboard styling
│   │   └── portalia.css      # Custom student marketplace stylesheet
│   ├── images/               # Avatars, banners, and default image assets
│   ├── js/                   # Bootstrapping and layout scripts
│   └── vendors/              # Bootstrap Icons pack
│
├── marketplace/              # Student Marketplace Area (Mobile & Desktop Views)
│   ├── _desktop_navbar.php   # Reusable top navigation for larger viewports
│   ├── chat.php              # Chat inbox and message threads
│   ├── index.php             # Marketplace homepage with recommendations
│   ├── login.php             # Authentication page for students & guest accounts
│   ├── logout.php            # Session termination logic
│   ├── product.php           # Product detailed page & purchase triggers
│   ├── profile.php           # Student dashboard and listing status tabs
│   ├── register.php          # Account creation interface for new students
│   ├── review_add.php        # Add review and rating system
│   ├── upload.php            # Guided product listing wizard
│   ├── welcome.php           # Starting splash welcome view
│   ├── wishlist.php          # Saved items grid
│   └── wishlist_toggle.php   # Wishlist state AJAX controller
│
├── sessions/                 # Local directory for PHP session state files
├── supabase/                 # Cloud database PostgreSQL schemas
│   ├── schema.sql            # Table structures, constraints, and relationship declarations
│   └── seed.sql              # Initial system seed data (users, categories, products, etc.)
│
├── .env                      # Local environment variables containing credentials (git-ignored)
├── .env.example              # Sample environment template file
├── database.sql              # Legacy MySQL schema for reference
├── db.php                    # Central PDO database connection and utility library
├── index.php                 # Root session router (Entry Point)
├── DESIGN.md                 # UI/UX design specifications
├── AGENTS.md                 # Agent and developer instruction notes
└── serve.bat                 # Helper script to run local PHP server
```

---

## ⚙️ Requirements & Environment

To run this application locally, ensure you have:
* **PHP**: version `7.4` or higher (ensure `pdo_pgsql` and `pgsql` extensions are enabled in your `php.ini` file).
* **Database**: Supabase PostgreSQL database instance (or a local PostgreSQL server).
* **Web Server**: Built-in PHP development server or local server setup like Apache (Laragon, XAMPP, or MAMP) configured with PostgreSQL support.

---

## 🚀 Setup & Installation Guide

### Step 1: Prepare the Codebase
Clone or copy the project files to your server directory (e.g., `C:\laragon\www\portaliaMarketplace` or `C:\xampp\htdocs\portaliaMarketplace`).

### Step 2: Database Setup on Supabase
1. Create a free account and a new project at [Supabase](https://supabase.com).
2. Go to the **SQL Editor** in your Supabase project dashboard.
3. Open [schema.sql](file:///d:/School/Pemweb/ProjectUAS/portaliaMarketplace/supabase/schema.sql) and paste its contents into the editor, then run it to create tables.
4. Open [seed.sql](file:///d:/School/Pemweb/ProjectUAS/portaliaMarketplace/supabase/seed.sql) and run its contents to seed mock data.
5. In your Supabase project, navigate to **Settings** > **Database** and copy your **Connection Parameters** (Host, Port, Database Name, User, Password).

### Step 3: Configure Environment Variables
1. Copy the [.env.example](file:///d:/School/Pemweb/ProjectUAS/portaliaMarketplace/.env.example) file and rename it to `.env`:
   ```bash
   cp .env.example .env
   ```
2. Open the `.env` file and replace the values with your actual Supabase database credentials:
   ```env
   DB_HOST=your-supabase-db-host-pooler.supabase.com
   DB_PORT=6543
   DB_NAME=postgres
   DB_USER=postgres.your-project-id
   DB_PASSWORD=your-secure-database-password
   ```

### Step 4: Enable PostgreSQL Extensions in PHP
Make sure that your PHP installation has the PostgreSQL PDO extension enabled. Open your `php.ini` file and ensure the following lines are uncommented:
```ini
extension=pdo_pgsql
extension=pgsql
```
Restart your local server/PHP service after saving changes.

### Step 5: Run the Server
Double-click [serve.bat](file:///d:/School/Pemweb/ProjectUAS/portaliaMarketplace/serve.bat) or open a terminal in the root directory and run:
```bash
php -S localhost:8000
```
Open your browser and navigate to:
```text
http://localhost:8000
```
*The router will automatically redirect you based on your login status: guest/anonymous users will land on the splash screen at [welcome.php](file:///d:/School/Pemweb/ProjectUAS/portaliaMarketplace/marketplace/welcome.php).*

---

## 🔑 Demo Account Credentials

You can test both user roles with the following preconfigured accounts:

| Role | Email | Password | Details |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@portalia.ac.id` | `password` | Complete access to the Admin panel dashboard |
| **Student (Seller)** | `budi@student.ac.id` | `password` | Has preconfigured active, pending, and sold listings |
| **Student (Buyer)** | `siti@student.ac.id` | `password` | Has active wishlist items and product reviews |
| **Guest Mode** | *(No credentials)* | *N/A* | Click "Browse as Guest" on the welcome page |

---

## 🔒 Technical Specifications

* **Prepared Statements (PDO)**: Safe parameterized queries are implemented throughout the application using PHP Data Objects (PDO) with PostgreSQL drivers to guard against SQL Injection vulnerabilities.
* **XSS Defenses**: All user inputs are sanitized with HTML entity encoders on rendering via the custom helper `sanitize()` in [db.php](file:///d:/School/Pemweb/ProjectUAS/portaliaMarketplace/db.php).
* **Secure Session Handling**: Role-based access validation stops students from entering `/admin/` directories and restricts guests from using chat and upload panels.
* **Image Upload Filters**: Implemented size restrictions (max 5MB) and mime-type verification filters for safer file storage.

---

## 🎨 Design System & Visuals

Portalia utilizes a modern, clean, student-centric design specified in [DESIGN.md](file:///d:/School/Pemweb/ProjectUAS/portaliaMarketplace/DESIGN.md):
* **Colors**: 
  - Primary Accent: `#4F8CFF` (Vibrant Blue)
  - Secondary Accent: `#7B61FF` (Vivid Purple)
  - Background: `#F7F9FC` (Soft Light Gray)
  - Text Primary: `#1F2937`
* **Typography**: Poppins (with fallback sans-serif) for clean modern layouts.
* **Feel**: White cards, soft shadows (`0 4px 12px rgba(0,0,0,0.08)`), rounded corners (up to `24px` for pills/containers), and fluid hover interactions.

---

## 🎨 Attributions & License
* **Dashboard Template**: Built on top of the open-source [AdminHMD](https://themewagon.com/themes/adminhmd/) design layout by Md. Hasan Mahmud, customized for dynamic PHP PostgreSQL database rendering.
* **Icons**: [Bootstrap Icons Pack](https://icons.getbootstrap.com/).
