# TNG Album Management to HeritagePress Conversion Notes

## Overview

Successfully ported TNG's album management functionality to HeritagePress WordPress plugin, creating a comprehensive album system that modernizes the original TNG approach while maintaining full compatibility and extending functionality significantly.

## TNG Files Converted

- `admin_add2albumxml.php` - AJAX media selection interface for albums
- `admin_addalbum.php` - Album creation functionality
- `admin_albums.php` - Main album listing and management interface
- `admin_editalbum.php` - Album editing interface
- `admin_updatealbum.php` - Album update processing
- `admin_deletealbum.php` - Album deletion functionality

## HeritagePress Implementation

### Controller

- **File**: `admin/controllers/class-hp-album-controller.php`
- **Purpose**: Complete album management with full CRUD operations and media management
- **Features**:
  - Full CRUD operations (Create, Read, Update, Delete)
  - Advanced media search and selection interface (equivalent to admin_add2albumxml.php)
  - AJAX handlers for all form submissions and media management
  - WordPress security (nonce verification, capability checks)
  - Album validation and sanitization
  - Media association management (add/remove media from albums)
  - Pagination support for large media collections
  - Advanced search filters (media type, tree, keywords)

### Views

#### Main Album Management (`albums-main.php`)

- **Purpose**: Modern WordPress admin interface for album listing and management
- **Features**:
  - Comprehensive album listing with search and pagination
  - Status indicators (Active/Inactive, Always On)
  - Media count per album with direct management links
  - AJAX-powered deletion with confirmation
  - Responsive design with WordPress admin styling
  - Advanced search and filtering capabilities

#### Add Album (`albums-add.php`)

- **Purpose**: Album creation interface
- **Features**:
  - Clean, accessible form design
  - Real-time character counting
  - Client-side validation
  - Status options (Active, Always On)
  - Proper error handling and user feedback

#### Edit Album (`albums-edit.php`)

- **Purpose**: Album editing interface with integrated media management
- **Features**:
  - Tabbed interface (Album Info, Media Items)
  - Complete album details editing
  - Direct integration with media management
  - Delete album functionality with cascaded cleanup
  - Media count display with management links

#### Manage Album Media (`albums-manage.php`)

- **Purpose**: Advanced media selection and management interface (equivalent to admin_add2albumxml.php)
- **Features**:
  - Advanced search interface with multiple filters
  - Real-time media search with AJAX
  - Thumbnail display with metadata
  - Add/remove media from albums
  - Pagination for large result sets
  - Current album media display
  - Status indicators for media already in album

### Admin Integration

- **File**: `admin/class-hp-admin-new.php` (updated)
- **Menu Item**: Albums (under HeritagePress main menu, after Media Management)
- **Access**: Requires `manage_options` capability
- **URL Structure**: `admin.php?page=heritagepress-albums&tab=[list|add|edit|manage]`

## Database Schema

Utilizes existing TNG-compatible album tables:

### `hp_albums` table

- `albumID` - Auto-increment primary key
- `albumname` - Album name (100 chars max)
- `description` - Album description (text)
- `keywords` - Search keywords (text)
- `active` - Active status (tinyint)
- `alwayson` - Always visible flag (tinyint)

### `hp_albumlinks` table

- `albumlinkID` - Auto-increment primary key
- `albumID` - Album reference
- `mediaID` - Media item reference
- `ordernum` - Display order
- `defphoto` - Default photo flag

### `hp_albumplinks` table

- `alinkID` - Auto-increment primary key
- `gedcom` - Tree reference
- `linktype` - Link type
- `entityID` - Entity reference
- `eventID` - Event reference
- `albumID` - Album reference
- `ordernum` - Display order

## Key Improvements Over TNG

### Enhanced Functionality

