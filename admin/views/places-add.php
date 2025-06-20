<?php

/**
 * HeritagePress Add New Place Interface
 *
 * Add new place form
 * Replicates admin_newplace.php functionality
 */

if (!defined('ABSPATH')) {
  exit;
}

// Initialize place controller if not already loaded
if (!class_exists('HP_Place_Controller')) {
  require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-place-controller.php';
}

// Get available trees
global $wpdb;
$trees_table = $wpdb->prefix . 'hp_trees';
$trees = $wpdb->get_results("SELECT gedcom, treename FROM {$trees_table} ORDER BY treename", ARRAY_A);

// Handle form submission
$message = '';
$error = '';

if (isset($_POST['submit_place'])) {
  // Verify nonce
  if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_add_place')) {
    $error = __('Security check failed.', 'heritagepress');
  } else {
    $place_controller = new HP_Place_Controller();
    $result = $place_controller->add_place($_POST);

    if (is_wp_error($result)) {
      $error = $result->get_error_message();
    } else {
      $message = __('Place added successfully.', 'heritagepress');

      // Redirect based on button clicked
      if (isset($_POST['submit_and_return'])) {
        wp_redirect(admin_url('admin.php?page=hp-places&message=' . urlencode($message)));
        exit;
      } else {
        wp_redirect(admin_url('admin.php?page=hp-places-edit&id=' . $result . '&message=' . urlencode($message)));
        exit;
      }
    }
  }
}

?>

