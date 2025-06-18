<?php

/**
 * Import Status Template
 * Displays background import job progress and status
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get job ID from URL parameter
$job_id = isset($_GET['job_id']) ? sanitize_text_field($_GET['job_id']) : '';

if (empty($job_id)) {
  echo '<div class="notice notice-error"><p>Invalid or missing job ID.</p></div>';
  return;
}

// Get initial job status
global $wpdb;

// Check if import jobs table exists
$table_name = $wpdb->prefix . 'hp_import_jobs';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if (!$table_exists) {
  echo '<div class="notice notice-error"><p>Import jobs table not found. Please reactivate the plugin to create required database tables.</p></div>';
  return;
}

$job = $wpdb->get_row($wpdb->prepare(
  "SELECT * FROM $table_name WHERE job_id = %s",
  $job_id
));

if (!$job) {
  echo '<div class="notice notice-error"><p>Import job not found.</p></div>';
  return;
}
?>

<div class="hp-import-status-container">
  <div class="hp-status-header">
    <h2>GEDCOM Import Status</h2>
    <p class="hp-job-id">Job ID: <code><?php echo esc_html($job_id); ?></code></p>
  </div>

  <div class="hp-status-card">
    <div class="hp-status-info">
      <div class="hp-status-badge">
        <span class="hp-status-indicator hp-status-<?php echo esc_attr($job->status); ?>"></span>
        <span class="hp-status-text" id="hp-status-text"><?php echo esc_html(ucfirst($job->status)); ?></span>
      </div>

      <div class="hp-progress-container">
        <div class="hp-progress-bar">
          <div class="hp-progress-fill" id="hp-progress-fill" style="width: <?php echo esc_attr($job->progress); ?>%"></div>
        </div>
        <div class="hp-progress-text">
          <span id="hp-progress-percent"><?php echo esc_html($job->progress); ?>%</span>
          <span id="hp-progress-records">
            <?php if ($job->total_records > 0): ?>
              (<span id="hp-processed-count"><?php echo esc_html($job->processed_records); ?></span> of
              <span id="hp-total-count"><?php echo esc_html($job->total_records); ?></span> records)
            <?php endif; ?>
          </span>
        </div>
      </div>
    </div>

    <div class="hp-status-actions">
      <?php if (in_array($job->status, ['queued', 'processing'])): ?>
        <button type="button" id="hp-cancel-import" class="button button-secondary">
          Cancel Import
        </button>
      <?php endif; ?>

      <?php if ($job->status === 'completed'): ?>
        <a href="<?php echo admin_url('admin.php?page=heritagepress-import&tab=post-import'); ?>"
          class="button button-primary">
          Run Post-Import Utilities
        </a>
      <?php endif; ?>

      <a href="<?php echo admin_url('admin.php?page=heritagepress-import&tab=import'); ?>"
        class="button">
        Back to Import
      </a>
    </div>
  </div>

  <div class="hp-status-details">
    <h3>Import Details</h3>
    <div class="hp-details-grid">
      <div class="hp-detail-item">
        <strong>Started:</strong>
        <span id="hp-created-time"><?php echo esc_html(mysql2date('F j, Y g:i a', $job->created_at)); ?></span>
      </div>
      <div class="hp-detail-item">
        <strong>Last Updated:</strong>
        <span id="hp-updated-time"><?php echo esc_html(mysql2date('F j, Y g:i a', $job->updated_at)); ?></span>
      </div>
      <div class="hp-detail-item">
        <strong>File:</strong>
        <span><?php echo esc_html(basename($job->file_path)); ?></span>
      </div>
    </div>
  </div>

  <div class="hp-log-container">
    <h3>Import Log</h3>
    <div class="hp-log-content" id="hp-log-content">
      <?php echo !empty($job->log) ? nl2br(esc_html($job->log)) : '<em>No log entries yet...</em>'; ?>
    </div>
  </div>
</div>

<script type="text/javascript">
  (function($) {
    let statusPolling;
    const jobId = '<?php echo esc_js($job_id); ?>';

    function updateStatus() {
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_get_import_status',
          job_id: jobId,
          nonce: '<?php echo wp_create_nonce('heritagepress_admin_nonce'); ?>'
        },
        success: function(response) {
          if (response.success) {
            const data = response.data;

            // Update status indicator
            $('#hp-status-text').text(data.status.charAt(0).toUpperCase() + data.status.slice(1));
            $('.hp-status-indicator').removeClass().addClass('hp-status-indicator hp-status-' + data.status);

            // Update progress
            $('#hp-progress-fill').css('width', data.progress + '%');
            $('#hp-progress-percent').text(data.progress + '%');

            // Update record counts
            if (data.total_records > 0) {
              $('#hp-processed-count').text(data.processed_records);
              $('#hp-total-count').text(data.total_records);
              $('#hp-progress-records').show();
            }

            // Update timestamps
            $('#hp-updated-time').text(new Date(data.updated_at + ' UTC').toLocaleString());

            // Update log
            if (data.log) {
              $('#hp-log-content').html(data.log.replace(/\n/g, '<br>'));
              // Scroll to bottom of log
              const logContainer = $('#hp-log-content');
              logContainer.scrollTop(logContainer[0].scrollHeight);
            }

            // Handle completion states
            if (['completed', 'failed', 'cancelled'].indexOf(data.status) !== -1) {
              clearInterval(statusPolling);
              $('#hp-cancel-import').hide();

              if (data.status === 'completed') {
                $('.hp-status-actions').prepend(
                  '<a href="<?php echo admin_url('admin.php?page=heritagepress-import&tab=post-import'); ?>" ' +
                  'class="button button-primary">Run Post-Import Utilities</a> '
                );
              }
            }
          }
        },
        error: function() {
          console.error('Failed to fetch import status');
        }
      });
    }

    // Start polling every 2 seconds if import is active
    if (['queued', 'processing'].indexOf('<?php echo esc_js($job->status); ?>') !== -1) {
      statusPolling = setInterval(updateStatus, 2000);
    }

    // Cancel import handler
    $('#hp-cancel-import').on('click', function() {
      if (confirm('Are you sure you want to cancel this import?')) {
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_cancel_import',
            job_id: jobId,
            nonce: '<?php echo wp_create_nonce('heritagepress_admin_nonce'); ?>'
          },
          success: function(response) {
            if (response.success) {
              clearInterval(statusPolling);
              updateStatus(); // Get final status
            } else {
              alert('Failed to cancel import: ' + response.data);
            }
          },
          error: function() {
            alert('Failed to cancel import. Please try again.');
          }
        });
      }
    });

  })(jQuery);
</script>
