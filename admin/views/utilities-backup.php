<?php

/**
 * HeritagePress Admin Backup View (Tables)
 *
 * @package    HeritagePress
 * @subpackage Admin\Views
 */

if (!defined('ABSPATH')) {
  exit;
}

// Include backup controller
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/class-hp-backup-controller.php';
$backup_controller = new HP_Backup_Controller();

// Get tables
$genealogy_tables = $backup_controller->get_genealogy_tables();

// Table display names
$table_labels = [
  'addresses' => __('Addresses', 'heritagepress'),
  'albumlinks' => __('Album Links', 'heritagepress'),
  'albums' => __('Albums', 'heritagepress'),
  'associations' => __('Associations', 'heritagepress'),
  'branches' => __('Branches', 'heritagepress'),
  'branchlinks' => __('Branch Links', 'heritagepress'),
  'cemeteries' => __('Cemeteries', 'heritagepress'),
  'children' => __('Children', 'heritagepress'),
  'citations' => __('Citations', 'heritagepress'),
  'countries' => __('Countries', 'heritagepress'),
  'dna_groups' => __('DNA Groups', 'heritagepress'),
  'dna_links' => __('DNA Links', 'heritagepress'),
  'dna_tests' => __('DNA Tests', 'heritagepress'),
  'events' => __('Events', 'heritagepress'),
  'eventtypes' => __('Event Types', 'heritagepress'),
  'families' => __('Families', 'heritagepress'),
  'image_tags' => __('Image Tags', 'heritagepress'),
  'languages' => __('Languages', 'heritagepress'),
  'media' => __('Media', 'heritagepress'),
  'medialinks' => __('Media Links', 'heritagepress'),
  'mediatypes' => __('Media Types', 'heritagepress'),
  'mostwanted' => __('Most Wanted', 'heritagepress'),
  'notelinks' => __('Note Links', 'heritagepress'),
  'people' => __('People', 'heritagepress'),
  'places' => __('Places', 'heritagepress'),
  'reports' => __('Reports', 'heritagepress'),
  'repositories' => __('Repositories', 'heritagepress'),
  'sources' => __('Sources', 'heritagepress'),
  'states' => __('States', 'heritagepress'),
  'templates' => __('Templates', 'heritagepress'),
  'timeevents' => __('Timeline Events', 'heritagepress'),
  'trees' => __('Trees', 'heritagepress'),
  'xnotes' => __('Notes', 'heritagepress'),
];

// Add nonce for AJAX operations
$backup_nonce = wp_create_nonce('hp_backup_operation');
?>

