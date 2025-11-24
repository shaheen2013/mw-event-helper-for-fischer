# MW Helper Plugin

A comprehensive WordPress plugin that provides Google Maps integration, business hours tracking, and user interaction analytics for branch/location management.

**Version:** 1.0.0  
**Author:** Mediusware.com  
**License:** GPL v2 or later  
**Text Domain:** mwhp

---

## Features

### 1. **Google Maps Integration**
- Interactive Google Maps display with location markers
- Location search by city or postal code
- Distance-based filtering (50km radius)
- Branch information display in map info windows
- Business hours display on map markers
- Responsive design with Bootstrap 5.3.2

### 2. **Business Hours Management**
- Automatic fetching of business hours from Google Places API
- Display of opening/closing times for each day
- Saturday and Sunday special hours support
- Real-time business status indicator (Open/Closed)
- Timezone-aware status calculation
- Transient caching for performance optimization

### 3. **Inspiration Tracker**
- Track user interactions with specific events:
  - `OPENED` - Page/product opened
  - `SECOND_PRODUCT` - User viewed second product
  - `HALF_VIEWED` - User viewed half of products
  - `OPEN_PRODUCT_PAGE` - User opened product page
  - `USER_LEFT` - User left inspiration slide
  - `ALL_PRODUCTS` - User viewed all products
- IP-based user tracking
- Metadata storage for additional context
- AJAX-based logging (frontend)
- Admin dashboard for viewing tracked data
- Bulk delete functionality with date filtering
- Summary statistics display

### 4. **Admin Dashboard**
- **MW Helper** main menu with subpages
- **Google Map API Settings** - Configure API keys and business name
- **Inspiration Tracker** - View and manage tracked user interactions
- **Metabox Integration** - Google Business Hours display on post edit pages

### 5. **Metabox Features**
- Google Business Hours metabox on "filialen" (branches) post type
- Automatic status badge (Open/Closed/Unknown) in post title
- Cache clearing functionality for refreshing business hours
- Visual status indicators with color coding

---

## File Structure & References

### Core Files

| File | Purpose |
|------|---------|
| `index.php` | Plugin entry point, defines constants and hooks |
| `autoloaders.php` | PSR-4 autoloader for plugin classes |

### Main Initialization

| File | Class | Purpose |
|------|-------|---------|
| `inc/mwhp-init.php` | `Mwhp_Init` | Main plugin initialization, singleton pattern |

### Admin Module (`inc/admin/`)

| File | Class | Purpose |
|------|-------|---------|
| `admin-init.php` | `Admin_Init` | Initializes admin components |
| `map-settings.php` | `Map_Settings` | Google Map API settings page (Settings → Google Map API) |
| `inspiration-tracker-page.php` | `Inspiration_Tracker_Page` | Inspiration tracker admin page with data table |

### Assets Module (`inc/assets/`)

| File | Class | Purpose |
|------|-------|---------|
| `assets-init.php` | `Assets_Init` | Initializes frontend and backend assets |
| `backend-assets.php` | `Backend_Assets` | Enqueues admin CSS/JS (DataTables, jQuery UI, tracker styles) |
| `frontend-assets.php` | `Frontend_Assets` | Enqueues frontend JS (zone-slider-tracker.js) |

### Database Module (`inc/database/`)

| File | Class | Purpose |
|------|-------|---------|
| `inspiration-tracker-table.php` | `Inspiration_Tracker_Table` | Database operations for inspiration tracking |

**Table Schema:**
- `wp_mw_inspiration_tracker` - Stores tracker events with IP, tracker name, metadata, and timestamp

### Inspirations Module (`inc/inspirations/`)

| File | Class | Purpose |
|------|-------|---------|
| `inspirations-tracker-init.php` | `Inspirations_Tracker_Init` | Initializes tracker components |
| `inspirations-tracker.php` | `Inspirations_Tracker` | AJAX handler for logging user interactions |
| `inspirations-tracker-delete.php` | `Inspirations_Tracker_Delete` | AJAX handler for deleting tracker records |

### Metabox Module (`inc/metabox/`)

| File | Class | Purpose |
|------|-------|---------|
| `metabox-init.php` | `Metabox_Init` | Initializes metabox components |
| `gpb-metabox.php` | `GPB_Metabox` | Google Business Hours metabox on filialen posts |
| `clear-weekday-cache.php` | `Clear_Weekday_Cache` | AJAX handler for clearing cached business hours |

