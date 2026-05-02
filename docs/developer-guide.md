# Starter Snippets — Developer Guide

## Architecture Overview

Starter Snippets uses a modular OOP architecture with clear separation of concerns.

### Layers

1. **Core** — Autoloader, configuration, and plugin loader
2. **Database** — Schema definitions, migrations, and repository (data access)
3. **Security** — Permissions and nonce verification
4. **Helpers** — Sanitization, validation, and logging
5. **Modules** — Business logic: Snippet Manager, Condition Engine, Import/Export
6. **Admin** — Dashboard, list pages, editor, and settings
7. **Frontend** — Snippet execution engine
8. **API** — REST API controller

### Request Flow

```
WordPress init
  └─ PluginLoader::init()
       ├─ Migrations::maybe_upgrade()
       ├─ Admin controllers (if is_admin)
       ├─ SnippetRunner::register() → hooks into wp_head / wp_footer / init
       └─ RestController → registers REST routes
```

---

## Hooks Reference

### Actions

| Hook | Parameters | Description |
|---|---|---|
| `starter_snippets_after_create` | `$id`, `$data` | Fires after a snippet is created |
| `starter_snippets_after_update` | `$id`, `$data` | Fires after a snippet is updated |
| `starter_snippets_after_delete` | `$id` | Fires after a snippet is deleted |
| `starter_snippets_status_changed` | `$id`, `$new_status` | Fires when a snippet is toggled |

### Filters

| Filter | Parameters | Description |
|---|---|---|
| `starter_snippets_before_create` | `$data` | Modify snippet data before saving |
| `starter_snippets_before_update` | `$data`, `$id` | Modify snippet data before updating |

---

## Database Tables

All tables use the WordPress table prefix (e.g., `wp_`).

### wp_starter_snippets

| Column | Type | Description |
|---|---|---|
| id | BIGINT PK | Auto-increment ID |
| title | VARCHAR(255) | Snippet name |
| description | TEXT | Optional description |
| code | LONGTEXT | The code content |
| language | VARCHAR(10) | php, js, css, html |
| location | VARCHAR(20) | everywhere, frontend, admin, header, footer |
| priority | INT | Execution order (lower = earlier) |
| status | VARCHAR(10) | active, inactive |
| tags | VARCHAR(255) | Comma-separated tags |
| created_by | BIGINT | User ID who created |
| created_at | DATETIME | Creation timestamp |
| updated_at | DATETIME | Last update timestamp |

### wp_starter_snippet_conditions

| Column | Type | Description |
|---|---|---|
| id | BIGINT PK | Auto-increment ID |
| snippet_id | BIGINT FK | References snippets table |
| condition_type | VARCHAR(30) | page_id, post_id, post_type, user_role, logged_in, url_pattern |
| condition_value | VARCHAR(255) | The condition value |
| condition_operator | VARCHAR(10) | include, exclude |

### wp_starter_snippet_logs

| Column | Type | Description |
|---|---|---|
| id | BIGINT PK | Auto-increment ID |
| snippet_id | BIGINT | Related snippet (0 for global) |
| event | VARCHAR(30) | activated, deactivated, error, info, warning |
| message | TEXT | Details |
| created_at | DATETIME | Timestamp |

---

## REST API

Namespace: `starter-snippets/v1`

All endpoints require the `manage_options` capability and use WordPress's built-in nonce/cookie authentication.

### Endpoints

```
GET    /wp-json/starter-snippets/v1/snippets              List (supports: status, language, per_page, page)
GET    /wp-json/starter-snippets/v1/snippets/{id}          Get one (includes conditions)
POST   /wp-json/starter-snippets/v1/snippets               Create
PUT    /wp-json/starter-snippets/v1/snippets/{id}          Update
DELETE /wp-json/starter-snippets/v1/snippets/{id}          Delete
POST   /wp-json/starter-snippets/v1/snippets/{id}/toggle   Toggle status
```

### Example: Create a Snippet

```bash
curl -X POST https://your-site.com/wp-json/starter-snippets/v1/snippets \
  -H "X-WP-Nonce: <nonce>" \
  --cookie "wordpress_logged_in_<hash>=<value>" \
  -d '{
    "title": "My CSS Snippet",
    "code": "body { background: #f0f0f0; }",
    "language": "css",
    "location": "header",
    "status": "active"
  }'
```

---

## Testing Guide

### Manual Testing Checklist

1. **Activate plugin** — No errors, menu appears
2. **Create PHP snippet** — `echo '<p>Hello from Starter Snippets!</p>';` — active, frontend, verify on site
3. **Create CSS snippet** — `body { border-top: 3px solid red; }` — header, active, verify styling appears
4. **Create JS snippet** — `console.log('Starter Snippets loaded');` — footer, check browser console
5. **Test error handling** — Create PHP snippet: `undefined_function_xyz();` — activate, visit frontend, confirm snippet auto-deactivates
6. **Conditional execution** — Create snippet with condition `post_type = page`, verify runs on Pages but not Posts
7. **Import/Export** — Export all, delete snippets, import JSON, verify restored
8. **Bulk actions** — Select multiple, activate/deactivate/delete
9. **Settings** — Toggle safe mode, verify PHP snippets stop running
10. **REST API** — Use a REST client to test CRUD endpoints
11. **Deactivate** — Data should remain
12. **Delete plugin** — Tables should be removed

### Unit Testing

The `tests/` directory is provided for PHPUnit tests. To set up:

```bash
# Install WP test suite (see https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/)
bash bin/install-wp-tests.sh <db-name> <db-user> <db-pass> <db-host> latest

# Run tests
./vendor/bin/phpunit
```

---

## Security Notes

- All admin actions check `manage_options` capability
- All form submissions use WordPress nonce verification
- Snippet code is stored raw but executed in controlled contexts
- PHP snippets run inside `try/catch` with output buffering
- Faulty snippets are auto-deactivated and logged
- SQL injection is prevented by using `$wpdb->prepare()` throughout
- All output is properly escaped with `esc_html()`, `esc_attr()`, etc.
- Imported snippets are always set to inactive
- Safe mode halts all PHP snippet execution
