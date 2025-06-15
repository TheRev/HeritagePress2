<?php

/**
 * GEDCOM Import - Media Options Tab
 *
 * Fifth step of GEDCOM import process - configure media import settings
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get import session data
$upload_data = isset($_SESSION['hp_gedcom_upload']) ? $_SESSION['hp_gedcom_upload'] : null;
$validation_results = isset($_SESSION['hp_gedcom_validation']) ? $_SESSION['hp_gedcom_validation'] : null;
$config_data = isset($_SESSION['hp_gedcom_config']) ? $_SESSION['hp_gedcom_config'] : null;
$people_data = isset($_SESSION['hp_gedcom_people']) ? $_SESSION['hp_gedcom_people'] : null;
$file_path = isset($upload_data['file_path']) ? $upload_data['file_path'] : '';

// If no people data, redirect to people tab
if (!$people_data && !isset($_GET['skip'])) {
  echo '<div class="error-box">';
  echo '<p>' . __('People settings have not been configured. Please return to the People Settings tab.', 'heritagepress') . '</p>';
  echo '<p><a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=people" class="button">' . __('Go to People Settings', 'heritagepress') . '</a></p>';
  echo '</div>';
  return;
}

// Program information
$program = isset($validation_results['program']) ? $validation_results['program'] : array('name' => __('Unknown', 'heritagepress'), 'version' => '');
$program_name = $program['name'];
$program_version = $program['version'];

// Media statistics
$media_count = isset($validation_results['stats']['media']) ? intval($validation_results['stats']['media']) : 0;
$media_info = isset($validation_results['media']) ? $validation_results['media'] : array();
$media_base_path = isset($media_info['base_path']) ? $media_info['base_path'] : '';

// Get default settings based on program
$default_settings = hp_get_default_media_settings($program_name);

// Check for WordPress upload directory
$wp_upload_dir = wp_upload_dir();
$default_media_path = $wp_upload_dir['basedir'] . '/heritagepress/media';
?>

<h2><?php _e('Media Import Options', 'heritagepress'); ?></h2>

<div class="message-box">
  <p><?php _e('Configure how media files referenced in your GEDCOM are imported and processed. These settings control which media files are imported and how they are handled during import.', 'heritagepress'); ?></p>
  <?php if ($media_count > 0): ?>
    <p><strong><?php _e('Media References in GEDCOM:', 'heritagepress'); ?></strong> <?php echo number_format($media_count); ?></p>
    <?php if ($media_base_path): ?>
      <p><strong><?php _e('Media Base Path in GEDCOM:', 'heritagepress'); ?></strong> <?php echo esc_html($media_base_path); ?></p>
    <?php endif; ?>
  <?php else: ?>
    <p><strong><?php _e('No media references found in this GEDCOM file.', 'heritagepress'); ?></strong></p>
  <?php endif; ?>
</div>

<?php if ($media_count > 0): ?>

  <form method="post" id="gedcom-media-form" class="hp-form">
    <?php wp_nonce_field('heritagepress_gedcom_media', 'gedcom_media_nonce'); ?>
    <input type="hidden" name="action" value="hp_save_gedcom_media">
    <input type="hidden" name="file_path" value="<?php echo esc_attr($file_path); ?>">

    <table class="form-table">
      <tr>
        <th scope="row">
          <label for="import_media"><?php _e('Import Media', 'heritagepress'); ?></label>
        </th>
        <td>
          <select name="import_media" id="import_media">
            <option value="all" <?php selected($default_settings['import_media'], 'all'); ?>><?php _e('Import all media files', 'heritagepress'); ?></option>
            <option value="links" <?php selected($default_settings['import_media'], 'links'); ?>><?php _e('Import links only (no files)', 'heritagepress'); ?></option>
            <option value="none" <?php selected($default_settings['import_media'], 'none'); ?>><?php _e('Do not import media', 'heritagepress'); ?></option>
          </select>
          <p class="description"><?php _e('Select how media files should be imported.', 'heritagepress'); ?></p>
        </td>
      </tr>

      <tr id="media-source-row" <?php echo ($default_settings['import_media'] == 'none') ? 'style="display:none"' : ''; ?>>
        <th scope="row">
          <label for="media_source"><?php _e('Media Source', 'heritagepress'); ?></label>
        </th>
        <td>
          <select name="media_source" id="media_source">
            <option value="local" <?php selected($default_settings['media_source'], 'local'); ?>><?php _e('Local files on your computer', 'heritagepress'); ?></option>
            <option value="url" <?php selected($default_settings['media_source'], 'url'); ?>><?php _e('Files on another website (URLs in GEDCOM)', 'heritagepress'); ?></option>
            <option value="archive" <?php selected($default_settings['media_source'], 'archive'); ?>><?php _e('Media archive to upload separately', 'heritagepress'); ?></option>
          </select>
          <p class="description"><?php _e('Where are the media files referenced in your GEDCOM located?', 'heritagepress'); ?></p>
        </td>
      </tr>

      <tr id="local-media-path-row" <?php echo ($default_settings['media_source'] != 'local' || $default_settings['import_media'] == 'none') ? 'style="display:none"' : ''; ?>>
        <th scope="row">
          <label for="local_media_path"><?php _e('Local Media Path', 'heritagepress'); ?></label>
        </th>
        <td>
          <input type="text" name="local_media_path" id="local_media_path" class="large-text" value="<?php echo esc_attr($default_settings['local_media_path']); ?>">
          <p class="description"><?php _e('Enter the full path to the folder containing your media files on your computer.', 'heritagepress'); ?></p>

          <div class="media-path-help">
            <p><?php _e('Examples:', 'heritagepress'); ?></p>
            <ul>
              <li><code>C:\Family Tree\Media</code> <?php _e('(Windows)', 'heritagepress'); ?></li>
              <li><code>/Users/username/FamilyTree/Media</code> <?php _e('(Mac/Linux)', 'heritagepress'); ?></li>
            </ul>

            <?php if ($media_base_path): ?>
              <div class="suggestion-box">
                <p><strong><?php _e('Suggested path based on GEDCOM:', 'heritagepress'); ?></strong></p>
                <p><code><?php echo esc_html($media_base_path); ?></code></p>
              </div>
            <?php endif; ?>
          </div>
        </td>
      </tr>

      <tr id="media-destination-row" <?php echo ($default_settings['import_media'] == 'none') ? 'style="display:none"' : ''; ?>>
        <th scope="row">
          <label for="media_destination"><?php _e('Media Destination', 'heritagepress'); ?></label>
        </th>
        <td>
          <input type="text" name="media_destination" id="media_destination" class="large-text" value="<?php echo esc_attr($default_settings['media_destination'] ?: $default_media_path); ?>">
          <p class="description"><?php _e('Where to store imported media files on your server.', 'heritagepress'); ?></p>

          <div class="media-path-help">
            <p><?php _e('Recommended:', 'heritagepress'); ?> <code><?php echo esc_html($default_media_path); ?></code></p>
            <p><?php _e('This folder will be created automatically if it does not exist.', 'heritagepress'); ?></p>
          </div>
        </td>
      </tr>

      <tr id="media-folder-structure-row" <?php echo ($default_settings['import_media'] == 'none') ? 'style="display:none"' : ''; ?>>
        <th scope="row">
          <label for="media_folder_structure"><?php _e('Folder Structure', 'heritagepress'); ?></label>
        </th>
        <td>
          <select name="media_folder_structure" id="media_folder_structure">
            <option value="flat" <?php selected($default_settings['media_folder_structure'], 'flat'); ?>><?php _e('Flat structure (all files in one folder)', 'heritagepress'); ?></option>
            <option value="preserve" <?php selected($default_settings['media_folder_structure'], 'preserve'); ?>><?php _e('Preserve subfolder structure from GEDCOM', 'heritagepress'); ?></option>
            <option value="type" <?php selected($default_settings['media_folder_structure'], 'type'); ?>><?php _e('Organize by media type (photos, documents, etc.)', 'heritagepress'); ?></option>
          </select>
          <p class="description"><?php _e('How to organize the imported media files.', 'heritagepress'); ?></p>
        </td>
      </tr>

      <tr id="file-handling-row" <?php echo ($default_settings['import_media'] == 'none') ? 'style="display:none"' : ''; ?>>
        <th scope="row">
          <label for="file_handling"><?php _e('File Handling', 'heritagepress'); ?></label>
        </th>
        <td>
          <select name="file_handling" id="file_handling">
            <option value="copy" <?php selected($default_settings['file_handling'], 'copy'); ?>><?php _e('Copy files (keep originals)', 'heritagepress'); ?></option>
            <option value="move" <?php selected($default_settings['file_handling'], 'move'); ?>><?php _e('Move files (remove originals)', 'heritagepress'); ?></option>
          </select>
          <p class="description"><?php _e('How to handle original media files during import.', 'heritagepress'); ?></p>
        </td>
      </tr>

      <tr id="file-types-row" <?php echo ($default_settings['import_media'] == 'none') ? 'style="display:none"' : ''; ?>>
        <th scope="row">
          <?php _e('File Types', 'heritagepress'); ?>
        </th>
        <td>
          <fieldset>
            <legend class="screen-reader-text"><?php _e('File types to import', 'heritagepress'); ?></legend>

            <div class="file-type-columns">
              <div class="file-type-column">
                <label for="import_images">
                  <input type="checkbox" name="import_images" id="import_images" value="1" <?php checked($default_settings['import_images'], true); ?>>
                  <?php _e('Images (JPG, PNG, GIF, etc.)', 'heritagepress'); ?>
                </label><br>

                <label for="import_documents">
                  <input type="checkbox" name="import_documents" id="import_documents" value="1" <?php checked($default_settings['import_documents'], true); ?>>
                  <?php _e('Documents (PDF, DOC, TXT, etc.)', 'heritagepress'); ?>
                </label>
              </div>

              <div class="file-type-column">
                <label for="import_audio">
                  <input type="checkbox" name="import_audio" id="import_audio" value="1" <?php checked($default_settings['import_audio'], true); ?>>
                  <?php _e('Audio files (MP3, WAV, etc.)', 'heritagepress'); ?>
                </label><br>

                <label for="import_video">
                  <input type="checkbox" name="import_video" id="import_video" value="1" <?php checked($default_settings['import_video'], true); ?>>
                  <?php _e('Video files (MP4, MOV, etc.)', 'heritagepress'); ?>
                </label>
              </div>
            </div>

            <p class="description"><?php _e('Select which types of media files to import.', 'heritagepress'); ?></p>
          </fieldset>
        </td>
      </tr>

      <tr id="media-options-row" <?php echo ($default_settings['import_media'] == 'none') ? 'style="display:none"' : ''; ?>>
        <th scope="row">
          <?php _e('Media Options', 'heritagepress'); ?>
        </th>
        <td>
          <fieldset>
            <legend class="screen-reader-text"><?php _e('Media processing options', 'heritagepress'); ?></legend>

            <label for="resize_images">
              <input type="checkbox" name="resize_images" id="resize_images" value="1" <?php checked($default_settings['resize_images'], true); ?>>
              <?php _e('Resize large images', 'heritagepress'); ?>
            </label><br>

            <div id="resize-options" <?php echo $default_settings['resize_images'] ? '' : 'style="display:none"'; ?>>
              <label for="max_image_size">
                <?php _e('Maximum image dimension:', 'heritagepress'); ?>
                <input type="number" name="max_image_size" id="max_image_size" value="<?php echo intval($default_settings['max_image_size']); ?>" min="100" max="5000" step="100" style="width: 80px;">
                <?php _e('pixels', 'heritagepress'); ?>
              </label>
              <p class="description"><?php _e('Larger images will be resized, maintaining aspect ratio.', 'heritagepress'); ?></p>
            </div>

            <label for="generate_thumbnails">
              <input type="checkbox" name="generate_thumbnails" id="generate_thumbnails" value="1" <?php checked($default_settings['generate_thumbnails'], true); ?>>
              <?php _e('Generate thumbnails for images', 'heritagepress'); ?>
            </label><br>

            <label for="extract_metadata">
              <input type="checkbox" name="extract_metadata" id="extract_metadata" value="1" <?php checked($default_settings['extract_metadata'], true); ?>>
              <?php _e('Extract EXIF metadata from images (date, location, etc.)', 'heritagepress'); ?>
            </label>
          </fieldset>
        </td>
      </tr>
    </table>

    <div class="hp-form-actions">
      <?php submit_button(__('Save Media Settings', 'heritagepress'), 'primary', 'save_media_settings', false); ?>
      &nbsp;<a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=people" class="button"><?php _e('Back to People Settings', 'heritagepress'); ?></a>
      &nbsp;<a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=places" class="button button-secondary"><?php _e('Skip to Places Settings', 'heritagepress'); ?></a>
    </div>
  </form>

<?php else: ?>
  <div class="message-box">
    <p><?php _e('No media files were found in your GEDCOM file. You can skip this step.', 'heritagepress'); ?></p>
    <p><a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=places" class="button button-primary"><?php _e('Continue to Places Settings', 'heritagepress'); ?></a>
      &nbsp;<a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=people" class="button"><?php _e('Back to People Settings', 'heritagepress'); ?></a></p>
  </div>
<?php endif; ?>

<script>
  jQuery(document).ready(function($) {
    // Toggle media options based on import media selection
    $('#import_media').on('change', function() {
      var value = $(this).val();
      if (value == 'none') {
        $('#media-source-row, #local-media-path-row, #media-destination-row, #media-folder-structure-row, #file-handling-row, #file-types-row, #media-options-row').hide();
      } else {
        $('#media-source-row, #media-destination-row, #media-folder-structure-row, #file-handling-row, #file-types-row, #media-options-row').show();
        $('#media_source').trigger('change');
      }
    });

    // Toggle local media path based on media source selection
    $('#media_source').on('change', function() {
      var value = $(this).val();
      if (value == 'local') {
        $('#local-media-path-row').show();
      } else {
        $('#local-media-path-row').hide();
      }
    });

    // Toggle resize options based on resize images checkbox
    $('#resize_images').on('change', function() {
      if ($(this).is(':checked')) {
        $('#resize-options').show();
      } else {
        $('#resize-options').hide();
      }
    });

    // Form submission
    $('#gedcom-media-form').on('submit', function() {
      $('<div class="loading-overlay"><span class="spinner is-active"></span> <?php _e('Saving media settings...', 'heritagepress'); ?></div>').appendTo('body');
    });
  });
</script>

<style>
  .file-type-columns {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
  }

  .file-type-column {
    min-width: 200px;
  }

  .media-path-help {
    margin-top: 10px;
    background: #f9f9f9;
    padding: 10px;
    border-left: 4px solid #2271b1;
  }

  .media-path-help ul {
    margin: 0.5em 0 0.5em 1.5em;
  }

  .suggestion-box {
    margin-top: 10px;
    padding: 10px;
    background: #edfaef;
    border-left: 4px solid #00a32a;
  }

  #resize-options {
    margin: 10px 0 10px 25px;
    padding: 10px;
    border-left: 3px solid #ddd;
  }
</style>