### Services Module (`inc/services/`)

| File | Class | Purpose |
|------|-------|---------|
| `gpb-places-client.php` | `GPB_Places_Client` | Google Places API client for fetching business hours |
| `business-hour-status.php` | `Business_Hour_Status` | Calculates current business status (Open/Closed) |
| `branch-business-hours.php` | `Branch_Business_Hours` | Alternative business hours status calculation |

### Settings Module (`inc/settings/`)

| File | Class | Purpose |
|------|-------|---------|
| `map-settings.php` | `Map_Settings` | Manages Google Map API settings and business name |

### Shortcodes Module (`inc/shortcodes/`)

| File | Class | Purpose |
|------|-------|---------|
| `shortcodes-init.php` | `Shortcodes_Init` | Initializes shortcode components |
| `mw-google-map.php` | `Mw_Google_Map` | Renders interactive Google Map shortcode `[mw_google_map]` |

### Traits Module (`inc/traits/`)

| File | Trait | Purpose |
|------|-------|---------|
| `singleton.php` | `Singleton` | Singleton pattern implementation for classes |

### Assets Directory (`assets/`)

| Path | Type | Purpose |
|------|------|---------|
| `assets/css/jquery-ui.css` | CSS | jQuery UI datepicker styling |
| `assets/css/dataTables.dataTables.min.css` | CSS | DataTables styling |
| `assets/css/tracker-summary.css` | CSS | Inspiration tracker summary styling |
| `assets/js/dataTables.min.js` | JS | DataTables library |
| `assets/js/admin-inspiration-dt.js` | JS | Admin inspiration tracker functionality |
| `assets/js/clean-cache.js` | JS | Cache clearing functionality |
| `assets/js/zone-slider-tracker.js` | JS | Frontend zone slider tracking |
| `assets/images/map-marker.png` | Image | Custom map marker icon |

---

## Configuration

### Required Settings

1. **Google Places API Key** - Settings → Google Map API
   - Enable Places API and Place Details
   - Set up billing in Google Cloud Console

2. **Business Name** (Optional) - Settings → Google Map API
   - Used as fallback for place queries
   - Combined with post title for Google Places search

### Constants Defined

| Constant | Value |
|----------|-------|
| `MWHP_VERSION` | 1.0.0 |
| `MWHP_PATH_DIR` | Plugin directory path |
| `MWHP_PATH_URI` | Plugin directory URL |
| `MWHP_PLUGIN_BASENAME` | Plugin basename |

---

## AJAX Endpoints

| Action | Handler | Purpose |
|--------|---------|---------|
| `mwhp_log_inspiration` | `Inspirations_Tracker::callback()` | Log user interactions |
| `mwhp_delete_inspiration_records` | `Inspirations_Tracker_Delete::callback()` | Delete tracker records by date |
| `mwhp_clear_cache` | `Clear_Weekday_Cache::ajax_clear_cache()` | Clear cached business hours |

---

## Shortcodes

### `[mw_google_map]`
Displays an interactive Google Map with branch locations and search functionality.

**Features:**
- Location search by city/postal code
- Distance-based filtering
- Business hours display
- Responsive design

---

## Database Tables

### `wp_mw_inspiration_tracker`

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT(20) | Primary key |
| `tracker_name` | ENUM | Event type (OPENED, SECOND_PRODUCT, ALL_PRODUCTS) |
| `user_ip` | VARCHAR(45) | User IP address (IPv4/IPv6) |
| `meta` | LONGTEXT | Additional metadata (JSON) |
| `created_at` | DATETIME | Event timestamp |

---

## Hooks & Filters

### Actions
- `plugins_loaded` - Plugin initialization
- `admin_menu` - Register admin pages
- `add_meta_boxes` - Register metaboxes
- `wp_enqueue_scripts` - Enqueue frontend assets
- `admin_enqueue_scripts` - Enqueue backend assets
- `wp_ajax_*` - AJAX handlers

### Filters
- `plugin_action_links_*` - Add settings link to plugin page

---

## Requirements

- **WordPress:** 5.2+
- **PHP:** 7.2+
- **Google Places API** - For business hours functionality

---

## Installation & Activation

1. Upload plugin to `/wp-content/plugins/mw-helper-plugin/`
2. Activate through WordPress admin
3. Configure Google Places API key in Settings → Google Map API
4. Use `[mw_google_map]` shortcode on pages

---

## License

GPL v2 or later - See LICENSE file for details

