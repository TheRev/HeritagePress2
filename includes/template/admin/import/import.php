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

// Display admin notices for import actions
if (class_exists('HP_Import_Controller')) {
  $import_controller = new HP_Import_Controller();
  $import_controller->display_notices();
}

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

<style>
  /* Ensure critical styles are loaded */
  .form-card {
    background: #fff;
    border: 1px solid #e8eaed;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    overflow: hidden;
  }

  .form-card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-bottom: 1px solid #e8eaed;
    padding: 25px 30px;
    position: relative;
  }

  .form-card-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #0073aa 0%, #005a87 50%, #0073aa 100%);
  }

  .header-content {
    display: flex;
    align-items: center;
    gap: 15px;
  }

  .header-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 115, 170, 0.3);
  }

  .header-icon .dashicons {
    color: #fff;
    font-size: 24px;
  }

  .form-card-body {
    padding: 30px;
  }

  .selection-button-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 35px;
  }

  .selection-btn {
    width: 100%;
    min-height: 140px;
    background: #ffffff;
    border: 2px solid #e8eaed;
    border-radius: 12px;
    padding: 0;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  }

  .selection-btn:hover {
    border-color: #0073aa;
    background: #f8fbfd;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 115, 170, 0.15);
    align-items: center;
    text-align: center;
    height: 100%;
  }

  .btn-icon {
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    border-radius: 10px;
  }

  .computer-btn .btn-icon {
    background: linear-gradient(135deg, #00a32a 0%, #008a20 100%);
  }

  .server-btn .btn-icon {
    background: linear-gradient(135deg, #135e96 0%, #0f4c7c 100%);
  }

  .btn-icon .dashicons {
    color: #fff;
    font-size: 22px;
  }

  .btn-content h4 {
    margin: 0 0 8px 0;
    font-size: 18px;
    font-weight: 600;
    color: #1d2327;
  }

  .btn-content p {
    margin: 0;
    font-size: 14px;
    color: #646970;
  }
</style>

<!-- Import Tab Content (Based on admin_dataimport.php) -->
<div class="import-section">
  <div class="section-header">
    <h2 class="section-title"><?php _e('Import GEDCOM Data', 'heritagepress'); ?></h2>
    <p class="section-description"><?php _e('Add or replace genealogy data in your HeritagePress database from GEDCOM files', 'heritagepress'); ?></p>
  </div>
  <form action="<?php echo admin_url('admin.php?page=heritagepress-import'); ?>" method="post" name="form1" enctype="multipart/form-data" id="gedcom-import-form">
    <?php wp_nonce_field('hp_import_gedcom', '_wpnonce'); ?>
    <input type="hidden" name="action" value="import_gedcom" /><!-- File Selection Card -->
    <div class="form-card file-selection-card">
      <div class="form-card-header">
        <div class="header-content">
          <div class="header-icon">
            <span class="dashicons dashicons-media-document"></span>
          </div>
          <div class="header-text">
            <h3 class="form-card-title"><?php _e('Select GEDCOM File', 'heritagepress'); ?></h3>
            <p class="form-card-subtitle"><?php _e('Choose your genealogy data file for import', 'heritagepress'); ?></p>
          </div>
        </div>
      </div>

      <div class="form-card-body">
        <!-- Elegant Two Button File Selection -->
        <div class="file-selection-container">
          <div class="selection-intro">
            <p><?php _e('Select the source of your GEDCOM file:', 'heritagepress'); ?></p>
          </div>

          <div class="selection-button-group">
            <!-- From Computer Button -->
            <div class="selection-option">
              <button type="button" id="computer-button" class="selection-btn computer-btn" aria-describedby="computer-desc">
                <div class="btn-background-pattern"></div>
                <div class="btn-content-wrapper">
                  <div class="btn-icon">
                    <span class="dashicons dashicons-desktop"></span>
                  </div>
                  <div class="btn-content">
                    <h4><?php _e('From Computer', 'heritagepress'); ?></h4>
                    <p id="computer-desc"><?php _e('Browse and upload from your device', 'heritagepress'); ?></p>
                  </div>
                  <div class="btn-arrow">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                  </div>
                </div>
              </button>
              <input type="file" name="gedcom_file" id="gedcom-file-input" accept=".ged,.gedcom" style="display: none;">
            </div>

            <!-- From Server Button -->
            <div class="selection-option">
              <button type="button" id="server-button" class="selection-btn server-btn" aria-describedby="server-desc">
                <div class="btn-background-pattern"></div>
                <div class="btn-content-wrapper">
                  <div class="btn-icon">
                    <span class="dashicons dashicons-cloud"></span>
                  </div>
                  <div class="btn-content">
                    <h4><?php _e('From Server', 'heritagepress'); ?></h4>
                    <p id="server-desc"><?php _e('Choose from previously uploaded files', 'heritagepress'); ?></p>
                  </div>
                  <div class="btn-arrow">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                  </div>
                </div>
              </button>
            </div>
          </div>

          <!-- File Requirements with Better Design -->
          <div class="file-requirements">
            <div class="requirements-header">
              <span class="dashicons dashicons-info"></span>
              <span><?php _e('File Requirements', 'heritagepress'); ?></span>
            </div>
            <div class="requirements-grid">
              <div class="requirement-item">
                <div class="requirement-icon">
                  <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="requirement-text">
                  <strong><?php _e('Size:', 'heritagepress'); ?></strong>
                  <?php printf(__('Up to %dMB', 'heritagepress'), 500); ?>
                </div>
              </div>
              <div class="requirement-item">
                <div class="requirement-icon">
                  <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="requirement-text">
                  <strong><?php _e('Format:', 'heritagepress'); ?></strong>
                  <?php _e('.ged, .gedcom', 'heritagepress'); ?>
                </div>
              </div>
              <div class="requirement-item">
                <div class="requirement-icon">
                  <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="requirement-text">
                  <strong><?php _e('Upload:', 'heritagepress'); ?></strong>
                  <?php _e('Chunked for large files', 'heritagepress'); ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Selected File Display -->
        <div id="selected-file-display" class="selected-file-display" style="display: none;">
          <div class="file-info-card">
            <div class="file-icon-large">
              <span class="dashicons dashicons-media-document"></span>
            </div>
            <div class="file-details">
              <h4 id="file-name" class="file-name"></h4>
              <div id="file-stats" class="file-stats"></div>
              <div class="file-status">
                <span class="dashicons dashicons-yes-alt"></span>
                <span id="file-status-text"><?php _e('Ready for import', 'heritagepress'); ?></span>
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

        <!-- Server File Selection Modal (Hidden by default) -->
        <div id="server-file-modal" class="server-modal" style="display: none;">
          <div class="modal-content">
            <div class="modal-header">
              <h3><?php _e('Select Server File', 'heritagepress'); ?></h3>
              <button type="button" id="close-modal" class="modal-close">
                <span class="dashicons dashicons-no-alt"></span>
              </button>
            </div>
            <div class="modal-body">
              <div class="server-file-selector">
                <label for="server-file-select"><?php _e('Available GEDCOM Files:', 'heritagepress'); ?></label>
                <div class="server-controls">
                  <select name="server_file" id="server-file-select" class="server-file-select">
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
                    <span class="dashicons dashicons-update-alt"></span>
                    <?php _e('Refresh', 'heritagepress'); ?>
                  </button>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" id="select-server-file" class="button button-primary" disabled>
                <?php _e('Select File', 'heritagepress'); ?>
              </button>
              <button type="button" id="cancel-server-selection" class="button button-secondary">
                <?php _e('Cancel', 'heritagepress'); ?>
              </button>
            </div>
          </div>
        </div>

        <!-- Add Tree Modal -->
        <div id="add-tree-modal" class="server-modal" style="display: none;">
          <div class="modal-content">
            <div class="modal-header">
              <h3><?php _e('Add New Tree', 'heritagepress'); ?></h3>
              <button type="button" id="close-add-tree-modal" class="modal-close">
                <span class="dashicons dashicons-no-alt"></span>
              </button>
            </div>
            <div class="modal-body">
              <form id="add-tree-form">
                <div class="form-field">
                  <label for="new-tree-id"><?php _e('Tree ID:', 'heritagepress'); ?> <span class="required">*</span></label>
                  <input type="text" id="new-tree-id" name="tree_id" required
                    pattern="[a-zA-Z0-9]+"
                    title="<?php _e('Tree ID must be alphanumeric (letters and numbers only)', 'heritagepress'); ?>"
                    placeholder="<?php _e('e.g., smith2024', 'heritagepress'); ?>" />
                  <p class="description"><?php _e('Unique identifier for this tree (alphanumeric only)', 'heritagepress'); ?></p>
                </div>
                <div class="form-field">
                  <label for="new-tree-name"><?php _e('Tree Name:', 'heritagepress'); ?> <span class="required">*</span></label>
                  <input type="text" id="new-tree-name" name="tree_name" required
                    placeholder="<?php _e('e.g., Smith Family Tree', 'heritagepress'); ?>" />
                  <p class="description"><?php _e('Display name for this tree', 'heritagepress'); ?></p>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" id="create-tree-btn" class="button button-primary">
                <?php _e('Create Tree', 'heritagepress'); ?>
              </button>
              <button type="button" id="cancel-add-tree" class="button button-secondary">
                <?php _e('Cancel', 'heritagepress'); ?>
              </button>
            </div>
          </div>
        </div>

        <input type="hidden" id="uploaded-file-path" name="uploaded_file_path">
        <input type="hidden" id="selected-upload-method" name="upload_method" value="">
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
              <input type="button" name="newtree" value="<?php _e('Add New Tree', 'heritagepress'); ?>" class="button button-secondary" onclick="if(typeof openAddTreeModal === 'function'){ openAddTreeModal(); } else { alert('Function not loaded yet. Please try again.'); console.error('openAddTreeModal function not found'); }" />
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

<script>
  // Ensure basic button functionality works
  document.addEventListener('DOMContentLoaded', function() {
    // From Computer button functionality
    var computerBtn = document.getElementById('computer-button');
    var fileInput = document.getElementById('gedcom-file-input');
    var uploadMethodInput = document.getElementById('selected-upload-method');

    if (computerBtn && fileInput) {
      computerBtn.addEventListener('click', function() {
        fileInput.click();
        if (uploadMethodInput) {
          uploadMethodInput.value = 'computer';
        }
      });
    }

    // From Server button functionality
    var serverBtn = document.getElementById('server-button');
    var serverModal = document.getElementById('server-file-modal');

    if (serverBtn && serverModal) {
      serverBtn.addEventListener('click', function() {
        serverModal.style.display = 'flex';
        if (uploadMethodInput) {
          uploadMethodInput.value = 'server';
        }
      });
    }

    // Close modal functionality
    var closeButtons = document.querySelectorAll('#close-modal, #cancel-server-selection');
    closeButtons.forEach(function(btn) {
      btn.addEventListener('click', function() {
        if (serverModal) {
          serverModal.style.display = 'none';
        }
      });
    });

    // File input change handler
    if (fileInput) {
      fileInput.addEventListener('change', function(e) {
        var file = e.target.files[0];
        if (file) {
          var fileName = document.getElementById('file-name');
          var fileStats = document.getElementById('file-stats');
          var fileDisplay = document.getElementById('selected-file-display');
          var uploadedPath = document.getElementById('uploaded-file-path');

          if (fileName) fileName.textContent = file.name;
          if (fileStats) {
            var sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            fileStats.innerHTML = '<span class="file-size">' + sizeMB + ' MB</span> • <span class="file-source">Computer file</span>';
          }
          if (fileDisplay) fileDisplay.style.display = 'block';
          if (uploadedPath) uploadedPath.value = file.name;
        }
      });
    }
  });

  // Fallback function for Add New Tree modal
  if (typeof openAddTreeModal === 'undefined') {
    console.log("Defining fallback openAddTreeModal function");
    window.openAddTreeModal = function() {
      console.log("Fallback openAddTreeModal called");
      var modal = document.getElementById('add-tree-modal');
      if (modal) {
        modal.style.display = 'flex';
        // Focus on first input
        setTimeout(function() {
          var firstInput = document.getElementById('new-tree-id');
          if (firstInput) firstInput.focus();
        }, 100);
      } else {
        alert('Modal element not found. Please refresh the page and try again.');
      }
    };
    window.closeAddTreeModal = function() {
      var modal = document.getElementById('add-tree-modal');
      if (modal) {
        modal.style.display = 'none';
      }
    };

    // Add event listeners for modal close buttons
    document.addEventListener('DOMContentLoaded', function() {
      var closeBtn = document.getElementById('close-add-tree-modal');
      var cancelBtn = document.getElementById('cancel-add-tree');

      if (closeBtn) {
        closeBtn.addEventListener('click', function() {
          window.closeAddTreeModal();
        });
      }

      if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
          window.closeAddTreeModal();
        });
      }

      // Close on overlay click
      var modal = document.getElementById('add-tree-modal');
      if (modal) {
        modal.addEventListener('click', function(e) {
          if (e.target === modal) {
            window.closeAddTreeModal();
          }
        });
      }
    });
  }
</script>
