<?php

/**
 * Event Management Admin View
 *
 * Replicates admin_newevent.php and admin_editevent.php functionality
 * Provides forms for adding and editing events with full feature parity
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'add';

// Get form data for editing
$event_data = array();
$event_id = 0;
$edit_mode = false;

if ($current_tab === 'edit' && isset($_GET['eventID'])) {
  $event_id = intval($_GET['eventID']);
  if ($event_id > 0) {
    global $wpdb;
    $events_table = $wpdb->prefix . 'hp_events';
    $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';
    $address_table = $wpdb->prefix . 'hp_addresses';

    $event_data = $wpdb->get_row($wpdb->prepare("
      SELECT e.*, et.display, et.tag, et.type,
             a.address1, a.address2, a.city, a.state, a.zip, a.country, a.phone, a.email, a.www
      FROM $events_table e
      LEFT JOIN $eventtypes_table et ON e.eventtypeID = et.eventtypeID
      LEFT JOIN $address_table a ON e.addressID = a.addressID
      WHERE e.eventID = %d
    ", $event_id), ARRAY_A);

    if ($event_data) {
      $edit_mode = true;
      // Escape quotes for form display
      foreach ($event_data as $key => $value) {
        if (is_string($value)) {
          $event_data[$key] = str_replace('"', '&#34;', $value ?? '');
        }
      }
    }
  }
}

// Get event types for person/family
$prefix = isset($_GET['prefix']) ? sanitize_text_field($_GET['prefix']) : 'I';
$person_family_id = isset($_GET['persfamID']) ? sanitize_text_field($_GET['persfamID']) : '';
$gedcom = isset($_GET['gedcom']) ? sanitize_text_field($_GET['gedcom']) : '';

global $wpdb;
$eventtypes_table = $wpdb->prefix . 'hp_eventtypes';
$event_types = $wpdb->get_results($wpdb->prepare(
  "SELECT * FROM $eventtypes_table WHERE keep = 1 AND type = %s ORDER BY tag",
  $prefix
), ARRAY_A);

?>

<div class="wrap heritagepress-admin">
  <h1><?php echo $edit_mode ? __('Edit Event', 'heritagepress') : __('Add New Event', 'heritagepress'); ?></h1>

  <div class="hp-admin-content">

    <!-- Event Form -->
    <div class="hp-form-container">
      <div class="hp-form-header">
        <h2><?php echo $edit_mode ? __('Modify Event', 'heritagepress') : __('Add New Event', 'heritagepress'); ?></h2>
        <?php if (!$edit_mode): ?>
          <p class="description"><?php _e('Add a new event to a person or family record.', 'heritagepress'); ?></p>
        <?php endif; ?>
      </div>

      <div class="hp-form-body">
        <form method="post" id="event-form" class="hp-event-form">
          <?php wp_nonce_field('hp_event_action', '_wpnonce'); ?>
          <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_event' : 'add_event'; ?>">
          <?php if ($edit_mode): ?>
            <input type="hidden" name="eventID" value="<?php echo $event_id; ?>">
          <?php endif; ?>

          <table class="hp-form-table">
            <!-- Event Type -->
            <tr>
              <th scope="row">
                <label for="eventtypeID"><?php _e('Event Type:', 'heritagepress'); ?></label>
              </th>
              <td>
                <?php if ($edit_mode): ?>
                  <span class="event-type-display">
                    <?php echo esc_html($event_data['tag'] . ' ' . $event_data['display']); ?>
                  </span>
                  <input type="hidden" name="eventtypeID" value="<?php echo esc_attr($event_data['eventtypeID']); ?>">
                <?php else: ?>
                  <select name="eventtypeID" id="eventtypeID" required>
                    <option value=""><?php _e('Select Event Type...', 'heritagepress'); ?></option>
                    <?php
                    $events_by_display = array();
                    foreach ($event_types as $event_type) {
                      $display = esc_html($event_type['display']);
                      $option = $display . ($event_type['tag'] != 'EVEN' ? ' (' . $event_type['tag'] . ')' : '');
                      $option_len = strlen($option);
                      $option = substr($option, 0, 40);
                      if ($option_len > strlen($option)) {
                        $option .= '&hellip;';
                      }
                      $events_by_display[$display] = '<option value="' . esc_attr($event_type['eventtypeID']) . '">' . $option . '</option>';
                    }
                    ksort($events_by_display);
                    foreach ($events_by_display as $event_option) {
                      echo $event_option;
                    }
                    ?>
                  </select>
                <?php endif; ?>
              </td>
            </tr>

            <!-- Person/Family ID -->
            <tr>
              <th scope="row">
                <label for="persfamID"><?php _e('Person/Family ID:', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="persfamID" id="persfamID"
                  value="<?php echo esc_attr($edit_mode ? $event_data['persfamID'] : $person_family_id); ?>"
                  class="regular-text" <?php echo $edit_mode ? 'readonly' : 'required'; ?>>
                <?php if (!$edit_mode): ?>
                  <button type="button" class="button find-person-btn" onclick="findPerson();" title="<?php _e('Find Person/Family', 'heritagepress'); ?>">
                    <span class="dashicons dashicons-search"></span> <?php _e('Find', 'heritagepress'); ?>
                  </button>
                <?php endif; ?>
              </td>
            </tr>

            <!-- Tree -->
            <tr>
              <th scope="row">
                <label for="gedcom"><?php _e('Tree:', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="gedcom" id="gedcom"
                  value="<?php echo esc_attr($edit_mode ? $event_data['gedcom'] : $gedcom); ?>"
                  class="regular-text" <?php echo $edit_mode ? 'readonly' : 'required'; ?>>
              </td>
            </tr>

            <!-- Event Date -->
            <tr>
              <th scope="row">
                <label for="eventdate"><?php _e('Event Date:', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="eventdate" id="eventdate"
                  value="<?php echo esc_attr($event_data['eventdate'] ?? ''); ?>"
                  class="regular-text" onblur="checkDate(this);">
                <span class="description"><?php _e('Date format: DD MMM YYYY (e.g., 15 JAN 1950)', 'heritagepress'); ?></span>
              </td>
            </tr>

            <!-- Event Place -->
            <tr>
              <th scope="row">
                <label for="eventplace"><?php _e('Event Place:', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="eventplace" id="eventplace"
                  value="<?php echo esc_attr($event_data['eventplace'] ?? ''); ?>"
                  class="large-text" size="70">
                &nbsp;<?php _e('or', 'heritagepress'); ?>&nbsp;
                <button type="button" class="button find-place-btn" onclick="findPlace();" title="<?php _e('Find Place', 'heritagepress'); ?>">
                  <span class="dashicons dashicons-location-alt"></span> <?php _e('Find', 'heritagepress'); ?>
                </button>
              </td>
            </tr>

            <!-- Event Details -->
            <tr>
              <th scope="row">
                <label for="info"><?php _e('Details:', 'heritagepress'); ?></label>
              </th>
              <td>
                <textarea name="info" id="info" rows="6" cols="70" class="large-text"><?php echo esc_textarea($event_data['info'] ?? ''); ?></textarea>
              </td>
            </tr>

            <!-- Duplicate for Other People/Families -->
            <tr>
              <th scope="row" colspan="2">
                <strong><?php _e('Duplicate for:', 'heritagepress'); ?></strong>
              </th>
            </tr>
            <tr>
              <th scope="row">
                <label for="dupIDs"><?php _e('ID(s):', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="dupIDs" id="dupIDs" class="regular-text" placeholder="<?php _e('Separate multiple IDs with commas', 'heritagepress'); ?>">
                &nbsp;<?php _e('or', 'heritagepress'); ?>&nbsp;
                <button type="button" class="button find-multiple-btn" onclick="findMultiple();" title="<?php _e('Find Multiple', 'heritagepress'); ?>">
                  <span class="dashicons dashicons-search"></span> <?php _e('Find', 'heritagepress'); ?>
                </button>
                <br>
                <span class="description"><?php _e('(Separate multiple IDs with commas)', 'heritagepress'); ?></span>
              </td>
            </tr>
          </table>

          <!-- More Details Section (Collapsible) -->
          <div class="hp-more-section">
            <h3 class="hp-section-toggle" onclick="toggleMoreSection();">
              <span class="dashicons dashicons-arrow-down-alt2" id="more-toggle-icon"></span>
              <?php _e('More Details', 'heritagepress'); ?>
            </h3>

            <div id="more-details" class="hp-section-content" style="display: none;">
              <table class="hp-form-table">
                <!-- Age -->
                <tr>
                  <th scope="row">
                    <label for="age"><?php _e('Age:', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <input type="text" name="age" id="age" value="<?php echo esc_attr($event_data['age'] ?? ''); ?>"
                      class="small-text" size="12" maxlength="12">
                  </td>
                </tr>

                <!-- Agency -->
                <tr>
                  <th scope="row">
                    <label for="agency"><?php _e('Agency:', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <input type="text" name="agency" id="agency" value="<?php echo esc_attr($event_data['agency'] ?? ''); ?>"
                      class="regular-text" size="40">
                  </td>
                </tr>

                <!-- Cause -->
                <tr>
                  <th scope="row">
                    <label for="cause"><?php _e('Cause:', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <input type="text" name="cause" id="cause" value="<?php echo esc_attr($event_data['cause'] ?? ''); ?>"
                      class="regular-text" size="40">
                  </td>
                </tr>

                <!-- Address Information -->
                <tr>
                  <th scope="row" colspan="2">
                    <strong><?php _e('Address Information', 'heritagepress'); ?></strong>
                  </th>
                </tr>

                <!-- Address 1 -->
                <tr>
                  <th scope="row">
                    <label for="address1"><?php _e('Address 1:', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <input type="text" name="address1" id="address1" value="<?php echo esc_attr($event_data['address1'] ?? ''); ?>"
                      class="regular-text" size="40">
                  </td>
                </tr>

                <!-- Address 2 -->
                <tr>
                  <th scope="row">
                    <label for="address2"><?php _e('Address 2:', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <input type="text" name="address2" id="address2" value="<?php echo esc_attr($event_data['address2'] ?? ''); ?>"
                      class="regular-text" size="40">
                  </td>
                </tr>

                <!-- City -->
                <tr>
                  <th scope="row">
                    <label for="city"><?php _e('City:', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <input type="text" name="city" id="city" value="<?php echo esc_attr($event_data['city'] ?? ''); ?>"
                      class="regular-text" size="40">
                  </td>
                </tr>

                <!-- State/Province -->
                <tr>
                  <th scope="row">
                    <label for="state"><?php _e('State/Province:', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <input type="text" name="state" id="state" value="<?php echo esc_attr($event_data['state'] ?? ''); ?>"
                      class="regular-text" size="40">
                  </td>
                </tr>

                <!-- ZIP/Postal Code -->
                <tr>
                  <th scope="row">
                    <label for="zip"><?php _e('ZIP/Postal Code:', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <input type="text" name="zip" id="zip" value="<?php echo esc_attr($event_data['zip'] ?? ''); ?>"
                      class="regular-text" size="20">
                  </td>
                </tr>

                <!-- Country -->
                <tr>
                  <th scope="row">
                    <label for="country"><?php _e('Country:', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <input type="text" name="country" id="country" value="<?php echo esc_attr($event_data['country'] ?? ''); ?>"
                      class="regular-text" size="40">
                  </td>
                </tr>

                <!-- Phone -->
                <tr>
                  <th scope="row">
                    <label for="phone"><?php _e('Phone:', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <input type="text" name="phone" id="phone" value="<?php echo esc_attr($event_data['phone'] ?? ''); ?>"
                      class="regular-text" size="30">
                  </td>
                </tr>

                <!-- Email -->
                <tr>
                  <th scope="row">
                    <label for="email"><?php _e('Email:', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <input type="email" name="email" id="email" value="<?php echo esc_attr($event_data['email'] ?? ''); ?>"
                      class="regular-text" size="50">
                  </td>
                </tr>

                <!-- Website -->
                <tr>
                  <th scope="row">
                    <label for="www"><?php _e('Website:', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <input type="url" name="www" id="www" value="<?php echo esc_attr($event_data['www'] ?? ''); ?>"
                      class="regular-text" size="50">
                  </td>
                </tr>
              </table>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="hp-form-actions">
            <p class="submit">
              <input type="submit" name="submit" id="submit" class="button button-primary"
                value="<?php echo $edit_mode ? __('Update Event', 'heritagepress') : __('Save Event', 'heritagepress'); ?>">
              <button type="button" class="button" onclick="cancelForm();"><?php _e('Cancel', 'heritagepress'); ?></button>
            </p>
          </div>
        </form>
      </div>
    </div>

    <!-- Event List (if browsing) -->
    <?php if ($current_tab === 'browse'): ?>
      <div class="hp-list-container">
        <div class="hp-list-header">
          <h2><?php _e('Events', 'heritagepress'); ?></h2>
          <p class="description"><?php _e('Manage events in your genealogy database.', 'heritagepress'); ?></p>
        </div>

        <div class="hp-list-actions">
          <a href="<?php echo admin_url('admin.php?page=hp-event-management&tab=add'); ?>" class="button button-primary">
            <?php _e('Add New Event', 'heritagepress'); ?>
          </a>
        </div>

        <div class="hp-list-body">
          <div id="events-list">
            <!-- Events will be loaded via AJAX -->
            <p class="loading"><?php _e('Loading events...', 'heritagepress'); ?></p>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<!-- AJAX Modal for Find Person/Family -->
<div id="find-person-modal" class="hp-modal" style="display: none;">
  <div class="hp-modal-content">
    <div class="hp-modal-header">
      <h3><?php _e('Find Person/Family', 'heritagepress'); ?></h3>
      <button type="button" class="hp-modal-close" onclick="closeFindPersonModal();">&times;</button>
    </div>
    <div class="hp-modal-body">
      <!-- Find person/family form will be loaded here -->
    </div>
  </div>
</div>

<!-- AJAX Modal for Find Place -->
<div id="find-place-modal" class="hp-modal" style="display: none;">
  <div class="hp-modal-content">
    <div class="hp-modal-header">
      <h3><?php _e('Find Place', 'heritagepress'); ?></h3>
      <button type="button" class="hp-modal-close" onclick="closeFindPlaceModal();">&times;</button>
    </div>
    <div class="hp-modal-body">
      <!-- Find place form will be loaded here -->
    </div>
  </div>
</div>

<style>
  .heritagepress-admin .hp-form-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
  }

  .heritagepress-admin .hp-form-header h2 {
    margin-top: 0;
    color: #23282d;
  }

  .heritagepress-admin .hp-form-table {
    width: 100%;
    border-collapse: collapse;
  }

  .heritagepress-admin .hp-form-table th,
  .heritagepress-admin .hp-form-table td {
    padding: 8px 10px;
    border-bottom: 1px solid #f1f1f1;
  }

  .heritagepress-admin .hp-form-table th {
    text-align: left;
    font-weight: 600;
    width: 150px;
    vertical-align: top;
  }

  .heritagepress-admin .hp-more-section {
    margin-top: 20px;
    border-top: 1px solid #ddd;
    padding-top: 15px;
  }

  .heritagepress-admin .hp-section-toggle {
    margin: 0;
    cursor: pointer;
    user-select: none;
    color: #0073aa;
  }

  .heritagepress-admin .hp-section-toggle:hover {
    color: #005177;
  }

  .heritagepress-admin .hp-section-content {
    margin-top: 15px;
  }

  .heritagepress-admin .hp-form-actions {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
  }

  .heritagepress-admin .hp-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
  }

  .heritagepress-admin .hp-modal-content {
    background-color: #fefefe;
    margin: 10% auto;
    padding: 0;
    border: 1px solid #888;
    border-radius: 4px;
    width: 80%;
    max-width: 600px;
  }

  .heritagepress-admin .hp-modal-header {
    padding: 15px 20px;
    background-color: #f1f1f1;
    border-bottom: 1px solid #ddd;
    position: relative;
  }

  .heritagepress-admin .hp-modal-header h3 {
    margin: 0;
  }

  .heritagepress-admin .hp-modal-close {
    position: absolute;
    right: 15px;
    top: 15px;
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
  }

  .heritagepress-admin .hp-modal-body {
    padding: 20px;
  }

  .event-type-display {
    font-weight: 600;
    color: #555;
  }

  .find-person-btn,
  .find-place-btn,
  .find-multiple-btn {
    vertical-align: top;
  }

  .description {
    color: #666;
    font-style: italic;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Toggle more details section
    window.toggleMoreSection = function() {
      const content = document.getElementById('more-details');
      const icon = document.getElementById('more-toggle-icon');

      if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.className = 'dashicons dashicons-arrow-up-alt2';
      } else {
        content.style.display = 'none';
        icon.className = 'dashicons dashicons-arrow-down-alt2';
      }
    };

    // Date validation placeholder
    window.checkDate = function(field) {
      // TODO: Implement date validation
      // For now, just basic validation
      const value = field.value.trim();
      if (value && !/^[0-9]{1,2}\s+[A-Z]{3}\s+[0-9]{4}$/i.test(value)) {
        // Allow more flexible date formats for now
        console.log('Date format suggestion: DD MMM YYYY (e.g., 15 JAN 1950)');
      }
    };

    // Find person/family placeholder
    window.findPerson = function() {
      alert('<?php _e('Person/Family finder will be implemented in a future version.', 'heritagepress'); ?>');
    };

    // Find place placeholder
    window.findPlace = function() {
      alert('<?php _e('Place finder will be implemented in a future version.', 'heritagepress'); ?>');
    };

    // Find multiple placeholder
    window.findMultiple = function() {
      alert('<?php _e('Multiple ID finder will be implemented in a future version.', 'heritagepress'); ?>');
    };

    // Cancel form
    window.cancelForm = function() {
      if (confirm('<?php _e('Are you sure you want to cancel? Any unsaved changes will be lost.', 'heritagepress'); ?>')) {
        window.history.back();
      }
    };

    // Close modals
    window.closeFindPersonModal = function() {
      $('#find-person-modal').hide();
    };

    window.closeFindPlaceModal = function() {
      $('#find-place-modal').hide();
    };

    // Form submission handling
    $('#event-form').on('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);
      const isEdit = formData.get('action') === 'update_event';

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
              '<?php _e('Event updated successfully!', 'heritagepress'); ?>' :
              '<?php _e('Event created successfully!', 'heritagepress'); ?>'
            );
            // Redirect or reload as needed
            if (!isEdit) {
              window.location.href = '<?php echo admin_url('admin.php?page=hp-event-management&tab=browse'); ?>';
            }
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

    // Load events if on browse tab
    <?php if ($current_tab === 'browse'): ?>
      loadEventsList();

      function loadEventsList() {
        $('#events-list').html('<p class="loading"><?php _e('Loading events...', 'heritagepress'); ?></p>');

        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_get_events_list',
            nonce: '<?php echo wp_create_nonce('hp_event_nonce'); ?>'
          },
          success: function(response) {
            if (response.success) {
              displayEventsList(response.data.events);
            } else {
              $('#events-list').html('<p class="error"><?php _e('Failed to load events.', 'heritagepress'); ?></p>');
            }
          },
          error: function() {
            $('#events-list').html('<p class="error"><?php _e('Network error loading events.', 'heritagepress'); ?></p>');
          }
        });
      }

      function displayEventsList(events) {
        if (!events || events.length === 0) {
          $('#events-list').html('<p><?php _e('No events found.', 'heritagepress'); ?></p>');
          return;
        }

        let html = '<table class="wp-list-table widefat fixed striped">';
        html += '<thead><tr>';
        html += '<th><?php _e('Event Type', 'heritagepress'); ?></th>';
        html += '<th><?php _e('Person/Family', 'heritagepress'); ?></th>';
        html += '<th><?php _e('Date', 'heritagepress'); ?></th>';
        html += '<th><?php _e('Place', 'heritagepress'); ?></th>';
        html += '<th><?php _e('Actions', 'heritagepress'); ?></th>';
        html += '</tr></thead><tbody>';

        events.forEach(function(event) {
          html += '<tr>';
          html += '<td>' + (event.display || '') + '</td>';
          html += '<td>' + (event.persfamID || '') + '</td>';
          html += '<td>' + (event.eventdate || '') + '</td>';
          html += '<td>' + (event.eventplace || '') + '</td>';
          html += '<td>';
          html += '<a href="<?php echo admin_url('admin.php?page=hp-event-management&tab=edit'); ?>&eventID=' + event.eventID + '" class="button button-small"><?php _e('Edit', 'heritagepress'); ?></a> ';
          html += '<button type="button" class="button button-small button-link-delete" onclick="deleteEvent(' + event.eventID + ')"><?php _e('Delete', 'heritagepress'); ?></button>';
          html += '</td>';
          html += '</tr>';
        });

        html += '</tbody></table>';
        $('#events-list').html(html);
      }

      window.deleteEvent = function(eventID) {
        if (!confirm('<?php _e('Are you sure you want to delete this event?', 'heritagepress'); ?>')) {
          return;
        }

        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_delete_event',
            nonce: '<?php echo wp_create_nonce('hp_event_nonce'); ?>',
            eventID: eventID
          },
          success: function(response) {
            if (response.success) {
              alert('<?php _e('Event deleted successfully!', 'heritagepress'); ?>');
              loadEventsList(); // Reload the list
            } else {
              alert('<?php _e('Error:', 'heritagepress'); ?> ' + (response.data || '<?php _e('Failed to delete event.', 'heritagepress'); ?>'));
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
