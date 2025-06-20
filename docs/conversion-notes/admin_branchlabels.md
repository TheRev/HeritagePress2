# TNG admin_branchlabels.php Conversion Notes

## Summary

Successfully ported TNG's branch labeling functionality to HeritagePress. This was a complex genealogy utility that applies, clears, or deletes branch labels based on family relationships.

## Files Modified/Created

### Modified Files:

- `admin/controllers/class-hp-branch-controller.php` - Added complete branch labeling backend logic
- `admin/views/branches-main.php` - Added "Label Branches" tab with full UI
- `docs/tng-file-tracker.md` - Updated to reflect completion

### Implementation Details:

#### 1. Backend Logic (class-hp-branch-controller.php)

- **apply_branch_labels()** - Main processing method supporting:
  - Add labels, clear labels, delete branch records
  - Apply to all records or specific genealogical relationships
  - Overwrite modes (replace, append, leave existing)
  - Ancestor/descendant traversal with generation limits
  - Spouse inclusion options

#### 2. Core Functions Ported:

- **get_person_gender()** - Determines gender and relationship terms
- **set_person_label()** - Applies labels to individuals
- **calculate_new_branch_value()** - Handles comma-separated branch values
- **clear_branch_from_people/families()** - Bulk clearing operations
- **delete_branch_people/families()** - Bulk deletion with cleanup
- **process_ancestors()** - Recursive ancestor traversal
- **process_descendants()** - Recursive descendant traversal
- **set_family_labels_for_person()** - Family relationship labeling
- **set_spouse_labels()** - Spouse labeling

#### 3. UI Implementation (branches-main.php)

- Added "Label Branches" tab to existing branch management interface
- Form fields matching TNG exactly:
  - Tree selection
  - Branch selection (dynamically loaded)
  - Action radio buttons (Add/Clear/Delete)
  - Apply scope (All records/Individual+relatives)
  - Overwrite options (Replace/Append/Leave)
  - Include spouses checkbox
- Progress bar with real-time feedback
- Results display with success/error messages
- Form validation and confirmations for destructive actions

#### 4. AJAX Handlers:

- **ajax_apply_branch_labels()** - Main processing endpoint
- **ajax_get_tree_branches()** - Dynamic branch loading by tree

## Key Differences from TNG:

### Improvements:

1. **WordPress Integration**: Uses $wpdb, WordPress nonces, and admin UI standards
2. **Better UX**: Progress bars, AJAX processing, better error handling
3. **Transaction Safety**: Database transactions for data consistency
4. **Modern UI**: Responsive design, WordPress admin styling
5. **Enhanced Validation**: Client and server-side validation

### TNG Features Preserved:

1. **Exact Functionality**: All labeling modes (add/clear/delete)
2. **Genealogical Logic**: Ancestor/descendant traversal with generation limits
3. **Branch Relationships**: Spouse inclusion, family processing
4. **Data Integrity**: Proper cleanup of related records
5. **Comma-separated Values**: Multiple branch labels per record

## Database Integration:

- Uses existing HeritagePress tables (wp_hp_people, wp_hp_families, etc.)
- Maintains TNG's branch field structure (comma-separated values)
- Handles branchlinks table for explicit branch relationships
- Proper foreign key handling and cascade deletions

## Security Measures:

- WordPress nonces for CSRF protection
- Input sanitization with sanitize_text_field()
- SQL injection prevention with $wpdb->prepare()
- User capability checks (implied through WordPress admin)

## Testing Considerations:

- Test with various generation limits (ancestors/descendants)
- Verify spouse inclusion/exclusion works correctly
- Test overwrite modes (replace/append/leave)
- Confirm destructive operations (clear/delete) work safely
- Validate branch links table synchronization
- Test with multiple trees and complex genealogical relationships

## Follow-up Items:

- Consider adding batch processing for very large trees
- Add logging for branch operations (audit trail)
- Implement undo functionality for accidental deletions
- Add export/import for branch configurations

## Notes:

This was one of the more complex TNG conversions due to the recursive genealogical processing and the need to maintain exact compatibility with TNG's branch labeling logic while modernizing the implementation for WordPress.
