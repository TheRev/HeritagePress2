# admin_branchoptions.php Conversion Notes

## Original TNG File Purpose

The TNG `admin_branchoptions.php` file was a simple AJAX endpoint that returns HTML `<option>` elements for populating branch selection dropdowns dynamically.

## TNG Functionality

- Takes a tree parameter (from global `$tree` variable)
- Queries the branches table for all branches in that tree
- Returns "0" if no branches exist
- Returns HTML `<option>` elements for each branch: `<option value="branch_code">Branch Description</option>`
- Includes an empty option at the top: `<option value=""></option>`

## HeritagePress Implementation

### Location

`admin/controllers/class-hp-branch-controller.php` - `ajax_get_branch_options()` method

### WordPress Integration

- **AJAX Hook**: `wp_ajax_hp_get_branch_options`
- **Security**: WordPress nonce verification
- **Sanitization**: `sanitize_text_field()` for input
- **Output Escaping**: `esc_attr()` and `esc_html()` for XSS protection
- **Database**: Uses `$wpdb->prepare()` for SQL injection protection

### Usage Example

```javascript
// JavaScript usage for dynamic branch options
function loadBranchOptions(treeId, selectElementId) {
  jQuery.ajax({
    url: ajaxurl,
    type: "POST",
    data: {
      action: "hp_get_branch_options",
      tree: treeId,
      nonce: hp_ajax_nonce,
    },
    success: function (response) {
      if (response === "0") {
        jQuery("#" + selectElementId).html(
          '<option value="">No branches available</option>'
        );
      } else {
        jQuery("#" + selectElementId).html(response);
      }
    },
    error: function () {
      jQuery("#" + selectElementId).html(
        '<option value="">Error loading branches</option>'
      );
    },
  });
}

// Usage: loadBranchOptions('tree1', 'branch-select');
```

### PHP Implementation Details

```php
public function ajax_get_branch_options()
{
    // Security check
    if (!wp_verify_nonce($_POST['nonce'], 'hp_ajax_nonce')) {
        wp_die(__('Security check failed', 'heritagepress'));
    }

    $tree = sanitize_text_field($_POST['tree'] ?? '');

    if (empty($tree)) {
        echo "0";
        wp_die();
    }

    $branches = $this->wpdb->get_results($this->wpdb->prepare(
        "SELECT branch, description FROM {$this->branches_table} WHERE gedcom = %s ORDER BY description",
        $tree
    ));

    $numrows = count($branches);

    if (!$numrows) {
        echo "0";
    } else {
        echo "<option value=\"\"></option>\n";
        foreach ($branches as $row) {
            echo "<option value=\"" . esc_attr($row->branch) . "\">" . esc_html($row->description) . "</option>\n";
        }
    }

    wp_die();
}
```

## Comparison with TNG

| Feature                  | TNG | HeritagePress | Status         |
| ------------------------ | --- | ------------- | -------------- |
| Branch Query             | ✅  | ✅            | ✅ Implemented |
| Empty Option             | ✅  | ✅            | ✅ Implemented |
| HTML Output Format       | ✅  | ✅            | ✅ Implemented |
| Tree Parameter           | ✅  | ✅            | ✅ Implemented |
| "0" for No Results       | ✅  | ✅            | ✅ Implemented |
| Security                 | ❌  | ✅            | ✅ Enhanced    |
| XSS Protection           | ❌  | ✅            | ✅ Enhanced    |
| SQL Injection Protection | ❌  | ✅            | ✅ Enhanced    |

## Integration Points

This endpoint is useful for:

1. **Dynamic Tree Selection**: When a user changes the tree, update branch options
2. **Person/Family Forms**: Populate branch dropdown based on selected tree
3. **Import Forms**: Allow branch selection during data import
4. **Admin Interfaces**: Any form that needs branch selection

## Current Usage in HeritagePress

Currently used in:

- `admin/views/people/add-person-modal.php` (static PHP, could be converted to use AJAX)

## Future Enhancements

Potential improvements over TNG:

1. **Caching**: Cache branch options to reduce database queries
2. **Filtering**: Add search/filter capability for large branch lists
3. **Hierarchical Display**: Show branch relationships if implemented
4. **Permissions**: Respect user branch permissions

## Completion Status

✅ **COMPLETE** - Fully functional and tested, maintains exact TNG compatibility while adding WordPress security features.
