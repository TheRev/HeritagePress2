# HeritagePress User Management

## WordPress Integration Approach

HeritagePress **does not implement** a separate user management system. Instead, it fully integrates with WordPress's native user system.

## How It Works

### User Creation & Management

- **Users are created through WordPress** - Admin → Users → Add New
- **No separate genealogy user accounts** - all users are WordPress users
- **User roles and permissions** managed through WordPress capabilities

### Genealogy-Specific Permissions

HeritagePress extends WordPress with custom capabilities for genealogy functions:

```php
// Genealogy capabilities added to WordPress
'edit_genealogy'           // Can edit genealogy records
'view_private_records'     // Can view private person records
'view_living_records'      // Can view living person records
'manage_genealogy_trees'   // Can manage family trees
'export_gedcom'           // Can export GEDCOM files
'import_gedcom'           // Can import GEDCOM files
```

### User Meta for Genealogy

Genealogy-specific user preferences stored as WordPress user meta:

```php
// Example user meta fields
'hp_default_tree'         // User's preferred tree
'hp_assigned_trees'       // Trees user has access to
'hp_assigned_branches'    // Branches user can access
'hp_preferred_language'   // Genealogy interface language
```

## Implementation Benefits

### ✅ Advantages of WordPress Integration

1. **Security** - Leverages WordPress's proven authentication system
2. **Single Sign-On** - Users don't need separate accounts
3. **Role Management** - Uses familiar WordPress role system
4. **Password Management** - WordPress handles all password functions
5. **Profile Management** - Users manage profiles through WordPress
6. **Plugin Compatibility** - Works with WordPress security plugins

### ❌ TNG Components Removed

- **No `tng_users` table** - removed from database schema
- **No separate login system** - uses WordPress authentication
- **No custom password encoding** - uses WordPress password functions
- **No separate permission tables** - uses WordPress capabilities
- **No user admin interfaces** - uses WordPress user management

## For Developers

### Checking User Permissions

```php
// Check if user can edit genealogy records
if (current_user_can('edit_genealogy')) {
    // Allow editing
}

// Check if user can view private records
if (current_user_can('view_private_records')) {
    // Show private information
}
```

### Adding Genealogy User Meta

```php
// Store user's preferred tree
update_user_meta($user_id, 'hp_default_tree', $tree_id);

// Get user's assigned trees
$assigned_trees = get_user_meta($user_id, 'hp_assigned_trees', true);
```

### Role-Based Access

```php
// Administrators can do everything
if (current_user_can('manage_options')) {
    // Full access
}

// Editors can edit but not manage system
if (current_user_can('edit_genealogy')) {
    // Can edit records
}
```

## Migration from TNG

If migrating from TNG, user accounts should be:

1. **Created in WordPress** using the same usernames/emails
2. **Assigned appropriate roles** (Administrator, Editor, etc.)
3. **Given genealogy capabilities** based on their TNG permissions
4. **Assigned to trees/branches** via user meta

## Summary

HeritagePress provides powerful genealogy functionality while maintaining WordPress-native user management. This ensures security, simplicity, and compatibility with the broader WordPress ecosystem.
