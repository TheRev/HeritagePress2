# HeritagePress Admin Architecture

## IMPORTANT: Controller-Based Architecture

This plugin uses a **controller-based architecture**.

### DO NOT CREATE:

- `class-hp-admin.php`
- `class-hp-admin-new.php` (except the loader)
- Any monolithic admin files

### INSTEAD USE:

#### Controllers (`admin/controllers/`)

Handle business logic and coordinate between models and views:

- `class-hp-import-controller.php` - GEDCOM import logic
- `class-hp-people-controller.php` - People management
- `class-hp-families-controller.php` - Family management
- `class-hp-trees-controller.php` - Tree management
- `class-hp-settings-controller.php` - Plugin settings

#### Handlers (`admin/handlers/`)

Handle form submissions and AJAX requests:

- `class-hp-import-handler.php` - Import form processing
- `class-hp-ajax-handler.php` - AJAX endpoints

#### Views (`admin/views/`)

Contain HTML templates:

- `import/` - Import page templates
- `people/` - People management templates
- `families/` - Family management templates
- `trees/` - Tree management templates

#### Templates (`admin/template/`)

Reusable template components

### Current Issue: Import Button

The import functionality is handled by:

1. `ImportController::display_page()` - Shows the import form
2. `ImportHandler::handle_gedcom_import()` - Processes the form
3. Form should POST to admin-post.php with action 'hp_import_gedcom'

### Adding New Features

1. Create controller in `controllers/`
2. Create handler in `handlers/` if needed
3. Create view templates in `views/`
4. Register controller in `class-hp-admin-new.php`
