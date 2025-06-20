# TNG admin_addtree.php Conversion Notes

## Original TNG Files Analyzed

### admin_addtree.php

- **Purpose**: Backend processing script for creating new family trees
- **Key Functions**:
  - Form data validation and sanitization
  - Tree ID uniqueness checking
  - Database insertion with error handling
  - Redirect handling for different submission contexts
  - Support for both regular admin and import contexts

### admin_newtree.php

- **Purpose**: Frontend form interface for adding new trees
- **Key Functions**:
  - Form presentation with all tree metadata fields
  - JavaScript validation for tree ID format
  - Support for both standalone and AJAX/modal contexts
  - Form data preservation on validation errors
  - Different submit button behaviors

## HeritagePress Implementation

### Files Created

1. **`includes/template/Trees/add-tree.php`**

   - Replaces `admin_newtree.php` functionality
   - Complete form interface with all TNG fields
   - WordPress admin styling and validation
   - AJAX tree ID availability checking
   - Support for both admin and import contexts

2. **`admin/handlers/class-hp-add-tree-handler.php`**

   - Replaces `admin_addtree.php` backend processing
   - Handles admin-post.php form submissions
   - Complete validation and sanitization
   - Proper WordPress security (nonces, capabilities)

3. **`admin/controllers/class-hp-admin-trees-controller.php`**
   - Handles direct admin page form submissions
   - Integrates with WordPress admin flow
   - Bulk actions support for trees management

### Database Integration

- Uses existing `wp_hp_trees` table with exact TNG field mapping:
  - `gedcom` (tree ID, primary key)
  - `treename`, `description`, `owner`, `email`
  - `address`, `city`, `state`, `country`, `zip`, `phone`
  - `secret`, `disallowgedcreate`, `disallowpdf`
  - `date_created`, `lastimportdate`, `importfilename`

### Key Features Implemented

#### Form Fields (Exact TNG Match)

- Tree ID (gedcom) - alphanumeric validation
- Tree Name (required)
- Description (optional)
- Owner contact information (name, email, address, etc.)
- Privacy settings (private tree, disable GEDCOM/PDF)

#### Validation Rules

- Tree ID format: `[a-zA-Z0-9_-]+` (matching TNG regex)
- Tree ID uniqueness checking
- Required field validation
- Proper input sanitization

#### Submission Handling

- Multiple submit buttons like TNG:
  - "Save and Return to Trees" (`submitx`)
  - "Save and Edit Tree" (`submit`)
- Context-aware redirects:
  - Regular form: redirect to trees list or edit
  - Import context: AJAX response
- Form data preservation on errors

#### Security Enhancements

- WordPress nonces for CSRF protection
- Capability checking (`manage_options`)
- Input sanitization with WordPress functions
- SQL injection prevention with prepared statements

### Integration Points

#### Admin Menu

- Integrated into HeritagePress admin menu
- Accessible via Trees > Add Tree tab
- Proper WordPress admin page structure

#### AJAX Support

- Tree ID availability checking
- Real-time validation feedback
- Import context support for modal dialogs

#### Error Handling

- WordPress notices system
- Form data preservation on validation errors
- Comprehensive error logging

### Differences from TNG

#### Improvements

- Modern WordPress admin UI/UX
- Enhanced security with nonces and capabilities
- Better input validation and sanitization
- Responsive design
- Accessibility improvements

#### WordPress Integration

- Uses WordPress hooks and filters
- Proper admin page integration
- WordPress coding standards compliance
- Translation ready (`__()`, `_e()` functions)

### Testing Considerations

1. **Form Validation**

   - Test all validation rules (required fields, format checking)
   - Verify tree ID uniqueness checking
   - Test form data preservation on errors

2. **Submission Contexts**

   - Regular admin form submission
   - Import context (AJAX) submission
   - Different submit button behaviors

3. **Database Operations**

   - Verify all fields are properly saved
   - Test with various data combinations
   - Validate date handling

4. **Security**
   - Test nonce validation
   - Verify permission checking
   - Test with different user roles

### Maintenance Notes

- Keep form fields in sync with database schema
- Update validation rules if tree ID requirements change
- Monitor for WordPress API changes affecting admin forms
- Ensure translation strings are properly maintained

## Migration Status

✅ **Complete**: All TNG admin_addtree.php and admin_newtree.php functionality has been successfully ported to HeritagePress with WordPress integration and security enhancements.

## Files Modified/Created

- ✅ `includes/template/Trees/add-tree.php` (created)
- ✅ `admin/handlers/class-hp-add-tree-handler.php` (created)
- ✅ `admin/controllers/class-hp-admin-trees-controller.php` (created)
- ✅ `admin/class-hp-admin.php` (modified to include handlers)
- ✅ `heritagepress.php` (modified to fix trees page reference)
- ✅ `docs/tng-file-tracker.md` (updated)

---

Last Updated: June 18, 2025
