<?php

/**
 * GEDCOM Import - Validate Tab
 *
 * Second step of GEDCOM import process - validate the GEDCOM file
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get upload session data
$upload_data = isset($_SESSION['hp_gedcom_upload']) ? $_SESSION['hp_gedcom_upload'] : null;
$validation_results = isset($_SESSION['hp_gedcom_validation']) ? $_SESSION['hp_gedcom_validation'] : null;
$file_path = isset($upload_data['file_path']) ? $upload_data['file_path'] : '';

// If no upload data, redirect to upload tab
if (!$upload_data || !file_exists($file_path)) {
  echo '<div class="error-box">';
  echo '<p>' . __('No GEDCOM file has been uploaded. Please return to the Upload tab.', 'heritagepress') . '</p>';
  echo '<p><a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=upload" class="button">' . __('Go to Upload', 'heritagepress') . '</a></p>';
  echo '</div>';
  return;
}

// If validation has not been run yet
if (!$validation_results) {
  // Display file information
  echo '<div class="message-box">';
  echo '<h3>' . __('GEDCOM File Information', 'heritagepress') . '</h3>';
  echo '<p><strong>' . __('File Name:', 'heritagepress') . '</strong> ' . esc_html(basename($file_path)) . '</p>';
  echo '<p><strong>' . __('File Size:', 'heritagepress') . '</strong> ' . size_format(filesize($file_path)) . '</p>';
  echo '<p><strong>' . __('Upload Date:', 'heritagepress') . '</strong> ' . date('Y-m-d H:i:s', $upload_data['timestamp']) . '</p>';
  echo '</div>';

  // Show validation form
  echo '<form method="post" id="gedcom-validate-form" class="hp-form">';
  wp_nonce_field('heritagepress_gedcom_validate', 'gedcom_validate_nonce');
  echo '<input type="hidden" name="action" value="hp_validate_gedcom">';
  echo '<input type="hidden" name="file_path" value="' . esc_attr($file_path) . '">';

  echo '<div class="message-box">';
  echo '<p>' . __('Click the button below to validate your GEDCOM file. The system will check for GEDCOM compliance, scan for errors, and analyze the structure and content of your file.', 'heritagepress') . '</p>';
  echo '</div>';

  echo '<div class="hp-form-actions">';
  submit_button(__('Validate GEDCOM File', 'heritagepress'), 'primary', 'validate_gedcom', false);
  echo '&nbsp;<a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=upload" class="button">' . __('Back to Upload', 'heritagepress') . '</a>';
  echo '</div>';
  echo '</form>';

  // Add validation script
  echo '<script>
  jQuery(document).ready(function($) {
    $("#gedcom-validate-form").on("submit", function() {
      $("<div class=\"loading-overlay\"><span class=\"spinner is-active\"></span> ' . __('Validating GEDCOM file...', 'heritagepress') . '</div>").appendTo("body");
    });
  });
  </script>';
}
// If validation results exist
else {
  // Display validation summary
  $header = isset($validation_results['header']) ? $validation_results['header'] : array();
  $stats = isset($validation_results['stats']) ? $validation_results['stats'] : array();
  $errors = isset($validation_results['errors']) ? $validation_results['errors'] : array();
  $warnings = isset($validation_results['warnings']) ? $validation_results['warnings'] : array();
  $has_valid_structure = isset($validation_results['valid_structure']) ? $validation_results['valid_structure'] : false;
  $program_name = isset($validation_results['program']) ? $validation_results['program']['name'] : __('Unknown', 'heritagepress');
  $program_version = isset($validation_results['program']) ? $validation_results['program']['version'] : '';

  echo '<h2>' . __('GEDCOM Validation Results', 'heritagepress') . '</h2>';

  // Overall status
  if ($has_valid_structure) {
    echo '<div class="success-box">';
    echo '<p><strong>' . __('GEDCOM File Structure: Valid', 'heritagepress') . '</strong></p>';
    echo '<p>' . __('Your GEDCOM file has a valid structure and can be imported.', 'heritagepress') . '</p>';
    echo '</div>';
  } else {
    echo '<div class="error-box">';
    echo '<p><strong>' . __('GEDCOM File Structure: Invalid', 'heritagepress') . '</strong></p>';
    echo '<p>' . __('Your GEDCOM file has structural errors that may prevent a successful import.', 'heritagepress') . '</p>';
    echo '</div>';
  }

  // Source program information
  echo '<div class="message-box">';
  echo '<h3>' . __('Source Program Information', 'heritagepress') . '</h3>';
  echo '<p><strong>' . __('Program:', 'heritagepress') . '</strong> ' . esc_html($program_name) . ($program_version ? ' ' . esc_html($program_version) : '') . '</p>';

  // GEDCOM header information
  if (!empty($header)) {
    echo '<h3>' . __('GEDCOM Header', 'heritagepress') . '</h3>';
    echo '<p><strong>' . __('GEDCOM Version:', 'heritagepress') . '</strong> ' . (isset($header['version']) ? esc_html($header['version']) : __('Not specified', 'heritagepress')) . '</p>';
    echo '<p><strong>' . __('Character Set:', 'heritagepress') . '</strong> ' . (isset($header['charset']) ? esc_html($header['charset']) : __('Not specified', 'heritagepress')) . '</p>';
    echo '<p><strong>' . __('Submitter:', 'heritagepress') . '</strong> ' . (isset($header['submitter']) ? esc_html($header['submitter']) : __('Not specified', 'heritagepress')) . '</p>';
  }
  echo '</div>';

  // Statistics
  if (!empty($stats)) {
    echo '<div class="message-box">';
    echo '<h3>' . __('GEDCOM Contents', 'heritagepress') . '</h3>';
    echo '<div class="import-statistics">';

    $stat_items = array(
      'individuals' => __('Individuals', 'heritagepress'),
      'families' => __('Families', 'heritagepress'),
      'sources' => __('Sources', 'heritagepress'),
      'repositories' => __('Repositories', 'heritagepress'),
      'notes' => __('Notes', 'heritagepress'),
      'media' => __('Media Objects', 'heritagepress'),
      'places' => __('Places', 'heritagepress'),
      'events' => __('Events', 'heritagepress'),
    );

    foreach ($stat_items as $key => $label) {
      $count = isset($stats[$key]) ? intval($stats[$key]) : 0;
      echo '<div class="stat-box">';
      echo '<div class="stat-count">' . number_format($count) . '</div>';
      echo '<div class="stat-label">' . $label . '</div>';
      echo '</div>';
    }

    echo '</div>';
    echo '</div>';
  }

  // Media information
  if (isset($validation_results['media'])) {
    $media = $validation_results['media'];
    echo '<div class="message-box">';
    echo '<h3>' . __('Media Information', 'heritagepress') . '</h3>';
    echo '<p><strong>' . __('Media References:', 'heritagepress') . '</strong> ' . (isset($media['total']) ? number_format($media['total']) : '0') . '</p>';

    if (isset($media['base_path']) && $media['base_path']) {
      echo '<p><strong>' . __('Media Base Path:', 'heritagepress') . '</strong> ' . esc_html($media['base_path']) . '</p>';
    }

    echo '</div>';
  }

  // Errors
  if (!empty($errors)) {
    echo '<div class="error-box">';
    echo '<h3>' . __('Errors', 'heritagepress') . ' (' . count($errors) . ')</h3>';
    echo '<ul class="validation-list">';
    foreach ($errors as $error) {
      echo '<li>' . esc_html($error) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
  }

  // Warnings
  if (!empty($warnings)) {
    echo '<div class="warning-box">';
    echo '<h3>' . __('Warnings', 'heritagepress') . ' (' . count($warnings) . ')</h3>';
    echo '<ul class="validation-list">';
    foreach ($warnings as $warning) {
      echo '<li>' . esc_html($warning) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
  }

  // Navigation buttons
  echo '<div class="hp-form-actions">';
  echo '<a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=config" class="button button-primary">' . __('Continue to Configuration', 'heritagepress') . '</a>';
  echo '&nbsp;<a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=upload" class="button">' . __('Back to Upload', 'heritagepress') . '</a>';
  echo '</div>';
}
?>

<style>
  .validation-list {
    max-height: 200px;
    overflow-y: auto;
    background: #f9f9f9;
    padding: 10px;
    border: 1px solid #ddd;
    margin: 10px 0;
  }

  .validation-list li {
    margin-bottom: 5px;
    padding-left: 20px;
    position: relative;
  }

  .validation-list li:before {
    content: "•";
    position: absolute;
    left: 0;
    top: 0;
  }
</style>