<div class="heritagepress-backup-section">
  <div class="backup-instructions">
    <p>
      <?php _e('Use the backup utility to save your genealogy data. You can back up individual tables or all tables at once.', 'heritagepress'); ?>
      <?php _e('Backups are stored in your WordPress uploads directory and can be downloaded for safekeeping.', 'heritagepress'); ?>
    </p>

    <div class="backup-options">
      <p>
        <b><?php _e('Backup Format Options:', 'heritagepress'); ?></b>
        <label>
          <input type="checkbox" id="include-sql" checked>
          <?php _e('SQL Format (for restoration)', 'heritagepress'); ?>
        </label>
        &nbsp;
        <label>
          <input type="checkbox" id="include-create" checked>
          <?php _e('Include CREATE statements', 'heritagepress'); ?>
        </label>
        &nbsp;
        <label>
          <input type="checkbox" id="include-drop" checked>
          <?php _e('Include DROP statements', 'heritagepress'); ?>
        </label>
        &nbsp;
        <a href="#" class="backup-help"><?php _e('Help', 'heritagepress'); ?></a>
      </p>

      <div class="bulk-actions">
        <button type="button" id="select-all" class="button"><?php _e('Select All', 'heritagepress'); ?></button>
        <button type="button" id="clear-all" class="button"><?php _e('Clear All', 'heritagepress'); ?></button>

        <span class="bulk-action-label"><?php _e('With selected:', 'heritagepress'); ?></span>
        <select id="bulk-action">
          <option value=""><?php _e('Choose action...', 'heritagepress'); ?></option>
          <option value="backup"><?php _e('Backup', 'heritagepress'); ?></option>
          <option value="delete"><?php _e('Delete Backups', 'heritagepress'); ?></option>
        </select>
        <button type="button" id="bulk-apply" class="button button-primary"><?php _e('Apply', 'heritagepress'); ?></button>
      </div>
    </div>
  </div>

  <table class="wp-list-table widefat fixed striped heritagepress-backup-table">
    <thead>
      <tr>
        <th class="column-actions"><?php _e('Actions', 'heritagepress'); ?></th>
        <th class="column-select"><input type="checkbox" id="select-all-tables"></th>
        <th class="column-table"><?php _e('Table', 'heritagepress'); ?></th>
        <th class="column-lastbackup"><?php _e('Last Backup', 'heritagepress'); ?></th>
        <th class="column-size"><?php _e('Backup Size', 'heritagepress'); ?></th>
        <th class="column-status"><?php _e('Status', 'heritagepress'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($genealogy_tables as $table): ?>
        <?php
        // Get backup info
        $backup_info = $backup_controller->get_backup_info($table);
        $has_backup = $backup_info['exists'];
        ?>
        <tr class="backup-table-row" data-table="<?php echo esc_attr($table); ?>">
          <td class="column-actions">
            <div class="action-buttons">
              <button type="button" class="button button-small backup-table"
                data-table="<?php echo esc_attr($table); ?>"
                title="<?php esc_attr_e('Backup', 'heritagepress'); ?>">
                <span class="dashicons dashicons-database-export"></span>
              </button>

              <button type="button" class="button button-small optimize-table"
                data-table="<?php echo esc_attr($table); ?>"
                title="<?php esc_attr_e('Optimize', 'heritagepress'); ?>">
                <span class="dashicons dashicons-update"></span>
              </button>

              <button type="button" class="button button-small restore-table <?php echo !$has_backup ? 'disabled' : ''; ?>"
                data-table="<?php echo esc_attr($table); ?>"
                title="<?php esc_attr_e('Restore', 'heritagepress'); ?>"
                <?php if (!$has_backup): ?>disabled<?php endif; ?>>
                <span class="dashicons dashicons-database-import"></span>
              </button>

              <a href="<?php echo $has_backup ? esc_url($backup_info['download_url']) : '#'; ?>"
                class="button button-small download-table <?php echo !$has_backup ? 'disabled' : ''; ?>"
                title="<?php esc_attr_e('Download', 'heritagepress'); ?>"
                <?php if (!$has_backup): ?>disabled<?php endif; ?>>
                <span class="dashicons dashicons-download"></span>
              </a>
            </div>
          </td>
          <td class="column-select">
            <input type="checkbox" name="tables[]" value="<?php echo esc_attr($table); ?>" class="table-checkbox">
          </td>
          <td class="column-table">
            <strong><?php echo isset($table_labels[$table]) ? esc_html($table_labels[$table]) : esc_html($table); ?></strong>
            <div class="table-name"><?php echo esc_html($table); ?></div>
          </td>
          <td class="column-lastbackup backup-timestamp" data-table="<?php echo esc_attr($table); ?>">
            <?php echo $has_backup ? esc_html($backup_info['timestamp']) : '—'; ?>
          </td>
          <td class="column-size backup-size" data-table="<?php echo esc_attr($table); ?>">
            <?php echo $has_backup ? esc_html($backup_info['filesize']) : '—'; ?>
          </td>
          <td class="column-status backup-status" data-table="<?php echo esc_attr($table); ?>">
            <?php if ($has_backup): ?>
              <span class="status-badge status-success"><?php _e('Backed up', 'heritagepress'); ?></span>
            <?php else: ?>
              <span class="status-badge status-none"><?php _e('No backup', 'heritagepress'); ?></span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Confirmation Modal -->
<div id="backup-modal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h3 id="modal-title"><?php _e('Confirm Operation', 'heritagepress'); ?></h3>
    <div id="modal-message"></div>
    <div class="modal-actions">
      <button type="button" id="modal-confirm" class="button button-primary"><?php _e('Confirm', 'heritagepress'); ?></button>
      <button type="button" id="modal-cancel" class="button"><?php _e('Cancel', 'heritagepress'); ?></button>
    </div>
  </div>
</div>

<!-- Help Modal -->
<div id="backup-help-modal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h3><?php _e('Backup Options Help', 'heritagepress'); ?></h3>
    <div class="help-content">
      <p><strong><?php _e('SQL Format:', 'heritagepress'); ?></strong> <?php _e('Creates a SQL file that can be used to restore your database. If unchecked, a CSV-like format is used.', 'heritagepress'); ?></p>

      <p><strong><?php _e('Include CREATE statements:', 'heritagepress'); ?></strong> <?php _e('Includes SQL commands to create the table structure. Required for restoration to a new database.', 'heritagepress'); ?></p>

      <p><strong><?php _e('Include DROP statements:', 'heritagepress'); ?></strong> <?php _e('Includes SQL commands to drop (delete) existing tables before creating them. Prevents errors during restoration.', 'heritagepress'); ?></p>

      <hr>

      <h4><?php _e('Backup Actions', 'heritagepress'); ?></h4>
      <ul>
        <li><strong><?php _e('Backup:', 'heritagepress'); ?></strong> <?php _e('Creates a backup file of the selected table.', 'heritagepress'); ?></li>
        <li><strong><?php _e('Optimize:', 'heritagepress'); ?></strong> <?php _e('Runs database optimization on the table to improve performance.', 'heritagepress'); ?></li>
        <li><strong><?php _e('Restore:', 'heritagepress'); ?></strong> <?php _e('Restores data from a backup file. This will overwrite existing data.', 'heritagepress'); ?></li>
        <li><strong><?php _e('Download:', 'heritagepress'); ?></strong> <?php _e('Downloads the backup file to your computer.', 'heritagepress'); ?></li>
      </ul>

      <p><em><?php _e('Note: Always create a full backup of your WordPress database before performing restoration operations.', 'heritagepress'); ?></em></p>
    </div>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {
    let backupNonce = '<?php echo esc_js($backup_nonce); ?>';

    // Select all tables
    $('#select-all, #select-all-tables').on('click', function() {
      $('.table-checkbox').prop('checked', $(this).prop('checked'));
      if (this.id === 'select-all') {
        $('.table-checkbox').prop('checked', true);
        $('#select-all-tables').prop('checked', true);
      }
    });

    // Clear all selections
    $('#clear-all').on('click', function() {
      $('.table-checkbox').prop('checked', false);
      $('#select-all-tables').prop('checked', false);
    });

    // Single table backup
    $('.backup-table').on('click', function() {
      let table = $(this).data('table');
      let row = $(this).closest('tr');

      // Get backup options
      let includeSql = $('#include-sql').prop('checked');
      let includeCreate = $('#include-create').prop('checked');
      let includeDrop = $('#include-drop').prop('checked');

      // Update status
      row.find('.backup-status').html('<span class="spinner is-active"></span> <?php esc_html_e('Backing up...', 'heritagepress'); ?>');

      // Perform backup via AJAX
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_backup_table',
          nonce: backupNonce,
          table: table,
          include_sql: includeSql ? 1 : 0,
          include_create: includeCreate ? 1 : 0,
          include_drop: includeDrop ? 1 : 0
        },
        success: function(response) {
          if (response.success) {
            // Update row with backup information
            row.find('.backup-timestamp').text(response.data.timestamp);
            row.find('.backup-size').text(response.data.filesize);
            row.find('.backup-status').html('<span class="status-badge status-success"><?php esc_html_e('Backed up', 'heritagepress'); ?></span>');

            // Enable restore and download buttons
            row.find('.restore-table, .download-table').removeClass('disabled').prop('disabled', false);
            row.find('.download-table').attr('href', response.data.download_url);

            // Show success notification
            showNotification('success', '<?php esc_html_e('Table backup completed successfully', 'heritagepress'); ?>');
          } else {
            // Show error message
            row.find('.backup-status').html('<span class="status-badge status-error"><?php esc_html_e('Error', 'heritagepress'); ?></span>');
            showNotification('error', response.data.message || '<?php esc_html_e('Backup failed', 'heritagepress'); ?>');
          }
        },
        error: function() {
          row.find('.backup-status').html('<span class="status-badge status-error"><?php esc_html_e('Error', 'heritagepress'); ?></span>');
          showNotification('error', '<?php esc_html_e('Server error during backup operation', 'heritagepress'); ?>');
        }
      });
    });

    // Bulk actions
    $('#bulk-apply').on('click', function() {
      let action = $('#bulk-action').val();

      if (!action) {
        showNotification('error', '<?php esc_html_e('Please select an action', 'heritagepress'); ?>');
        return;
      }

      let selectedTables = [];
      $('.table-checkbox:checked').each(function() {
        selectedTables.push($(this).val());
      });

      if (selectedTables.length === 0) {
        showNotification('error', '<?php esc_html_e('Please select at least one table', 'heritagepress'); ?>');
        return;
      }

      // Handle different bulk actions
      if (action === 'backup') {
        // Get backup options
        let includeSql = $('#include-sql').prop('checked');
        let includeCreate = $('#include-create').prop('checked');
        let includeDrop = $('#include-drop').prop('checked');

        // Show progress
        showNotification('info', '<?php esc_html_e('Starting backup of selected tables...', 'heritagepress'); ?>');

        // Perform backup via AJAX
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_backup_table',
            nonce: backupNonce,
            table: 'all',
            selected_tables: selectedTables,
            include_sql: includeSql ? 1 : 0,
            include_create: includeCreate ? 1 : 0,
            include_drop: includeDrop ? 1 : 0
          },
          success: function(response) {
            if (response.success) {
              // Update rows with backup information
              $.each(response.data.results, function(table, result) {
                if (result.success) {
                  let row = $('tr[data-table="' + table + '"]');
                  row.find('.backup-timestamp').text(result.timestamp);
                  row.find('.backup-size').text(result.filesize);
                  row.find('.backup-status').html('<span class="status-badge status-success"><?php esc_html_e('Backed up', 'heritagepress'); ?></span>');

                  // Enable restore and download buttons
                  row.find('.restore-table, .download-table').removeClass('disabled').prop('disabled', false);
                  row.find('.download-table').attr('href', result.download_url);
                }
              });

              showNotification('success', response.data.message);
            } else {
              showNotification('error', response.data.message || '<?php esc_html_e('Bulk backup operation failed', 'heritagepress'); ?>');
            }
          },
          error: function() {
            showNotification('error', '<?php esc_html_e('Server error during bulk backup operation', 'heritagepress'); ?>');
          }
        });
      } else if (action === 'delete') {
        // Show confirmation dialog
        $('#modal-title').text('<?php esc_html_e('Confirm Backup Deletion', 'heritagepress'); ?>');
        $('#modal-message').html('<?php esc_html_e('Are you sure you want to delete the backup files for the selected tables? This action cannot be undone.', 'heritagepress'); ?>');

        $('#modal-confirm').off('click').on('click', function() {
          // Close modal
          $('#backup-modal').hide();

          // Delete backups one by one
          let deleteCount = 0;
          let totalCount = selectedTables.length;

          showNotification('info', '<?php esc_html_e('Deleting backups...', 'heritagepress'); ?>');

          function deleteNextBackup(index) {
            if (index >= selectedTables.length) {
              // All done
              showNotification('success', '<?php esc_html_e('Deleted', 'heritagepress'); ?> ' + deleteCount + ' <?php esc_html_e('of', 'heritagepress'); ?> ' + totalCount + ' <?php esc_html_e('backup files', 'heritagepress'); ?>');
              return;
            }

            let table = selectedTables[index];
            let row = $('tr[data-table="' + table + '"]');

            $.ajax({
              url: ajaxurl,
              type: 'POST',
              data: {
                action: 'hp_delete_backup',
                nonce: backupNonce,
                table: table
              },
              success: function(response) {
                if (response.success) {
                  deleteCount++;

                  // Update row
                  row.find('.backup-timestamp').text('—');
                  row.find('.backup-size').text('—');
                  row.find('.backup-status').html('<span class="status-badge status-none"><?php esc_html_e('No backup', 'heritagepress'); ?></span>');

                  // Disable restore and download buttons
                  row.find('.restore-table, .download-table').addClass('disabled').prop('disabled', true);
                  row.find('.download-table').attr('href', '#');
                }

                // Process next backup
                deleteNextBackup(index + 1);
              },
              error: function() {
                // Process next backup even on error
                deleteNextBackup(index + 1);
              }
            });
          }

          // Start deletion process
          deleteNextBackup(0);
        });

        $('#modal-cancel').off('click').on('click', function() {
          $('#backup-modal').hide();
        });

        // Show modal
        $('#backup-modal').show();
      }
    });

    // Toggle SQL-specific options
    $('#include-sql').on('change', function() {
      let checked = $(this).prop('checked');
      $('#include-create').prop('disabled', !checked);
      if (!checked) {
        $('#include-drop').prop('checked', false);
        $('#include-drop').prop('disabled', true);
      } else {
        $('#include-drop').prop('disabled', false);
      }
    });

    // Toggle DROP option based on CREATE
    $('#include-create').on('change', function() {
      let checked = $(this).prop('checked');
      if (!checked) {
        $('#include-drop').prop('checked', false);
        $('#include-drop').prop('disabled', true);
      } else {
        $('#include-drop').prop('disabled', false);
      }
    });

    // Close modal on X click
    $('.close').on('click', function() {
      $(this).closest('.modal').hide();
    });

    // Show help modal
    $('.backup-help').on('click', function(e) {
      e.preventDefault();
      $('#backup-help-modal').show();
    });

    // Close modal when clicking outside
    $(window).on('click', function(event) {
      if ($(event.target).hasClass('modal')) {
        $('.modal').hide();
      }
    });

    // Helper function to show notifications
    function showNotification(type, message) {
      let noticeClass = 'notice-info';
      if (type === 'success') noticeClass = 'notice-success';
      if (type === 'error') noticeClass = 'notice-error';

      const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
      $('.heritagepress-backup-section').prepend(notice);

      // Auto dismiss after 5 seconds
      setTimeout(function() {
        notice.fadeOut(function() {
          $(this).remove();
        });
      }, 5000);
    }
  });