- **Complete CRUD Interface**: Full management vs TNG's basic functionality
- **Advanced Media Search**: Multi-criteria search with real-time filtering
- **Media Preview**: Thumbnail display with metadata
- **Status Management**: Active/inactive and always-on status controls
- **Integrated Navigation**: Seamless integration between album and media management
- **Bulk Operations**: Future-ready for bulk media operations

### WordPress Standards

- **Security**: Proper nonce verification and capability checks
- **Database**: Uses WordPress $wpdb abstraction
- **UI/UX**: Modern WordPress admin interface design
- **AJAX**: Proper WordPress AJAX implementation
- **Sanitization**: All inputs properly sanitized and validated
- **Responsive**: Mobile-friendly admin interface

### User Experience

- **Real-time Search**: Instant media search results
- **Visual Feedback**: Clear status indicators and loading states
- **Intuitive Navigation**: Tab-based interface for complex operations
- **Error Handling**: Comprehensive error reporting and user guidance
- **Performance**: Pagination and optimized queries for large datasets

## TNG vs HeritagePress Comparison

### TNG `admin_add2albumxml.php`

```php
// Simple AJAX endpoint for media selection
$query = "SELECT ... FROM $media_table WHERE ... ORDER BY description LIMIT $newoffset$maxsearchresults";
echo "<table>..."; // Direct HTML output
```

### HeritagePress Implementation

- **Structured Controller**: Clean MVC architecture
- **Advanced Search**: Multiple filter criteria
- **Better UI**: Modern, accessible interface
- **Enhanced Security**: WordPress security standards
- **Extensible**: Easy to add new features

## AJAX Endpoints

- `wp_ajax_hp_add_album` - Create new album
- `wp_ajax_hp_update_album` - Update album details
- `wp_ajax_hp_delete_album` - Delete album with cleanup
- `wp_ajax_hp_get_album` - Retrieve album data
- `wp_ajax_hp_get_albums` - List all albums
- `wp_ajax_hp_search_media_for_album` - Search media for album selection
- `wp_ajax_hp_add_media_to_album` - Add media to album
- `wp_ajax_hp_remove_media_from_album` - Remove media from album

## Security Features

- **Nonce Verification**: All AJAX requests protected
- **Capability Checks**: Requires administrative privileges
- **Input Sanitization**: All data properly sanitized
- **SQL Injection Protection**: Prepared statements used
- **XSS Prevention**: Output properly escaped

## Usage Examples

### Creating Albums

1. Navigate to HeritagePress → Albums
2. Click "Add New Album"
3. Enter album details (name, description, keywords)
4. Set status options (Active, Always On)
5. Save album

### Managing Album Media

1. From album list, click "Manage Media" or edit album and use "Media Items" tab
2. Use search filters to find media items
3. Click "Add to Album" for desired media
4. Remove items using "Remove" button
5. Current album contents update in real-time

### Editing Albums

1. Click "Edit" next to any album
2. Use "Album Information" tab for basic details
3. Use "Media Items" tab to see current contents
4. Direct links to media management interface

## Future Enhancements

- **Drag & Drop Ordering**: Visual media ordering within albums
- **Bulk Media Operations**: Select multiple media items at once
- **Album Templates**: Pre-configured album types
- **Export/Import**: Album configuration backup/restore
- **Public Gallery**: Front-end album display
- **Media Batch Upload**: Upload multiple items directly to album

## Conversion Completion

TNG's album management functionality has been successfully enhanced and ported to HeritagePress with:

- ✅ Complete CRUD operations (vs TNG's basic functionality)
- ✅ Advanced media selection interface (enhanced admin_add2albumxml.php)
- ✅ Modern WordPress admin integration
- ✅ Enhanced security and validation
- ✅ Real-time search and filtering
- ✅ AJAX-powered user experience
- ✅ WordPress coding standards compliance
- ✅ Mobile-responsive interface
- ✅ Complete backward compatibility with TNG database schema

The implementation significantly expands on TNG's functionality while maintaining full compatibility with existing album data and genealogy workflows.

Date: June 18, 2025
