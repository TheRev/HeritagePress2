# TNG Media Collection Management to HeritagePress Conversion Notes

## Overview

Ported TNG's `admin_addcollection.php` functionality to HeritagePress WordPress plugin, creating a comprehensive media collection/media type management system that modernizes the original TNG approach while maintaining full compatibility.

## TNG File Converted

- `admin_addcollection.php` - Simple AJAX endpoint for adding custom media collections

## HeritagePress Implementation

### Controller

- **File**: `admin/controllers/class-hp-media-collection-controller.php`
- **Purpose**: Complete media collection management with full CRUD operations
- **Features**:
  - Full CRUD operations (Create, Read, Update, Delete)
  - AJAX handlers for form submissions and data retrieval
  - WordPress security (nonce verification, capability checks)
  - Collection validation and sanitization
  - Protection of standard collection types
  - Usage checking before deletion

### View

- **File**: `admin/views/media-collections.php`
- **Purpose**: Modern WordPress admin interface for media collection management
- **Features**:
  - Comprehensive form for adding/editing collections
  - Real-time collection listing with status indicators
  - Auto-generation of collection IDs from display names
  - Protection indicators for standard collections
  - AJAX-powered interactions
  - Responsive design with WordPress admin styling

### Admin Integration

- **File**: `admin/class-hp-admin-new.php` (updated)
- **Menu Item**: Media Collections (under HeritagePress main menu)
- **Access**: Requires `manage_options` capability

## Key Improvements Over TNG

### Enhanced Functionality

- **Complete CRUD Interface**: Full management vs TNG's add-only functionality
- **Data Validation**: Comprehensive validation and error handling
- **Usage Protection**: Prevents deletion of collections in use
- **Standard Collection Protection**: Prevents modification of core collection types
- **Auto-ID Generation**: Automatically generates clean IDs from display names

### WordPress Standards

- **Security**: Proper nonce verification and capability checks
- **Database**: Uses WordPress $wpdb abstraction
- **UI/UX**: Modern WordPress admin interface
- **AJAX**: Proper WordPress AJAX implementation
- **Sanitization**: All inputs properly sanitized and validated

### Database Integration

- **Table**: Utilizes existing `hp_mediatypes` table
- **Compatibility**: Maintains full compatibility with GEDCOM import
- **Standards**: Preserves TNG's standard collection types
- **Structure**: All original TNG fields supported plus status management

## TNG vs HeritagePress Comparison

### TNG `admin_addcollection.php`

```php
// Simple AJAX endpoint
$stdcolls = array("photos", "histories", "headstones", "documents", "recordings", "videos");
$collid = cleanID($collid);
if(!in_array($collid, $stdcolls)) {
    // Insert into mediatypes table
    echo $newcollid; // Return collection ID
}
```

### HeritagePress Implementation

- **Complete Admin Interface**: Full management UI
- **Enhanced Validation**: Multiple validation layers
- **Better Security**: WordPress security standards
- **Extended Functionality**: Edit, delete, status management
- **User Experience**: Modern, intuitive interface

## Standard Collections Maintained

The implementation preserves TNG's standard collection types:

- `photos` - Photo collections
- `histories` - Historical documents
- `headstones` - Cemetery/headstone images
- `documents` - General documents
- `recordings` - Audio recordings
- `videos` - Video files

## Security Features

- **Nonce Verification**: All AJAX requests protected
- **Capability Checks**: Requires administrative privileges
- **Input Sanitization**: All data properly sanitized
- **SQL Injection Protection**: Prepared statements used
- **XSS Prevention**: Output properly escaped

## Code Organization

- **MVC Pattern**: Clean separation of concerns
- **Reusable Components**: Modular AJAX handlers
- **WordPress Integration**: Follows WP coding standards
- **Error Handling**: Comprehensive error reporting
- **User Feedback**: Clear success/error messaging

## Database Schema

Utilizes existing `hp_mediatypes` table structure:

- `mediatypeID` - Unique collection identifier
- `display` - User-friendly display name
- `path` - File path or URL pattern
- `liketype` - Similar media type for handling
- `icon` - Icon for UI display
- `thumb` - Thumbnail image
- `exportas` - Export format specification
- `disabled` - Enable/disable status
- `ordernum` - Display order
- `localpath` - Local file system path

## Usage Examples

### Adding Custom Collection

1. Navigate to HeritagePress → Media Collections
2. Click "Add New Collection"
3. Enter display name (ID auto-generated)
4. Configure paths and settings
5. Save collection

### Editing Collections

1. Click "Edit" next to any custom collection
2. Modify settings as needed
3. Save changes

### Deleting Collections

1. Only custom collections can be deleted
2. System prevents deletion if collection is in use
3. Confirmation required before deletion

## Future Enhancements

- **Import/Export**: Collection configuration import/export
- **Templates**: Pre-configured collection templates
- **Media Browser**: Integration with WordPress media library
- **Usage Analytics**: Collection usage statistics
- **Bulk Operations**: Mass collection management

## Conversion Completion

TNG's `admin_addcollection.php` functionality has been successfully enhanced and ported to HeritagePress with:

- ✅ Full CRUD operations (vs TNG's add-only)
- ✅ Modern WordPress admin interface
- ✅ Enhanced security and validation
- ✅ Protection of standard collections
- ✅ Usage-aware deletion prevention
- ✅ AJAX-powered user experience
- ✅ WordPress coding standards compliance
- ✅ Complete backward compatibility

The implementation significantly expands on TNG's basic functionality while maintaining full compatibility with existing media collections and GEDCOM import processes.

Date: June 18, 2025
