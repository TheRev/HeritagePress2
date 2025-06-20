<?php

/**
 * HeritagePress Edit Place Interface
 *
 * Edit existing place form
 * Replicates admin_editplace.php functionality
 */

if (!defined('ABSPATH')) {
  exit;
}

// Initialize place controller if not already loaded
if (!class_exists('HP_Place_Controller')) {
  require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-place-controller.php';
}

$place_controller = new HP_Place_Controller();

// Get place ID
$place_id = intval($_GET['id'] ?? 0);
if (!$place_id) {
  wp_die(__('Invalid place ID.', 'heritagepress'));
}

// Get place data
$place = $place_controller->get_place($place_id);
if (!$place) {
  wp_die(__('Place not found.', 'heritagepress'));
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
  if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_edit_place')) {
    $error = __('Security check failed.', 'heritagepress');
  } else {
    $result = $place_controller->update_place($place_id, $_POST);

    if (is_wp_error($result)) {
      $error = $result->get_error_message();
    } else {
      $message = __('Place updated successfully.', 'heritagepress');

      // Refresh place data
      $place = $place_controller->get_place($place_id);

      // Redirect based on button clicked
      if (isset($_POST['submit_and_return'])) {
        wp_redirect(admin_url('admin.php?page=hp-places&message=' . urlencode($message)));
        exit;
      }
    }
  }
}

// Get linked cemeteries
$cemeteries = $place_controller->get_place_cemeteries($place['place']);

?>

