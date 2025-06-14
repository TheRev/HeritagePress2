# TNG to HeritagePress Table Mapping Reference

This document provides a complete mapping between TNG (The Next Generation) database tables and their corresponding HeritagePress equivalents. Use this reference when working with TNG functions that need to be adapted for HeritagePress.

## Table Prefix Mapping

- **TNG Prefix**: `tng_`
- **HeritagePress Prefix**: `wp_hp_` (WordPress prefix + HeritagePress prefix)

## Complete Table Mapping (37 Tables)

| #   | TNG Table            | HeritagePress Table    | Module   | Description                                |
| --- | -------------------- | ---------------------- | -------- | ------------------------------------------ |
| 1   | `tng_addresses`      | `wp_hp_addresses`      | Places   | Address information for locations          |
| 2   | `tng_albumlinks`     | `wp_hp_albumlinks`     | Media    | Links between albums and media items       |
| 3   | `tng_albumplinks`    | `wp_hp_albumplinks`    | Media    | Links between albums and people/families   |
| 4   | `tng_albums`         | `wp_hp_albums`         | Media    | Photo/media albums                         |
| 5   | `tng_associations`   | `wp_hp_associations`   | Research | Person associations and relationships      |
| 6   | `tng_branches`       | `wp_hp_branches`       | System   | Family tree branches                       |
| 7   | `tng_branchlinks`    | `wp_hp_branchlinks`    | System   | Links between branches and people/families |
| 8   | `tng_cemeteries`     | `wp_hp_cemeteries`     | Places   | Cemetery information                       |
| 9   | `tng_children`       | `wp_hp_children`       | Core     | Child-parent relationships                 |
| 10  | `tng_citations`      | `wp_hp_citations`      | Research | Source citations                           |
| 11  | `tng_countries`      | `wp_hp_countries`      | Places   | Country lookup table                       |
| 12  | `tng_dna_groups`     | `wp_hp_dna_groups`     | DNA      | DNA testing groups                         |
| 13  | `tng_dna_links`      | `wp_hp_dna_links`      | DNA      | Links between DNA tests and people         |
| 14  | `tng_dna_tests`      | `wp_hp_dna_tests`      | DNA      | DNA test results                           |
| 15  | `tng_events`         | `wp_hp_events`         | Events   | Life events (birth, death, marriage, etc.) |
| 16  | `tng_eventtypes`     | `wp_hp_eventtypes`     | Events   | Event type definitions                     |
| 17  | `tng_families`       | `wp_hp_families`       | Core     | Family/marriage records                    |
| 18  | `tng_image_tags`     | `wp_hp_image_tags`     | Media    | Tagged people in images                    |
| 19  | `tng_languages`      | `wp_hp_languages`      | System   | Language settings                          |
| 20  | `tng_media`          | `wp_hp_media`          | Media    | Media files (photos, documents, etc.)      |
| 21  | `tng_medialinks`     | `wp_hp_medialinks`     | Media    | Links between media and people/events      |
| 22  | `tng_mediatypes`     | `wp_hp_mediatypes`     | Media    | Media type definitions                     |
| 23  | `tng_mostwanted`     | `wp_hp_mostwanted`     | Research | Most wanted ancestors list                 |
| 24  | `tng_notelinks`      | `wp_hp_notelinks`      | Research | Links between notes and records            |
| 25  | `tng_people`         | `wp_hp_people`         | Core     | Individual person records                  |
| 26  | `tng_places`         | `wp_hp_places`         | Places   | Place/location information                 |
| 27  | `tng_reports`        | `wp_hp_reports`        | System   | Custom report definitions                  |
| 28  | `tng_repositories`   | `wp_hp_repositories`   | Research | Source repositories                        |
| 29  | `tng_saveimport`     | `wp_hp_saveimport`     | Utility  | GEDCOM import progress tracking            |
| 30  | `tng_sources`        | `wp_hp_sources`        | Research | Source records                             |
| 31  | `tng_states`         | `wp_hp_states`         | Places   | State/province lookup table                |
| 32  | `tng_templates`      | `wp_hp_templates`      | System   | Template configurations                    |
| 33  | `tng_temp_events`    | `wp_hp_temp_events`    | Utility  | Temporary event storage                    |
| 34  | `tng_timelineevents` | `wp_hp_timelineevents` | Events   | Historical timeline events                 |
| 35  | `tng_trees`          | `wp_hp_trees`          | System   | Family tree definitions                    |
| 36  | `tng_users`          | `wp_hp_users`          | System   | User accounts and permissions              |
| 37  | `tng_xnotes`         | `wp_hp_xnotes`         | Research | Extended notes                             |

