# Import/Export Template Split Documentation

## Overview

The `import-export.php` template has been successfully split into three separate files as requested, making the codebase more modular and maintainable.

## File Structure

### Original File

- `includes/template/Import/import-export.php` - Combined file with all three tabs (preserved for reference)

### New Split Files

- `includes/template/Import/import.php` - Import tab content only
- `includes/template/Import/export.php` - Export tab content only
- `includes/template/Import/post-import.php` - Post-Import tab content only
- `includes/template/Import/import-export-split.php` - Main wrapper that includes the individual files

## File Contents

### 1. import.php

Contains the complete Import tab interface including:

- GEDCOM file selection (upload or server path)
- Tree and branch selection
- Import options (replace, match, ignore, append)
- Additional options (uppercase surnames, media, coordinates)
- ID offset settings for append mode
- Form validation and submission

### 2. export.php

Contains the complete Export tab interface including:

- Tree and branch selection for export
- Data filtering options (living, private, notes)
- Media export configuration
- Media type paths configuration
- Export form submission

### 3. post-import.php

Contains the complete Post-Import utilities interface including:

- Tree selection for processing
- Grid of available utilities:
  - Track Lines
  - Sort Children
  - Sort Spouses
  - Relabel Branches
  - Create GenDex
  - Evaluate Media
  - Refresh Living
  - Make Private
- Utility descriptions and execution

### 4. import-export-split.php (Main Wrapper)

Contains:

- Tab navigation UI
- Tab switching logic
- Include statements for individual files
- Shared JavaScript functions
- Form initialization and validation

## Integration Changes

### Updated Admin Class

The reference in `admin/class-hp-admin.php` has been updated:

**Before:**

```php
include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/import-export.php';
```

**After:**

```php
include HERITAGEPRESS_PLUGIN_DIR . 'includes/template/Import/import-export-split.php';
```

## Benefits of the Split

1. **Modularity**: Each tab is now a separate file, making it easier to maintain specific functionality
2. **Code Organization**: Related functionality is grouped together in dedicated files
3. **Easier Development**: Developers can work on individual tabs without affecting others
4. **Better Maintainability**: Smaller, focused files are easier to understand and modify
5. **Future Extensibility**: New tabs can be easily added by creating new files and updating the wrapper

## File Relationships

```
import-export-split.php (Main wrapper)
├── import.php (Tab 1)
├── export.php (Tab 2)
└── post-import.php (Tab 3)
```

## Usage

The system now works exactly as before, but with improved code organization:

1. User navigates to Import/Export admin page
2. `import-export-split.php` loads and displays tab navigation
3. Based on selected tab, the appropriate individual file is included
4. All JavaScript functions remain shared in the main wrapper
5. CSS and styling remains consistent across all tabs

## Preserved Functionality

All original functionality has been preserved:

- Tab navigation and switching
- Form validation and submission
- AJAX functionality
- JavaScript interactions
- WordPress integration
- Nonce security
- Error handling
- User interface consistency

The split maintains 100% backward compatibility while providing better code organization for future development and maintenance.
