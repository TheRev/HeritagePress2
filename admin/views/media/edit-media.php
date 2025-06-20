<?php

/**
 * Edit Media View
 *
 * This sub-view provides the media editing interface
 * for the HeritagePress plugin. It allows users to update
 * media details, regenerate thumbnails, and manage person links.
 * It also includes options for geographic information and display settings.
 *
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Media item should be available from parent view
if (!$media_item) {
  echo '<div class="notice notice-error"><p>' . esc_html__('Media item not found.', 'heritagepress') . '</p></div>';
  return;
}
?>

<div id="edit-media-tab" class="hp-tab-content">
  <div class="hp-media-header">
    <h3><?php echo sprintf(esc_html__('Edit Media: %s', 'heritagepress'), esc_html($media_item->description ?: $media_item->path)); ?></h3>
    <div class="hp-media-actions">
      <a href="<?php echo admin_url('admin.php?page=heritagepress-media&tab=browse'); ?>" class="button">
        <?php esc_html_e('Back to Browse', 'heritagepress'); ?>
      </a>
      <button type="button" id="delete-media" class="button button-danger" data-media-id="<?php echo esc_attr($media_item->mediaID); ?>">
        <?php esc_html_e('Delete Media', 'heritagepress'); ?>
      </button>
    </div>
  </div>

  <form id="edit-media-form" method="post">
    <?php wp_nonce_field('hp_update_media', 'hp_update_media_nonce'); ?>
    <input type="hidden" name="mediaID" value="<?php echo esc_attr($media_item->mediaID); ?>">

    <div class="hp-media-edit-layout">
      <!-- Media Preview Section -->
      <div class="hp-media-preview-section">
        <div class="hp-media-preview">
          <?php if ($media_item->thumbpath): ?>
            <img src="<?php echo esc_url($media_item->thumbpath); ?>" alt="<?php echo esc_attr($media_item->description); ?>" class="hp-media-thumbnail">
          <?php elseif (in_array(strtoupper($media_item->form), ['JPG', 'JPEG', 'PNG', 'GIF'])): ?>
            <img src="<?php echo esc_url($media_item->path); ?>" alt="<?php echo esc_attr($media_item->description); ?>" class="hp-media-thumbnail">
          <?php else: ?>
            <div class="hp-media-placeholder">
              <span class="hp-file-icon"><?php echo esc_html(strtoupper($media_item->form)); ?></span>
              <p><?php echo esc_html($media_item->form); ?> <?php esc_html_e('File', 'heritagepress'); ?></p>
            </div>
          <?php endif; ?>
        </div>

        <div class="hp-media-info">
          <p><strong><?php esc_html_e('Media ID:', 'heritagepress'); ?></strong> <?php echo esc_html($media_item->mediaID); ?></p>
          <p><strong><?php esc_html_e('File Type:', 'heritagepress'); ?></strong> <?php echo esc_html($media_item->form); ?></p>
          <p><strong><?php esc_html_e('Media Type:', 'heritagepress'); ?></strong> <?php echo esc_html($media_item->media_type_name); ?></p>
          <p><strong><?php esc_html_e('Tree:', 'heritagepress'); ?></strong> <?php echo esc_html($media_item->gedcom); ?></p>
          <?php if ($media_item->width && $media_item->height): ?>
            <p><strong><?php esc_html_e('Dimensions:', 'heritagepress'); ?></strong> <?php echo esc_html($media_item->width . ' × ' . $media_item->height); ?></p>
          <?php endif; ?>
          <p><strong><?php esc_html_e('Created:', 'heritagepress'); ?></strong> <?php echo esc_html($media_item->changedate); ?></p>
          <p><strong><?php esc_html_e('Changed by:', 'heritagepress'); ?></strong> <?php echo esc_html($media_item->changedby); ?></p>
        </div>

        <div class="hp-media-links">
          <h4><?php esc_html_e('Actions', 'heritagepress'); ?></h4>
          <a href="#" id="view-full-media" class="button" target="_blank">
            <?php esc_html_e('View Full Size', 'heritagepress'); ?>
          </a>
          <button type="button" id="regenerate-thumbnail" class="button">
            <?php esc_html_e('Regenerate Thumbnail', 'heritagepress'); ?>
          </button>
          <button type="button" id="download-media" class="button">
            <?php esc_html_e('Download', 'heritagepress'); ?>
          </button>
        </div>
      </div>

      <!-- Edit Form Section -->
      <div class="hp-media-form-section">
        <div class="hp-form-sections">
          <!-- Basic Information Section -->
          <div class="hp-form-section">
            <h3><?php esc_html_e('Basic Information', 'heritagepress'); ?></h3>
            <table class="form-table">
              <tr>
                <th scope="row">
                  <label for="edit-media-path"><?php esc_html_e('File Path/URL', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" id="edit-media-path" name="path" value="<?php echo esc_attr($media_item->path); ?>" class="large-text">
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="edit-media-type"><?php esc_html_e('Media Type', 'heritagepress'); ?></label>
                </th>
                <td>
                  <select id="edit-media-type" name="mediatypeID">
                    <?php foreach ($media_types as $type): ?>
                      <option value="<?php echo esc_attr($type->mediatypeID); ?>" <?php selected($type->mediatypeID, $media_item->mediatypeID); ?>>
                        <?php echo esc_html($type->display); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="edit-media-tree"><?php esc_html_e('Tree', 'heritagepress'); ?></label>
                </th>
                <td>
                  <select id="edit-media-tree" name="gedcom">
                    <?php foreach ($trees as $tree): ?>
                      <option value="<?php echo esc_attr($tree->gedcom); ?>" <?php selected($tree->gedcom, $media_item->gedcom); ?>>
                        <?php echo esc_html($tree->treename); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="edit-media-description"><?php esc_html_e('Description', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" id="edit-media-description" name="description" value="<?php echo esc_attr($media_item->description); ?>" class="large-text">
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="edit-media-notes"><?php esc_html_e('Notes', 'heritagepress'); ?></label>
                </th>
                <td>
                  <textarea id="edit-media-notes" name="notes" rows="4" class="large-text"><?php echo esc_textarea($media_item->notes); ?></textarea>
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
                  <label for="edit-media-date-taken"><?php esc_html_e('Date Taken', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" id="edit-media-date-taken" name="datetaken" value="<?php echo esc_attr($media_item->datetaken); ?>" class="regular-text">
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="edit-media-place-taken"><?php esc_html_e('Place Taken', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" id="edit-media-place-taken" name="placetaken" value="<?php echo esc_attr($media_item->placetaken); ?>" class="large-text">
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="edit-media-owner"><?php esc_html_e('Owner', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" id="edit-media-owner" name="owner" value="<?php echo esc_attr($media_item->owner); ?>" class="regular-text">
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="edit-media-status"><?php esc_html_e('Status', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" id="edit-media-status" name="status" value="<?php echo esc_attr($media_item->status); ?>" class="regular-text">
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="edit-media-width"><?php esc_html_e('Width (pixels)', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="number" id="edit-media-width" name="width" value="<?php echo esc_attr($media_item->width); ?>" class="small-text" min="0">
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="edit-media-height"><?php esc_html_e('Height (pixels)', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="number" id="edit-media-height" name="height" value="<?php echo esc_attr($media_item->height); ?>" class="small-text" min="0">
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
                  <label for="edit-thumbnail-path"><?php esc_html_e('Thumbnail Path', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" id="edit-thumbnail-path" name="thumbpath" value="<?php echo esc_attr($media_item->thumbpath); ?>" class="large-text">
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="edit-thumbnail-file"><?php esc_html_e('Replace Thumbnail', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="file" id="edit-thumbnail-file" name="thumbnail_file" accept="image/*">
                  <p class="description">
                    <?php esc_html_e('Upload a new thumbnail image to replace the current one', 'heritagepress'); ?>
                  </p>
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
                  <label for="edit-media-latitude"><?php esc_html_e('Latitude', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" id="edit-media-latitude" name="latitude" value="<?php echo esc_attr($media_item->latitude); ?>" class="regular-text">
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="edit-media-longitude"><?php esc_html_e('Longitude', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" id="edit-media-longitude" name="longitude" value="<?php echo esc_attr($media_item->longitude); ?>" class="regular-text">
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label for="edit-media-zoom"><?php esc_html_e('Map Zoom Level', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="number" id="edit-media-zoom" name="zoom" value="<?php echo esc_attr($media_item->zoom); ?>" class="small-text" min="1" max="20">
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label>
                    <input type="checkbox" id="edit-show-map" name="showmap" value="1" <?php checked($media_item->showmap, 1); ?>>
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

          <!-- Display Options Section -->
          <div class="hp-form-section">
            <h3><?php esc_html_e('Display Options', 'heritagepress'); ?></h3>
            <table class="form-table">
              <tr>
                <th scope="row">
                  <?php esc_html_e('Options', 'heritagepress'); ?>
                </th>
                <td>
                  <label>
                    <input type="checkbox" name="private" value="1" <?php checked($media_item->private, 1); ?>>
                    <?php esc_html_e('Private (restricted access)', 'heritagepress'); ?>
                  </label><br>
                  <label>
                    <input type="checkbox" name="alwayson" value="1" <?php checked($media_item->alwayson, 1); ?>>
                    <?php esc_html_e('Always display', 'heritagepress'); ?>
                  </label><br>
                  <label>
                    <input type="checkbox" name="newwindow" value="1" <?php checked($media_item->newwindow, 1); ?>>
                    <?php esc_html_e('Open in new window', 'heritagepress'); ?>
                  </label><br>
                  <label>
                    <input type="checkbox" name="usecollfolder" value="1" <?php checked($media_item->usecollfolder, 1); ?>>
                    <?php esc_html_e('Use collection folder structure', 'heritagepress'); ?>
                  </label><br>
                  <label>
                    <input type="checkbox" name="abspath" value="1" <?php checked($media_item->abspath, 1); ?>>
                    <?php esc_html_e('Use absolute path/URL', 'heritagepress'); ?>
                  </label>
                </td>
              </tr>
            </table>
          </div>
        </div>

        <p class="submit">
          <input type="submit" id="submit-update-media" class="button-primary" value="<?php esc_attr_e('Update Media', 'heritagepress'); ?>">
          <button type="button" id="preview-changes" class="button">
            <?php esc_html_e('Preview Changes', 'heritagepress'); ?>
          </button>
          <a href="<?php echo admin_url('admin.php?page=heritagepress-media&tab=add'); ?>" class="button">
            <?php esc_html_e('Add New Media', 'heritagepress'); ?>
          </a>
        </p>
      </div>
    </div>
  </form>

  <!-- Person Links Section -->
  <div class="hp-media-links-section">
    <h3><?php esc_html_e('Person Links', 'heritagepress'); ?></h3>
    <div id="person-links-list">
      <!-- Person links loaded via AJAX -->
    </div>
    <button type="button" id="add-person-link" class="button">
      <?php esc_html_e('Add Person Link', 'heritagepress'); ?>
    </button>
  </div>
</div>

<style>
  .hp-media-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ddd;
  }

  .hp-media-actions {
    display: flex;
    gap: 10px;
  }

  .button-danger {
    background: #d63638;
    color: #fff;
    border-color: #d63638;
  }

  .button-danger:hover {
    background: #ba2c2e;
    border-color: #ba2c2e;
  }

  .hp-media-edit-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 20px;
  }

  .hp-media-preview-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
    padding: 15px;
    height: fit-content;
  }

  .hp-media-preview {
    text-align: center;
    margin-bottom: 20px;
  }

  .hp-media-thumbnail {
    max-width: 100%;
    max-height: 200px;
    border: 1px solid #ddd;
    border-radius: 4px;
  }

  .hp-media-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 150px;
    background: #f6f7f7;
    border: 2px dashed #c3c4c7;
    border-radius: 4px;
  }

  .hp-file-icon {
    font-size: 24px;
    font-weight: bold;
    color: #666;
  }

  .hp-media-info p {
    margin: 8px 0;
    font-size: 13px;
  }

  .hp-media-links {
    margin-top: 20px;
  }

  .hp-media-links h4 {
    margin-bottom: 10px;
  }

  .hp-media-links .button {
    display: block;
    margin-bottom: 5px;
    text-align: center;
  }

  .hp-media-form-section .hp-form-section {
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

  .hp-media-links-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
    margin-top: 20px;
    padding: 15px;
  }

  @media (max-width: 782px) {
    .hp-media-edit-layout {
      grid-template-columns: 1fr;
    }

    .hp-media-header {
      flex-direction: column;
      gap: 10px;
      align-items: flex-start;
    }
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Load person links
    loadPersonLinks();

    // Form submission
    $('#edit-media-form').submit(function(e) {
      e.preventDefault();

      const formData = new FormData(this);
      formData.append('action', 'hp_update_media');

      $('#submit-update-media').prop('disabled', true);

      $.ajax({
        url: hp_ajax.ajax_url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
          if (response.success) {
            alert('Media updated successfully!');
            location.reload();
          } else {
            alert('Error: ' + response.data.message);
          }
        },
        error: function() {
          alert('Error updating media. Please try again.');
        },
        complete: function() {
          $('#submit-update-media').prop('disabled', false);
        }
      });
    });

    // Delete media
    $('#delete-media').click(function() {
      if (!confirm('Are you sure you want to delete this media item? This action cannot be undone.')) {
        return;
      }

      const mediaId = $(this).data('media-id');

      $.ajax({
        url: hp_ajax.ajax_url,
        type: 'POST',
        data: {
          action: 'hp_delete_media',
          media_id: mediaId,
          nonce: hp_ajax.nonce
        },
        success: function(response) {
          if (response.success) {
            alert('Media deleted successfully!');
            window.location.href = 'admin.php?page=heritagepress-media&tab=browse';
          } else {
            alert('Error: ' + response.data.message);
          }
        },
        error: function() {
          alert('Error deleting media. Please try again.');
        }
      });
    });

    // Regenerate thumbnail
    $('#regenerate-thumbnail').click(function() {
      const mediaId = $('input[name="mediaID"]').val();

      $(this).prop('disabled', true).text('Regenerating...');

      $.ajax({
        url: hp_ajax.ajax_url,
        type: 'POST',
        data: {
          action: 'hp_create_thumbnail',
          media_id: mediaId,
          nonce: hp_ajax.nonce
        },
        success: function(response) {
          if (response.success) {
            alert('Thumbnail regenerated successfully!');
            location.reload();
          } else {
            alert('Error: ' + response.data.message);
          }
        },
        error: function() {
          alert('Error regenerating thumbnail. Please try again.');
        },
        complete: function() {
          $('#regenerate-thumbnail').prop('disabled', false).text('Regenerate Thumbnail');
        }
      });
    });

    function loadPersonLinks() {
      const mediaId = $('input[name="mediaID"]').val();

      $.ajax({
        url: hp_ajax.ajax_url,
        type: 'POST',
        data: {
          action: 'hp_get_media_person_links',
          media_id: mediaId,
          nonce: hp_ajax.nonce
        },
        success: function(response) {
          if (response.success) {
            $('#person-links-list').html(response.data.html);
          }
        }
      });
    }
  });
</script>
