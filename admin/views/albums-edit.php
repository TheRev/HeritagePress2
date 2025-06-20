<?php

/**
 * Edit Album View for HeritagePress
 *
 * This file provides the edit album interface for the WordPress admin.
 * It allows users to modify existing albums with attributes such as name, description, keywords, and status.
 * It includes form validation, character counting for the album name, and AJAX handling for form submission.
 * * It also provides a section to manage media items associated with the album.
 * * This view is part of the HeritagePress plugin, which manages heritage and genealogy data.
 *
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Get album ID from URL
$album_id = isset($_GET['albumID']) ? intval($_GET['albumID']) : 0;
$added = isset($_GET['added']) ? true : false;

if (!$album_id) {
  wp_redirect(admin_url('admin.php?page=heritagepress&section=albums'));
  exit;
}

// Get album data
global $wpdb;
$album = $wpdb->get_row($wpdb->prepare(
  "SELECT * FROM {$wpdb->prefix}hp_albums WHERE albumID = %d",
  $album_id
));

if (!$album) {
  wp_redirect(admin_url('admin.php?page=heritagepress&section=albums'));
  exit;
}

// Get media count
$media_count = $wpdb->get_var($wpdb->prepare(
  "SELECT COUNT(*) FROM {$wpdb->prefix}hp_albumlinks WHERE albumID = %d",
  $album_id
));
?>

<div class="wrap">
  <h1 class="wp-heading-inline"><?php esc_html_e('Edit Album', 'heritagepress'); ?>: <?php echo esc_html($album->albumname); ?></h1>
  <a href="<?php echo admin_url('admin.php?page=heritagepress&section=albums'); ?>" class="page-title-action">
    <?php esc_html_e('Back to Albums', 'heritagepress'); ?>
  </a>
  <a href="<?php echo admin_url('admin.php?page=heritagepress&section=albums&tab=manage&albumID=' . $album_id); ?>" class="page-title-action">
    <?php esc_html_e('Manage Media', 'heritagepress'); ?>
  </a>
  <hr class="wp-header-end">

  <?php if ($added): ?>
    <div class="notice notice-success is-dismissible">
      <p><?php esc_html_e('Album created successfully! You can now add media items to this album.', 'heritagepress'); ?></p>
    </div>
  <?php endif; ?>

  <div class="hp-edit-album-container">
    <!-- Album Information Tab -->
    <div class="album-tabs">
      <div class="nav-tab-wrapper">
        <a href="#album-info" class="nav-tab nav-tab-active" id="album-info-tab">
          <?php esc_html_e('Album Information', 'heritagepress'); ?>
        </a>
        <a href="#album-media" class="nav-tab" id="album-media-tab">
          <?php esc_html_e('Media Items', 'heritagepress'); ?> (<?php echo esc_html($media_count); ?>)
        </a>
      </div>
    </div>

    <!-- Album Information Panel -->
    <div id="album-info-panel" class="tab-panel active">
      <div class="album-form-wrapper">
        <h3><?php esc_html_e('Album Details', 'heritagepress'); ?></h3>

        <form id="edit-album-form" method="post" action="">
          <?php wp_nonce_field('hp_update_album', 'hp_album_nonce'); ?>
          <input type="hidden" name="action" value="hp_update_album">
          <input type="hidden" name="albumID" value="<?php echo esc_attr($album_id); ?>">

          <table class="form-table">
            <tbody>
              <tr>
                <th scope="row">
                  <label for="albumname"><?php esc_html_e('Album Name', 'heritagepress'); ?> <span class="required">*</span></label>
                </th>
                <td>
                  <input type="text" name="albumname" id="albumname" class="regular-text"
                    value="<?php echo esc_attr($album->albumname); ?>" required maxlength="100">
                  <p class="description"><?php esc_html_e('A unique name for this album.', 'heritagepress'); ?></p>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="description"><?php esc_html_e('Description', 'heritagepress'); ?></label>
                </th>
                <td>
                  <textarea name="description" id="description" rows="4" cols="50" class="large-text"><?php echo esc_textarea($album->description); ?></textarea>
                  <p class="description"><?php esc_html_e('Optional description of this album.', 'heritagepress'); ?></p>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label for="keywords"><?php esc_html_e('Keywords', 'heritagepress'); ?></label>
                </th>
                <td>
                  <input type="text" name="keywords" id="keywords" class="regular-text"
                    value="<?php echo esc_attr($album->keywords); ?>">
                  <p class="description"><?php esc_html_e('Keywords for searching and categorizing this album.', 'heritagepress'); ?></p>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label><?php esc_html_e('Status', 'heritagepress'); ?></label>
                </th>
                <td>
                  <fieldset>
                    <legend class="screen-reader-text"><?php esc_html_e('Album Status', 'heritagepress'); ?></legend>

                    <label for="active">
                      <input type="checkbox" name="active" id="active" value="1" <?php checked($album->active, 1); ?>>
                      <?php esc_html_e('Active', 'heritagepress'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Whether this album is active and visible.', 'heritagepress'); ?></p>

                    <label for="alwayson">
                      <input type="checkbox" name="alwayson" id="alwayson" value="1" <?php checked($album->alwayson, 1); ?>>
                      <?php esc_html_e('Always On', 'heritagepress'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Whether this album should always be displayed.', 'heritagepress'); ?></p>
                  </fieldset>
                </td>
              </tr>
            </tbody>
          </table>

          <p class="submit">
            <input type="submit" name="submit" id="submit-album" class="button button-primary" value="<?php esc_attr_e('Update Album', 'heritagepress'); ?>">
            <a href="<?php echo admin_url('admin.php?page=heritagepress&section=albums'); ?>" class="button">
              <?php esc_html_e('Cancel', 'heritagepress'); ?>
            </a>
          </p>
        </form>

        <!-- Delete Album Section -->
        <div class="delete-album-section">
          <h3><?php esc_html_e('Delete Album', 'heritagepress'); ?></h3>
          <p><?php esc_html_e('Permanently delete this album and all its media associations. This action cannot be undone.', 'heritagepress'); ?></p>

          <form id="delete-album-form" method="post" action="" style="display: inline;">
            <?php wp_nonce_field('hp_delete_album', 'hp_album_nonce'); ?>
            <input type="hidden" name="action" value="hp_delete_album">
            <input type="hidden" name="albumID" value="<?php echo esc_attr($album_id); ?>">

            <button type="button" id="delete-album-btn" class="button button-link-delete">
              <?php esc_html_e('Delete Album', 'heritagepress'); ?>
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Media Items Panel -->
    <div id="album-media-panel" class="tab-panel">
      <div class="media-panel-wrapper">
        <h3><?php esc_html_e('Media Items in Album', 'heritagepress'); ?></h3>

        <div class="media-actions">
          <a href="<?php echo admin_url('admin.php?page=heritagepress&section=albums&tab=manage&albumID=' . $album_id); ?>"
            class="button button-primary">
            <?php esc_html_e('Manage Album Media', 'heritagepress'); ?>
          </a>
        </div>

        <div id="album-media-list">
          <?php if ($media_count > 0): ?>
            <p><?php printf(esc_html__('This album contains %d media items.', 'heritagepress'), $media_count); ?></p>
            <p>
              <a href="<?php echo admin_url('admin.php?page=heritagepress&section=albums&tab=manage&albumID=' . $album_id); ?>">
                <?php esc_html_e('View and manage media items →', 'heritagepress'); ?>
              </a>
            </p>
          <?php else: ?>
            <div class="notice notice-info inline">
              <p><?php esc_html_e('This album does not contain any media items yet.', 'heritagepress'); ?></p>
              <p>
                <a href="<?php echo admin_url('admin.php?page=heritagepress&section=albums&tab=manage&albumID=' . $album_id); ?>">
                  <?php esc_html_e('Add media items to this album →', 'heritagepress'); ?>
                </a>
              </p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div id="album-messages"></div>
</div>

<style>
  .hp-edit-album-container {
    margin-top: 20px;
  }

  .album-tabs {
    margin-bottom: 0;
  }

  .nav-tab-wrapper {
    border-bottom: 1px solid #ccd0d4;
    margin: 0;
    padding-top: 9px;
    padding-bottom: 0;
    line-height: inherit;
  }

  .nav-tab {
    border: 1px solid #ccd0d4;
    border-bottom: none;
    background: #f6f7f7;
    color: #0073aa;
    text-decoration: none;
    padding: 8px 14px;
    font-size: 12px;
    line-height: 16px;
    display: inline-block;
    margin: -1px 4px -1px 0;
    font-weight: 600;
  }

  .nav-tab-active,
  .nav-tab:focus {
    border-bottom: 1px solid #f1f1f1;
    background: #f1f1f1;
    color: #000;
  }

  .tab-panel {
    display: none;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-top: none;
    padding: 20px;
  }

  .tab-panel.active {
    display: block;
  }

  .album-form-wrapper,
  .media-panel-wrapper {
    max-width: 800px;
  }

  .required {
    color: #d63638;
  }

  .form-table th {
    width: 200px;
    vertical-align: top;
    padding-top: 15px;
  }

  .form-table td {
    vertical-align: top;
    padding-top: 10px;
  }

  .form-table .description {
    margin-top: 5px;
    margin-bottom: 0;
    color: #646970;
    font-size: 13px;
    font-style: italic;
  }

  .form-table fieldset {
    border: none;
    padding: 0;
    margin: 0;
  }

  .form-table fieldset label {
    display: block;
    margin-bottom: 8px;
    font-weight: 400;
  }

  .form-table fieldset input[type="checkbox"] {
    margin-right: 8px;
  }

  .delete-album-section {
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
  }

  .delete-album-section h3 {
    color: #d63638;
  }

  .media-actions {
    margin: 20px 0;
  }

  #album-messages {
    margin-top: 20px;
  }

  .notice {
    margin: 5px 0 15px;
    padding: 1px 12px;
  }

  .notice.inline {
    display: inline-block;
    margin: 5px 0;
    padding: 12px;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').click(function(e) {
      e.preventDefault();

      var target = $(this).attr('href');

      // Update active tab
      $('.nav-tab').removeClass('nav-tab-active');
      $(this).addClass('nav-tab-active');

      // Show/hide panels
      $('.tab-panel').removeClass('active');
      $(target + '-panel').addClass('active');
    });

    // Form validation
    $('#edit-album-form').submit(function(e) {
      var albumName = $('#albumname').val().trim();

      if (!albumName) {
        e.preventDefault();
        showMessage('<?php echo esc_js(__('Album name is required.', 'heritagepress')); ?>', 'error');
        $('#albumname').focus();
        return false;
      }

      if (albumName.length > 100) {
        e.preventDefault();
        showMessage('<?php echo esc_js(__('Album name must be 100 characters or less.', 'heritagepress')); ?>', 'error');
        $('#albumname').focus();
        return false;
      }

      // Show loading state
      $('#submit-album').prop('disabled', true).val('<?php echo esc_js(__('Updating Album...', 'heritagepress')); ?>');
    });

    // Delete album confirmation
    $('#delete-album-btn').click(function() {
      if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this album?', 'heritagepress')); ?>\n\n<?php echo esc_js(__('This will permanently delete the album and remove all media associations. This action cannot be undone.', 'heritagepress')); ?>')) {
        return;
      }

      $('#delete-album-form').submit();
    });

    // Character counter for album name
    $('#albumname').on('input', function() {
      var currentLength = $(this).val().length;
      var maxLength = 100;
      var remaining = maxLength - currentLength;

      var $counter = $('#albumname-counter');
      if ($counter.length === 0) {
        $counter = $('<span id="albumname-counter" class="character-counter"></span>');
        $(this).parent().find('.description').after($counter);
      }

      $counter.text(remaining + ' characters remaining');

      if (remaining < 0) {
        $counter.addClass('over-limit').css('color', '#d63638');
      } else if (remaining < 20) {
        $counter.removeClass('over-limit').css('color', '#dba617');
      } else {
        $counter.removeClass('over-limit').css('color', '#646970');
      }
    });

    function showMessage(message, type) {
      var className = type === 'success' ? 'notice-success' : 'notice-error';
      $('#album-messages').html('<div class="notice ' + className + '"><p>' + message + '</p></div>');

      $('html, body').animate({
        scrollTop: $('#album-messages').offset().top - 50
      }, 500);

      setTimeout(function() {
        $('#album-messages .notice').fadeOut();
      }, 5000);
    }
  });
</script>