<div class="wrap">
  <h1><?php _e('Edit Place', 'heritagepress'); ?></h1>

  <!-- Tab Navigation -->
  <nav class="nav-tab-wrapper">
    <a href="?page=hp-places" class="nav-tab"><?php _e('Search Places', 'heritagepress'); ?></a>
    <a href="?page=hp-places-add" class="nav-tab"><?php _e('Add New', 'heritagepress'); ?></a>
    <a href="?page=hp-places-merge" class="nav-tab"><?php _e('Merge Places', 'heritagepress'); ?></a>
    <a href="?page=hp-places-geocode" class="nav-tab"><?php _e('Geocode', 'heritagepress'); ?></a>
    <a href="#" class="nav-tab nav-tab-active"><?php _e('Edit', 'heritagepress'); ?></a>
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

  <!-- Place Header -->
  <div class="hp-place-header">
    <h2><?php echo esc_html($place['place']); ?></h2>
    <p class="description">
      <?php
      printf(
        __('Last modified: %s by %s', 'heritagepress'),
        esc_html($place['changedate_formatted']),
        esc_html($place['changedby'])
      );
      ?>
    </p>
  </div>

  <!-- Edit Place Form -->
  <div class="hp-form-section">
    <form method="post" id="edit-place-form">
      <?php wp_nonce_field('hp_edit_place'); ?>

      <table class="form-table">
        <?php if (!empty($trees)): ?>
          <tr>
            <th scope="row">
              <label for="gedcom"><?php _e('Tree:', 'heritagepress'); ?></label>
            </th>
            <td>
              <?php if (!empty($place['gedcom'])): ?>
                <?php
                $tree_name = '';
                foreach ($trees as $tree) {
                  if ($tree['gedcom'] === $place['gedcom']) {
                    $tree_name = $tree['treename'];
                    break;
                  }
                }
                echo esc_html($tree_name ?: $place['gedcom']);
                ?>
                <input type="hidden" name="gedcom" value="<?php echo esc_attr($place['gedcom']); ?>">
              <?php else: ?>
                <select name="gedcom" id="gedcom" class="regular-text">
                  <option value=""><?php _e('Select Tree', 'heritagepress'); ?></option>
                  <?php foreach ($trees as $tree): ?>
                    <option value="<?php echo esc_attr($tree['gedcom']); ?>"
                      <?php selected($_POST['gedcom'] ?? $place['gedcom'], $tree['gedcom']); ?>>
                      <?php echo esc_html($tree['treename']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>
            </td>
          </tr>
        <?php endif; ?>

        <tr>
          <th scope="row">
            <label for="place"><?php _e('Place Name:', 'heritagepress'); ?> <span class="description">(<?php _e('required', 'heritagepress'); ?>)</span></label>
          </th>
          <td>
            <input type="text" name="place" id="place"
              value="<?php echo esc_attr($_POST['place'] ?? $place['place']); ?>"
              class="large-text" required />
            <p class="description">
              <?php _e('Edit the place name. Changes will be propagated if "Update all references" is checked.', 'heritagepress'); ?>
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
              <option value="-1" <?php selected($_POST['placelevel'] ?? $place['placelevel'], '-1'); ?>>
                <?php _e('Do not geocode', 'heritagepress'); ?>
              </option>
              <option value="1" <?php selected($_POST['placelevel'] ?? $place['placelevel'], '1'); ?>>
                <?php _e('Country', 'heritagepress'); ?>
              </option>
              <option value="2" <?php selected($_POST['placelevel'] ?? $place['placelevel'], '2'); ?>>
                <?php _e('State/Province', 'heritagepress'); ?>
              </option>
              <option value="3" <?php selected($_POST['placelevel'] ?? $place['placelevel'], '3'); ?>>
                <?php _e('County', 'heritagepress'); ?>
              </option>
              <option value="4" <?php selected($_POST['placelevel'] ?? $place['placelevel'], '4'); ?>>
                <?php _e('City/Town', 'heritagepress'); ?>
              </option>
              <option value="5" <?php selected($_POST['placelevel'] ?? $place['placelevel'], '5'); ?>>
                <?php _e('Locality', 'heritagepress'); ?>
              </option>
              <option value="6" <?php selected($_POST['placelevel'] ?? $place['placelevel'], '6'); ?>>
                <?php _e('Address', 'heritagepress'); ?>
              </option>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label><?php _e('LDS Temple:', 'heritagepress'); ?></label>
          </th>
          <td>
            <label>
              <input type="checkbox" name="temple" value="1"
                <?php checked($_POST['temple'] ?? $place['temple']); ?>>
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
              value="<?php echo esc_attr($_POST['latitude'] ?? $place['latitude']); ?>"
              class="regular-text" />
            <button type="button" class="button geocode-place" data-place-id="<?php echo $place_id; ?>">
              <?php _e('Geocode', 'heritagepress'); ?>
            </button>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="longitude"><?php _e('Longitude:', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="longitude" id="longitude"
              value="<?php echo esc_attr($_POST['longitude'] ?? $place['longitude']); ?>"
              class="regular-text" />
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="zoom"><?php _e('Map Zoom Level:', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="number" name="zoom" id="zoom" min="1" max="20"
              value="<?php echo esc_attr($_POST['zoom'] ?? $place['zoom']); ?>"
              class="small-text" />
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="cemeteries"><?php _e('Linked Cemeteries:', 'heritagepress'); ?></label>
          </th>
          <td>
            <?php if (!empty($cemeteries)): ?>
              <table class="wp-list-table widefat fixed striped" style="max-width: 600px;">
                <thead>
                  <tr>
                    <th><?php _e('Actions', 'heritagepress'); ?></th>
                    <th><?php _e('Cemetery', 'heritagepress'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($cemeteries as $cemetery): ?>
                    <tr id="cemetery-row-<?php echo esc_attr($cemetery['cemeteryID']); ?>">
                      <td>
                        <button type="button" class="button button-small unlink-cemetery"
                          data-cemetery-id="<?php echo esc_attr($cemetery['cemeteryID']); ?>">
                          <?php _e('Unlink', 'heritagepress'); ?>
                        </button>
                        <button type="button" class="button button-small copy-geo"
                          data-cemetery-id="<?php echo esc_attr($cemetery['cemeteryID']); ?>">
                          <?php _e('Copy Geo', 'heritagepress'); ?>
                        </button>
                      </td>
                      <td>
                        <?php
                        $location_parts = array_filter(array(
                          $cemetery['cemname'],
                          $cemetery['city'],
                          $cemetery['county'],
                          $cemetery['state'],
                          $cemetery['country']
                        ));
                        echo esc_html(implode(', ', $location_parts));
                        ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else: ?>
              <p class="description"><?php _e('No cemeteries linked to this place.', 'heritagepress'); ?></p>
            <?php endif; ?>

            <p>
              <button type="button" class="button link-cemetery">
                <?php _e('Link Cemetery', 'heritagepress'); ?>
              </button>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="notes"><?php _e('Notes:', 'heritagepress'); ?></label>
          </th>
          <td>
            <textarea name="notes" id="notes" rows="5" class="large-text">
<?php echo esc_textarea($_POST['notes'] ?? $place['notes']); ?>
</textarea>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label><?php _e('Update Options:', 'heritagepress'); ?></label>
          </th>
          <td>
            <label>
              <input type="checkbox" name="propagate" value="1" checked>
              <?php _e('Update all references to this place name throughout the database', 'heritagepress'); ?>
            </label>
            <p class="description">
              <?php _e('If unchecked, only this place record will be updated.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>
      </table>

      <!-- Map Section (placeholder for future Google Maps integration) -->
      <div id="place-map-container" style="display: none;">
        <h3><?php _e('Map Location', 'heritagepress'); ?></h3>
        <div id="place-map" style="height: 400px; border: 1px solid #ddd;"></div>
      </div>

      <p class="submit">
        <input type="submit" name="submit_place" class="button-primary"
          value="<?php _e('Update Place and Continue Editing', 'heritagepress'); ?>">
        <input type="submit" name="submit_and_return" class="button"
          value="<?php _e('Update Place and Return to List', 'heritagepress'); ?>">
        <a href="?page=hp-places" class="button"><?php _e('Cancel', 'heritagepress'); ?></a>
      </p>
    </form>
  </div>
</div>

<style>
  .hp-place-header {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
  }

  .hp-place-header h2 {
    margin: 0 0 10px 0;
  }

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

  .geocode-place {
    margin-left: 10px;
  }

  .copy-geo,
  .unlink-cemetery,
  .link-cemetery {
    margin-right: 5px;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    // Form validation
    $('#edit-place-form').on('submit', function(e) {
      var placeName = $('#place').val().trim();

      if (!placeName) {
        alert('<?php _e('Please enter a place name.', 'heritagepress'); ?>');
        e.preventDefault();
        $('#place').focus();
        return false;
      }
    });

    // Geocode place
    $('.geocode-place').on('click', function() {
      var placeId = $(this).data('place-id');
      var placeName = $('#place').val();

      if (!placeName) {
        alert('<?php _e('Please enter a place name first.', 'heritagepress'); ?>');
        return;
      }

      $(this).prop('disabled', true).text('<?php _e('Geocoding...', 'heritagepress'); ?>');

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_place_geocode',
          place_id: placeId,
          place_name: placeName,
          nonce: '<?php echo wp_create_nonce('heritagepress_admin'); ?>'
        },
        success: function(response) {
          if (response.success) {
            alert('<?php _e('Place geocoded successfully.', 'heritagepress'); ?>');
            location.reload();
          } else {
            alert('<?php _e('Geocoding failed:', 'heritagepress'); ?> ' + response.data);
          }
        },
        error: function() {
          alert('<?php _e('Error communicating with server.', 'heritagepress'); ?>');
        },
        complete: function() {
          $('.geocode-place').prop('disabled', false).text('<?php _e('Geocode', 'heritagepress'); ?>');
        }
      });
    });

    // Unlink cemetery
    $('.unlink-cemetery').on('click', function() {
      var cemeteryId = $(this).data('cemetery-id');

      if (confirm('<?php _e('Are you sure you want to unlink this cemetery?', 'heritagepress'); ?>')) {
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'hp_cemetery_unlink',
            cemetery_id: cemeteryId,
            nonce: '<?php echo wp_create_nonce('heritagepress_admin'); ?>'
          },
          success: function(response) {
            if (response.success) {
              $('#cemetery-row-' + cemeteryId).fadeOut();
            } else {
              alert('<?php _e('Error unlinking cemetery:', 'heritagepress'); ?> ' + response.data);
            }
          }
        });
      }
    });

    // Copy geo info
    $('.copy-geo').on('click', function() {
      var cemeteryId = $(this).data('cemetery-id');
      var latitude = $('#latitude').val();
      var longitude = $('#longitude').val();
      var zoom = $('#zoom').val();

      if (!latitude || !longitude) {
        alert('<?php _e('Please enter coordinates first.', 'heritagepress'); ?>');
        return;
      }

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_copy_geo_info',
          cemetery_id: cemeteryId,
          latitude: latitude,
          longitude: longitude,
          zoom: zoom,
          nonce: '<?php echo wp_create_nonce('heritagepress_admin'); ?>'
        },
        success: function(response) {
          if (response.success) {
            alert('<?php _e('Geographic information copied successfully.', 'heritagepress'); ?>');
          } else {
            alert('<?php _e('Error copying information:', 'heritagepress'); ?> ' + response.data);
          }
        }
      });
    });

    // Link cemetery (placeholder)
    $('.link-cemetery').on('click', function() {
      alert('<?php _e('Cemetery linking functionality will be implemented with cemetery management.', 'heritagepress'); ?>');
    });
  });
</script>