## Module Breakdown

### Core Tables (3 tables)

Essential genealogy data managed by `HP_Database_Core` class:

- `wp_hp_people` - Individual person records
- `wp_hp_families` - Family/marriage records
- `wp_hp_children` - Parent-child relationships

### Events Tables (3 tables)

Life events managed by `HP_Database_Events` class:

- `wp_hp_events` - Individual life events
- `wp_hp_eventtypes` - Event type definitions
- `wp_hp_timelineevents` - Historical timeline events

### Media Tables (7 tables)

Photos and documents managed by `HP_Database_Media` class:

- `wp_hp_media` - Media files
- `wp_hp_medialinks` - Media-to-person/event links
- `wp_hp_mediatypes` - Media type definitions
- `wp_hp_albums` - Photo albums
- `wp_hp_albumlinks` - Album-to-media links
- `wp_hp_albumplinks` - Album-to-person links
- `wp_hp_image_tags` - Tagged people in images

### Places Tables (5 tables)

Geographic data managed by `HP_Database_Places` class:

- `wp_hp_places` - Place/location records
- `wp_hp_addresses` - Address information
- `wp_hp_cemeteries` - Cemetery records
- `wp_hp_countries` - Country lookup
- `wp_hp_states` - State/province lookup

### DNA Tables (3 tables)

DNA testing data managed by `HP_Database_DNA` class:

- `wp_hp_dna_tests` - DNA test records
- `wp_hp_dna_links` - DNA-to-person links
- `wp_hp_dna_groups` - DNA testing groups

### Research Tables (7 tables)

Sources and research managed by `HP_Database_Research` class:

- `wp_hp_sources` - Source records
- `wp_hp_citations` - Source citations
- `wp_hp_repositories` - Source repositories
- `wp_hp_xnotes` - Extended notes
- `wp_hp_notelinks` - Note-to-record links
- `wp_hp_associations` - Person associations
- `wp_hp_mostwanted` - Most wanted ancestors

### System Tables (7 tables)

Admin and config managed by `HP_Database_System` class:

- `wp_hp_users` - User accounts
- `wp_hp_trees` - Family tree definitions
- `wp_hp_branches` - Tree branches
- `wp_hp_branchlinks` - Branch-to-record links
- `wp_hp_languages` - Language settings
- `wp_hp_templates` - Template configurations
- `wp_hp_reports` - Custom report definitions

### Utility Tables (2 tables)

Import and temp data managed by `HP_Database_Utility` class:

- `wp_hp_saveimport` - GEDCOM import tracking
- `wp_hp_temp_events` - Temporary event storage

## Usage Notes for TNG Function Adaptation

When adapting TNG functions for HeritagePress:

1. **Replace table prefixes**: Change `tng_` to `wp_hp_`
2. **Update WordPress integration**: Use WordPress database functions (`$wpdb`)
3. **Maintain field compatibility**: All field names and types are identical
4. **Preserve relationships**: All foreign key relationships are maintained
5. **Use modular classes**: Access tables through appropriate HeritagePress database classes

## Example Adaptations

### SQL Query Adaptation

```sql
-- TNG Original:
SELECT * FROM tng_people WHERE lastname = 'Smith'

-- HeritagePress Equivalent:
SELECT * FROM wp_hp_people WHERE lastname = 'Smith'
```

### PHP Code Adaptation

```php
// TNG Original:
$result = mysql_query("SELECT * FROM tng_people WHERE personID = '$personID'");

// HeritagePress Equivalent:
global $wpdb;
$table_name = $wpdb->prefix . 'hp_people';
$result = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_name WHERE personID = %s",
    $personID
));
```

### Function Call Adaptation

```php
// TNG Original:
function getTNGPerson($personID) {
    // Uses tng_people table
}

// HeritagePress Equivalent:
function getHPPerson($personID) {
    // Uses wp_hp_people table
    // Integration with WordPress user system
}
```

This mapping ensures seamless integration between TNG functionality and HeritagePress while maintaining data integrity and WordPress compatibility.
