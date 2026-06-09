# Pengabdian Logbook KKN - AI Coding Agent Instructions

## Project Overview
This is a **PHP-native + MySQL proof-of-concept** (PoC) web application for UGM KKN (community service) student logbook management. Single-page routing pattern with dual-role access: authenticated mahasiswa (students) for logbook input, and public role for read-only search/map views. Deployed on Laragon (Apache + MySQL).

## Architecture & Key Patterns

### Single-File MVC Pattern
- **`index.php`** (796 lines): Monolithic handler combining routing, business logic, and view rendering
  - Form POST actions dispatched via `$_POST['action']` parameter: `login_simulation`, `save_profile`, `save_logbook`
  - Page routing via `$_GET['page']` parameter (see `page_title()` map for all routes)
  - Each page block: `if ($page === 'name') { ... layout_start(); ...content... layout_end(); exit; }`
- **`db.php`** (89 lines): Database connection singleton + helper functions
- **`config.php`**: Environment constants (DB credentials, `GOOGLE_SSO_SIMULATION` flag)

### Database Model
6 tables in `logbook_kkn` database:
- **students**: User accounts (email login), biodata fields (faculty, program, phone, emergency_contact)
- **logbooks**: Core records linking student→theme→program→activity_type→location + timestamp, health status, community count, notes
- **Master tables** (themes, programs, activity_types, locations): Dynamic dropdown data populated via `get_or_create_master()` function

**Key Design**: New master data entered in form fields → auto-inserted to DB → immediately available as dropdown options in future forms.

### Layout & UI Components
- **Sidebar Navigation** (`layout_start()`): Two-column grid layout (.shell with .sidebar + .content)
  - Role-aware menu: logged-in students see dashboard/data_kkn/logbook_form; public users see search/map pages
  - Active page highlighting via `active()` helper
- **CSS Framework** (style.css): Custom variables (--navy, --ocean, --gold), BEM-lite classes (grid-4/grid-2, form-row, pill, hero)
  - Responsive breakpoint: 980px (sidebar → stacked, grid-4/3 → grid-1)
- **Data Display**: Tables via `render_logbook_table()` with inline health/status pills; Maps via Leaflet.js with geoJSON markers

### Authentication & Sessions
- **Simulated Google SSO**: `login_simulation` action maps email input to student record, stores `$_SESSION['user_id']`
- **Access Control**: `require_login()` redirects unauthenticated users; `current_user()` fetches active student record
- **Flash Messages**: `flash()` function for success/error alerts (stored in session, single-render pattern)

## Common Tasks & Conventions

### Adding a New Page
1. Add route label to `page_title()` and `page_desc()` maps
2. Add navigation link in sidebar nav group (inside `layout_start()`)
3. Create new page block: `if ($page === 'page_key') { layout_start(...); /* render content */ layout_end(); exit; }`
4. Use `logbook_query($where, $params)` for complex queries with joins

### Form Handling Pattern
```php
if ($action === 'action_name') {
    require_login(); // if auth-required
    $data = [/* POST values */];
    // validation...
    // insert/update...
    flash('Success message');
    redirect('index.php?page=destination');
}
```
- All form data sanitized via `e()` (htmlspecialchars) before output
- Coordinate parsing: `parse_coord()` handles "lat, lng" string → [float, float] array

### Dropdown Data Management
- **Read**: `master_options('table_name')` returns all rows ordered by name
- **Create-if-not-exists**: `get_or_create_master(table, name, extra_fields)` (allowed: themes, programs, activity_types, locations)
- **Form Pattern**: Dual inputs (dropdown select + text input for new data) via `.master-inline` CSS class

### Geospatial Data
- **Locations**: Stored with latitude/longitude (DECIMAL 10,7)
- **Mapping**: Leaflet.js maps rendered server-side with `json_encode($markers)` in script tag
- **Marker Styling**: Color-coded by health_status (red=sick, orange=kendala/izin, blue=normal)

## Deployment & Development

### Laragon Setup
1. Extract folder to `C:\laragon\www\`
2. Start Apache + MySQL in Laragon UI
3. Import `database.sql` via phpMyAdmin
4. Access `http://localhost/poc-logbook-kkn-laragon-v2-sidebar` (folder name must match config.php APP_BASE_URL)

### Test Credentials (Pre-seeded in database.sql)
- azmi@student.demo, alya@student.demo, nadi@student.demo, rafi@student.demo, dimas@student.demo, mira@student.demo

### Export Feature
- `page=export` route generates CSV with raw logbook data (requires auth)
- Uses `php://output` stream for direct download

## Critical Gotchas & Decisions

1. **No CSRF protection**: Forms lack token validation (PoC scope)
2. **No SQL parameterization in table names**: `get_or_create_master()` uses string interpolation on table names (whitelist validation present)
3. **Sticky sidebar**: `.sidebar { position: sticky; height: 100vh; overflow: auto }` - on mobile becomes unsticky
4. **Master data uniqueness**: UNIQUE constraints on themes/programs/activity_types/locations names
5. **Locale**: All copy in Indonesian (id); timestamps use PHP's date() in MySQL format
6. **Datetime handling**: Uses MySQL NOW() for created_at; log_date is user-provided (datetime-local input)

## Files to Know
- **index.php**: Routes, business logic, all page templates
- **db.php**: PDO singleton, query helpers, `e()` sanitizer, `redirect()`, flash messaging
- **config.php**: Database and app URL constants
- **database.sql**: Full schema + seed data
- **assets/style.css**: Responsive grid, sidebar, card, button components
- **exports/**: Directory for generated exports (if needed for file storage)
