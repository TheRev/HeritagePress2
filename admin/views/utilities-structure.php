<?php

/**
 * HeritagePress Admin Structure View
 * For backing up table structure
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

// Get structure backup info
$structure_info = $backup_controller->get_backup_info('heritagepress_tablestructure');
$has_backup = $structure_info['exists'];

// Add nonce for AJAX operations
$backup_nonce = wp_create_nonce('hp_backup_operation');
?>

<div class="heritagepress-structure-section">
  <div class="structure-instructions">
    <p>
      <?php _e('This utility allows you to back up the entire structure of your genealogy database. This creates a single SQL file containing all CREATE TABLE statements.', 'heritagepress'); ?>
    </p>
    <p>
      <?php _e('A structure-only backup is useful when:', 'heritagepress'); ?>
    </p>
    <ul>
      <li><?php _e('Setting up a new HeritagePress installation with the same table structure', 'heritagepress'); ?></li>
      <li><?php _e('Preparing to migrate your data to a different server or database', 'heritagepress'); ?></li>
      <li><?php _e('Creating an empty database structure for development or testing', 'heritagepress'); ?></li>
    </ul>
  </div>

  <div class="structure-actions card">
    <h3><?php _e('Table Structure Backup', 'heritagepress'); ?></h3>

    <table class="wp-list-table widefat fixed structure-table">
      <tr>
        <td class="column-actions">
          <div class="action-buttons">
            <button type="button" id="backup-structure" class="button button-primary">
              <span class="dashicons dashicons-database-export"></span>
              <?php _e('Create Structure Backup', 'heritagepress'); ?>
            </button>

            <button type="button" id="restore-structure" class="button<?php echo !$has_backup ? ' disabled' : ''; ?>" <?php echo !$has_backup ? 'disabled' : ''; ?>>
              <span class="dashicons dashicons-database-import"></span>
              <?php _e('Restore Structure', 'heritagepress'); ?>
            </button>

            <a href="<?php echo $has_backup ? esc_url($structure_info['download_url']) : '#'; ?>"
              id="download-structure" class="button<?php echo !$has_backup ? ' disabled' : ''; ?>" <?php echo !$has_backup ? 'disabled' : ''; ?>>
              <span class="dashicons dashicons-download"></span>
              <?php _e('Download SQL File', 'heritagepress'); ?>
            </a>
          </div>
        </td>
      </tr>
    </table>

    <div class="structure-info">
      <div class="structure-status">
        <h4><?php _e('Status', 'heritagepress'); ?></h4>
        <?php if ($has_backup): ?>
          <p class="status-good"><?php _e('Structure backup is available', 'heritagepress'); ?></p>
        <?php else: ?>
          <p class="status-none"><?php _e('No structure backup available', 'heritagepress'); ?></p>
        <?php endif; ?>
      </div>

      <div class="structure-details">
        <h4><?php _e('Backup Details', 'heritagepress'); ?></h4>
        <table class="widefat structure-details-table">
          <tr>
            <th><?php _e('Filename', 'heritagepress'); ?>:</th>
            <td id="structure-filename"><?php echo $has_backup ? esc_html($structure_info['filename']) : '—'; ?></td>
          </tr>
          <tr>
            <th><?php _e('Last Backup', 'heritagepress'); ?>:</th>
            <td id="structure-timestamp"><?php echo $has_backup ? esc_html($structure_info['timestamp']) : '—'; ?></td>
          </tr>
          <tr>
            <th><?php _e('File Size', 'heritagepress'); ?>:</th>
            <td id="structure-filesize"><?php echo $has_backup ? esc_html($structure_info['filesize']) : '—'; ?></td>
          </tr>
        </table>
      </div>
    </div>

    <div class="structure-warning">
      <div class="notice notice-warning">
        <p>
          <strong><?php _e('Important:', 'heritagepress'); ?></strong>
          <?php _e('Restoring table structure will recreate all tables and may cause data loss if tables already exist. Make sure you have backed up your data before proceeding.', 'heritagepress'); ?>
        </p>
      </div>
    </div>
  </div>
</div>

<!-- Confirmation Modal -->
<div id="structure-modal" class="modal" style="display: none;">
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

<script type="text/javascript">
  jQuery(document).ready(function($) {
    let backupNonce = '<?php echo esc_js($backup_nonce); ?>';

    // Backup structure
    $('#backup-structure').on('click', function() {
      $(this).prop('disabled', true).html('<span class="spinner is-active" style="float:left;margin-top:0"></span> <?php esc_html_e('Creating Backup...', 'heritagepress'); ?>');

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_backup_structure',
          nonce: backupNonce
        },
        success: function(response) {
          $('#backup-structure').prop('disabled', false).html('<span class="dashicons dashicons-database-export"></span> <?php esc_html_e('Create Structure Backup', 'heritagepress'); ?>');

          if (response.success) {
            // Update structure info
            $('#structure-filename').text(response.data.filename);
            $('#structure-timestamp').text(response.data.timestamp);
            $('#structure-filesize').text(response.data.filesize);

            // Update status
            $('.structure-status p').removeClass('status-none').addClass('status-good').text('<?php esc_html_e('Structure backup is available', 'heritagepress'); ?>');

            // Enable restore and download buttons
            $('#restore-structure, #download-structure').removeClass('disabled').prop('disabled', false);
            $('#download-structure').attr('href', response.data.download_url);

            // Show success notification
            showNotification('success', '<?php esc_html_e('Table structure backup completed successfully', 'heritagepress'); ?>');
          } else {
            showNotification('error', response.data.message || '<?php esc_html_e('Structure backup failed', 'heritagepress'); ?>');
          }
        },
        error: function() {
          $('#backup-structure').prop('disabled', false).html('<span class="dashicons dashicons-database-export"></span> <?php esc_html_e('Create Structure Backup', 'heritagepress'); ?>');
          showNotification('error', '<?php esc_html_e('Server error during structure backup operation', 'heritagepress'); ?>');
        }
      });
    });

    // Restore structure
    $('#restore-structure').on('click', function() {
      // Show confirmation dialog
      $('#modal-title').text('<?php esc_html_e('Confirm Structure Restoration', 'heritagepress'); ?>');
      $('#modal-message').html('<p><?php esc_html_e('This will recreate all genealogy database tables. Any existing tables will be dropped and recreated, resulting in data loss if the tables contain data.', 'heritagepress'); ?></p><p><strong><?php esc_html_e('Are you sure you want to continue?', 'heritagepress'); ?></strong></p>');

      $('#modal-confirm').off('click').on('click', function() {
        // Close modal
        $('#structure-modal').hide();

        // Show restore in progress
        $('#restore-structure').prop('disabled', true).html('<span class="spinner is-active" style="float:left;margin-top:0"></span> <?php esc_html_e('Restoring...', 'heritagepress'); ?>');

        // Perform restore via AJAX
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_restore_structure',
            nonce: backupNonce
          },
          success: function(response) {
            $('#restore-structure').prop('disabled', false).html('<span class="dashicons dashicons-database-import"></span> <?php esc_html_e('Restore Structure', 'heritagepress'); ?>');

            if (response.success) {
              showNotification('success', '<?php esc_html_e('Table structure restored successfully', 'heritagepress'); ?>');
            } else {
              showNotification('error', response.data.message || '<?php esc_html_e('Structure restoration failed', 'heritagepress'); ?>');
            }
          },
          error: function() {
            $('#restore-structure').prop('disabled', false).html('<span class="dashicons dashicons-database-import"></span> <?php esc_html_e('Restore Structure', 'heritagepress'); ?>');
            showNotification('error', '<?php esc_html_e('Server error during structure restoration', 'heritagepress'); ?>');
          }
        });
      });

      $('#modal-cancel').off('click').on('click', function() {
        $('#structure-modal').hide();
      });

      // Show modal
      $('#structure-modal').show();
    });

    // Close modal on X click
    $('.close').on('click', function() {
      $(this).closest('.modal').hide();
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
      $('.heritagepress-structure-section').prepend(notice);

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
  .heritagepress-structure-section {
    margin: 20px 0;
  }

  .structure-instructions {
    margin-bottom: 20px;
  }

  .structure-actions {
    background: #fff;
    border: 1px solid #e5e5e5;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
  }

  .structure-info {
    display: flex;
    margin-top: 30px;
    margin-bottom: 30px;
  }

  .structure-status,
  .structure-details {
    flex: 1;
  }

  .structure-details-table {
    border-collapse: collapse;
  }

  .structure-details-table th {
    width: 120px;
    text-align: left;
    padding: 8px;
  }

  .structure-details-table td {
    padding: 8px;
  }

  .action-buttons {
    display: flex;
    gap: 10px;
  }

  .status-good {
    color: #46b450;
    font-weight: bold;
  }

  .status-none {
    color: #999;
    font-style: italic;
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

  /* Button disabled state */
  .button.disabled {
    pointer-events: none;
    opacity: 0.6;
  }
</style>
