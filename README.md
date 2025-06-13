# HeritagePress - WordPress Genealogy Plugin

A complete genealogy management system for WordPress with **full TNG (The Next Generation) database compatibility** for seamless data migration and interoperability.

## Features

### 🔄 TNG Database Compatibility

- **Complete TNG Schema Implementation**: All 35+ TNG database tables exactly replicated
- **Direct TNG Import**: Connect to existing TNG databases and import data directly
- **TNG Export**: Export HeritagePress data in TNG-compatible format
- **Schema Migration**: Convert between standard HeritagePress and TNG-compatible schemas
- **Bidirectional Sync**: Maintain data synchronization between TNG and WordPress

### 🎯 Core Genealogy Features

- **GEDCOM Import/Export**: Full GEDCOM 5.5.1 support
- **Advanced Person Management**: Complete vital records, multiple names, relationships
- **Family Tree Visualization**: Interactive family trees and pedigree charts
- **Event Management**: Births, deaths, marriages, and custom events
- **Source Citations**: Comprehensive source and citation management
- **Media Management**: Photos, documents, and multimedia integration

### 🏗️ Technical Excellence

- **Modular Architecture**: Clean, maintainable code structure
- **WordPress Integration**: Native WordPress admin interface and capabilities
- **Performance Optimized**: Efficient database queries and caching
- **Developer Friendly**: Extensive hooks, filters, and APIs
- **Mobile Responsive**: Modern, accessible user interface

## Installation

1. Upload the `heritagepress` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Choose your database schema:
   - **Standard**: Traditional WordPress-style tables (default)
   - **TNG Compatible**: Full TNG schema for maximum compatibility
4. Navigate to 'HeritagePress' in your WordPress admin menu to get started

## TNG Compatibility

### Database Schema Options

**Standard Schema**: WordPress-optimized tables with auto-increment IDs

```sql
CREATE TABLE wp_hp_people (
    id int(11) NOT NULL AUTO_INCREMENT,
    first_name varchar(100),
    last_name varchar(100),
    -- WordPress-style naming
)
```

**TNG Compatible Schema**: Exact TNG table structure

```sql
CREATE TABLE wp_hp_people (
    ID int(11) NOT NULL AUTO_INCREMENT,
    gedcom varchar(20) NOT NULL,
    personID varchar(22) NOT NULL,
    firstname varchar(127) NOT NULL,
    lastname varchar(127) NOT NULL,
    -- Exact TNG field names and types
)
```

### Migration Path

1. **New Installations**: Choose TNG compatibility during setup
2. **Existing Installations**: Use the Schema Migration tool in HeritagePress → TNG Import/Export → Database Schema
3. **Data Preservation**: All existing data is preserved and mapped during migration

### TNG Import Process

1. Navigate to **HeritagePress → TNG Import/Export**
2. Enter your TNG database connection details
3. Test the connection and validate TNG schema
4. Select import options:
   - GEDCOM filter (specific family trees)
   - Data types (people, families, sources, media)
   - Backup options
5. Start import with real-time progress tracking

### Supported TNG Tables

✅ **Core Genealogy** (8 tables)

- people, families, children, events, eventtypes
- sources, citations, repositories

✅ **Media & Albums** (7 tables)

- media, medialinks, mediatypes, albums, albumlinks, album2entities, image_tags

✅ **Geography & Places** (5 tables)

- places, address, countries, states, cemeteries

✅ **Extended Features** (10+ tables)

- xnotes, notelinks, branches, branchlinks, assoc
- mostwanted, trees, users, languages, reports

✅ **DNA & Research** (6 tables)

- dna_tests, dna_links, dna_groups, temp_events, tlevents, saveimport

✅ **WordPress Integration** (2 tables)

- user_permissions, import_log

## Quick Start Guide

### For TNG Users

1. **Install HeritagePress** and activate the plugin
2. **Navigate to HeritagePress → TNG Import/Export**
3. **Test your TNG database connection**
4. **Import your TNG data** with one click
5. **Your genealogy data is now in WordPress!**

### For New Users

1. **Install HeritagePress** and activate the plugin
2. **Choose TNG compatibility** during setup (recommended)
3. **Import your GEDCOM file** via HeritagePress → Import
4. **Start building your family tree website**

## Development

### Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.2+
- Composer (for dependencies)

### Project Structure

```
heritagepress/
├── includes/                          # Core plugin classes
│   ├── class-hp-database-core.php          # Standard WordPress schema
│   ├── class-hp-database-tng-compatible.php # TNG-compatible schema
│   ├── class-hp-tng-mapper.php             # TNG ↔ HeritagePress mapping
│   ├── class-hp-tng-importer.php           # TNG import/export engine
│   ├── class-hp-person.php                 # Person management
│   ├── class-hp-family.php                 # Family management
│   └── class-hp-gedcom-importer.php        # GEDCOM processing
├── admin/                             # WordPress admin interface
│   ├── class-hp-admin.php                  # Main admin controller
│   ├── class-hp-tng-admin.php              # TNG import/export interface
│   ├── js/tng-admin.js                     # TNG admin JavaScript
│   └── css/tng-admin.css                   # TNG admin styling
├── public/                            # Public-facing features
├── tng-reference/                     # TNG table definitions
│   └── tabledefs.php                       # Reference TNG schema
└── TNG-REBUILD-ANALYSIS.md           # Migration documentation
```

### API Examples

#### Check TNG Compatibility

```php
$tng_db = new HP_Database_TNG_Compatible();
$is_compatible = $tng_db->check_tng_compatibility();
```

#### Import from TNG Database

```php
$importer = new HP_TNG_Importer();
$importer->connect_tng_database($host, $db, $user, $pass);
$results = $importer->import_from_tng('family_tree_1');
```

#### Map TNG Data

```php
$mapper = new HP_TNG_Mapper();
$hp_person = $mapper->map_tng_person_to_hp($tng_person_data);
```

## Technical Notes

### Database Compatibility

- **Field Mapping**: Automatic conversion between TNG and WordPress naming conventions
- **Data Integrity**: Foreign key relationships preserved during migration
- **Performance**: Optimized queries for large genealogy databases
- **Backup Safety**: Automatic backups before major operations

### WordPress Integration

- **User Permissions**: Integrates with WordPress role/capability system
- **Media Library**: TNG media can be imported to WordPress media library
- **SEO Friendly**: WordPress-native URLs and meta data
- **Theme Compatible**: Works with any WordPress theme

## Support & Contributing

### Documentation

- [TNG Migration Guide](TNG-REBUILD-ANALYSIS.md)
- [API Documentation](docs/api.md)
- [Developer Guide](docs/development.md)

### Getting Help

- **GitHub Issues**: Technical support and bug reports
- **WordPress Forums**: General questions and community support
- **Documentation**: Comprehensive guides and examples

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes with tests
4. Submit a pull request

## License

MIT License - see LICENSE file for details.

## Changelog

### v1.0.0

- ✨ Complete TNG database compatibility
- ✨ Direct TNG import/export functionality
- ✨ Schema migration tools
- ✨ WordPress admin interface for TNG operations
- ✨ Comprehensive data mapping layer
- ✨ 35+ TNG-compatible database tables
- ✨ Real-time import progress tracking
- ✨ Data validation and error handling

---

**Ready to migrate from TNG to WordPress?** HeritagePress makes it seamless with full database compatibility and easy migration tools. Your genealogy data will feel right at home in WordPress!

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
