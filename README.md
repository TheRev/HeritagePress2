# HeritagePress - WordPress Genealogy Plugin

A complete genealogy management system for WordPress that allows you to import GEDCOM files, manage family trees, and create beautiful genealogy websites.

## Features

- **TNG Database Compatibility**: Complete implementation of all TNG (The Next Generation) genealogy database tables
- **Modular Architecture**: Clean, maintainable code organized into logical categories
- **GEDCOM Import**: Import your existing genealogy data
- **WordPress Integration**: Seamless integration with WordPress admin and frontend
- **Responsive Design**: Modern, mobile-friendly interface
- **Extensible**: Built with developers in mind

## Installation

1. Upload the `heritagepress` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically create all necessary database tables upon activation
4. Navigate to 'HeritagePress' in your WordPress admin menu to get started

## Database Tables

HeritagePress creates 37+ specialized tables organized into categories:

### Core Tables

- Persons (individuals)
- Families
- Children (family relationships)
- Events (births, deaths, marriages, etc.)
- Event types
- Timeline events

### Sources & Research

- Sources
- Source types
- Citations
- Repositories
- Research logs
- Research log types

### Media & Albums

- Media items
- Albums
- Media links
- Media types

### Geography

- Places
- Addresses
- Address types

### System & Administration

- Users
- Trees (family tree configurations)
- Settings
- Session data
- Logs

## Basic Usage

### Admin Interface

1. **Dashboard**: View statistics and quick actions
2. **Table Management**: Create, update, or drop database tables
3. **Settings**: Configure plugin options
4. **Import**: Upload and process GEDCOM files

### Frontend Display

Use shortcodes to display genealogy content:

```
[heritagepress_tree tree_id="main" generations="4"]
[heritagepress_person person_id="I001"]
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher (or MariaDB equivalent)