<div class="wrap">
  <h1><?php _e('Add New Place', 'heritagepress'); ?></h1>

  <!-- Tab Navigation -->
  <nav class="nav-tab-wrapper">
    <a href="?page=hp-places" class="nav-tab"><?php _e('Search Places', 'heritagepress'); ?></a>
    <a href="?page=hp-places-add" class="nav-tab nav-tab-active"><?php _e('Add New', 'heritagepress'); ?></a>
    <a href="?page=hp-places-merge" class="nav-tab"><?php _e('Merge Places', 'heritagepress'); ?></a>
    <a href="?page=hp-places-geocode" class="nav-tab"><?php _e('Geocode', 'heritagepress'); ?></a>
  </nav>

  <?php if ($message): ?>
    <div class="notice notice-success is-dismissible">
      <p><?php echo esc_html($message); ?></p>
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="notice notice-error is-dismissible">
      <p><?php echo esc_html($error); ?></p>
    </div>
  <?php endif; ?>

  <!-- Add Place Form -->
  <div class="hp-form-section">
    <form method="post" id="add-place-form">
      <?php wp_nonce_field('hp_add_place'); ?>

      <table class="form-table">
        <?php if (!empty($trees)): ?>
          <tr>
            <th scope="row">
              <label for="gedcom"><?php _e('Tree:', 'heritagepress'); ?></label>
            </th>
            <td>
              <select name="gedcom" id="gedcom" class="regular-text">
                <option value=""><?php _e('Select Tree', 'heritagepress'); ?></option>
                <?php foreach ($trees as $tree): ?>
                  <option value="<?php echo esc_attr($tree['gedcom']); ?>"
                    <?php selected($_POST['gedcom'] ?? '', $tree['gedcom']); ?>>
                    <?php echo esc_html($tree['treename']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
        <?php endif; ?>

        <tr>
          <th scope="row">
            <label for="place"><?php _e('Place Name:', 'heritagepress'); ?> <span class="description">(<?php _e('required', 'heritagepress'); ?>)</span></label>
          </th>
          <td>
            <input type="text" name="place" id="place"
              value="<?php echo esc_attr($_POST['place'] ?? ''); ?>"
              class="large-text" required />
            <p class="description">
              <?php _e('Enter the full place name (e.g., "Chicago, Cook County, Illinois, USA").', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="placelevel"><?php _e('Place Level:', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="placelevel" id="placelevel">
              <option value=""><?php _e('No level designation', 'heritagepress'); ?></option>
              <option value="-1" <?php selected($_POST['placelevel'] ?? '', '-1'); ?>>
                <?php _e('Do not geocode', 'heritagepress'); ?>
              </option>
              <option value="1" <?php selected($_POST['placelevel'] ?? '', '1'); ?>>
                <?php _e('Country', 'heritagepress'); ?>
              </option>
              <option value="2" <?php selected($_POST['placelevel'] ?? '', '2'); ?>>
                <?php _e('State/Province', 'heritagepress'); ?>
              </option>
              <option value="3" <?php selected($_POST['placelevel'] ?? '', '3'); ?>>
                <?php _e('County', 'heritagepress'); ?>
              </option>
              <option value="4" <?php selected($_POST['placelevel'] ?? '', '4'); ?>>
                <?php _e('City/Town', 'heritagepress'); ?>
              </option>
              <option value="5" <?php selected($_POST['placelevel'] ?? '', '5'); ?>>
                <?php _e('Locality', 'heritagepress'); ?>
              </option>
              <option value="6" <?php selected($_POST['placelevel'] ?? '', '6'); ?>>
                <?php _e('Address', 'heritagepress'); ?>
              </option>
            </select>
            <p class="description">
              <?php _e('Specify the hierarchical level of this place for better geocoding accuracy.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label><?php _e('LDS Temple:', 'heritagepress'); ?></label>
          </th>
          <td>
            <label>
              <input type="checkbox" name="temple" value="1"
                <?php checked($_POST['temple'] ?? ''); ?>>
              <?php _e('This is an LDS Temple', 'heritagepress'); ?>
            </label>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="latitude"><?php _e('Latitude:', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="latitude" id="latitude"
              value="<?php echo esc_attr($_POST['latitude'] ?? ''); ?>"
              class="regular-text" placeholder="<?php _e('e.g., 41.8781', 'heritagepress'); ?>" />
            <p class="description">
              <?php _e('Enter decimal degrees format (positive for North, negative for South).', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="longitude"><?php _e('Longitude:', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="longitude" id="longitude"
              value="<?php echo esc_attr($_POST['longitude'] ?? ''); ?>"
              class="regular-text" placeholder="<?php _e('e.g., -87.6298', 'heritagepress'); ?>" />
            <p class="description">
              <?php _e('Enter decimal degrees format (positive for East, negative for West).', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="zoom"><?php _e('Map Zoom Level:', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="number" name="zoom" id="zoom" min="1" max="20"
              value="<?php echo esc_attr($_POST['zoom'] ?? ''); ?>"
              class="small-text" />
            <p class="description">
              <?php _e('Zoom level for map display (1-20). Default is 13 if coordinates are provided.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="notes"><?php _e('Notes:', 'heritagepress'); ?></label>
          </th>
          <td>
            <textarea name="notes" id="notes" rows="5" class="large-text">
<?php echo esc_textarea($_POST['notes'] ?? ''); ?>
</textarea>
            <p class="description">
              <?php _e('Additional information about this place.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>
      </table>

      <!-- Map Section (placeholder for future Google Maps integration) -->
      <div id="place-map-container" style="display: none;">
        <h3><?php _e('Map Location', 'heritagepress'); ?></h3>
        <div id="place-map" style="height: 400px; border: 1px solid #ddd;"></div>
        <p class="description">
          <?php _e('Click on the map to set coordinates, or enter them manually above.', 'heritagepress'); ?>
        </p>
      </div>

      <p class="submit">
        <input type="submit" name="submit_place" class="button-primary"
          value="<?php _e('Add Place and Continue Editing', 'heritagepress'); ?>">
        <input type="submit" name="submit_and_return" class="button"
          value="<?php _e('Add Place and Return to List', 'heritagepress'); ?>">
        <a href="?page=hp-places" class="button"><?php _e('Cancel', 'heritagepress'); ?></a>
      </p>
    </form>
  </div>
</div>

<style>
  .hp-form-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
  }

  #place-map-container {
    margin-top: 20px;
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
  }

  .form-table th {
    width: 200px;
  }

  .description {
    color: #666;
    font-style: italic;
  }

  .required {
    color: #d63638;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Form validation
    $('#add-place-form').on('submit', function(e) {
      var placeName = $('#place').val().trim();

      if (!placeName) {
        alert('<?php _e('Please enter a place name.', 'heritagepress'); ?>');
        e.preventDefault();
        $('#place').focus();
        return false;
      }
    });

    // Auto-set zoom level when coordinates are entered
    $('#latitude, #longitude').on('blur', function() {
      var lat = $('#latitude').val().trim();
      var lng = $('#longitude').val().trim();
      var zoom = $('#zoom').val();

      if (lat && lng && !zoom) {
        $('#zoom').val(13);
      }
    });

    // Coordinate format validation
    $('#latitude').on('blur', function() {
      var lat = parseFloat($(this).val());
      if ($(this).val() && (isNaN(lat) || lat < -90 || lat > 90)) {
        alert('<?php _e('Latitude must be a number between -90 and 90.', 'heritagepress'); ?>');
        $(this).focus();
      }
    });

    $('#longitude').on('blur', function() {
      var lng = parseFloat($(this).val());
      if ($(this).val() && (isNaN(lng) || lng < -180 || lng > 180)) {
        alert('<?php _e('Longitude must be a number between -180 and 180.', 'heritagepress'); ?>');
        $(this).focus();
      }
    });

    // Future: Initialize Google Maps
    // initializeMap();
  });

  // Placeholder for future Google Maps integration
  function initializeMap() {
    // This would initialize Google Maps when API is integrated
    console.log('Map initialization placeholder');
  }
</script>
