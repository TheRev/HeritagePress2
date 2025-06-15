<?php

/**
 * GEDCOM Import - Process Import Tab
 *
 * Final step of GEDCOM import process - execute the import and show progress
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get import session data
$upload_data = isset($_SESSION['hp_gedcom_upload']) ? $_SESSION['hp_gedcom_upload'] : null;
$validation_results = isset($_SESSION['hp_gedcom_validation']) ? $_SESSION['hp_gedcom_validation'] : null;
$config_data = isset($_SESSION['hp_gedcom_config']) ? $_SESSION['hp_gedcom_config'] : null;
$people_data = isset($_SESSION['hp_gedcom_people']) ? $_SESSION['hp_gedcom_people'] : null;
$media_data = isset($_SESSION['hp_gedcom_media']) ? $_SESSION['hp_gedcom_media'] : null;
$places_data = isset($_SESSION['hp_gedcom_places']) ? $_SESSION['hp_gedcom_places'] : null;
$file_path = isset($upload_data['file_path']) ? $upload_data['file_path'] : '';

// If no places data, redirect to places tab
if (!$places_data && !isset($_GET['skip'])) {
  echo '<div class="error-box">';
  echo '<p>' . __('Places settings have not been configured. Please return to the Places Settings tab.', 'heritagepress') . '</p>';
  echo '<p><a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=places" class="button">' . __('Go to Places Settings', 'heritagepress') . '</a></p>';
  echo '</div>';
  return;
}

// Check if file exists
if (!file_exists($file_path)) {
  echo '<div class="error-box">';
  echo '<p>' . __('The GEDCOM file could not be found. Please start the import process again.', 'heritagepress') . '</p>';
  echo '<p><a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=upload" class="button">' . __('Go to Upload', 'heritagepress') . '</a></p>';
  echo '</div>';
  return;
}

// Program information
$program = isset($validation_results['program']) ? $validation_results['program'] : array('name' => __('Unknown', 'heritagepress'), 'version' => '');
$program_name = $program['name'];
$program_version = $program['version'];

// Import statistics
$stats = isset($validation_results['stats']) ? $validation_results['stats'] : array();
$individual_count = isset($stats['individuals']) ? intval($stats['individuals']) : 0;
$family_count = isset($stats['families']) ? intval($stats['families']) : 0;
$source_count = isset($stats['sources']) ? intval($stats['sources']) : 0;
$media_count = isset($stats['media']) ? intval($stats['media']) : 0;

// Tree information
$tree_id = isset($upload_data['tree_id']) ? $upload_data['tree_id'] : '';
$tree_name = isset($upload_data['tree_name']) ? $upload_data['tree_name'] : '';

// Check if import is in progress
$import_in_progress = isset($_GET['import_started']) && $_GET['import_started'] === '1';
$import_key = isset($_GET['import_key']) ? $_GET['import_key'] : '';
$import_completed = isset($_GET['import_completed']) && $_GET['import_completed'] === '1';

// Check for import results
$import_results = get_transient('hp_gedcom_import_results_' . $import_key);
?>

<h2><?php _e('Process GEDCOM Import', 'heritagepress'); ?></h2>

<?php if ($import_completed && $import_results): ?>
  <div class="success-box">
    <h3><?php _e('Import Completed Successfully!', 'heritagepress'); ?></h3>
    <p><?php _e('Your GEDCOM file has been imported into the HeritagePress database.', 'heritagepress'); ?></p>

    <div class="import-summary">
      <h4><?php _e('Import Summary', 'heritagepress'); ?></h4>

      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th><?php _e('Record Type', 'heritagepress'); ?></th>
            <th><?php _e('Imported', 'heritagepress'); ?></th>
            <th><?php _e('Updated', 'heritagepress'); ?></th>
            <th><?php _e('Skipped', 'heritagepress'); ?></th>
            <th><?php _e('Errors', 'heritagepress'); ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?php _e('Individuals', 'heritagepress'); ?></td>
            <td><?php echo isset($import_results['individuals']['imported']) ? number_format($import_results['individuals']['imported']) : 0; ?></td>
            <td><?php echo isset($import_results['individuals']['updated']) ? number_format($import_results['individuals']['updated']) : 0; ?></td>
            <td><?php echo isset($import_results['individuals']['skipped']) ? number_format($import_results['individuals']['skipped']) : 0; ?></td>
            <td><?php echo isset($import_results['individuals']['errors']) ? number_format($import_results['individuals']['errors']) : 0; ?></td>
          </tr>
          <tr>
            <td><?php _e('Families', 'heritagepress'); ?></td>
            <td><?php echo isset($import_results['families']['imported']) ? number_format($import_results['families']['imported']) : 0; ?></td>
            <td><?php echo isset($import_results['families']['updated']) ? number_format($import_results['families']['updated']) : 0; ?></td>
            <td><?php echo isset($import_results['families']['skipped']) ? number_format($import_results['families']['skipped']) : 0; ?></td>
            <td><?php echo isset($import_results['families']['errors']) ? number_format($import_results['families']['errors']) : 0; ?></td>
          </tr>
          <tr>
            <td><?php _e('Sources', 'heritagepress'); ?></td>
            <td><?php echo isset($import_results['sources']['imported']) ? number_format($import_results['sources']['imported']) : 0; ?></td>
            <td><?php echo isset($import_results['sources']['updated']) ? number_format($import_results['sources']['updated']) : 0; ?></td>
            <td><?php echo isset($import_results['sources']['skipped']) ? number_format($import_results['sources']['skipped']) : 0; ?></td>
            <td><?php echo isset($import_results['sources']['errors']) ? number_format($import_results['sources']['errors']) : 0; ?></td>
          </tr>
          <tr>
            <td><?php _e('Media', 'heritagepress'); ?></td>
            <td><?php echo isset($import_results['media']['imported']) ? number_format($import_results['media']['imported']) : 0; ?></td>
            <td><?php echo isset($import_results['media']['updated']) ? number_format($import_results['media']['updated']) : 0; ?></td>
            <td><?php echo isset($import_results['media']['skipped']) ? number_format($import_results['media']['skipped']) : 0; ?></td>
            <td><?php echo isset($import_results['media']['errors']) ? number_format($import_results['media']['errors']) : 0; ?></td>
          </tr>
          <tr>
            <td><?php _e('Places', 'heritagepress'); ?></td>
            <td><?php echo isset($import_results['places']['imported']) ? number_format($import_results['places']['imported']) : 0; ?></td>
            <td><?php echo isset($import_results['places']['updated']) ? number_format($import_results['places']['updated']) : 0; ?></td>
            <td><?php echo isset($import_results['places']['skipped']) ? number_format($import_results['places']['skipped']) : 0; ?></td>
            <td><?php echo isset($import_results['places']['errors']) ? number_format($import_results['places']['errors']) : 0; ?></td>
          </tr>
          <tr>
            <td><?php _e('Notes', 'heritagepress'); ?></td>
            <td><?php echo isset($import_results['notes']['imported']) ? number_format($import_results['notes']['imported']) : 0; ?></td>
            <td><?php echo isset($import_results['notes']['updated']) ? number_format($import_results['notes']['updated']) : 0; ?></td>
            <td><?php echo isset($import_results['notes']['skipped']) ? number_format($import_results['notes']['skipped']) : 0; ?></td>
            <td><?php echo isset($import_results['notes']['errors']) ? number_format($import_results['notes']['errors']) : 0; ?></td>
          </tr>
          <tr>
            <td><?php _e('Repositories', 'heritagepress'); ?></td>
            <td><?php echo isset($import_results['repositories']['imported']) ? number_format($import_results['repositories']['imported']) : 0; ?></td>
            <td><?php echo isset($import_results['repositories']['updated']) ? number_format($import_results['repositories']['updated']) : 0; ?></td>
            <td><?php echo isset($import_results['repositories']['skipped']) ? number_format($import_results['repositories']['skipped']) : 0; ?></td>
            <td><?php echo isset($import_results['repositories']['errors']) ? number_format($import_results['repositories']['errors']) : 0; ?></td>
          </tr>
        </tbody>
      </table>
    </div>

    <?php if (!empty($import_results['errors'])): ?>
      <div class="import-errors-section">
        <h4><?php _e('Import Errors', 'heritagepress'); ?></h4>
        <p><?php _e('The following errors were encountered during import:', 'heritagepress'); ?></p>
        <div class="import-errors">
          <ul>
            <?php foreach ($import_results['errors'] as $error): ?>
              <li><?php echo esc_html($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($import_results['warnings'])): ?>
      <div class="import-warnings-section">
        <h4><?php _e('Import Warnings', 'heritagepress'); ?></h4>
        <p><?php _e('The following warnings were generated during import:', 'heritagepress'); ?></p>
        <div class="import-warnings">
          <ul>
            <?php foreach ($import_results['warnings'] as $warning): ?>
              <li><?php echo esc_html($warning); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <div class="post-import-actions">
      <h4><?php _e('Next Steps', 'heritagepress'); ?></h4>
      <div class="button-row">
        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress&section=trees&action=view&tree=' . urlencode($tree_id))); ?>" class="button button-primary">
          <?php _e('View Family Tree', 'heritagepress'); ?>
        </a>

        <?php if (isset($import_results['media']) && ($import_results['media']['errors'] > 0 || $import_results['media']['skipped'] > 0)): ?>
          <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress&section=media&tree=' . urlencode($tree_id))); ?>" class="button">
            <?php _e('Manage Media Files', 'heritagepress'); ?>
          </a>
        <?php endif; ?>

        <a href="<?php echo esc_url(admin_url('admin.php?page=heritagepress&section=import-export')); ?>" class="button">
          <?php _e('Return to Import/Export', 'heritagepress'); ?>
        </a>
      </div>
    </div>
  </div>

<?php elseif ($import_in_progress): ?>
  <div class="import-progress-container">
    <h3><?php _e('Import in Progress', 'heritagepress'); ?></h3>
    <p><?php _e('Your GEDCOM file is being imported. This may take several minutes depending on the size of your file.', 'heritagepress'); ?></p>

    <div class="progress-container">
      <div class="progress-bar">
        <div id="progress-fill" class="progress-fill" style="width: 0%;">0%</div>
      </div>
      <div id="progress-status"><?php _e('Initializing import...', 'heritagepress'); ?></div>
    </div>

    <div class="import-details">
      <div id="current-operation"><?php _e('Preparing import process...', 'heritagepress'); ?></div>
      <div id="records-processed">
        <?php _e('Records processed:', 'heritagepress'); ?> <span id="processed-count">0</span> / <span id="total-count"><?php echo number_format($individual_count + $family_count + $source_count + $media_count); ?></span>
      </div>
    </div>

    <div id="import-log-container">
      <h4><?php _e('Import Log', 'heritagepress'); ?></h4>
      <div id="import-log"></div>
    </div>

    <div class="import-warning">
      <p><strong><?php _e('Please do not leave this page until the import is complete.', 'heritagepress'); ?></strong></p>
    </div>
  </div>

  <script>
    jQuery(document).ready(function($) {
      var importKey = '<?php echo esc_js($import_key); ?>';
      var pollInterval;
      var retryCount = 0;
      var maxRetries = 5;

      // Start polling for import progress
      pollImportProgress();

      function pollImportProgress() {
        pollInterval = setInterval(function() {
          $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
              action: 'hp_check_import_progress',
              import_key: importKey,
              nonce: '<?php echo wp_create_nonce('hp_check_import_progress'); ?>'
            },
            success: function(response) {
              if (response.success) {
                updateImportProgress(response.data);
                retryCount = 0;

                // If import is complete, redirect to results page
                if (response.data.complete) {
                  clearInterval(pollInterval);
                  window.location.href = '<?php echo esc_url(admin_url('admin.php?page=heritagepress&section=import-export&tab=gedcom-import&step=process&import_completed=1&import_key=')); ?>' + importKey;
                }
              } else {
                // Handle error
                retryCount++;
                addToLog('Error checking import progress: ' + (response.data || 'Unknown error'));

                if (retryCount >= maxRetries) {
                  clearInterval(pollInterval);
                  addToLog('Too many errors. Please check the import status in the database management section.');
                }
              }
            },
            error: function() {
              retryCount++;
              addToLog('Error connecting to server. Retrying...');

              if (retryCount >= maxRetries) {
                clearInterval(pollInterval);
                addToLog('Connection failed. The import may still be processing in the background. Please check the database management section.');
              }
            }
          });
        }, 3000); // Poll every 3 seconds
      }

      function updateImportProgress(data) {
        // Update progress bar
        var percent = data.percent || 0;
        $('#progress-fill').css('width', percent + '%').text(percent + '%');

        // Update status message
        $('#progress-status').text(data.status || '');
        $('#current-operation').text(data.current_operation || '');

        // Update counts
        $('#processed-count').text(data.processed_count ? data.processed_count.toLocaleString() : '0');

        // Add log entries
        if (data.log_entries && data.log_entries.length > 0) {
          data.log_entries.forEach(function(entry) {
            addToLog(entry);
          });
        }
      }

      function addToLog(message) {
        var time = new Date().toLocaleTimeString();
        $('#import-log').prepend('<div class="log-entry"><span class="log-time">[' + time + ']</span> ' + message + '</div>');

        // Keep log at a reasonable size
        if ($('#import-log .log-entry').length > 100) {
          $('#import-log .log-entry').slice(100).remove();
        }
      }
    });
  </script>

<?php else: ?>
  <div class="message-box">
    <p><?php _e('You are about to import your GEDCOM file into the HeritagePress database. Please review the import summary and click "Begin Import" when ready.', 'heritagepress'); ?></p>
  </div>

  <div class="import-summary">
    <h3><?php _e('Import Summary', 'heritagepress'); ?></h3>

    <table class="wp-list-table widefat fixed striped import-summary-table">
      <tbody>
        <tr>
          <th><?php _e('GEDCOM File', 'heritagepress'); ?></th>
          <td><?php echo esc_html(basename($file_path)); ?></td>
        </tr>
        <tr>
          <th><?php _e('File Size', 'heritagepress'); ?></th>
          <td><?php echo size_format(filesize($file_path)); ?></td>
        </tr>
        <tr>
          <th><?php _e('Source Program', 'heritagepress'); ?></th>
          <td><?php echo esc_html($program_name . ($program_version ? ' ' . $program_version : '')); ?></td>
        </tr>
        <tr>
          <th><?php _e('Tree ID', 'heritagepress'); ?></th>
          <td><?php echo esc_html($tree_id); ?></td>
        </tr>
        <tr>
          <th><?php _e('Tree Name', 'heritagepress'); ?></th>
          <td><?php echo esc_html($tree_name); ?></td>
        </tr>
      </tbody>
    </table>

    <h4><?php _e('Records to Import', 'heritagepress'); ?></h4>

    <div class="import-statistics">
      <div class="stat-box">
        <div class="stat-count"><?php echo number_format($individual_count); ?></div>
        <div class="stat-label"><?php _e('Individuals', 'heritagepress'); ?></div>
      </div>

      <div class="stat-box">
        <div class="stat-count"><?php echo number_format($family_count); ?></div>
        <div class="stat-label"><?php _e('Families', 'heritagepress'); ?></div>
      </div>

      <div class="stat-box">
        <div class="stat-count"><?php echo number_format($source_count); ?></div>
        <div class="stat-label"><?php _e('Sources', 'heritagepress'); ?></div>
      </div>

      <div class="stat-box">
        <div class="stat-count"><?php echo number_format($media_count); ?></div>
        <div class="stat-label"><?php _e('Media', 'heritagepress'); ?></div>
      </div>

      <?php
      $note_count = isset($stats['notes']) ? intval($stats['notes']) : 0;
      $repo_count = isset($stats['repositories']) ? intval($stats['repositories']) : 0;
      $place_count = isset($stats['places']) ? intval($stats['places']) : 0;

      if ($note_count > 0): ?>
        <div class="stat-box">
          <div class="stat-count"><?php echo number_format($note_count); ?></div>
          <div class="stat-label"><?php _e('Notes', 'heritagepress'); ?></div>
        </div>
      <?php endif; ?>

      <?php if ($repo_count > 0): ?>
        <div class="stat-box">
          <div class="stat-count"><?php echo number_format($repo_count); ?></div>
          <div class="stat-label"><?php _e('Repositories', 'heritagepress'); ?></div>
        </div>
      <?php endif; ?>

      <?php if ($place_count > 0): ?>
        <div class="stat-box">
          <div class="stat-count"><?php echo number_format($place_count); ?></div>
          <div class="stat-label"><?php _e('Places', 'heritagepress'); ?></div>
        </div>
      <?php endif; ?>
    </div>

    <h4><?php _e('Import Options', 'heritagepress'); ?></h4>
    <div class="import-options-summary">
      <div class="options-group">
        <h5><?php _e('General', 'heritagepress'); ?></h5>
        <ul>
          <li><strong><?php _e('Import Type:', 'heritagepress'); ?></strong>
            <?php
            $import_type = isset($config_data['import_type']) ? $config_data['import_type'] : 'all';
            $type_labels = array(
              'all' => __('All Records', 'heritagepress'),
              'update' => __('Update Existing Only', 'heritagepress'),
              'missing' => __('Add Missing Only', 'heritagepress')
            );
            echo isset($type_labels[$import_type]) ? $type_labels[$import_type] : $import_type;
            ?>
          </li>
          <?php if (isset($config_data['apply_privacy']) && $config_data['apply_privacy']): ?>
            <li><strong><?php _e('Privacy:', 'heritagepress'); ?></strong>
              <?php _e('Apply privacy rules to living people', 'heritagepress'); ?>
            </li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="options-group">
        <h5><?php _e('Media', 'heritagepress'); ?></h5>
        <ul>
          <li><strong><?php _e('Media Import:', 'heritagepress'); ?></strong>
            <?php
            $media_import = isset($media_data['import_media']) ? $media_data['import_media'] : 'all';
            $media_labels = array(
              'all' => __('Import all media files', 'heritagepress'),
              'links' => __('Import links only', 'heritagepress'),
              'none' => __('Do not import media', 'heritagepress')
            );
            echo isset($media_labels[$media_import]) ? $media_labels[$media_import] : $media_import;
            ?>
          </li>
        </ul>
      </div>

      <div class="options-group">
        <h5><?php _e('Places', 'heritagepress'); ?></h5>
        <ul>
          <li><strong><?php _e('Place Handling:', 'heritagepress'); ?></strong>
            <?php
            $place_handling = isset($places_data['place_handling']) ? $places_data['place_handling'] : 'exact';
            $place_labels = array(
              'exact' => __('Import places exactly as in GEDCOM', 'heritagepress'),
              'standardize' => __('Standardize place names', 'heritagepress'),
              'prioritize_existing' => __('Prioritize existing places', 'heritagepress')
            );
            echo isset($place_labels[$place_handling]) ? $place_labels[$place_handling] : $place_handling;
            ?>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <div class="warning-box">
    <p><?php _e('Warning: This process may take several minutes for large GEDCOM files. Please do not close your browser or navigate away from this page during the import.', 'heritagepress'); ?></p>
  </div>

  <form method="post" id="gedcom-process-form" class="hp-form">
    <?php wp_nonce_field('heritagepress_gedcom_import', 'gedcom_import_nonce'); ?>
    <input type="hidden" name="action" value="hp_process_gedcom_import">
    <input type="hidden" name="file_path" value="<?php echo esc_attr($file_path); ?>">

    <div class="hp-form-actions">
      <?php submit_button(__('Begin Import', 'heritagepress'), 'primary large', 'start_import', false); ?>
      &nbsp;<a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=places" class="button"><?php _e('Back to Places Settings', 'heritagepress'); ?></a>
    </div>
  </form>

  <script>
    jQuery(document).ready(function($) {
      // Form submission
      $('#gedcom-process-form').on('submit', function() {
        // Show overlay
        $('<div class="loading-overlay"><span class="spinner is-active"></span> <?php _e('Starting import process...', 'heritagepress'); ?></div>').appendTo('body');

        // Submit the form but handle the response with AJAX
        var formData = $(this).serialize();

        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: formData,
          success: function(response) {
            if (response.success) {
              // Redirect to the import progress page
              window.location.href = '?page=heritagepress&section=import-export&tab=gedcom-import&step=process&import_started=1&import_key=' + response.data.import_key;
            } else {
              // Show error
              $('.loading-overlay').remove();
              alert('<?php _e('Error starting import:', 'heritagepress'); ?> ' + (response.data || '<?php _e('Unknown error', 'heritagepress'); ?>'));
            }
          },
          error: function() {
            // Show error
            $('.loading-overlay').remove();
            alert('<?php _e('Error connecting to server. Please try again.', 'heritagepress'); ?>');
          }
        });

        return false; // Prevent standard form submission
      });
    });
  </script>
<?php endif; ?>

<style>
  .import-summary-table th {
    width: 200px;
  }

  .import-options-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    margin-bottom: 30px;
  }

  .options-group {
    flex: 1;
    min-width: 250px;
    background: #f8f8f8;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
  }

  .options-group h5 {
    margin-top: 0;
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 1px solid #ddd;
  }

  .options-group ul {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .options-group ul li {
    margin-bottom: 8px;
  }

  .progress-container {
    margin: 20px 0;
  }

  .import-details {
    margin-bottom: 20px;
    font-size: 14px;
  }

  #current-operation {
    margin-bottom: 8px;
    font-weight: bold;
  }

  #import-log-container {
    margin-top: 30px;
  }

  #import-log {
    height: 200px;
    overflow-y: auto;
    font-family: monospace;
    font-size: 12px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 10px;
  }

  .log-entry {
    margin-bottom: 3px;
  }

  .log-time {
    color: #646970;
  }

  .import-errors-section,
  .import-warnings-section {
    margin-top: 20px;
    margin-bottom: 20px;
  }

  .import-errors,
  .import-warnings {
    max-height: 200px;
    overflow-y: auto;
    background: #f9f9f9;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
  }

  .import-errors ul,
  .import-warnings ul {
    margin: 0;
    padding: 0 0 0 20px;
  }

  .post-import-actions {
    margin-top: 30px;
  }

  .button-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
  }

  .import-warning {
    margin-top: 25px;
    color: #d63638;
    text-align: center;
  }
</style>
