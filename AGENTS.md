# AGENTS.md

## Project Overview

This project is **Portalia**, a PHP-based campus marketplace web application for students to buy, sell, and manage products within the university environment.

The current codebase uses:

* PHP
* MySQL / existing database connection
* Bootstrap
* Bootstrap Icons
* Custom CSS in `assets/css/portalia.css`

This project is currently in the **Frontend Improvement Phase**.

---

## Current Development Focus

The current priority is to improve the **frontend UI/UX only**.

Focus on:

* Responsive layout
* Desktop full-width improvement
* Mobile layout polish
* Product card design
* Product image handling
* Marketplace homepage UI
* Product detail UI
* Navigation and bottom bar polish
* Clean spacing, typography, shadows, and rounded corners

Do **not** work on Supabase integration yet.

---

## Important Rules

### Do Not Modify Backend Logic

Do not change:

* Authentication/session logic
* Login/register flow
* SQL queries
* Database schema
* Transaction logic
* Wishlist backend logic
* Chat backend logic
* PHP business rules

Only modify PHP markup when it is needed for frontend layout improvement.

---

## Frontend Rules

Use the existing stack:

* PHP templates/pages
* Bootstrap utility classes
* Bootstrap Icons
* Custom CSS

Do not convert the project to:

* React
* Vue
* Next.js
* Laravel
* Any frontend framework

Keep the project as a PHP-based application.

---

## Layout Goals

The app should feel good on both desktop and mobile.

### Desktop

* Use a wider layout.
* Avoid narrow mobile-app-like containers.
* Content should use most of the screen width.
* Recommended max width: `1320px` to `1440px`.
* Product grid should show multiple columns naturally.
* Avoid huge empty left and right spaces.

### Mobile

* Keep the layout clean and comfortable.
* Use full width with around `16px` padding.
* Avoid horizontal scrolling.
* Bottom navigation should remain usable.
* Sticky CTA should not block important content.

---

## Marketplace Homepage Goals

Improve the homepage UI:

* Better header section
* Cleaner search bar
* Responsive category pills
* Better product grid
* Better product cards
* Better image placeholders
* Modern campus marketplace feel

Product grid recommendation:

```css
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 20px;
}
```

Use responsive media queries where needed.

---

## Product Card Goals

Product cards should have:

* Consistent height
* Good image ratio
* `object-fit: cover` for images
* Clean placeholder when image is missing
* Visible price
* Clear category label
* Floating wishlist button
* Smooth hover effect on desktop
* Clean layout for long product names

---

## Product Detail Page Goals

Improve product detail page layout.

### Desktop

Use a two-column layout:

* Left side: product image/gallery
* Right side: product information, price, seller card, CTA

### Mobile

Use stacked layout:

* Image first
* Product info below
* Seller card
* Sticky CTA at bottom

Keep all existing PHP variables and actions unchanged.

---

## CSS Architecture

Prioritize editing:

```txt
assets/css/portalia.css
```

Use CSS variables for:

* Colors
* Spacing
* Border radius
* Shadows
* Typography scale

Avoid excessive inline styles.

Move repeated inline styles into reusable CSS classes when safe.

Do not remove class names that may be used by JavaScript or PHP logic.

---

## Visual Direction

The UI should feel:

* Clean
* Modern
* Friendly
* Student-focused
* Trustworthy
* Similar to a polished campus marketplace/e-commerce app

Use:

* White cards
* Soft shadows
* Rounded corners
* Light background
* Blue/purple Portalia accent
* Subtle gradients only when appropriate

Avoid:

* Neon colors
* Overly colorful UI
* Heavy gradients
* Crowded sections
* Unnecessary animations

---

## Safety Checklist Before Editing

Before making changes, inspect:

* `index.php`
* `product.php`
* `upload.php`
* `profile.php`
* `wishlist.php`
* `chat.php`
* `assets/css/portalia.css`

When editing:

1. Keep PHP logic intact.
2. Keep form actions intact.
3. Keep links intact.
4. Keep JavaScript selectors intact.
5. Keep session/auth checks intact.
6. Only change layout, classes, and CSS.
7. Test desktop and mobile responsiveness.

---

## Current Phase Reminder

This phase is only for:

```txt
Frontend UI/UX improvement
```

Do not start:

```txt
Supabase integration
Login storage migration
Database redesign
API refactor
Authentication rewrite
```

Those will be handled in a later phase.

## Current Phase: Supabase Database Integration

The project is now entering the Supabase database integration phase.

Focus on:
- Reading `database.sql`
- Converting MySQL schema to PostgreSQL-compatible Supabase SQL
- Creating `supabase/schema.sql`
- Creating `supabase/seed.sql` if needed
- Updating `db.php` to connect to Supabase PostgreSQL
- Preserving all existing PHP behavior

Do not:
- Rewrite the app into another framework
- Remove existing PHP pages
- Break frontend layout
- Expose Supabase credentials
- Put real secrets in Git
- Change UI unless required for database integration