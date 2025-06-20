<?php

/**
 * Add Album View for HeritagePress
 *
 * This file provides the add album interface for the WordPress admin.
 * It allows users to create new albums with various attributes such as name, description, keywords, and status.
 * It includes form validation, character counting for the album name, and AJAX handling for form submission.
 *
 *
 * @package HeritagePress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}
?>

<div class="wrap">
  <h1 class="wp-heading-inline"><?php esc_html_e('Add New Album', 'heritagepress'); ?></h1>
  <a href="<?php echo admin_url('admin.php?page=heritagepress&section=albums'); ?>" class="page-title-action">
    <?php esc_html_e('Back to Albums', 'heritagepress'); ?>
  </a>
  <hr class="wp-header-end">

  <div class="hp-add-album-container">
    <div class="album-form-wrapper">
      <h2><?php esc_html_e('Album Information', 'heritagepress'); ?></h2>

      <form id="add-album-form" method="post" action="">
        <?php wp_nonce_field('hp_add_album', 'hp_album_nonce'); ?>
        <input type="hidden" name="action" value="hp_add_album">

        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="albumname"><?php esc_html_e('Album Name', 'heritagepress'); ?> <span class="required">*</span></label>
              </th>
              <td>
                <input type="text" name="albumname" id="albumname" class="regular-text" required maxlength="100">
                <p class="description"><?php esc_html_e('A unique name for this album.', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="description"><?php esc_html_e('Description', 'heritagepress'); ?></label>
              </th>
              <td>
                <textarea name="description" id="description" rows="4" cols="50" class="large-text"></textarea>
                <p class="description"><?php esc_html_e('Optional description of this album.', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="keywords"><?php esc_html_e('Keywords', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="keywords" id="keywords" class="regular-text">
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
                    <input type="checkbox" name="active" id="active" value="1" checked>
                    <?php esc_html_e('Active', 'heritagepress'); ?>
                  </label>
                  <p class="description"><?php esc_html_e('Whether this album is active and visible.', 'heritagepress'); ?></p>

                  <label for="alwayson">
                    <input type="checkbox" name="alwayson" id="alwayson" value="1">
                    <?php esc_html_e('Always On', 'heritagepress'); ?>
                  </label>
                  <p class="description"><?php esc_html_e('Whether this album should always be displayed.', 'heritagepress'); ?></p>
                </fieldset>
              </td>
            </tr>
          </tbody>
        </table>

        <p class="submit">
          <input type="submit" name="submit" id="submit-album" class="button button-primary" value="<?php esc_attr_e('Create Album', 'heritagepress'); ?>">
          <a href="<?php echo admin_url('admin.php?page=heritagepress&section=albums'); ?>" class="button">
            <?php esc_html_e('Cancel', 'heritagepress'); ?>
          </a>
        </p>
      </form>
    </div>
  </div>

  <div id="album-messages"></div>
</div>

<style>
  .hp-add-album-container {
    margin-top: 20px;
  }

  .album-form-wrapper {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
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

  .form-table fieldset legend {
    padding: 0;
  }

  .form-table fieldset label {
    display: block;
    margin-bottom: 8px;
    font-weight: 400;
  }

  .form-table fieldset input[type="checkbox"] {
    margin-right: 8px;
  }

  #album-messages {
    margin-top: 20px;
  }

  .notice {
    margin: 5px 0 15px;
    padding: 1px 12px;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Form validation
    $('#add-album-form').submit(function(e) {
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
      $('#submit-album').prop('disabled', true).val('<?php echo esc_js(__('Creating Album...', 'heritagepress')); ?>');
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
