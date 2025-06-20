<?php

/**
 * Add Media View
 *
 * This sub-view provides the media upload and add interface
 * for the HeritagePress plugin.
 * It includes fields for file upload, media type selection,
 * and various metadata inputs.
 * It also handles AJAX requests for adding media.
 *
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}
?>

<div id="add-media-tab" class="hp-tab-content">
  <form id="add-media-form" method="post" enctype="multipart/form-data">
    <?php wp_nonce_field('hp_add_media', 'hp_add_media_nonce'); ?>

    <div class="hp-form-sections">
      <!-- File Upload Section -->
      <div class="hp-form-section">
        <h3><?php esc_html_e('File Upload', 'heritagepress'); ?></h3>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="media-file"><?php esc_html_e('Select File', 'heritagepress'); ?> <span class="required">*</span></label>
            </th>
            <td>
              <input type="file" id="media-file" name="media_file" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt" required>
              <p class="description">
                <?php esc_html_e('Supadapted formats: Images (JPG, PNG, GIF), Videos (MP4, AVI), Audio (MP3, WAV), Documents (PDF, DOC, DOCX, TXT)', 'heritagepress'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="media-path"><?php esc_html_e('File Path/URL', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" id="media-path" name="path" class="regular-text" placeholder="<?php esc_attr_e('Or enter external URL...', 'heritagepress'); ?>">
              <p class="description">
                <?php esc_html_e('Leave empty to upload file, or enter external URL for remote media', 'heritagepress'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label>
                <input type="checkbox" id="use-absolute-path" name="abspath" value="1">
                <?php esc_html_e('Use absolute path/URL', 'heritagepress'); ?>
              </label>
            </th>
            <td>
              <p class="description">
                <?php esc_html_e('Check this if the path is an absolute URL or path', 'heritagepress'); ?>
              </p>
            </td>
          </tr>
        </table>
      </div>

      <!-- Media Information Section -->
      <div class="hp-form-section">
        <h3><?php esc_html_e('Media Information', 'heritagepress'); ?></h3>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="media-type"><?php esc_html_e('Media Type', 'heritagepress'); ?> <span class="required">*</span></label>
            </th>
            <td>
              <select id="media-type" name="mediatypeID" required>
                <option value=""><?php esc_html_e('Select Media Type', 'heritagepress'); ?></option>
                <?php foreach ($media_types as $type): ?>
                  <option value="<?php echo esc_attr($type->mediatypeID); ?>">
                    <?php echo esc_html($type->display); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="media-tree"><?php esc_html_e('Tree', 'heritagepress'); ?> <span class="required">*</span></label>
            </th>
            <td>
              <select id="media-tree" name="gedcom" required>
                <option value=""><?php esc_html_e('Select Tree', 'heritagepress'); ?></option>
                <?php foreach ($trees as $tree): ?>
                  <option value="<?php echo esc_attr($tree->gedcom); ?>">
                    <?php echo esc_html($tree->treename); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="media-description"><?php esc_html_e('Description', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" id="media-description" name="description" class="large-text">
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="media-notes"><?php esc_html_e('Notes', 'heritagepress'); ?></label>
            </th>
            <td>
              <textarea id="media-notes" name="notes" rows="4" class="large-text"></textarea>
            </td>
          </tr>
        </table>
      </div>

      <!-- Details Section -->
      <div class="hp-form-section">
        <h3><?php esc_html_e('Details', 'heritagepress'); ?></h3>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="media-date-taken"><?php esc_html_e('Date Taken', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" id="media-date-taken" name="datetaken" class="regular-text">
              <p class="description">
                <?php esc_html_e('Format: YYYY-MM-DD or descriptive date', 'heritagepress'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="media-place-taken"><?php esc_html_e('Place Taken', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" id="media-place-taken" name="placetaken" class="large-text">
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="media-owner"><?php esc_html_e('Owner', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" id="media-owner" name="owner" class="regular-text">
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="media-status"><?php esc_html_e('Status', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" id="media-status" name="status" class="regular-text">
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="media-width"><?php esc_html_e('Width (pixels)', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="number" id="media-width" name="width" class="small-text" min="0">
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="media-height"><?php esc_html_e('Height (pixels)', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="number" id="media-height" name="height" class="small-text" min="0">
            </td>
          </tr>
        </table>
      </div>

      <!-- Thumbnail Section -->
      <div class="hp-form-section">
        <h3><?php esc_html_e('Thumbnail', 'heritagepress'); ?></h3>
        <table class="form-table">
          <tr>
            <th scope="row">
              <?php esc_html_e('Thumbnail Creation', 'heritagepress'); ?>
            </th>
            <td>
              <label>
                <input type="radio" name="thumb_create" value="auto" checked>
                <?php esc_html_e('Auto-generate thumbnail', 'heritagepress'); ?>
              </label><br>
              <label>
                <input type="radio" name="thumb_create" value="manual">
                <?php esc_html_e('Upload custom thumbnail', 'heritagepress'); ?>
              </label><br>
              <label>
                <input type="radio" name="thumb_create" value="none">
                <?php esc_html_e('No thumbnail', 'heritagepress'); ?>
              </label>
            </td>
          </tr>
          <tr id="thumbnail-upload-row" style="display: none;">
            <th scope="row">
              <label for="thumbnail-file"><?php esc_html_e('Thumbnail File', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="file" id="thumbnail-file" name="thumbnail_file" accept="image/*">
            </td>
          </tr>
          <tr id="thumbnail-path-row" style="display: none;">
            <th scope="row">
              <label for="thumbnail-path"><?php esc_html_e('Thumbnail Path', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" id="thumbnail-path" name="thumbpath" class="regular-text">
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="thumb-max-width"><?php esc_html_e('Max Thumbnail Width', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="number" id="thumb-max-width" name="thumbmaxw" value="150" class="small-text" min="50" max="500">
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="thumb-max-height"><?php esc_html_e('Max Thumbnail Height', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="number" id="thumb-max-height" name="thumbmaxh" value="150" class="small-text" min="50" max="500">
            </td>
          </tr>
        </table>
      </div>

      <!-- Geographic Information Section -->
      <div class="hp-form-section">
        <h3><?php esc_html_e('Geographic Information', 'heritagepress'); ?></h3>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="media-latitude"><?php esc_html_e('Latitude', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" id="media-latitude" name="latitude" class="regular-text" pattern="^-?([1-8]?[0-9](\.[0-9]+)?|90(\.0+)?)$">
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="media-longitude"><?php esc_html_e('Longitude', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" id="media-longitude" name="longitude" class="regular-text" pattern="^-?((1[0-7]|[0-9])?[0-9](\.[0-9]+)?|180(\.0+)?)$">
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="media-zoom"><?php esc_html_e('Map Zoom Level', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="number" id="media-zoom" name="zoom" value="13" class="small-text" min="1" max="20">
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label>
                <input type="checkbox" id="show-map" name="showmap" value="1">
                <?php esc_html_e('Show map for this media', 'heritagepress'); ?>
              </label>
            </th>
            <td>
              <p class="description">
                <?php esc_html_e('Display a map when viewing this media item', 'heritagepress'); ?>
              </p>
            </td>
          </tr>
        </table>
      </div>

      <!-- Link to Person Section -->
      <div class="hp-form-section">
        <h3><?php esc_html_e('Link to Person', 'heritagepress'); ?></h3>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="link-person-id"><?php esc_html_e('Person ID', 'heritagepress'); ?></label>
            </th>
            <td>
              <input type="text" id="link-person-id" name="link_personID" class="regular-text">
              <button type="button" id="find-person" class="button">
                <?php esc_html_e('Find Person', 'heritagepress'); ?>
              </button>
              <p class="description">
                <?php esc_html_e('Enter person ID or click Find Person to search', 'heritagepress'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="link-tree-select"><?php esc_html_e('Person Tree', 'heritagepress'); ?></label>
            </th>
            <td>
              <select id="link-tree-select" name="link_tree">
                <option value=""><?php esc_html_e('Select Tree', 'heritagepress'); ?></option>
                <?php foreach ($trees as $tree): ?>
                  <option value="<?php echo esc_attr($tree->gedcom); ?>">
                    <?php echo esc_html($tree->treename); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="link-type"><?php esc_html_e('Link Type', 'heritagepress'); ?></label>
            </th>
            <td>
              <select id="link-type" name="link_linktype">
                <option value=""><?php esc_html_e('General', 'heritagepress'); ?></option>
                <option value="person"><?php esc_html_e('Person Photo', 'heritagepress'); ?></option>
                <option value="event"><?php esc_html_e('Event Photo', 'heritagepress'); ?></option>
                <option value="document"><?php esc_html_e('Document', 'heritagepress'); ?></option>
              </select>
            </td>
          </tr>
        </table>
      </div>

      <!-- Options Section -->
      <div class="hp-form-section">
        <h3><?php esc_html_e('Display Options', 'heritagepress'); ?></h3>
        <table class="form-table">
          <tr>
            <th scope="row">
              <?php esc_html_e('Options', 'heritagepress'); ?>
            </th>
            <td>
              <label>
                <input type="checkbox" name="private" value="1">
                <?php esc_html_e('Private (restricted access)', 'heritagepress'); ?>
              </label><br>
              <label>
                <input type="checkbox" name="alwayson" value="1">
                <?php esc_html_e('Always display', 'heritagepress'); ?>
              </label><br>
              <label>
                <input type="checkbox" name="newwindow" value="1">
                <?php esc_html_e('Open in new window', 'heritagepress'); ?>
              </label><br>
              <label>
                <input type="checkbox" name="usecollfolder" value="1">
                <?php esc_html_e('Use collection folder structure', 'heritagepress'); ?>
              </label>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <p class="submit">
      <input type="submit" id="submit-add-media" class="button-primary" value="<?php esc_attr_e('Add Media', 'heritagepress'); ?>">
      <button type="button" id="preview-media" class="button">
        <?php esc_html_e('Preview', 'heritagepress'); ?>
      </button>
      <button type="reset" class="button">
        <?php esc_html_e('Reset Form', 'heritagepress'); ?>
      </button>
    </p>
  </form>
</div>

<style>
  .hp-form-sections .hp-form-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
    margin-bottom: 20px;
    padding: 15px;
  }

  .hp-form-section h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
  }

  .required {
    color: #d63638;
  }

  #add-media-form .form-table th {
    width: 200px;
  }

  .hp-progress {
    display: none;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 15px;
    margin: 20px 0;
  }

  .hp-progress-bar {
    background: #f0f0f1;
    border-radius: 3px;
    height: 20px;
    position: relative;
    overflow: hidden;
  }

  .hp-progress-fill {
    background: #00a32a;
    height: 100%;
    transition: width 0.3s ease;
    width: 0%;
  }

  .hp-preview {
    display: none;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 15px;
    margin: 20px 0;
  }

  .hp-preview img {
    max-width: 300px;
    max-height: 300px;
    border: 1px solid #ddd;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Show/hide thumbnail upload based on selection
    $('input[name="thumb_create"]').change(function() {
      const value = $(this).val();
      $('#thumbnail-upload-row, #thumbnail-path-row').hide();

      if (value === 'manual') {
        $('#thumbnail-upload-row, #thumbnail-path-row').show();
      }
    });

    // File selection handler
    $('#media-file').change(function() {
      const file = this.files[0];
      if (file) {
        // Auto-fill dimensions for images
        if (file.type.startsWith('image/')) {
          const img = new Image();
          img.onload = function() {
            $('#media-width').val(this.width);
            $('#media-height').val(this.height);
          };
          img.src = URL.createObjectURL(file);
        }

        // Auto-generate description from filename
        if (!$('#media-description').val()) {
          const filename = file.name.replace(/\.[^/.]+$/, "");
          $('#media-description').val(filename);
        }
      }
    });

    // Form submission
    $('#add-media-form').submit(function(e) {
      e.preventDefault();

      const formData = new FormData(this);
      formData.append('action', 'hp_add_media');

      // Show progress
      $('.hp-progress').show();
      $('#submit-add-media').prop('disabled', true);

      $.ajax({
        url: hp_ajax.ajax_url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
          const xhr = new window.XMLHttpRequest();
          xhr.upload.addEventListener("progress", function(evt) {
            if (evt.lengthComputable) {
              const percentComplete = (evt.loaded / evt.total) * 100;
              $('.hp-progress-fill').css('width', percentComplete + '%');
            }
          }, false);
          return xhr;
        },
        success: function(response) {
          if (response.success) {
            alert('Media added successfully!');
            // Redirect to edit page
            window.location.href = 'admin.php?page=heritagepress-media&tab=edit&media_id=' + response.data.media_id;
          } else {
            alert('Error: ' + response.data.message);
          }
        },
        error: function() {
          alert('Error uploading media. Please try again.');
        },
        complete: function() {
          $('.hp-progress').hide();
          $('#submit-add-media').prop('disabled', false);
          $('.hp-progress-fill').css('width', '0%');
        }
      });
    });
  });
</script>
