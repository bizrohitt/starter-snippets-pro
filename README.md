# Starter Snippets Pro

A production-ready WordPress code snippets manager. Create, manage, and conditionally execute PHP, JavaScript, CSS, and HTML snippets — directly from your WordPress dashboard.

**Version:** 1.2.0  
**Developer:** Rohit Saha

## Features

- **Multi-language support** — PHP, JavaScript, CSS, and HTML snippets
- **Smart code editor** — Powered by WordPress's built-in CodeMirror with syntax highlighting
- **Conditional execution** — Run snippets on specific pages, posts, post types, user roles, login status, or URL patterns
- **Execution locations** — Site-wide, frontend only, admin only, header, or footer
- **Safe PHP execution** — Faulty PHP snippets are caught and auto-deactivated
- **Import / Export** — JSON-based backup and restore
- **REST API** — Full CRUD API for programmatic snippet management
- **Activity logging** — Track snippet activations, deactivations, and errors
- **Safe mode** — Disable all PHP snippet execution for debugging
- **Tags** — Organize snippets with comma-separated tags
- **Bulk actions** — Activate, deactivate, or delete multiple snippets at once
- **Developer extensible** — Action hooks and filters throughout

## Requirements

| Requirement | Version |
|---|---|
| WordPress | 6.0+ |
| PHP | 8.0+ |

## Installation

1. Download or clone this repository
2. Copy the `starter-snippets` folder into your `wp-content/plugins/` directory
3. Go to **WP Admin → Plugins** and activate **Starter Snippets**
4. Navigate to **Snippets** in the admin sidebar

## Usage

### Creating a Snippet

1. Go to **Snippets → Add New**
2. Fill in the title and write your code
3. Select the language (PHP, JS, CSS, HTML)
4. Choose where the snippet should run (everywhere, frontend, admin, header, footer)
5. Optionally add conditions for targeted execution
6. Set status to **Active** and save

### Conditional Execution

Add conditions to control where your snippet runs:

| Condition Type | Value Example | Description |
|---|---|---|
| Page ID | `42` | Run only on page with ID 42 |
| Post ID | `15` | Run only on post with ID 15 |
| Post Type | `product` | Run on all posts of type "product" |
| User Role | `administrator` | Run only for administrators |
| Logged In | `true` | Run for logged-in users only |
| URL Pattern | `/\/shop\/.*/` | Run on URLs matching the regex |

### Import / Export

- Go to **Snippets → Import / Export**
- Click **Export All Snippets** to download a JSON backup
- Use the import form to restore snippets from a JSON file
- Imported snippets are set to **Inactive** by default for safety

## REST API

Base URL: `/wp-json/starter-snippets/v1/`

All endpoints require authentication with `manage_options` capability.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/snippets` | List all snippets |
| GET | `/snippets/{id}` | Get a single snippet |
| POST | `/snippets` | Create a snippet |
| PUT | `/snippets/{id}` | Update a snippet |
| DELETE | `/snippets/{id}` | Delete a snippet |
| POST | `/snippets/{id}/toggle` | Toggle status |

## File Structure

```
starter-snippets/
├── starter-snippets.php        # Main plugin file
├── uninstall.php               # Uninstall bridge
├── core/
│   ├── autoloader.php          # PSR-4 autoloader
│   ├── config.php              # Central configuration
│   └── plugin-loader.php       # Module orchestrator
├── bootstrap/
│   ├── activate.php            # Activation handler
│   ├── deactivate.php          # Deactivation handler
│   └── uninstall.php           # Uninstall cleanup
├── database/
│   ├── schema.php              # Table definitions
│   ├── migrations.php          # Version migrations
│   └── repository.php          # Data access layer
├── security/
│   ├── permissions.php         # Capability checks
│   └── nonce-handler.php       # Nonce management
├── helpers/
│   ├── sanitization.php        # Input sanitization
│   ├── validation.php          # Data validation
│   └── logger.php              # Event logging
├── modules/
│   ├── snippet-manager/        # CRUD + business logic
│   ├── condition-engine/       # Conditional execution
│   └── import-export/          # JSON import/export
├── admin/
│   ├── dashboard.php           # Dashboard + menu
│   ├── snippets-page.php       # Snippet list page
│   ├── snippet-editor.php      # Add/edit form
│   └── settings-page.php       # Plugin settings
├── frontend/
│   └── snippet-runner.php      # Frontend execution
├── api/
│   └── rest-controller.php     # REST API
├── templates/admin/            # PHP templates
├── assets/css/                 # Admin styles
├── assets/js/                  # Admin scripts
├── languages/                  # i18n
├── tests/                      # Unit tests
└── docs/                       # Documentation
```

## License

GPL-2.0+ — https://www.gnu.org/licenses/gpl-2.0.html

Built with ❤️ by Rohitt.