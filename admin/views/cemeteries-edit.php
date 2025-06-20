<?php

/**
 * Cemetery Edit Form View
 *
 * @package HeritagePress
 * @subpackage Admin/Views
 */

if (!defined('ABSPATH')) {
  exit;
}

// Cemetery data is passed from controller as $cemetery
if (!isset($cemetery) || !$cemetery) {
  wp_die(__('Cemetery not found.', 'heritagepress'));
}

// Get states and countries for dropdowns
$controller = new HP_Cemetery_Controller();
$states = $controller->get_states();
$countries = $controller->get_countries();

// Show messages
if (isset($_GET['error'])) {
  echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(urldecode($_GET['error'])) . '</p></div>';
}
?>

<div class="wrap">
  <h1 class="wp-heading-inline">
    <?php _e('Cemetery Management', 'heritagepress'); ?> &gt;&gt; <?php _e('Edit Cemetery', 'heritagepress'); ?>
  </h1>

  <hr class="wp-header-end">

  <form method="post" enctype="multipart/form-data" id="cemetery-form">
    <?php wp_nonce_field('hp_cemetery_action', 'hp_cemetery_nonce'); ?>
    <input type="hidden" name="hp_cemetery_action" value="update">
    <input type="hidden" name="cemeteryID" value="<?php echo esc_attr($cemetery->cemeteryID); ?>">

    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row">
            <label for="cemname"><?php _e('Cemetery Name', 'heritagepress'); ?> <span class="required">*</span></label>
          </th>
          <td>
            <input type="text" name="cemname" id="cemname" class="regular-text"
              value="<?php echo esc_attr($cemetery->cemname); ?>" required>
            <p class="description"><?php _e('Enter the name of the cemetery.', 'heritagepress'); ?></p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="newfile"><?php _e('Upload New Map File', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="file" name="newfile" id="newfile" accept=".jpg,.jpeg,.png,.gif,.pdf">
            <p class="description">
              <?php _e('Upload a new cemetery map to replace the current one. Allowed formats: JPG, PNG, GIF, PDF', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="maplink"><?php _e('Current Map File Path', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="maplink" id="maplink" class="regular-text"
              value="<?php echo esc_attr($cemetery->maplink); ?>">
            <?php if (!empty($cemetery->maplink)): ?>
              <div class="current-map">
                <p><strong><?php _e('Current map:', 'heritagepress'); ?></strong> <?php echo esc_html($cemetery->maplink); ?></p>
                <?php
                $upload_dir = wp_upload_dir();
                $map_path = $upload_dir['basedir'] . '/' . $cemetery->maplink;
                $map_url = $upload_dir['baseurl'] . '/' . $cemetery->maplink;

                if (file_exists($map_path)):
                  $file_ext = strtolower(pathinfo($cemetery->maplink, PATHINFO_EXTENSION));
                  if (in_array($file_ext, array('jpg', 'jpeg', 'png', 'gif'))):
                ?>
                    <div class="map-preview">
                      <img src="<?php echo esc_url($map_url); ?>" alt="<?php _e('Cemetery Map', 'heritagepress'); ?>"
                        style="max-width: 300px; max-height: 200px; border: 1px solid #ccd0d4;">
                    </div>
                  <?php endif; ?>
                  <p>
                    <a href="<?php echo esc_url($map_url); ?>" target="_blank" class="button button-small">
                      <?php _e('View Current Map', 'heritagepress'); ?>
                    </a>
                  </p>
                <?php else: ?>
                  <p class="error"><?php _e('Map file not found on server.', 'heritagepress'); ?></p>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <p class="description">
              <?php _e('Path to map file. Upload a new file above to replace, or edit the path directly.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="city"><?php _e('City', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="city" id="city" class="regular-text"
              value="<?php echo esc_attr($cemetery->city); ?>">
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="county"><?php _e('County/Parish', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="county" id="county" class="regular-text"
              value="<?php echo esc_attr($cemetery->county); ?>">
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="state"><?php _e('State/Province', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="state" id="state" class="regular-text">
              <option value=""><?php _e('Select State/Province', 'heritagepress'); ?></option>
              <?php foreach ($states as $state): ?>
                <option value="<?php echo esc_attr($state->state); ?>"
                  <?php selected($cemetery->state, $state->state); ?>>
                  <?php echo esc_html($state->state); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="entity-actions">
              <button type="button" class="button button-small add-entity" data-entity-type="state">
                <?php _e('Add New', 'heritagepress'); ?>
              </button>
              <button type="button" class="button button-small delete-entity" data-entity-type="state">
                <?php _e('Delete Selected', 'heritagepress'); ?>
              </button>
            </div>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="country"><?php _e('Country', 'heritagepress'); ?> <span class="required">*</span></label>
          </th>
          <td>
            <select name="country" id="country" class="regular-text" required>
              <option value=""><?php _e('Select Country', 'heritagepress'); ?></option>
              <?php foreach ($countries as $country): ?>
                <option value="<?php echo esc_attr($country->country); ?>"
                  <?php selected($cemetery->country, $country->country); ?>>
                  <?php echo esc_html($country->country); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="entity-actions">
              <button type="button" class="button button-small add-entity" data-entity-type="country">
                <?php _e('Add New', 'heritagepress'); ?>
              </button>
              <button type="button" class="button button-small delete-entity" data-entity-type="country">
                <?php _e('Delete Selected', 'heritagepress'); ?>
              </button>
            </div>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="place"><?php _e('Linked Place', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="place" id="place" class="regular-text"
              value="<?php echo esc_attr($cemetery->place); ?>">
            <button type="button" class="button find-place">
              <?php _e('Find Place', 'heritagepress'); ?>
            </button>
            <button type="button" class="button fill-place">
              <?php _e('Fill from Location', 'heritagepress'); ?>
            </button>
            <p class="description">
              <?php _e('Link this cemetery to an existing place, or create a new place.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row"></th>
          <td>
            <label>
              <input type="checkbox" name="usecoords" value="1" checked>
              <?php _e('Use cemetery coordinates for place', 'heritagepress'); ?>
            </label>
            <p class="description">
              <?php _e('When linking to a place, use the cemetery coordinates to update or create the place coordinates.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <!-- Map Section -->
        <tr>
          <th scope="row"><?php _e('Map Location', 'heritagepress'); ?></th>
          <td>
            <div id="cemetery-map" style="height: 400px; width: 100%; margin-bottom: 10px; border: 1px solid #ccd0d4;">
              <p style="text-align: center; padding: 50px; color: #666;">
                <?php _e('Map integration would be implemented here with Google Maps or similar service.', 'heritagepress'); ?>
                <br><br>
                <?php if (!empty($cemetery->latitude) && !empty($cemetery->longitude)): ?>
                  <?php printf(__('Current location: %s, %s', 'heritagepress'), $cemetery->latitude, $cemetery->longitude); ?>
                  <br>
                <?php endif; ?>
                <?php _e('Users could click on the map to update coordinates, or enter them manually below.', 'heritagepress'); ?>
              </p>
            </div>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="latitude"><?php _e('Latitude', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="latitude" id="latitude" class="regular-text"
              value="<?php echo esc_attr($cemetery->latitude); ?>"
              pattern="^-?([1-8]?[0-9]\.{1}\d{1,6}$|90\.{1}0{1,6}$)">
            <p class="description"><?php _e('Decimal degrees format (e.g., 40.712776)', 'heritagepress'); ?></p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="longitude"><?php _e('Longitude', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="longitude" id="longitude" class="regular-text"
              value="<?php echo esc_attr($cemetery->longitude); ?>"
              pattern="^-?([1]?[0-7][0-9]\.{1}\d{1,6}$|180\.{1}0{1,6}$|[1-9]?[0-9]\.{1}\d{1,6}$)">
            <p class="description"><?php _e('Decimal degrees format (e.g., -74.005974)', 'heritagepress'); ?></p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="zoom"><?php _e('Map Zoom Level', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="number" name="zoom" id="zoom" min="1" max="20" class="small-text"
              value="<?php echo esc_attr($cemetery->zoom); ?>">
            <p class="description"><?php _e('Zoom level for map display (1-20, default 13)', 'heritagepress'); ?></p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="notes"><?php _e('Notes', 'heritagepress'); ?></label>
          </th>
          <td>
            <?php
            wp_editor($cemetery->notes, 'notes', array(
              'textarea_name' => 'notes',
              'textarea_rows' => 8,
              'media_buttons' => false,
              'teeny' => true,
              'quicktags' => true
            ));
            ?>
            <p class="description"><?php _e('Additional information about the cemetery.', 'heritagepress'); ?></p>
          </td>
        </tr>
      </tbody>
    </table>

    <p class="submit">
      <input type="submit" name="submit" class="button-primary" value="<?php _e('Update Cemetery', 'heritagepress'); ?>">
      <a href="<?php echo admin_url('admin.php?page=hp-cemeteries'); ?>" class="button">
        <?php _e('Cancel', 'heritagepress'); ?>
      </a>
      <?php if (!empty($cemetery->latitude) && !empty($cemetery->longitude)): ?>
        <a href="#" class="button test-map" data-lat="<?php echo esc_attr($cemetery->latitude); ?>"
          data-lng="<?php echo esc_attr($cemetery->longitude); ?>" data-zoom="<?php echo esc_attr($cemetery->zoom ?: 13); ?>">
          <?php _e('Test Map Location', 'heritagepress'); ?>
        </a>
      <?php endif; ?>
    </p>

    <p class="required-note">
      <span class="required">*</span> <?php _e('Required fields', 'heritagepress'); ?>
    </p>
  </form>

  <!-- Cemetery Info -->
  <div class="cemetery-info">
    <h3><?php _e('Cemetery Information', 'heritagepress'); ?></h3>
    <table class="form-table">
      <tr>
        <th scope="row"><?php _e('Cemetery ID', 'heritagepress'); ?>:</th>
        <td><?php echo esc_html($cemetery->cemeteryID); ?></td>
      </tr>
      <?php if (!empty($cemetery->maplink)): ?>
        <tr>
          <th scope="row"><?php _e('Map File', 'heritagepress'); ?>:</th>
          <td>
            <?php echo esc_html($cemetery->maplink); ?>
            <?php
            $upload_dir = wp_upload_dir();
            $map_path = $upload_dir['basedir'] . '/' . $cemetery->maplink;
            if (file_exists($map_path)):
              $file_size = size_format(filesize($map_path));
            ?>
              <br><small><?php printf(__('File size: %s', 'heritagepress'), $file_size); ?></small>
            <?php endif; ?>
          </td>
        </tr>
      <?php endif; ?>
    </table>
  </div>
</div>

<!-- Entity Management Modal -->
<div id="entity-modal" style="display: none;">
  <div class="entity-modal-content">
    <h3 id="entity-modal-title"></h3>
    <form id="entity-form">
      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="entity-name"><?php _e('Name', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" id="entity-name" name="entity_name" class="regular-text" required>
          </td>
        </tr>
      </table>
      <p class="submit">
        <button type="submit" class="button-primary"><?php _e('Add', 'heritagepress'); ?></button>
        <button type="button" class="button cancel-entity"><?php _e('Cancel', 'heritagepress'); ?></button>
      </p>
    </form>
  </div>
</div>

<style>
  .required {
    color: #d63638;
  }

  .entity-actions {
    margin-top: 5px;
  }

  .entity-actions .button {
    margin-right: 5px;
  }

  .required-note {
    font-style: italic;
    color: #666;
  }

  .find-place,
  .fill-place {
    margin-left: 5px;
  }

  .current-map {
    margin-top: 10px;
    padding: 10px;
    background: #f9f9f9;
    border: 1px solid #e1e1e1;
    border-radius: 3px;
  }

  .map-preview {
    margin: 10px 0;
  }

  .cemetery-info {
    margin-top: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 5px;
  }

  .cemetery-info h3 {
    margin-top: 0;
  }

  /* Entity Modal Styles */
  #entity-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 100000;
  }

  .entity-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    border-radius: 5px;
    min-width: 400px;
    max-width: 90%;
  }

  @media (max-width: 782px) {
    .entity-modal-content {
      min-width: 90%;
      padding: 15px;
    }

    .entity-actions .button {
      display: block;
      margin: 2px 0;
      width: 100%;
    }
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Auto-populate map link from file upload
    $('#newfile').change(function() {
      if (this.files && this.files[0]) {
        var filename = this.files[0].name;
        $('#maplink').val('heritagepress/maps/' + filename);
      }
    });

    // Fill place from location fields
    $('.fill-place').click(function() {
      var parts = [];
      if ($('#city').val()) parts.push($('#city').val());
      if ($('#county').val()) parts.push($('#county').val());
      if ($('#state').val()) parts.push($('#state').val());
      if ($('#country').val()) parts.push($('#country').val());

      $('#place').val(parts.join(', '));
    });

    // Test map location
    $('.test-map').click(function(e) {
      e.preventDefault();
      var lat = $(this).data('lat');
      var lng = $(this).data('lng');
      var zoom = $(this).data('zoom');

      // Simple implementation - opens Google Maps
      var url = 'https://www.google.com/maps/@' + lat + ',' + lng + ',' + zoom + 'z';
      window.open(url, '_blank');
    });

    // Entity management
    $('.add-entity').click(function() {
      var entityType = $(this).data('entity-type');
      $('#entity-modal-title').text('<?php _e('Add New', 'heritagepress'); ?> ' + entityType.charAt(0).toUpperCase() + entityType.slice(1));
      $('#entity-form').data('entity-type', entityType);
      $('#entity-modal').show();
      $('#entity-name').focus();
    });

    $('.cancel-entity').click(function() {
      $('#entity-modal').hide();
      $('#entity-name').val('');
    });

    $('#entity-form').submit(function(e) {
      e.preventDefault();
      var entityType = $(this).data('entity-type');
      var entityName = $('#entity-name').val().trim();

      if (!entityName) {
        alert('<?php _e('Please enter a name.', 'heritagepress'); ?>');
        return;
      }

      // Add to select box
      var selectId = entityType === 'state' ? '#state' : '#country';
      var option = new Option(entityName, entityName);
      $(selectId).append(option);
      $(selectId).val(entityName);

      // Close modal
      $('#entity-modal').hide();
      $('#entity-name').val('');

      // In a real implementation, this would also save to database via AJAX
      alert('<?php _e('Entity added. Note: This will be saved when you save the cemetery.', 'heritagepress'); ?>');
    });

    $('.delete-entity').click(function() {
      var entityType = $(this).data('entity-type');
      var selectId = entityType === 'state' ? '#state' : '#country';
      var selected = $(selectId).val();

      if (!selected) {
        alert('<?php _e('Please select an item to delete.', 'heritagepress'); ?>');
        return;
      }

      if (confirm('<?php _e('Are you sure you want to delete', 'heritagepress'); ?> "' + selected + '"?')) {
        $(selectId + ' option:selected').remove();
        // In a real implementation, this would also delete from database via AJAX
      }
    });

    // Form validation
    $('#cemetery-form').submit(function(e) {
      var cemname = $('#cemname').val().trim();
      var country = $('#country').val();

      if (!cemname) {
        alert('<?php _e('Cemetery name is required.', 'heritagepress'); ?>');
        $('#cemname').focus();
        e.preventDefault();
        return false;
      }

      if (!country) {
        alert('<?php _e('Country is required.', 'heritagepress'); ?>');
        $('#country').focus();
        e.preventDefault();
        return false;
      }

      return true;
    });

    // Coordinate validation
    $('#latitude, #longitude').blur(function() {
      var value = $(this).val();
      if (value && value.includes(',')) {
        $(this).val(value.replace(/,/g, '.'));
      }
    });

    // Auto-set zoom when coordinates are entered
    $('#latitude, #longitude').blur(function() {
      var lat = $('#latitude').val();
      var lng = $('#longitude').val();
      var zoom = $('#zoom').val();

      if (lat && lng && !zoom) {
        $('#zoom').val(13);
      }
    });
  });
</script>
