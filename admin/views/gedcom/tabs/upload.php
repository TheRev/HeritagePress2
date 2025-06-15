<?php

/**
 * GEDCOM Import - Upload Tab
 *
 * First step of GEDCOM import process - upload the GEDCOM file
 */

if (!defined('ABSPATH')) {
  exit;
}
?>

<h2><?php _e('Upload GEDCOM File', 'heritagepress'); ?></h2>

<form method="post" enctype="multipart/form-data" id="gedcom-upload-form" class="hp-form">
  <?php wp_nonce_field('heritagepress_gedcom_upload', 'gedcom_upload_nonce'); ?>
  <input type="hidden" name="action" value="hp_upload_gedcom">

  <div class="message-box">
    <p><?php _e('Select a GEDCOM file from your computer to begin the import process. HeritagePress supports standard GEDCOM 5.5.1 files from all major genealogy programs.', 'heritagepress'); ?></p>
  </div>

  <table class="form-table">
    <tr>
      <th scope="row">
        <label for="gedcom_file"><?php _e('GEDCOM File', 'heritagepress'); ?> <span class="required">*</span></label>
      </th>
      <td>
        <input type="file"
          name="gedcom_file"
          id="gedcom_file"
          accept=".ged,.gedcom"
          required>
        <p class="description">
          <?php printf(
            __('Select a GEDCOM (.ged) file from your computer. Maximum upload size: %s MB', 'heritagepress'),
            $max_file_size_mb
          ); ?>
        </p>
      </td>
    </tr>

    <tr>
      <th scope="row">
        <label for="tree_destination"><?php _e('Import Destination', 'heritagepress'); ?></label>
      </th>
      <td>
        <select name="tree_destination" id="tree_destination">
          <option value="new"><?php _e('Create new tree', 'heritagepress'); ?></option>
          <?php if (!empty($trees)): ?>
            <option value="existing"><?php _e('Add to existing tree', 'heritagepress'); ?></option>
          <?php endif; ?>
        </select>

        <div id="new-tree-options">
          <p class="tree-id-field">
            <label for="tree_id"><?php _e('Tree ID', 'heritagepress'); ?> <span class="required">*</span></label>
            <input type="text"
              name="tree_id"
              id="tree_id"
              class="regular-text"
              value="main"
              pattern="[a-zA-Z0-9_-]+"
              required>
            <span class="description"><?php _e('Unique identifier for this tree (letters, numbers, underscore, dash)', 'heritagepress'); ?></span>
          </p>

          <p>
            <label for="tree_name"><?php _e('Tree Name', 'heritagepress'); ?> <span class="required">*</span></label>
            <input type="text"
              name="tree_name"
              id="tree_name"
              class="regular-text"
              value="<?php _e('My Family Tree', 'heritagepress'); ?>"
              required>
            <span class="description"><?php _e('Display name for this family tree', 'heritagepress'); ?></span>
          </p>
        </div>

        <?php if (!empty($trees)): ?>
          <div id="existing-tree-options" style="display: none;">
            <p>
              <label for="existing_tree_id"><?php _e('Select Tree', 'heritagepress'); ?> <span class="required">*</span></label>
              <select name="existing_tree_id" id="existing_tree_id">
                <?php foreach ($trees as $tree): ?>
                  <option value="<?php echo esc_attr($tree['id']); ?>"><?php echo esc_html($tree['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </p>

            <div class="warning-box">
              <p><?php _e('Warning: Adding to an existing tree might create duplicates. Use this option only if you are importing additional people to an existing tree.', 'heritagepress'); ?></p>
            </div>
          </div>
        <?php endif; ?>
      </td>
    </tr>

    <tr>
      <th scope="row">
        <label for="character_set"><?php _e('Character Set', 'heritagepress'); ?></label>
      </th>
      <td>
        <select name="character_set" id="character_set">
          <option value="auto"><?php _e('Auto-detect (recommended)', 'heritagepress'); ?></option>
          <option value="ANSEL"><?php _e('ANSEL (GEDCOM standard)', 'heritagepress'); ?></option>
          <option value="UTF-8"><?php _e('UTF-8', 'heritagepress'); ?></option>
          <option value="ASCII"><?php _e('ASCII', 'heritagepress'); ?></option>
          <option value="ANSI"><?php _e('ANSI / Windows-1252', 'heritagepress'); ?></option>
          <option value="UTF-16"><?php _e('UTF-16', 'heritagepress'); ?></option>
          <option value="Windows-1250"><?php _e('Windows-1250 (Eastern European)', 'heritagepress'); ?></option>
          <option value="Windows-1251"><?php _e('Windows-1251 (Cyrillic)', 'heritagepress'); ?></option>
          <option value="ISO-8859-1"><?php _e('ISO-8859-1 (Western European)', 'heritagepress'); ?></option>
          <option value="ISO-8859-2"><?php _e('ISO-8859-2 (Central European)', 'heritagepress'); ?></option>
        </select>
        <p class="description"><?php _e('Character encoding used in the GEDCOM file. Auto-detect will analyze the file to determine the encoding.', 'heritagepress'); ?></p>
      </td>
    </tr>
  </table>

  <div class="hp-form-actions">
    <?php submit_button(__('Upload and Validate', 'heritagepress'), 'primary', 'upload_gedcom', false); ?>
  </div>
</form>

<script>
  jQuery(document).ready(function($) {
    // Toggle tree options based on selection
    $('#tree_destination').on('change', function() {
      if ($(this).val() === 'new') {
        $('#new-tree-options').show();
        $('#existing-tree-options').hide();
      } else {
        $('#new-tree-options').hide();
        $('#existing-tree-options').show();
      }
    });

    // Form validation
    $('#gedcom-upload-form').on('submit', function(e) {
      var fileInput = $('#gedcom_file');
      if (fileInput.get(0).files.length === 0) {
        e.preventDefault();
        alert('<?php _e('Please select a GEDCOM file to upload', 'heritagepress'); ?>');
        return false;
      }

      if ($('#tree_destination').val() === 'new' && $('#tree_id').val().trim() === '') {
        e.preventDefault();
        alert('<?php _e('Please enter a Tree ID', 'heritagepress'); ?>');
        $('#tree_id').focus();
        return false;
      }

      // Show loading indicator
      $('<div class="loading-overlay"><span class="spinner is-active"></span> <?php _e('Uploading GEDCOM file...', 'heritagepress'); ?></div>').appendTo('body');
    });
  });
</script>

<style>
  .hp-form .tree-id-field {
    margin-top: 15px;
  }

  .hp-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
  }

  .hp-form .required {
    color: #d63638;
  }

  .hp-form-actions {
    margin-top: 25px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
  }

  .loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
  }

  .loading-overlay .spinner {
    float: none;
    margin: 0 0 15px 0;
  }
</style>