</script>

<style>
  .heritagepress-backup-section {
    margin: 20px 0;
  }

  .backup-instructions {
    margin-bottom: 20px;
  }

  .backup-options {
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
  }

  .bulk-actions {
    margin-top: 10px;
    display: flex;
    align-items: center;
  }

  .bulk-action-label {
    margin: 0 10px;
  }

  .heritagepress-backup-table {
    margin-top: 15px;
  }

  .column-actions {
    width: 150px;
  }

  .column-select {
    width: 30px;
  }

  .column-table {
    width: 25%;
  }

  .column-lastbackup {
    width: 25%;
  }

  .column-size {
    width: 15%;
  }

  .column-status {
    width: 15%;
  }

  .table-name {
    color: #666;
    font-size: 12px;
    margin-top: 3px;
  }

  .action-buttons {
    display: flex;
    gap: 5px;
  }

  .action-buttons .button {
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .action-buttons .dashicons {
    margin-top: 0;
  }

  .status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
  }

  .status-success {
    background-color: #dff0d8;
    color: #3c763d;
  }

  .status-error {
    background-color: #f2dede;
    color: #a94442;
  }

  .status-none {
    background-color: #f5f5f5;
    color: #666;
  }

  /* Modal Styles */
  .modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.4);
  }

  .modal-content {
    position: relative;
    background-color: #fefefe;
    margin: 10% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 50%;
    box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
    border-radius: 5px;
  }

  .close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
  }

  .close:hover,
  .close:focus {
    color: black;
    text-decoration: none;
  }

  .modal-actions {
    margin-top: 20px;
    text-align: right;
  }

  .help-content {
    max-height: 400px;
    overflow-y: auto;
  }

  /* Button disabled state */
  .button.disabled {
    pointer-events: none;
    opacity: 0.6;
  }
</style>
