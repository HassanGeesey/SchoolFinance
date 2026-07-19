# IMS - Institute Management System

PHP-based English Institute Management System with MySQL database.

## Quick Setup

1. **Database**: Import `database.sql` into MySQL (database: `english_institute`)
2. **Config**: Edit `config.php` with MySQL credentials (default: root/empty password)
3. **URL**: Access via `http://localhost/IMS/` (or `http://localhost/IMS/v2/` if using v2 subdirectory)
4. **Login**: Username: `admin`, Password: `admin123`

## Project Structure

- `v2/` - Current stable version (most features)
- `v1/` - Legacy version
- `config.php` - Database connection & auth functions
- `database.sql` - Full schema with seed data
- `css/design-system.css` - Global styles (CSS variables for theming)
- `uploads/` - File uploads (logos, etc.)

## Key Conventions

- Use `requireLogin()` on all authenticated pages
- Use `requireAdmin()` for admin-only pages
- Use `getDB()` for database queries (PDO)
- Use `isAdmin()` / `isTeacher()` to check roles
- Session uses `$_SESSION['user_id']` and `$_SESSION['role']`
- All user input must use `htmlspecialchars()` for output

## Common Tasks

**Add new page**: Include `header.php` at top, wrap content in `<main>`, close with `footer.php`

**Add database table**: Add to `database.sql` with proper foreign keys

**Add user role check**:
```php
if (!isAdmin()) { header('Location: index.php'); exit; }
```

## Design System

Use existing CSS classes: `.card`, `.btn`, `.table`, `.badge`, `.alert`, `.form-control`

Dark mode supported via `[data-theme="dark"]` on `<html>` and CSS variables.

## Git Workflow

1. Make changes
2. `git add <files>`
3. `git commit -m "message"`
4. `git push` (remote: origin, branch: master)

Use `gh repo create` to create GitHub repo if needed.
