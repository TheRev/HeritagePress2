<?php

/**
 * Timeline Events Management Page
 *
 * @package HeritagePress
 * @subpackage Admin/Views
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

// Include the controller
require_once plugin_dir_path(__FILE__) . '../controllers/class-hp-timeline-controller.php';

$timeline_controller = new HP_Timeline_Controller();

// Handle form submissions
$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['timeline_nonce'])) {
  if (!wp_verify_nonce($_POST['timeline_nonce'], 'hp_timeline_action')) {
    wp_die(__('Security check failed', 'heritagepress'));
  }

  if (isset($_POST['action'])) {
    switch ($_POST['action']) {
      case 'delete_selected':
        if (!empty($_POST['selected_events']) && is_array($_POST['selected_events'])) {
          $deleted_count = $timeline_controller->delete_multiple_timeline_events($_POST['selected_events']);
          $message = sprintf(__('%d timeline events deleted successfully.', 'heritagepress'), $deleted_count);
        }
        break;
    }
  }
}

// Get search parameters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Get timeline events
$results = $timeline_controller->get_timeline_events(array(
  'search' => $search,
  'limit' => $per_page,
  'offset' => $offset
));

$events = $results['events'];
$total_events = $results['total'];

// Calculate pagination
$total_pages = ceil($total_events / $per_page);
$start_record = $offset + 1;
$end_record = min($offset + count($events), $total_events);

?>

<div class="wrap">
  <h1><?php _e('Timeline Events', 'heritagepress'); ?></h1>

  <?php if ($message): ?>
    <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
      <p><?php echo esc_html($message); ?></p>
    </div>
  <?php endif; ?>

  <!-- Tab Navigation -->
  <h2 class="nav-tab-wrapper">
    <a href="#search" class="nav-tab nav-tab-active" data-tab="search"><?php _e('Search Timeline Events', 'heritagepress'); ?></a>
    <a href="#add" class="nav-tab" data-tab="add"><?php _e('Add New', 'heritagepress'); ?></a>
  </h2>

  <!-- Search Tab -->
  <div id="search-tab" class="tab-content active">
    <!-- Search Form -->
    <div class="tablenav top">
      <form method="get" action="">
        <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
        <p class="search-box">
          <label class="screen-reader-text" for="timeline-search-input"><?php _e('Search Timeline Events:', 'heritagepress'); ?></label>
          <input type="search" id="timeline-search-input" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search by year, title, or detail...', 'heritagepress'); ?>">
          <input type="submit" id="search-submit" class="button" value="<?php _e('Search', 'heritagepress'); ?>">
          <?php if ($search): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $_GET['page'])); ?>" class="button"><?php _e('Reset', 'heritagepress'); ?></a>
          <?php endif; ?>
        </p>
      </form>
    </div>

    <!-- Results Info -->
    <?php if ($total_events > 0): ?>
      <div class="tablenav-pages">
        <span class="displaying-num">
          <?php printf(__('Showing %d to %d of %d timeline events', 'heritagepress'), $start_record, $end_record, $total_events); ?>
        </span>
        <?php if ($total_pages > 1): ?>
          <?php
          $page_links = paginate_links(array(
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'prev_text' => __('&laquo; Previous'),
            'next_text' => __('Next &raquo;'),
            'total' => $total_pages,
            'current' => $page
          ));
          if ($page_links) {
            echo '<span class="pagination-links">' . $page_links . '</span>';
          }
          ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Timeline Events Table -->
    <form method="post" id="timeline-events-form">
      <?php wp_nonce_field('hp_timeline_action', 'timeline_nonce'); ?>
      <input type="hidden" name="action" value="delete_selected">

      <?php if (!empty($events)): ?>
        <div class="tablenav top">
          <div class="alignleft actions bulkactions">
            <button type="button" id="select-all-events" class="button"><?php _e('Select All', 'heritagepress'); ?></button>
            <button type="button" id="clear-all-events" class="button"><?php _e('Clear All', 'heritagepress'); ?></button>
            <input type="submit" name="delete_selected_events" class="button button-secondary" value="<?php _e('Delete Selected', 'heritagepress'); ?>" onclick="return confirm('<?php _e('Are you sure you want to delete the selected timeline events?', 'heritagepress'); ?>');">
          </div>
        </div>

        <table class="wp-list-table widefat fixed striped timeline-events">
          <thead>
            <tr>
              <th scope="col" class="manage-column column-cb check-column">
                <input type="checkbox" id="cb-select-all">
              </th>
              <th scope="col" class="manage-column"><?php _e('Actions', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php _e('Start Year', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php _e('End Year', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php _e('Title', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php _e('Detail', 'heritagepress'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($events as $event): ?>
              <tr id="event-row-<?php echo esc_attr($event['tleventID']); ?>">
                <th scope="row" class="check-column">
                  <input type="checkbox" name="selected_events[]" value="<?php echo esc_attr($event['tleventID']); ?>" class="event-checkbox">
                </th>
                <td class="column-actions">
                  <div class="row-actions">
                    <span class="edit">
                      <a href="#edit" class="edit-timeline-event" data-id="<?php echo esc_attr($event['tleventID']); ?>"><?php _e('Edit', 'heritagepress'); ?></a>
                      |
                    </span>
                    <span class="delete">
                      <a href="#delete" class="delete-timeline-event" data-id="<?php echo esc_attr($event['tleventID']); ?>" data-title="<?php echo esc_attr($event['evtitle']); ?>"><?php _e('Delete', 'heritagepress'); ?></a>
                    </span>
                  </div>
                </td>
                <td><?php echo esc_html($timeline_controller->format_event_date($event, true)); ?></td>
                <td><?php echo esc_html($timeline_controller->format_event_date($event, false)); ?></td>
                <td><?php echo esc_html($event['evtitle']); ?></td>
                <td><?php echo esc_html(wp_trim_words($event['evdetail'], 20)); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Bottom pagination -->
        <?php if ($total_pages > 1): ?>
          <div class="tablenav bottom">
            <div class="tablenav-pages">
              <?php echo $page_links; ?>
            </div>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <div class="notice notice-info">
          <p><?php
              if ($search) {
                _e('No timeline events found matching your search criteria.', 'heritagepress');
              } else {
                _e('No timeline events found. <a href="#add" class="nav-tab-link" data-tab="add">Add your first timeline event</a>.', 'heritagepress');
              }
              ?></p>
        </div>
      <?php endif; ?>
    </form>
  </div>

  <!-- Add New Tab -->
  <div id="add-tab" class="tab-content">
    <h3><?php _e('Add New Timeline Event', 'heritagepress'); ?></h3>

    <form id="add-timeline-form" method="post" class="timeline-form">
      <?php wp_nonce_field('hp_timeline_nonce', 'timeline_nonce'); ?>

      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row">
              <label for="evyear"><?php _e('Start Date', 'heritagepress'); ?> <span class="required">*</span></label>
            </th>
            <td>
              <select name="evday" id="evday">
                <option value=""><?php _e('Day', 'heritagepress'); ?></option>
                <?php for ($i = 1; $i <= 31; $i++): ?>
                  <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
              </select>

              <select name="evmonth" id="evmonth">
                <option value=""><?php _e('Month', 'heritagepress'); ?></option>
                <?php foreach ($timeline_controller->get_months() as $num => $name): ?>
                  <option value="<?php echo $num; ?>"><?php echo esc_html($name); ?></option>
                <?php endforeach; ?>
              </select>

              <input type="text" name="evyear" id="evyear" size="4" maxlength="10" placeholder="<?php _e('Year', 'heritagepress'); ?>" required>
              <span class="description"><?php _e('Year is required', 'heritagepress'); ?></span>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="endyear"><?php _e('End Date', 'heritagepress'); ?></label>
            </th>
            <td>
              <select name="endday" id="endday">
                <option value=""><?php _e('Day', 'heritagepress'); ?></option>
                <?php for ($i = 1; $i <= 31; $i++): ?>
                  <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php endfor; ?>
              </select>

              <select name="endmonth" id="endmonth">
                <option value=""><?php _e('Month', 'heritagepress'); ?></option>
                <?php foreach ($timeline_controller->get_months() as $num => $name): ?>
                  <option value="<?php echo $num; ?>"><?php echo esc_html($name); ?></option>
                <?php endforeach; ?>
              </select>

              <input type="text" name="endyear" id="endyear" size="4" maxlength="10" placeholder="<?php _e('Year', 'heritagepress'); ?>">
              <span class="description"><?php _e('Optional ending date', 'heritagepress'); ?></span>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="evtitle"><?php _e('Event Title', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" name="evtitle" id="evtitle" class="regular-text" maxlength="128">
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="evdetail"><?php _e('Event Detail', 'heritagepress'); ?> <span class="required">*</span></label>
            </th>
            <td>
              <textarea name="evdetail" id="evdetail" rows="8" cols="80" required></textarea>
              <span class="description"><?php _e('Event detail is required', 'heritagepress'); ?></span>
            </td>
          </tr>
        </tbody>
      </table>

      <p class="submit">
        <input type="submit" name="add_timeline_event" class="button button-primary" value="<?php _e('Add Timeline Event', 'heritagepress'); ?>">
        <button type="button" id="reset-form" class="button"><?php _e('Reset Form', 'heritagepress'); ?></button>
      </p>
    </form>
  </div>

  <!-- Edit Modal -->
  <div id="edit-timeline-modal" class="heritagepress-modal" style="display: none;">
    <div class="modal-content">
      <div class="modal-header">
        <h3><?php _e('Edit Timeline Event', 'heritagepress'); ?></h3>
        <span class="close-modal">&times;</span>
      </div>
      <div class="modal-body">
        <form id="edit-timeline-form" class="timeline-form">
          <input type="hidden" name="timeline_id" id="edit-timeline-id">

          <table class="form-table">
            <tbody>
              <tr>
                <th scope="row">
                  <label for="edit-evyear"><?php _e('Start Date', 'heritagepress'); ?> <span class="required">*</span></label>
                </th>
                <td>
                  <select name="evday" id="edit-evday">
                    <option value=""><?php _e('Day', 'heritagepress'); ?></option>
                    <?php for ($i = 1; $i <= 31; $i++): ?>
                      <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                  </select>

                  <select name="evmonth" id="edit-evmonth">
                    <option value=""><?php _e('Month', 'heritagepress'); ?></option>
                    <?php foreach ($timeline_controller->get_months() as $num => $name): ?>
                      <option value="<?php echo $num; ?>"><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                  </select>

                  <input type="text" name="evyear" id="edit-evyear" size="4" maxlength="10" required>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="edit-endyear"><?php _e('End Date', 'heritagepress'); ?></label>
                </th>
                <td>
                  <select name="endday" id="edit-endday">
                    <option value=""><?php _e('Day', 'heritagepress'); ?></option>
                    <?php for ($i = 1; $i <= 31; $i++): ?>
                      <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                  </select>

                  <select name="endmonth" id="edit-endmonth">
                    <option value=""><?php _e('Month', 'heritagepress'); ?></option>
                    <?php foreach ($timeline_controller->get_months() as $num => $name): ?>
                      <option value="<?php echo $num; ?>"><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                  </select>

                  <input type="text" name="endyear" id="edit-endyear" size="4" maxlength="10">
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="edit-evtitle"><?php _e('Event Title', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" name="evtitle" id="edit-evtitle" class="regular-text" maxlength="128">
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="edit-evdetail"><?php _e('Event Detail', 'heritagepress'); ?> <span class="required">*</span></label>
                </th>
                <td>
                  <textarea name="evdetail" id="edit-evdetail" rows="8" cols="80" required></textarea>
                </td>
              </tr>
            </tbody>
          </table>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" id="save-timeline-event" class="button button-primary"><?php _e('Save Changes', 'heritagepress'); ?></button>
        <button type="button" class="button close-modal"><?php _e('Cancel', 'heritagepress'); ?></button>
      </div>
    </div>
  </div>
</div>

<style>
  .tab-content {
    display: none;
    margin-top: 20px;
  }

  .tab-content.active {
    display: block;
  }

  .timeline-form .required {
    color: #d63638;
  }

  .heritagepress-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
  }

  .modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #ccc;
    width: 80%;
    max-width: 600px;
    border-radius: 3px;
  }

  .modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    position: relative;
  }

  .modal-header h3 {
    margin: 0;
  }

  .close-modal {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 24px;
    cursor: pointer;
    color: #999;
  }

  .close-modal:hover {
    color: #000;
  }

  .modal-body {
    padding: 20px;
  }

  .modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    text-align: right;
  }

  .modal-footer .button {
    margin-left: 10px;
  }

  .timeline-events th,
  .timeline-events td {
    padding: 8px 10px;
  }

  .timeline-events .column-actions {
    width: 80px;
  }

  .row-actions {
    color: #ddd;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
      e.preventDefault();

      var tab = $(this).data('tab');

      // Update tab appearance
      $('.nav-tab').removeClass('nav-tab-active');
      $(this).addClass('nav-tab-active');

      // Show/hide tab content
      $('.tab-content').removeClass('active');
      $('#' + tab + '-tab').addClass('active');
    });

    // Handle nav-tab-link clicks (from within content)
    $(document).on('click', '.nav-tab-link', function(e) {
      e.preventDefault();
      var tab = $(this).data('tab');
      $('.nav-tab[data-tab="' + tab + '"]').click();
    });

    // Select all/clear all checkboxes
    $('#select-all-events').on('click', function() {
      $('.event-checkbox').prop('checked', true);
    });

    $('#clear-all-events').on('click', function() {
      $('.event-checkbox').prop('checked', false);
    });

    $('#cb-select-all').on('change', function() {
      $('.event-checkbox').prop('checked', $(this).prop('checked'));
    });

    // Reset form
    $('#reset-form').on('click', function() {
      $('#add-timeline-form')[0].reset();
    });

    // Add timeline event via AJAX
    $('#add-timeline-form').on('submit', function(e) {
      e.preventDefault();

      var formData = {
        action: 'hp_add_timeline_event',
        nonce: $('#timeline_nonce').val(),
        timeline_data: {
          evday: $('#evday').val(),
          evmonth: $('#evmonth').val(),
          evyear: $('#evyear').val(),
          endday: $('#endday').val(),
          endmonth: $('#endmonth').val(),
          endyear: $('#endyear').val(),
          evtitle: $('#evtitle').val(),
          evdetail: $('#evdetail').val()
        }
      };

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        success: function(response) {
          if (response.success) {
            alert(response.data.message);
            $('#add-timeline-form')[0].reset();
            // Switch to search tab and refresh
            $('.nav-tab[data-tab="search"]').click();
            location.reload();
          } else {
            alert('Error: ' + response.data);
          }
        },
        error: function() {
          alert('An error occurred while adding the timeline event.');
        }
      });
    });

    // Edit timeline event
    $('.edit-timeline-event').on('click', function(e) {
      e.preventDefault();

      var eventId = $(this).data('id');

      // Get event data via AJAX
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_get_timeline_event',
          nonce: $('#timeline_nonce').val(),
          timeline_id: eventId
        },
        success: function(response) {
          if (response.success) {
            var event = response.data;

            // Populate edit form
            $('#edit-timeline-id').val(event.tleventID);
            $('#edit-evday').val(event.evday == 0 ? '' : event.evday);
            $('#edit-evmonth').val(event.evmonth == 0 ? '' : event.evmonth);
            $('#edit-evyear').val(event.evyear);
            $('#edit-endday').val(event.endday == 0 ? '' : event.endday);
            $('#edit-endmonth').val(event.endmonth == 0 ? '' : event.endmonth);
            $('#edit-endyear').val(event.endyear);
            $('#edit-evtitle').val(event.evtitle);
            $('#edit-evdetail').val(event.evdetail);

            // Show modal
            $('#edit-timeline-modal').show();
          } else {
            alert('Error loading timeline event data.');
          }
        }
      });
    });

    // Save timeline event changes
    $('#save-timeline-event').on('click', function() {
      var formData = {
        action: 'hp_update_timeline_event',
        nonce: $('#timeline_nonce').val(),
        timeline_id: $('#edit-timeline-id').val(),
        timeline_data: {
          evday: $('#edit-evday').val(),
          evmonth: $('#edit-evmonth').val(),
          evyear: $('#edit-evyear').val(),
          endday: $('#edit-endday').val(),
          endmonth: $('#edit-endmonth').val(),
          endyear: $('#edit-endyear').val(),
          evtitle: $('#edit-evtitle').val(),
          evdetail: $('#edit-evdetail').val()
        }
      };

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        success: function(response) {
          if (response.success) {
            alert(response.data);
            $('#edit-timeline-modal').hide();
            location.reload();
          } else {
            alert('Error: ' + response.data);
          }
        },
        error: function() {
          alert('An error occurred while updating the timeline event.');
        }
      });
    });

    // Delete timeline event
    $('.delete-timeline-event').on('click', function(e) {
      e.preventDefault();

      var eventId = $(this).data('id');
      var title = $(this).data('title');

      if (confirm('Are you sure you want to delete the timeline event "' + title + '"?')) {
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_delete_timeline_event',
            nonce: $('#timeline_nonce').val(),
            timeline_id: eventId
          },
          success: function(response) {
            if (response.success) {
              $('#event-row-' + eventId).fadeOut(function() {
                $(this).remove();
              });
              alert(response.data);
            } else {
              alert('Error: ' + response.data);
            }
          },
          error: function() {
            alert('An error occurred while deleting the timeline event.');
          }
        });
      }
    });

    // Close modal
    $('.close-modal').on('click', function() {
      $('#edit-timeline-modal').hide();
    });

    // Close modal when clicking outside
    $(window).on('click', function(e) {
      if (e.target == $('#edit-timeline-modal')[0]) {
        $('#edit-timeline-modal').hide();
      }
    });

    // Form validation
    function validateTimelineForm(formPrefix) {
      var evyear = $('#' + formPrefix + 'evyear').val();
      var evdetail = $('#' + formPrefix + 'evdetail').val();
      var endyear = $('#' + formPrefix + 'endyear').val();
      var endmonth = $('#' + formPrefix + 'endmonth').val();
      var endday = $('#' + formPrefix + 'endday').val();
      var evday = $('#' + formPrefix + 'evday').val();
      var evmonth = $('#' + formPrefix + 'evmonth').val();

      if (!evyear) {
        alert('<?php _e('Year is required.', 'heritagepress'); ?>');
        return false;
      }

      if (!evdetail) {
        alert('<?php _e('Event detail is required.', 'heritagepress'); ?>');
        return false;
      }

      if (!endyear && (endmonth || endday)) {
        alert('<?php _e('If you enter a day or month for the ending date, you must also enter an ending year.', 'heritagepress'); ?>');
        return false;
      }

      if ((evday && !evmonth) || (endday && !endmonth)) {
        alert('<?php _e('If you select a day, you must also select a month.', 'heritagepress'); ?>');
        return false;
      }

      if (endyear && evyear && parseInt(endyear) < parseInt(evyear)) {
        alert('<?php _e('Ending year cannot be less than beginning year.', 'heritagepress'); ?>');
        return false;
      }

      return true;
    }

    // Apply validation to forms
    $('#add-timeline-form').on('submit', function(e) {
      if (!validateTimelineForm('')) {
        e.preventDefault();
      }
    });

    $('#save-timeline-event').on('click', function(e) {
      if (!validateTimelineForm('edit-')) {
        e.preventDefault();
      }
    });
  });
</script>
