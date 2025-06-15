<?php

/**
 * HeritagePress GEDCOM Import Interface
 *
 * Comprehensive GEDCOM import interface with multi-tab process workflow
 * Features GEDCOM upload, validation, configuration, and import process
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get current tab or set default
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'upload';

// Define import tabs
$import_tabs = array(
  'upload' => __('Upload GEDCOM', 'heritagepress'),
  'validate' => __('Validate', 'heritagepress'),
  'config' => __('Configure Import', 'heritagepress'),
  'people' => __('People Settings', 'heritagepress'),
  'media' => __('Media Options', 'heritagepress'),
  'places' => __('Places Settings', 'heritagepress'),
  'process' => __('Process Import', 'heritagepress'),
);

// Get relevant data variables
$max_file_size = wp_max_upload_size();
$max_file_size_mb = round($max_file_size / 1024 / 1024, 1);
$trees = hp_get_trees(); // Function to get existing family trees
?>

<div class="wrap heritagepress-admin gedcom-import">
  <h1><?php _e('GEDCOM Import', 'heritagepress'); ?></h1>

  <nav class="nav-tab-wrapper hp-nav-tabs">
    <?php foreach ($import_tabs as $tab_id => $tab_name): ?>
      <a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=<?php echo $tab_id; ?>"
        class="nav-tab <?php echo $current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
        <?php echo $tab_name; ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="hp-tab-content">
    <?php
    // Include the appropriate tab content based on current tab
    $tab_file = HERITAGEPRESS_PLUGIN_DIR . 'admin/views/gedcom/tabs/' . $current_tab . '.php';
    if (file_exists($tab_file)) {
      include $tab_file;
    } else {
      // Default to upload tab if file doesn't exist
      include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/gedcom/tabs/upload.php';
    }
    ?>
  </div>
</div>

<style>
  .heritagepress-admin.gedcom-import .hp-nav-tabs {
    margin-bottom: 20px;
  }

  .heritagepress-admin.gedcom-import .hp-tab-content {
    background: #fff;
    border: 1px solid #ccc;
    border-top: none;
    padding: 20px;
  }

  .heritagepress-admin.gedcom-import .form-table th {
    width: 250px;
  }

  .heritagepress-admin.gedcom-import .progress-container {
    margin: 20px 0;
  }

  .heritagepress-admin.gedcom-import .progress-bar {
    width: 100%;
    height: 24px;
    background-color: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 10px;
  }

  .heritagepress-admin.gedcom-import .progress-fill {
    height: 100%;
    background-color: #2271b1;
    transition: width 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: bold;
  }

  .heritagepress-admin.gedcom-import .import-statistics {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 20px;
  }

  .heritagepress-admin.gedcom-import .stat-box {
    border: 1px solid #ddd;
    padding: 15px;
    text-align: center;
    border-radius: 4px;
  }

  .heritagepress-admin.gedcom-import .stat-count {
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
  }

  .heritagepress-admin.gedcom-import .stat-label {
    color: #555;
    margin-top: 5px;
  }

  .heritagepress-admin.gedcom-import .message-box {
    background: #f8f8f8;
    border-left: 4px solid #2271b1;
    padding: 10px 15px;
    margin: 15px 0;
  }

  .heritagepress-admin.gedcom-import .error-box {
    background: #fef1f1;
    border-left: 4px solid #d63638;
    padding: 10px 15px;
    margin: 15px 0;
  }

  .heritagepress-admin.gedcom-import .warning-box {
    background: #fff8e5;
    border-left: 4px solid #f0b849;
    padding: 10px 15px;
    margin: 15px 0;
  }

  .heritagepress-admin.gedcom-import .success-box {
    background: #edfaef;
    border-left: 4px solid #00a32a;
    padding: 10px 15px;
    margin: 15px 0;
  }

  .heritagepress-admin.gedcom-import #import-log {
    height: 300px;
    overflow-y: auto;
    font-family: monospace;
    font-size: 12px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 10px;
    margin-top: 15px;
  }
</style>
