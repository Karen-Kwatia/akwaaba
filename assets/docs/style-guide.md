
# Akwaaba 360 - Shared Style Guide

## 1. Brand Identity

Akwaaba 360 is a Ghanaian tourism platform that helps
visitors discover tourist destinations and local culture.

## 2. Colour Palette

| Colour | Hex Code | Usage |
|---|---|---|
| Forest Green | #12372A | Header, footer and branding |
| Heritage Green | #2E8B70 | Buttons and highlights |
| Warm Cream | #F5F3ED | Main background |
| Gold | #D9A441 | Secondary buttons and accents |
| Dark Grey | #252525 | Main text |

## 3. Typography

Primary font: Arial, Helvetica, sans-serif.

Use clear headings and readable paragraph text.

## 4. Shared Files

- assets/css/style.css: Main stylesheet.
- includes/header.php: Shared navigation and page header.
- includes/footer.php: Shared website footer.
- includes/auth.php: Authentication and role protection.
- includes/db.php: Database connection.

## 5. Reusable CSS Classes

- .container: Main page content container.
- .content-card: White content section.
- .btn: General button styling.
- .btn-primary: Main action button.
- .btn-secondary: Secondary action button.
- .site-header: Shared navigation header.
- .site-footer: Shared footer.

## 6. Development Rules

1. Use the shared header and footer instead of creating
   separate versions for individual pages.
2. Use the shared colour palette.
3. Use prepared statements for database queries.
4. Protect restricted pages using includes/auth.php.
5. Use htmlspecialchars() when displaying user-provided text.
6. Make pages responsive on mobile devices.
7. Do not modify shared files without communicating
   with the team.
