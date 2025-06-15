<?php

/**
 * Import Admin View
 * GEDCOM data import interface (Based on TNG admin_dataimport.php)
 */

if (!defined('ABSPATH')) {
  exit;
}

// Check if we're viewing a specific import job status
if (isset($_GET['job_id']) && !empty($_GET['job_id'])) {
  include_once 'import-status.php';
  return;
}

global $wpdb;

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, ARRAY_A);

// Get branches
$branches_table = $wpdb->prefix . 'hp_branches';

// Import configuration
$import_config = array(
  'gedpath' => 'gedcom',
  'saveconfig' => '',
  'saveimport' => 1,
  'rrnum' => 100,
  'readmsecs' => 750,
  'defimpopt' => 0,
  'chdate' => 0,
  'livingreqbirth' => 0,
  'maxlivingage' => 110,
  'maxprivyrs' => '',
  'maxdecdyrs' => '',
  'maxmarriedage' => 0,
  'privnote' => '',
  'coerce' => 0
);

$max_file_size = wp_max_upload_size();
$max_file_size_mb = round($max_file_size / 1024 / 1024, 1);
?>

<!-- Import Tab Content (Based on admin_dataimport.php) -->
<div class="import-section">
  <div class="section-header">
    <h2 class="section-title"><?php _e('Import GEDCOM Data', 'heritagepress'); ?></h2>
    <p class="section-description"><?php _e('Add or replace genealogy data in your HeritagePress database from GEDCOM files', 'heritagepress'); ?></p>
  </div>

  <form action="<?php echo admin_url('admin.php?page=heritagepress-import&tab=import'); ?>" method="post" name="form1" enctype="multipart/form-data" id="gedcom-import-form">
    <?php wp_nonce_field('heritagepress_import', '_wpnonce'); ?>
    <input type="hidden" name="action" value="import_gedcom" />

    <!-- File Selection Card -->
    <div class="form-card file-selection-card">
      <div class="form-card-header">
        <h3 class="form-card-title">
          <span class="dashicons dashicons-groups"></span>
          <?php _e('Select GEDCOM File', 'heritagepress'); ?>
        </h3>
        <p class="form-card-subtitle"><?php _e('Choose your genealogy data file for import - supports files up to 500MB with chunked upload', 'heritagepress'); ?></p>
      </div>

      <div class="form-card-body">
        <!-- Upload Method Selection -->
        <div class="upload-method-tabs" role="tablist" aria-label="<?php esc_attr_e('Upload Method Selection', 'heritagepress'); ?>">
          <div class="upload-area-header">
            <h4><?php _e('Upload GEDCOM File', 'heritagepress'); ?></h4>
          </div>

          <div class="method-tab-buttons">
            <button type="button" class="method-tab-button active" data-method="computer"
              role="tab" aria-selected="true" aria-controls="computer-upload-tab"
              id="computer-tab-button" tabindex="0">
              <span class="dashicons dashicons-upload" aria-hidden="true"></span>
              <span class="tab-label"><?php _e('Upload from Computer', 'heritagepress'); ?></span>
            </button> <button type="button" class="method-tab-button" data-method="server"
              role="tab" aria-selected="false" aria-controls="server-upload-tab"
              id="server-tab-button" tabindex="-1">
              <span class="dashicons dashicons-database" aria-hidden="true"></span>
              <span class="tab-label"><?php _e('Select from Server', 'heritagepress'); ?></span>
            </button>
          </div>

          <!-- Hidden radio inputs for form submission -->
          <input type="radio" name="upload_method" value="computer" id="method-computer" checked style="display: none;" aria-hidden="true">
          <input type="radio" name="upload_method" value="server" id="method-server" style="display: none;" aria-hidden="true">
        </div> <!-- Computer Upload Tab Content -->
        <div class="upload-tab-content" id="computer-upload-tab" role="tabpanel"
          aria-labelledby="computer-tab-button" tabindex="0">
          <div class="upload-section">
            <div class="file-input-section">
              <div class="file-input-wrapper">
                <input type="file" name="gedcom_file" id="gedcom-file-input" accept=".ged,.gedcom"
                  class="file-input" aria-describedby="upload-requirements" style="display: none;">
                <div class="selected-file-info" id="selected-file-info" style="display: none;">
                  <span class="dashicons dashicons-media-document" aria-hidden="true"></span>
                  <span class="file-name" id="selected-file-name"></span>
                  <span class="file-size" id="selected-file-size"></span>
                </div>
              </div>

              <div class="upload-requirements" id="upload-requirements">
                <div class="requirement-item">
                  <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                  <?php printf(__('Maximum file size: %dMB', 'heritagepress'), 500); ?>
                </div>
                <div class="requirement-item">
                  <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                  <?php _e('Supported formats: .ged, .gedcom', 'heritagepress'); ?>
                </div>
                <div class="requirement-item">
                  <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                  <?php _e('Chunked upload for large files', 'heritagepress'); ?>
                </div>
              </div>
            </div>

            <!-- Upload Progress -->
            <div id="upload-progress" class="upload-progress" style="display: none;">
              <div class="progress-header">
                <h4 class="progress-title">
                  <span class="dashicons dashicons-upload"></span>
                  <?php _e('Uploading GEDCOM file...', 'heritagepress'); ?>
                </h4>
                <button type="button" id="cancel-upload" class="button button-link-delete">
                  <span class="dashicons dashicons-dismiss"></span>
                  <?php _e('Cancel', 'heritagepress'); ?>
                </button>
              </div>
              <div class="progress-container">
                <div class="progress-bar">
                  <div id="upload-progress-bar" class="progress-fill"></div>
                </div>
                <div class="progress-stats">
                  <span id="upload-progress-text" class="progress-percentage">0%</span>
                  <span id="upload-speed" class="progress-speed"><?php _e('Calculating...', 'heritagepress'); ?></span>
                </div>
              </div>
            </div>

            <!-- Selected File Info -->
            <div id="selected-file-info" class="selected-file-display" style="display: none;">
              <div class="file-info-card">
                <div class="file-icon-large">
                  <span class="dashicons dashicons-media-document"></span>
                </div>
                <div class="file-details">
                  <h4 id="file-name" class="file-name"></h4>
                  <div id="file-stats" class="file-stats"></div>
                  <div class="file-status">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Ready for import', 'heritagepress'); ?>
                  </div>
                </div>
                <div class="file-actions">
                  <button type="button" id="remove-file" class="button button-link-delete">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Remove', 'heritagepress'); ?>
                  </button>
                </div>
              </div>
            </div>

            <input type="hidden" id="uploaded-file-path" name="uploaded_file_path">
          </div>
        </div> <!-- Server File Selection Tab Content -->
        <div class="upload-tab-content" id="server-upload-tab" style="display: none;"
          role="tabpanel" aria-labelledby="server-tab-button" tabindex="0">
          <div class="upload-section">
            <div class="upload-area-header">
              <h4><?php _e('Select Server File', 'heritagepress'); ?></h4>
              <p class="upload-area-description"><?php _e('Choose from previously uploaded GEDCOM files on the server', 'heritagepress'); ?></p>
            </div>

            <div class="server-file-selector">
              <div class="server-file-dropdown">
                <label for="server-file-select" class="server-file-label">
                  <span class="dashicons dashicons-portfolio" aria-hidden="true"></span>
                  <?php _e('Available GEDCOM Files', 'heritagepress'); ?>
                </label>
                <div class="server-file-controls"> <select name="server_file" id="server-file-select" class="server-file-select"
                    aria-describedby="server-file-help">
                    <option value=""><?php _e('Select a file from server...', 'heritagepress'); ?></option>
                    <?php
                    // Get server files
                    $upload_dir = wp_upload_dir();
                    $gedcom_dir = $upload_dir['basedir'] . '/heritagepress/gedcom/';
                    if (is_dir($gedcom_dir)) {
                      $files = glob($gedcom_dir . '*.{ged,gedcom}', GLOB_BRACE);
                      if (empty($files)) {
                        echo '<option value="" disabled>' . __('No GEDCOM files found on server', 'heritagepress') . '</option>';
                      } else {
                        foreach ($files as $file) {
                          $filename = basename($file);
                          $size = filesize($file);
                          $size_mb = number_format($size / 1024 / 1024, 2);
                          $modified = date('M j, Y H:i', filemtime($file));
                          echo '<option value="' . esc_attr($filename) . '" data-size="' . $size . '">' .
                            esc_html($filename) . ' (' . $size_mb . ' MB - ' . $modified . ')</option>';
                        }
                      }
                    } else {
                      echo '<option value="" disabled>' . __('Server directory not accessible', 'heritagepress') . '</option>';
                    }
                    ?>
                  </select>
                  <button type="button" id="refresh-server-files" class="button button-secondary">
                    <span class="dashicons dashicons-update-alt" aria-hidden="true"></span>
                    <?php _e('Refresh List', 'heritagepress'); ?>
                  </button>
                </div>
                <div id="server-file-help" class="sr-only">
                  <?php _e('Select a GEDCOM file that has been previously uploaded to the server. Files are listed with their size and last modified date.', 'heritagepress'); ?>
                </div>
              </div>

              <!-- Server File Info Display -->
              <div id="server-file-info" class="selected-file-display" style="display: none;">
                <div class="file-info-card server-file-card">
                  <div class="file-icon-large">
                    <span class="dashicons dashicons-database"></span>
                  </div>
                  <div class="file-details">
                    <h4 id="server-file-name" class="file-name"></h4>
                    <div id="server-file-stats" class="file-stats"></div>
                    <div class="file-status">
                      <span class="dashicons dashicons-yes-alt"></span>
                      <?php _e('Server file selected', 'heritagepress'); ?>
                    </div>
                  </div>
                  <div class="file-source">
                    <span class="dashicons dashicons-admin-site"></span>
                    <?php _e('From Server', 'heritagepress'); ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Event Import Options Card -->
    <div class="form-card">
      <div class="form-card-header">
        <h3 class="form-card-title"><?php _e('Event Import Options', 'heritagepress'); ?></h3>
      </div>
      <div class="form-card-body">
        <div class="option-group">
          <div class="option-item">
            <input type="checkbox" name="allevents" id="allevents" value="yes" onclick="if(document.form1.allevents.checked && document.form1.eventsonly.checked) {document.form1.eventsonly.checked ='';toggleSections(false)}" />
            <label for="allevents"><?php _e('Accept data for all new event types and attributes', 'heritagepress'); ?></label>
          </div>
          <div class="option-item">
            <input type="checkbox" name="eventsonly" id="eventsonly" value="yes" onclick="toggleSections(this.checked);" />
            <label for="eventsonly"><?php _e('Import custom event types and attributes only (no data is added, replaced or appended)', 'heritagepress'); ?></label>
          </div>
        </div>
      </div>
    </div>

    <!-- Tree Selection Card -->
    <div class="form-card" id="desttree">
      <div class="form-card-header">
        <h3 class="form-card-title"><?php _e('Destination Tree', 'heritagepress'); ?></h3>
      </div>
      <div class="form-card-body">
        <table class="hp-form-table">
          <tr id="desttree2">
            <td><?php _e('Select tree', 'heritagepress'); ?>:</td>
            <td>
              <select name="tree1" id="tree1" onchange="getBranches(this,this.selectedIndex);">
                <?php if (count($trees_result) != 1): ?>
                  <option value=""><?php _e('Select a tree...', 'heritagepress'); ?></option>
                <?php endif; ?>
                <?php foreach ($trees_result as $tree): ?>
                  <option value="<?php echo esc_attr($tree['gedcom']); ?>"><?php echo esc_html($tree['treename']); ?></option>
                <?php endforeach; ?>
              </select>
              <input type="button" name="newtree" value="<?php _e('Add New Tree', 'heritagepress'); ?>" class="button button-secondary" onclick="alert('Add new tree functionality not yet implemented');" />
            </td>
          </tr>
          <tr id="destbranch" style="display:none">
            <td><?php _e('Select branch', 'heritagepress'); ?>:</td>
            <td>
              <div id="branch1div">
                <select name="branch1" id="branch1">
                  <option value=""><?php _e('All branches', 'heritagepress'); ?></option>
                </select>
              </div>
            </td>
          </tr>
        </table>
      </div>
    </div> <!-- Import Options Card -->
    <div class="form-card" id="replace">
      <div class="form-card-header">
        <h3 class="form-card-title"><?php _e('Import Options', 'heritagepress'); ?></h3>
      </div>

      <div class="form-card-body">
        <div class="radio-group">
          <div class="radio-option <?php echo ($import_config['defimpopt'] == 1) ? 'selected' : ''; ?>">
            <input type="radio" name="del" id="del_yes" value="yes" <?php if ($import_config['defimpopt'] == 1) echo " checked=\"checked\""; ?> onclick="document.form1.norecalc.checked = false; toggleNorecalcdiv(0); toggleAppenddiv(0);" />
            <div class="radio-option-content">
              <div class="radio-option-title"><?php _e('Replace all current data in tree', 'heritagepress'); ?></div>
              <div class="radio-option-description"><?php _e('This will completely replace all existing data in the selected tree', 'heritagepress'); ?></div>
            </div>
          </div>

          <div class="radio-option <?php echo (!$import_config['defimpopt']) ? 'selected' : ''; ?>">
            <input type="radio" name="del" id="del_match" value="match" <?php if (!$import_config['defimpopt']) echo " checked=\"checked\""; ?> onclick="toggleNorecalcdiv(1); toggleAppenddiv(0);" />
            <div class="radio-option-content">
              <div class="radio-option-title"><?php _e('Replace matching data only', 'heritagepress'); ?></div>
              <div class="radio-option-description"><?php _e('Only replace records that have matching IDs in both the file and database', 'heritagepress'); ?></div>
            </div>
          </div>

          <div class="radio-option <?php echo ($import_config['defimpopt'] == 2) ? 'selected' : ''; ?>">
            <input type="radio" name="del" id="del_no" value="no" <?php if ($import_config['defimpopt'] == 2) echo " checked=\"checked\""; ?> onclick="document.form1.norecalc.checked = false; toggleNorecalcdiv(0); toggleAppenddiv(0);" />
            <div class="radio-option-content">
              <div class="radio-option-title"><?php _e('Do not replace, ignore matching data', 'heritagepress'); ?></div>
              <div class="radio-option-description"><?php _e('Skip any records that already exist in the database', 'heritagepress'); ?></div>
            </div>
          </div>

          <div class="radio-option <?php echo ($import_config['defimpopt'] == 3) ? 'selected' : ''; ?>">
            <input type="radio" name="del" id="del_append" value="append" <?php if ($import_config['defimpopt'] == 3) echo " checked=\"checked\""; ?> onclick="document.form1.norecalc.checked = false; toggleNorecalcdiv(0); toggleAppenddiv(1);" />
            <div class="radio-option-content">
              <div class="radio-option-title"><?php _e('Append all data (add ID offset)', 'heritagepress'); ?></div>
              <div class="radio-option-description"><?php _e('Import all records with new IDs, adding an offset to avoid conflicts', 'heritagepress'); ?></div>
            </div>
          </div>
        </div>
        <div class="import-options-info">
          <div class="info-box info-box-yellow" style="margin: 20px 0; padding: 12px 16px; background: #fff9c4; border: 1px solid #e6db74; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #333;">
              <strong><?php _e('Import Behavior Notes:', 'heritagepress'); ?></strong><br>
              <?php _e('"Replace all current data in tree" includes people, families, sources, and notes. Media associations are preserved when record IDs remain unchanged. "Replace matching data only" is always based on IDs only. New records are always added regardless of the selected option. "Append all data" imports all records with new IDs to avoid conflicts.', 'heritagepress'); ?>
            </p>
          </div>
        </div>

        <div style="display: flex; gap: 40px; margin-top: 20px;">
          <div style="flex: 1;">
            <h4 style="margin-bottom: 10px; font-weight: 600;"><?php _e('Additional Options', 'heritagepress'); ?></h4>
            <div class="option-group" style="flex-direction: column; align-items: flex-start;">
              <div class="option-item">
                <input type="checkbox" name="ucaselast" id="ucaselast" value="1" />
                <label for="ucaselast"><?php _e('Upper case all surnames', 'heritagepress'); ?></label>
              </div>
              <div class="option-item">
                <input type="checkbox" name="norecalcliving" id="norecalcliving" value="1" />
                <label for="norecalcliving"><?php _e('Do not recalculate Living flag', 'heritagepress'); ?></label>
              </div>
              <div class="option-item">
                <input type="checkbox" name="neweronly" id="neweronly" value="1" />
                <label for="neweronly"><?php _e('Replace only if newer', 'heritagepress'); ?></label>
              </div>
              <div class="option-item">
                <input type="checkbox" name="importmedia" id="importmedia" value="1" />
                <label for="importmedia"><?php _e('Import media links', 'heritagepress'); ?></label>
              </div>
              <div class="option-item">
                <input type="checkbox" name="importlatlong" id="importlatlong" value="1" />
                <label for="importlatlong"><?php _e('Import latitude / longitude data if present', 'heritagepress'); ?></label>
              </div>
            </div>

            <!-- Import Warnings -->
            <div class="import-warning-box" style="margin-top: 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-left: 4px solid #dc3545; padding: 15px; border-radius: 4px;">
              <p style="margin: 0 0 10px 0; font-weight: 600; color: #721c24;"><span class="dashicons dashicons-warning" style="color: #dc3545; margin-right: 8px;"></span><?php _e('Important Import Guidelines:', 'heritagepress'); ?></p>
              <ul style="margin: 0; padding-left: 20px; color: #495057; line-height: 1.5;">
                <li><?php _e('Stop and backup before importing large files', 'heritagepress'); ?></li>
                <li><?php _e('Check import settings before importing', 'heritagepress'); ?></li>
                <li><?php _e('Large imports may take several minutes', 'heritagepress'); ?></li>
              </ul>
            </div>
          </div>

          <div style="flex: 1;">
            <div id="appenddiv" <?php if ($import_config['defimpopt'] != 3) echo " style=\"display:none;\""; ?>>
              <h4 style="margin-bottom: 10px; font-weight: 600;"><?php _e('ID Offset Settings', 'heritagepress'); ?></h4>
              <div class="option-group" style="flex-direction: column; align-items: flex-start;">
                <div class="option-item">
                  <input type="radio" name="offsetchoice" id="offset_auto" value="auto" checked />
                  <label for="offset_auto"><?php _e('Calculate ID offset automatically', 'heritagepress'); ?></label>
                </div>
                <div class="option-item">
                  <input type="radio" name="offsetchoice" id="offset_user" value="user" />
                  <label for="offset_user"><?php _e('Use this ID offset:', 'heritagepress'); ?></label>
                  <input type="text" name="useroffset" style="width: 100px; margin-left: 8px;" maxlength="9" />
                </div>
              </div>
            </div>
          </div>
        </div>
        <div style="display: flex; justify-content: flex-end; align-items: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e1e1e1;">
          <input type="submit" name="submit" class="button button-primary" value="<?php _e('Import Data', 'heritagepress'); ?>" />
        </div>
      </div>
    </div>
  </form>

  <!-- Recent Import Jobs -->
  <div class="form-card">
    <div class="form-card-header">
      <h3 class="form-card-title"><?php _e('Recent Import Jobs', 'heritagepress'); ?></h3>
      <p class="form-card-subtitle"><?php _e('Track the status of your recent GEDCOM imports', 'heritagepress'); ?></p>
    </div>

    <div class="recent-imports">
      <?php
      // Get recent import jobs for current user
      $user_id = get_current_user_id();
      $recent_jobs = array();

      // Check if import jobs table exists
      $table_name = $wpdb->prefix . 'hp_import_jobs';
      $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

      if ($table_exists) {
        $recent_jobs = $wpdb->get_results($wpdb->prepare(
          "SELECT * FROM {$wpdb->prefix}hp_import_jobs
           WHERE user_id = %d
           ORDER BY created_at DESC
           LIMIT 10",
          $user_id
        ));
      }

      if (empty($recent_jobs)): ?>
        <div class="no-imports">
          <p><?php _e('No recent import jobs found.', 'heritagepress'); ?></p>
        </div>
      <?php else: ?>
        <div class="imports-table-container">
          <table class="imports-table">
            <thead>
              <tr>
                <th><?php _e('Job ID', 'heritagepress'); ?></th>
                <th><?php _e('File', 'heritagepress'); ?></th>
                <th><?php _e('Status', 'heritagepress'); ?></th>
                <th><?php _e('Progress', 'heritagepress'); ?></th>
                <th><?php _e('Started', 'heritagepress'); ?></th>
                <th><?php _e('Actions', 'heritagepress'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_jobs as $job): ?>
                <tr>
                  <td>
                    <code class="job-id"><?php echo esc_html(substr($job->job_id, 0, 8)); ?>...</code>
                  </td>
                  <td>
                    <span class="file-name" title="<?php echo esc_attr($job->file_path); ?>">
                      <?php echo esc_html(basename($job->file_path)); ?>
                    </span>
                  </td>
                  <td>
                    <span class="status-badge status-<?php echo esc_attr($job->status); ?>">
                      <?php echo esc_html(ucfirst($job->status)); ?>
                    </span>
                  </td>
                  <td>
                    <div class="mini-progress">
                      <div class="mini-progress-bar">
                        <div class="mini-progress-fill" style="width: <?php echo esc_attr($job->progress); ?>%"></div>
                      </div>
                      <span class="mini-progress-text"><?php echo esc_html($job->progress); ?>%</span>
                    </div>
                  </td>
                  <td>
                    <time datetime="<?php echo esc_attr($job->created_at); ?>">
                      <?php echo esc_html(mysql2date('M j, g:i a', $job->created_at)); ?>
                    </time>
                  </td>
                  <td>
                    <a href="<?php echo admin_url('admin.php?page=heritagepress-import&tab=import&job_id=' . urlencode($job->job_id)); ?>"
                      class="button button-small">
                      <?php _e('View', 'heritagepress'); ?>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <iframe id="results" height="0" width="0" frameborder="0" name="results" onload="iframeLoaded();" style="display:none;"></iframe>
</div>
