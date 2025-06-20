<?php

/**
 * Media Collections View for HeritagePress
 *
 * This file provides the media collection management interface for the WordPress admin.
 * Ported from admin_addcollection.php functionality
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Get existing collections
global $wpdb;
$collections = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hp_mediatypes ORDER BY ordernum ASC, display ASC");

// Standard collections that cannot be deleted
$standard_collections = array('photos', 'histories', 'headstones', 'documents', 'recordings', 'videos');
?>

<div class="wrap">
  <h1 class="wp-heading-inline"><?php esc_html_e('Media Collections', 'heritagepress'); ?></h1>
  <a href="#" id="add-collection-btn" class="page-title-action">
    <?php esc_html_e('Add New Collection', 'heritagepress'); ?>
  </a>
  <hr class="wp-header-end">

  <div class="hp-media-collections-container">
    <!-- Add/Edit Collection Form -->
    <div id="collection-form-container" style="display: none;">
      <div class="collection-form-wrapper">
        <h2 id="form-title"><?php esc_html_e('Add New Collection', 'heritagepress'); ?></h2>

        <form id="collection-form" method="post">
          <?php wp_nonce_field('hp_add_collection', 'hp_collection_nonce'); ?>
          <input type="hidden" name="action" value="hp_add_collection" id="form-action">
          <input type="hidden" name="original_collection_id" value="" id="original-collection-id">

          <table class="form-table">
            <tbody>
              <tr>
                <th scope="row">
                  <label for="collection_id"><?php esc_html_e('Collection ID', 'heritagepress'); ?> <span class="required">*</span></label>
                </th>
                <td>
                  <input type="text" name="collection_id" id="collection_id"
                    class="regular-text" required>
                  <p class="description"><?php esc_html_e('Unique identifier for the collection (lowercase, no spaces).', 'heritagepress'); ?></p>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="display"><?php esc_html_e('Display Name', 'heritagepress'); ?> <span class="required">*</span></label>
                </th>
                <td>
                  <input type="text" name="display" id="display"
                    class="regular-text" required>
                  <p class="description"><?php esc_html_e('Name shown to users.', 'heritagepress'); ?></p>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="path"><?php esc_html_e('Path', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" name="path" id="path" class="regular-text">
                  <p class="description"><?php esc_html_e('File path or URL pattern for this media type.', 'heritagepress'); ?></p>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="liketype"><?php esc_html_e('Like Type', 'heritagepress'); ?></label>
                </th>
                <td>
                  <select name="liketype" id="liketype" class="regular-text">
                    <option value=""><?php esc_html_e('Select similar type...', 'heritagepress'); ?></option>
                    <option value="photos"><?php esc_html_e('Photos', 'heritagepress'); ?></option>
                    <option value="documents"><?php esc_html_e('Documents', 'heritagepress'); ?></option>
                    <option value="recordings"><?php esc_html_e('Audio Recordings', 'heritagepress'); ?></option>
                    <option value="videos"><?php esc_html_e('Videos', 'heritagepress'); ?></option>
                    <option value="histories"><?php esc_html_e('Histories', 'heritagepress'); ?></option>
                    <option value="headstones"><?php esc_html_e('Headstones', 'heritagepress'); ?></option>
                  </select>
                  <p class="description"><?php esc_html_e('Similar media type for handling purposes.', 'heritagepress'); ?></p>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="icon"><?php esc_html_e('Icon', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" name="icon" id="icon" class="regular-text">
                  <p class="description"><?php esc_html_e('Icon filename or path for this media type.', 'heritagepress'); ?></p>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="thumb"><?php esc_html_e('Thumbnail', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" name="thumb" id="thumb" class="regular-text">
                  <p class="description"><?php esc_html_e('Thumbnail filename or path.', 'heritagepress'); ?></p>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="exportas"><?php esc_html_e('Export As', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" name="exportas" id="exportas" class="regular-text">
                  <p class="description"><?php esc_html_e('How this media type should be exported.', 'heritagepress'); ?></p>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="ordernum"><?php esc_html_e('Order Number', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="number" name="ordernum" id="ordernum" class="regular-text" value="0" min="0">
                  <p class="description"><?php esc_html_e('Display order (lower numbers appear first).', 'heritagepress'); ?></p>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="localpath"><?php esc_html_e('Local Path', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" name="localpath" id="localpath" class="regular-text">
                  <p class="description"><?php esc_html_e('Local file system path for this media type.', 'heritagepress'); ?></p>
                </td>
              </tr>

              <tr id="disabled-row" style="display: none;">
                <th scope="row">
                  <label for="disabled"><?php esc_html_e('Status', 'heritagepress'); ?></label>
                </th>
                <td>
                  <label for="disabled">
                    <input type="checkbox" name="disabled" id="disabled" value="1">
                    <?php esc_html_e('Disable this collection', 'heritagepress'); ?>
                  </label>
                </td>
              </tr>
            </tbody>
          </table>

          <p class="submit">
            <input type="submit" name="submit" id="submit-collection" class="button button-primary"
              value="<?php esc_attr_e('Add Collection', 'heritagepress'); ?>">
            <button type="button" id="cancel-collection" class="button">
              <?php esc_html_e('Cancel', 'heritagepress'); ?>
            </button>
          </p>
        </form>
      </div>
    </div>

    <!-- Collections List -->
    <div id="collections-list-container">
      <h2><?php esc_html_e('Existing Collections', 'heritagepress'); ?></h2>

      <?php if ($collections): ?>
        <table class="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <th scope="col" class="manage-column"><?php esc_html_e('ID', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php esc_html_e('Display Name', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php esc_html_e('Type', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php esc_html_e('Path', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php esc_html_e('Order', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php esc_html_e('Status', 'heritagepress'); ?></th>
              <th scope="col" class="manage-column"><?php esc_html_e('Actions', 'heritagepress'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($collections as $collection): ?>
              <tr data-collection-id="<?php echo esc_attr($collection->mediatypeID); ?>">
                <td><strong><?php echo esc_html($collection->mediatypeID); ?></strong></td>
                <td><?php echo esc_html($collection->display); ?></td>
                <td>
                  <?php
                  echo esc_html($collection->liketype);
                  if (in_array($collection->mediatypeID, $standard_collections)) {
                    echo ' <span class="standard-type">(' . esc_html__('Standard', 'heritagepress') . ')</span>';
                  }
                  ?>
                </td>
                <td><?php echo esc_html($collection->path); ?></td>
                <td><?php echo esc_html($collection->ordernum); ?></td>
                <td>
                  <?php if ($collection->disabled): ?>
                    <span class="status-disabled"><?php esc_html_e('Disabled', 'heritagepress'); ?></span>
                  <?php else: ?>
                    <span class="status-active"><?php esc_html_e('Active', 'heritagepress'); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <button type="button" class="button button-small edit-collection"
                    data-collection-id="<?php echo esc_attr($collection->mediatypeID); ?>">
                    <?php esc_html_e('Edit', 'heritagepress'); ?>
                  </button>
                  <?php if (!in_array($collection->mediatypeID, $standard_collections)): ?>
                    <button type="button" class="button button-small button-link-delete delete-collection"
                      data-collection-id="<?php echo esc_attr($collection->mediatypeID); ?>">
                      <?php esc_html_e('Delete', 'heritagepress'); ?>
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="notice notice-info">
          <p><?php esc_html_e('No media collections found. Add your first collection above.', 'heritagepress'); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div id="collection-messages"></div>
</div>

<style>
  .hp-media-collections-container {
    margin-top: 20px;
  }

  .collection-form-wrapper {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
  }

  .standard-type {
    color: #666;
    font-style: italic;
  }

  .status-active {
    color: #00a32a;
    font-weight: 600;
  }

  .status-disabled {
    color: #d63638;
    font-weight: 600;
  }

  .required {
    color: #d63638;
  }

  .button-small {
    padding: 2px 8px;
    font-size: 11px;
    line-height: 1.5;
    height: auto;
  }

  #collection-messages {
    margin-top: 20px;
  }

  .notice {
    margin: 5px 0 15px;
    padding: 1px 12px;
  }

  #collections-list-container {
    margin-top: 30px;
  }

  #collection-form-container.editing .form-table tr:first-child input {
    background-color: #f0f0f1;
    cursor: not-allowed;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Show/hide add form
    $('#add-collection-btn').click(function(e) {
      e.preventDefault();
      resetForm();
      $('#collection-form-container').show();
      $('#form-title').text('<?php echo esc_js(__('Add New Collection', 'heritagepress')); ?>');
      $('#form-action').val('hp_add_collection');
      $('#submit-collection').val('<?php echo esc_js(__('Add Collection', 'heritagepress')); ?>');
      $('#disabled-row').hide();
      $('#collection_id').prop('readonly', false).css('background-color', '');
    });

    // Cancel form
    $('#cancel-collection').click(function() {
      $('#collection-form-container').hide();
      resetForm();
    });

    // Edit collection
    $('.edit-collection').click(function() {
      var collectionId = $(this).data('collection-id');

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_get_media_collection',
          collection_id: collectionId,
          nonce: '<?php echo wp_create_nonce('hp_get_collection'); ?>'
        },
        success: function(response) {
          if (response.success) {
            populateForm(response.data);
            $('#collection-form-container').addClass('editing').show();
            $('#form-title').text('<?php echo esc_js(__('Edit Collection', 'heritagepress')); ?>');
            $('#form-action').val('hp_update_collection');
            $('#submit-collection').val('<?php echo esc_js(__('Update Collection', 'heritagepress')); ?>');
            $('#disabled-row').show();
            $('#collection_id').prop('readonly', true).css('background-color', '#f0f0f1');

            // Update nonce for update action
            $('input[name="hp_collection_nonce"]').val('<?php echo wp_create_nonce('hp_update_collection'); ?>');
          } else {
            showMessage(response.data, 'error');
          }
        },
        error: function() {
          showMessage('<?php echo esc_js(__('Error loading collection data.', 'heritagepress')); ?>', 'error');
        }
      });
    });

    // Delete collection
    $('.delete-collection').click(function() {
      var collectionId = $(this).data('collection-id');
      var $row = $(this).closest('tr');

      if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this collection?', 'heritagepress')); ?>')) {
        return;
      }

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_delete_media_collection',
          collection_id: collectionId,
          nonce: '<?php echo wp_create_nonce('hp_delete_collection'); ?>'
        },
        success: function(response) {
          if (response.success) {
            $row.fadeOut(300, function() {
              $(this).remove();
            });
            showMessage(response.data.message, 'success');
          } else {
            showMessage(response.data, 'error');
          }
        },
        error: function() {
          showMessage('<?php echo esc_js(__('Error deleting collection.', 'heritagepress')); ?>', 'error');
        }
      });
    });

    // Form submission
    $('#collection-form').submit(function(e) {
      e.preventDefault();

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
          if (response.success) {
            showMessage(response.data.message, 'success');
            $('#collection-form-container').hide();
            setTimeout(function() {
              location.reload();
            }, 1500);
          } else {
            showMessage(response.data, 'error');
          }
        },
        error: function() {
          showMessage('<?php echo esc_js(__('Error saving collection.', 'heritagepress')); ?>', 'error');
        }
      });
    });

    // Auto-generate collection ID from display name
    $('#display').on('input', function() {
      if ($('#collection_id').prop('readonly')) return;

      var display = $(this).val();
      var collectionId = display.toLowerCase()
        .replace(/[^a-z0-9\s_-]/g, '')
        .replace(/\s+/g, '_')
        .replace(/[-_]+/g, '_')
        .replace(/^_+|_+$/g, '');

      $('#collection_id').val(collectionId);
    });

    function resetForm() {
      $('#collection-form')[0].reset();
      $('#original-collection-id').val('');
      $('#collection-form-container').removeClass('editing');
      $('#collection_id').prop('readonly', false).css('background-color', '');

      // Reset nonce for add action
      $('input[name="hp_collection_nonce"]').val('<?php echo wp_create_nonce('hp_add_collection'); ?>');
    }

    function populateForm(data) {
      $('#collection_id').val(data.mediatypeID);
      $('#original-collection-id').val(data.mediatypeID);
      $('#display').val(data.display);
      $('#path').val(data.path);
      $('#liketype').val(data.liketype);
      $('#icon').val(data.icon);
      $('#thumb').val(data.thumb);
      $('#exportas').val(data.exportas);
      $('#ordernum').val(data.ordernum);
      $('#localpath').val(data.localpath);
      $('#disabled').prop('checked', data.disabled == 1);
    }

    function showMessage(message, type) {
      var className = type === 'success' ? 'notice-success' : 'notice-error';
      $('#collection-messages').html('<div class="notice ' + className + '"><p>' + message + '</p></div>');

      setTimeout(function() {
        $('#collection-messages .notice').fadeOut();
      }, 5000);
    }
  });
</script>
