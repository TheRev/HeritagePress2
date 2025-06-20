# HeritagePress Admin Architecture

## Overview

The admin functionality uses a **modular architecture** with clear separation of concerns.

### Architecture Components:

1. **Main Admin Class** (`class-hp-admin.php`):

   - Handles menu registration and initialization
   - Loads handlers, renderers, and controllers
   - Acts as the entry point for admin functionality

2. **Controllers** (`admin/controllers/`):

   - Handle business logic
   - Coordinate between models and views
   - Examples:
     - `class-hp-import-controller.php` - GEDCOM import logic
     - `class-hp-people-controller.php` - People management
     - `class-hp-families-controller.php` - Family management
     - `class-hp-trees-controller.php` - Tree management
     - `class-hp-settings-controller.php` - Plugin settings

3. **Handlers** (`admin/handlers/`):

   - Process form submissions and AJAX requests
   - Examples:
     - `class-hp-import-handler.php` - Import form processing
     - `class-hp-ajax-handler.php` - AJAX endpoints
     - `class-hp-tree-handler.php` - Tree form processing

4. **Renderers** (`admin/renderers/`):
   - Render UI components and admin pages
   - Examples:
     - `class-hp-tree-page-renderer.php` - Tree admin pages

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
