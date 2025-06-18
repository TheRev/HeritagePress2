# HeritagePress Architecture Guide

## Directory Structure

### `includes/` - Core Business Logic

- `core/` - Core managers (PersonManager, FamilyManager)
- `database/` - Database classes and schema
- `gedcom/` - GEDCOM parsing and import logic
- `helpers/` - Utility functions
- `template/` - Template helpers

### `admin/` - Admin Interface (Controller Pattern)

- `controllers/` - Handle admin page logic
- `handlers/` - Process forms and AJAX
- `views/` - HTML templates
- `template/` - Reusable components

### `public/` - Frontend Interface

- Public-facing functionality

## Key Principle

**Separation of Concerns**: Business logic goes in `includes/`, admin interface logic goes in `admin/controllers/`

## Common Mistake

Do NOT put admin page logic in `includes/` or create monolithic admin classes. Use the controller pattern in `admin/`.
