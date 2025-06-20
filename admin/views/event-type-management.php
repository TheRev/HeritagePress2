<?php

/**
 * Event Type Management Admin View
 *
 * Replicates admin_eventtypes.php and admin_addeventtype.php functionality
 * Provides forms for adding, editing, and managing event types with full feature parity
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';

// Get form data for editing
$event_type_data = array();
$event_type_id = '';
$edit_mode = false;

if ($current_tab === 'edit' && isset($_GET['eventtypeID'])) {
  $event_type_id = sanitize_text_field($_GET['eventtypeID']);
  if (!empty($event_type_id)) {
    global $wpdb;
    $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';

    $event_type_data = $wpdb->get_row($wpdb->prepare("
      SELECT * FROM $eventtypes_table WHERE eventtypeID = %s
    ", $event_type_id), ARRAY_A);

    if ($event_type_data) {
      $edit_mode = true;
      // Escape quotes for form display
      foreach ($event_type_data as $key => $value) {
        if (is_string($value)) {
          $event_type_data[$key] = str_replace('"', '&#34;', $value ?? '');
        }
      }
    }
  }
}

// Get controller instance for data access
$controller = new HP_Event_Type_Controller();

?>

<div class="wrap heritagepress-admin">
  <h1>
    <?php echo $edit_mode ? __('Edit Event Type', 'heritagepress') : __('Event Types', 'heritagepress'); ?>
    <?php if ($current_tab === 'browse'): ?>
      <a href="<?php echo admin_url('admin.php?page=hp-event-type-management&tab=add'); ?>" class="page-title-action">
        <?php _e('Add New', 'heritagepress'); ?>
      </a>
    <?php endif; ?>
  </h1>

  <!-- Tabs Navigation -->
  <nav class="nav-tab-wrapper">
    <a href="<?php echo admin_url('admin.php?page=hp-event-type-management&tab=browse'); ?>"
      class="nav-tab <?php echo $current_tab === 'browse' ? 'nav-tab-active' : ''; ?>">
      <?php _e('Browse', 'heritagepress'); ?>
    </a>
    <a href="<?php echo admin_url('admin.php?page=hp-event-type-management&tab=add'); ?>"
      class="nav-tab <?php echo $current_tab === 'add' ? 'nav-tab-active' : ''; ?>">
      <?php _e('Add New', 'heritagepress'); ?>
    </a>
    <?php if ($edit_mode): ?>
      <a href="#" class="nav-tab nav-tab-active">
        <?php _e('Edit', 'heritagepress'); ?>
      </a>
    <?php endif; ?>
  </nav>

  <div class="hp-admin-content">

    <!-- Event Type Form (Add/Edit) -->
    <?php if ($current_tab === 'add' || $edit_mode): ?>
      <div class="hp-form-container">
        <div class="hp-form-header">
          <h2><?php echo $edit_mode ? __('Modify Event Type', 'heritagepress') : __('Add New Event Type', 'heritagepress'); ?></h2>
          <?php if (!$edit_mode): ?>
            <p class="description"><?php _e('Add a new event type to the genealogy system.', 'heritagepress'); ?></p>
          <?php endif; ?>
        </div>

        <div class="hp-form-body">
          <form method="post" id="event-type-form" class="hp-event-type-form">
            <?php wp_nonce_field('hp_event_type_action', '_wpnonce'); ?>
            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_event_type' : 'add_event_type'; ?>">
            <?php if ($edit_mode): ?>
              <input type="hidden" name="eventtypeID" value="<?php echo esc_attr($event_type_id); ?>">
            <?php endif; ?>

            <table class="hp-form-table">
              <!-- Tag -->
              <tr>
                <th scope="row">
                  <label for="tag"><?php _e('Tag:', 'heritagepress'); ?> <span class="required">*</span></label>
                </th>
                <td>
                  <input type="text" name="tag" id="tag"
                    value="<?php echo esc_attr($event_type_data['tag'] ?? ''); ?>"
                    class="regular-text" maxlength="10" required
                    <?php echo $edit_mode ? 'readonly' : ''; ?>>
                  <span class="description"><?php _e('GEDCOM tag (e.g., BIRT, MARR, DEAT)', 'heritagepress'); ?></span>
                </td>
              </tr>

              <!-- Event Type ID -->
              <?php if (!$edit_mode): ?>
                <tr>
                  <th scope="row">
                    <label for="eventtypeID"><?php _e('Event Type ID:', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <input type="text" name="eventtypeID" id="eventtypeID"
                      value="<?php echo esc_attr($event_type_data['eventtypeID'] ?? ''); ?>"
                      class="regular-text" maxlength="25">
                    <span class="description"><?php _e('Leave blank to use tag as ID', 'heritagepress'); ?></span>
                  </td>
                </tr>
              <?php endif; ?>

              <!-- Display Name -->
              <tr>
                <th scope="row">
                  <label for="display"><?php _e('Display Name:', 'heritagepress'); ?> <span class="required">*</span></label>
                </th>
                <td>
                  <input type="text" name="display" id="display"
                    value="<?php echo esc_attr($event_type_data['display'] ?? ''); ?>"
                    class="regular-text" required>
                  <span class="description"><?php _e('User-friendly display name', 'heritagepress'); ?></span>
                </td>
              </tr>

              <!-- Description -->
              <tr>
                <th scope="row">
                  <label for="description"><?php _e('Description:', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" name="description" id="description"
                    value="<?php echo esc_attr($event_type_data['description'] ?? ''); ?>"
                    class="large-text">
                  <span class="description"><?php _e('Internal description (optional)', 'heritagepress'); ?></span>
                </td>
              </tr>

              <!-- Type -->
              <tr>
                <th scope="row">
                  <label for="type"><?php _e('Type:', 'heritagepress'); ?> <span class="required">*</span></label>
                </th>
                <td>
                  <select name="type" id="type" required>
                    <option value="I" <?php selected($event_type_data['type'] ?? 'I', 'I'); ?>><?php _e('Individual', 'heritagepress'); ?></option>
                    <option value="F" <?php selected($event_type_data['type'] ?? '', 'F'); ?>><?php _e('Family', 'heritagepress'); ?></option>
                    <option value="S" <?php selected($event_type_data['type'] ?? '', 'S'); ?>><?php _e('Source', 'heritagepress'); ?></option>
                  </select>
                  <span class="description"><?php _e('Whether this event type applies to individuals, families, or sources', 'heritagepress'); ?></span>
                </td>
              </tr>

              <!-- Keep (Active) -->
              <tr>
                <th scope="row">
                  <label for="keep"><?php _e('Active:', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="checkbox" name="keep" id="keep" value="1"
                    <?php checked($event_type_data['keep'] ?? 1, 1); ?>>
                  <span class="description"><?php _e('Whether this event type is available for use', 'heritagepress'); ?></span>
                </td>
              </tr>

              <!-- Order Number -->
              <tr>
                <th scope="row">
                  <label for="ordernum"><?php _e('Order Number:', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="number" name="ordernum" id="ordernum"
                    value="<?php echo esc_attr($event_type_data['ordernum'] ?? 0); ?>"
                    class="small-text" min="0" max="999">
                  <span class="description"><?php _e('Sort order (0 = default order)', 'heritagepress'); ?></span>
                </td>
              </tr>

              <!-- Collapse (UI Setting) -->
              <tr>
                <th scope="row">
                  <label for="collapse"><?php _e('Collapse in UI:', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="checkbox" name="collapse" id="collapse" value="1"
                    <?php checked($event_type_data['collapse'] ?? 0, 1); ?>>
                  <span class="description"><?php _e('Whether this event type should be collapsed by default in forms', 'heritagepress'); ?></span>
                </td>
              </tr>

              <!-- LDS Event -->
              <tr>
                <th scope="row">
                  <label for="ldsevent"><?php _e('LDS Temple Ordinance:', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="checkbox" name="ldsevent" id="ldsevent" value="1"
                    <?php checked($event_type_data['ldsevent'] ?? 0, 1); ?>>
                  <span class="description"><?php _e('Whether this is an LDS temple ordinance event', 'heritagepress'); ?></span>
                </td>
              </tr>
            </table>

            <!-- Form Actions -->
            <div class="hp-form-actions">
              <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary"
                  value="<?php echo $edit_mode ? __('Update Event Type', 'heritagepress') : __('Save Event Type', 'heritagepress'); ?>">
                <button type="button" class="button" onclick="cancelForm();"><?php _e('Cancel', 'heritagepress'); ?></button>
              </p>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <!-- Event Type List (Browse) -->
    <?php if ($current_tab === 'browse'): ?>
      <div class="hp-list-container">
        <div class="hp-list-header">
          <h2><?php _e('Event Types', 'heritagepress'); ?></h2>
          <p class="description"><?php _e('Manage event types available in your genealogy system.', 'heritagepress'); ?></p>
        </div>

        <!-- Search and Filters -->
        <div class="hp-list-filters">
          <div class="hp-search-box">
            <input type="text" id="event-type-search" placeholder="<?php _e('Search event types...', 'heritagepress'); ?>" class="regular-text">
            <button type="button" id="search-event-types" class="button"><?php _e('Search', 'heritagepress'); ?></button>
          </div>

          <div class="hp-filter-box">
            <select id="type-filter">
              <option value=""><?php _e('All Types', 'heritagepress'); ?></option>
              <option value="I"><?php _e('Individual', 'heritagepress'); ?></option>
              <option value="F"><?php _e('Family', 'heritagepress'); ?></option>
              <option value="S"><?php _e('Source', 'heritagepress'); ?></option>
            </select>

            <select id="keep-filter">
              <option value=""><?php _e('All Status', 'heritagepress'); ?></option>
              <option value="1"><?php _e('Active', 'heritagepress'); ?></option>
              <option value="0"><?php _e('Inactive', 'heritagepress'); ?></option>
            </select>

            <button type="button" id="filter-event-types" class="button"><?php _e('Filter', 'heritagepress'); ?></button>
            <button type="button" id="reset-filters" class="button"><?php _e('Reset', 'heritagepress'); ?></button>
          </div>
        </div>

        <!-- Bulk Actions -->
        <div class="hp-bulk-actions">
          <select id="bulk-action-selector">
            <option value=""><?php _e('Bulk Actions', 'heritagepress'); ?></option>
            <option value="activate"><?php _e('Activate', 'heritagepress'); ?></option>
            <option value="deactivate"><?php _e('Deactivate', 'heritagepress'); ?></option>
            <option value="delete"><?php _e('Delete', 'heritagepress'); ?></option>
          </select>
          <button type="button" id="bulk-action-btn" class="button"><?php _e('Apply', 'heritagepress'); ?></button>
        </div>

        <div class="hp-list-body">
          <div id="event-types-list">
            <!-- Event types will be loaded via AJAX -->
            <p class="loading"><?php _e('Loading event types...', 'heritagepress'); ?></p>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<!-- Inline CSS for Event Type Management -->
<style>
  .heritagepress-admin .hp-form-table {
    max-width: 100%;
    margin: 0;
  }

  .heritagepress-admin .hp-form-table th {
    width: 150px;
    vertical-align: top;
    padding: 15px 10px 15px 0;
  }

  .heritagepress-admin .hp-form-table td {
    padding: 15px 10px;
  }

  .heritagepress-admin .required {
    color: #d63638;
  }

  .heritagepress-admin .hp-list-filters {
    display: flex;
    gap: 20px;
    margin: 20px 0;
    align-items: center;
    flex-wrap: wrap;
  }

  .heritagepress-admin .hp-search-box,
  .heritagepress-admin .hp-filter-box {
    display: flex;
    gap: 10px;
    align-items: center;
  }

  .heritagepress-admin .hp-bulk-actions {
    margin: 15px 0;
    padding: 10px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 3px;
  }

  .heritagepress-admin .hp-bulk-actions select,
  .heritagepress-admin .hp-bulk-actions button {
    margin-right: 10px;
  }

  .heritagepress-admin .hp-form-actions {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
  }

  .heritagepress-admin .event-type-status {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
  }

  .heritagepress-admin .event-type-status.active {
    background: #d4edda;
    color: #155724;
  }

  .heritagepress-admin .event-type-status.inactive {
    background: #f8d7da;
    color: #721c24;
  }

  .heritagepress-admin .event-type-actions {
    white-space: nowrap;
  }

  .heritagepress-admin .event-type-actions .button {
    margin-right: 5px;
  }

  .heritagepress-admin .loading {
    text-align: center;
    padding: 40px;
    color: #666;
    font-style: italic;
  }

  .heritagepress-admin .error {
    color: #d63638;
    font-style: italic;
  }

  .heritagepress-admin .type-badge {
    display: inline-block;
    padding: 2px 6px;
    background: #f0f0f1;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
  }

  .heritagepress-admin .type-badge.individual {
    background: #e7f3ff;
    border-color: #72aee6;
    color: #0073aa;
  }

  .heritagepress-admin .type-badge.family {
    background: #f0fff4;
    border-color: #46b450;
    color: #00a32a;
  }

  .heritagepress-admin .type-badge.source {
    background: #fff8e1;
    border-color: #ffb900;
    color: #b26500;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Form submission handling
    $('#event-type-form').on('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);
      const isEdit = formData.get('action') === 'update_event_type';

      // Add AJAX action and nonce
      formData.append('action', isEdit ? 'hp_update_event_type' : 'hp_add_event_type');
      formData.append('nonce', '<?php echo wp_create_nonce('hp_event_type_nonce'); ?>');

      // Disable submit button
      const $submitBtn = $('#submit');
      const originalText = $submitBtn.val();
      $submitBtn.prop('disabled', true).val('<?php _e('Saving...', 'heritagepress'); ?>');

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
          if (response.success) {
            alert(isEdit ?
              '<?php _e('Event type updated successfully!', 'heritagepress'); ?>' :
              '<?php _e('Event type created successfully!', 'heritagepress'); ?>'
            );
            // Redirect to browse tab
            window.location.href = '<?php echo admin_url('admin.php?page=hp-event-type-management&tab=browse'); ?>';
          } else {
            alert('<?php _e('Error:', 'heritagepress'); ?> ' + (response.data || '<?php _e('Unknown error occurred.', 'heritagepress'); ?>'));
          }
        },
        error: function() {
          alert('<?php _e('Network error. Please try again.', 'heritagepress'); ?>');
        },
        complete: function() {
          $submitBtn.prop('disabled', false).val(originalText);
        }
      });
    });

    // Cancel form
    window.cancelForm = function() {
      if (confirm('<?php _e('Are you sure you want to cancel? Any unsaved changes will be lost.', 'heritagepress'); ?>')) {
        window.location.href = '<?php echo admin_url('admin.php?page=hp-event-type-management&tab=browse'); ?>';
      }
    };

    // Load event types if on browse tab
    <?php if ($current_tab === 'browse'): ?>
      loadEventTypesList();

      // Search functionality
      $('#search-event-types, #filter-event-types').on('click', function() {
        loadEventTypesList();
      });

      $('#reset-filters').on('click', function() {
        $('#event-type-search').val('');
        $('#type-filter').val('');
        $('#keep-filter').val('');
        loadEventTypesList();
      });

      // Search on Enter key
      $('#event-type-search').on('keypress', function(e) {
        if (e.which === 13) {
          loadEventTypesList();
        }
      });

      // Bulk actions
      $('#bulk-action-btn').on('click', function() {
        const action = $('#bulk-action-selector').val();
        const selected = $('.event-type-checkbox:checked');

        if (!action) {
          alert('<?php _e('Please select an action.', 'heritagepress'); ?>');
          return;
        }

        if (selected.length === 0) {
          alert('<?php _e('Please select event types to perform bulk action.', 'heritagepress'); ?>');
          return;
        }

        if (action === 'delete' && !confirm('<?php _e('Are you sure you want to delete the selected event types?', 'heritagepress'); ?>')) {
          return;
        }

        const eventTypeIds = [];
        selected.each(function() {
          eventTypeIds.push($(this).val());
        });

        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_bulk_event_type_action',
            nonce: '<?php echo wp_create_nonce('hp_event_type_nonce'); ?>',
            bulk_action: action,
            event_type_ids: eventTypeIds
          },
          success: function(response) {
            if (response.success) {
              alert('<?php _e('Bulk action completed successfully.', 'heritagepress'); ?>');
              loadEventTypesList();
            } else {
              alert('<?php _e('Error:', 'heritagepress'); ?> ' + response.data);
            }
          },
          error: function() {
            alert('<?php _e('Network error. Please try again.', 'heritagepress'); ?>');
          }
        });
      });

      function loadEventTypesList() {
        $('#event-types-list').html('<p class="loading"><?php _e('Loading event types...', 'heritagepress'); ?></p>');

        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_get_event_types_list',
            nonce: '<?php echo wp_create_nonce('hp_event_type_nonce'); ?>',
            search: $('#event-type-search').val(),
            type: $('#type-filter').val(),
            keep: $('#keep-filter').val()
          },
          success: function(response) {
            if (response.success) {
              displayEventTypesList(response.data.event_types);
            } else {
              $('#event-types-list').html('<p class="error"><?php _e('Failed to load event types.', 'heritagepress'); ?></p>');
            }
          },
          error: function() {
            $('#event-types-list').html('<p class="error"><?php _e('Network error loading event types.', 'heritagepress'); ?></p>');
          }
        });
      }

      function displayEventTypesList(eventTypes) {
        if (!eventTypes || eventTypes.length === 0) {
          $('#event-types-list').html('<p><?php _e('No event types found.', 'heritagepress'); ?></p>');
          return;
        }

        let html = '<table class="wp-list-table widefat fixed striped">';
        html += '<thead><tr>';
        html += '<td class="check-column"><input type="checkbox" id="select-all-event-types"></td>';
        html += '<th><?php _e('Tag', 'heritagepress'); ?></th>';
        html += '<th><?php _e('Display Name', 'heritagepress'); ?></th>';
        html += '<th><?php _e('Type', 'heritagepress'); ?></th>';
        html += '<th><?php _e('Status', 'heritagepress'); ?></th>';
        html += '<th><?php _e('Order', 'heritagepress'); ?></th>';
        html += '<th><?php _e('Actions', 'heritagepress'); ?></th>';
        html += '</tr></thead><tbody>';

        eventTypes.forEach(function(eventType) {
          const typeClass = eventType.type === 'I' ? 'individual' : (eventType.type === 'F' ? 'family' : 'source');
          const typeName = eventType.type === 'I' ? '<?php _e('Individual', 'heritagepress'); ?>' :
            (eventType.type === 'F' ? '<?php _e('Family', 'heritagepress'); ?>' : '<?php _e('Source', 'heritagepress'); ?>');
          const statusClass = eventType.keep == 1 ? 'active' : 'inactive';
          const statusText = eventType.keep == 1 ? '<?php _e('Active', 'heritagepress'); ?>' : '<?php _e('Inactive', 'heritagepress'); ?>';

          html += '<tr>';
          html += '<th scope="row" class="check-column"><input type="checkbox" class="event-type-checkbox" value="' + eventType.eventtypeID + '"></th>';
          html += '<td><strong>' + eventType.tag + '</strong></td>';
          html += '<td>' + (eventType.display || '') + '</td>';
          html += '<td><span class="type-badge ' + typeClass + '">' + typeName + '</span></td>';
          html += '<td><span class="event-type-status ' + statusClass + '">' + statusText + '</span></td>';
          html += '<td>' + (eventType.ordernum || '0') + '</td>';
          html += '<td class="event-type-actions">';
          html += '<a href="<?php echo admin_url('admin.php?page=hp-event-type-management&tab=edit'); ?>&eventtypeID=' + eventType.eventtypeID + '" class="button button-small"><?php _e('Edit', 'heritagepress'); ?></a> ';
          html += '<button type="button" class="button button-small button-link-delete" onclick="deleteEventType(\'' + eventType.eventtypeID + '\')"><?php _e('Delete', 'heritagepress'); ?></button>';
          html += '</td>';
          html += '</tr>';
        });

        html += '</tbody></table>';
        $('#event-types-list').html(html);

        // Select all functionality
        $('#select-all-event-types').on('change', function() {
          $('.event-type-checkbox').prop('checked', this.checked);
        });
      }

      window.deleteEventType = function(eventTypeID) {
        if (!confirm('<?php _e('Are you sure you want to delete this event type?', 'heritagepress'); ?>')) {
          return;
        }

        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_delete_event_type',
            nonce: '<?php echo wp_create_nonce('hp_event_type_nonce'); ?>',
            eventtypeID: eventTypeID
          },
          success: function(response) {
            if (response.success) {
              alert('<?php _e('Event type deleted successfully!', 'heritagepress'); ?>');
              loadEventTypesList(); // Reload the list
            } else {
              alert('<?php _e('Error:', 'heritagepress'); ?> ' + (response.data || '<?php _e('Failed to delete event type.', 'heritagepress'); ?>'));
            }
          },
          error: function() {
            alert('<?php _e('Network error. Please try again.', 'heritagepress'); ?>');
          }
        });
      };
    <?php endif; ?>
  });
</script>
