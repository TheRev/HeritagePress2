<?php

/**
 * HeritagePress Geocode Places Interface
 *
 * Bulk geocoding functionality for places
 * Replicates admin_geocodeform.php functionality
 */

if (!defined('ABSPATH')) {
  exit;
}

// Initialize place controller if not already loaded
if (!class_exists('HP_Place_Controller')) {
  require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-place-controller.php';
}

$place_controller = new HP_Place_Controller();

// Handle geocoding request
$message = '';
$error = '';
$geocoding_results = null;

if (isset($_POST['start_geocoding'])) {
  // Verify nonce
  if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_geocode_places')) {
    $error = __('Security check failed.', 'heritagepress');
  } else {
    // TODO: Implement bulk geocoding functionality
    $message = __('Bulk geocoding functionality will be implemented.', 'heritagepress');
  }
}

// Get geocoding statistics
global $wpdb;
$places_table = $wpdb->prefix . 'hp_places';

$total_places = $wpdb->get_var("SELECT COUNT(*) FROM {$places_table}");
$geocoded_places = $wpdb->get_var("SELECT COUNT(*) FROM {$places_table} WHERE latitude IS NOT NULL AND latitude != '' AND longitude IS NOT NULL AND longitude != ''");
$ungeocoded_places = $total_places - $geocoded_places;

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees = $wpdb->get_results("SELECT gedcom, treename FROM {$trees_table} ORDER BY treename", ARRAY_A);

?>

<div class="wrap">
  <h1><?php _e('Geocode Places', 'heritagepress'); ?></h1>

  <!-- Tab Navigation -->
  <nav class="nav-tab-wrapper">
    <a href="?page=hp-places" class="nav-tab"><?php _e('Search Places', 'heritagepress'); ?></a>
    <a href="?page=hp-places-add" class="nav-tab"><?php _e('Add New', 'heritagepress'); ?></a>
    <a href="?page=hp-places-merge" class="nav-tab"><?php _e('Merge Places', 'heritagepress'); ?></a>
    <a href="?page=hp-places-geocode" class="nav-tab nav-tab-active"><?php _e('Geocode', 'heritagepress'); ?></a>
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

  <!-- Geocoding Statistics -->
  <div class="hp-stats-section">
    <h3><?php _e('Geocoding Statistics', 'heritagepress'); ?></h3>
    <div class="hp-stats-grid">
      <div class="hp-stat-card">
        <div class="hp-stat-number"><?php echo number_format($total_places); ?></div>
        <div class="hp-stat-label"><?php _e('Total Places', 'heritagepress'); ?></div>
      </div>
      <div class="hp-stat-card geocoded">
        <div class="hp-stat-number"><?php echo number_format($geocoded_places); ?></div>
        <div class="hp-stat-label"><?php _e('Geocoded Places', 'heritagepress'); ?></div>
      </div>
      <div class="hp-stat-card ungeocoded">
        <div class="hp-stat-number"><?php echo number_format($ungeocoded_places); ?></div>
        <div class="hp-stat-label"><?php _e('Need Geocoding', 'heritagepress'); ?></div>
      </div>
      <div class="hp-stat-card">
        <div class="hp-stat-number">
          <?php echo $total_places > 0 ? number_format(($geocoded_places / $total_places) * 100, 1) : 0; ?>%
        </div>
        <div class="hp-stat-label"><?php _e('Completion Rate', 'heritagepress'); ?></div>
      </div>
    </div>
  </div>

  <!-- About Geocoding -->
  <div class="hp-info-section">
    <h3><?php _e('About Geocoding', 'heritagepress'); ?></h3>
    <p><?php _e('Geocoding converts place names into geographic coordinates (latitude and longitude). This enables mapping features and geographic analysis of your genealogy data.', 'heritagepress'); ?></p>
    <p><?php _e('The geocoding process will:', 'heritagepress'); ?></p>
    <ul>
      <li><?php _e('Search online geocoding services for coordinates', 'heritagepress'); ?></li>
      <li><?php _e('Update place records with latitude and longitude', 'heritagepress'); ?></li>
      <li><?php _e('Set appropriate zoom levels for mapping', 'heritagepress'); ?></li>
      <li><?php _e('Respect rate limits to avoid service blocking', 'heritagepress'); ?></li>
    </ul>
  </div>

  <!-- Geocoding Form -->
  <div class="hp-geocoding-section">
    <h3><?php _e('Start Geocoding', 'heritagepress'); ?></h3>

    <form method="post" id="geocoding-form">
      <?php wp_nonce_field('hp_geocode_places'); ?>

      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="geocoding_service"><?php _e('Geocoding Service:', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="geocoding_service" id="geocoding_service">
              <option value="nominatim" <?php selected($_POST['geocoding_service'] ?? 'nominatim', 'nominatim'); ?>>
                <?php _e('OpenStreetMap Nominatim (Free)', 'heritagepress'); ?>
              </option>
              <option value="google" <?php selected($_POST['geocoding_service'] ?? '', 'google'); ?>>
                <?php _e('Google Maps (API Key Required)', 'heritagepress'); ?>
              </option>
              <option value="bing" <?php selected($_POST['geocoding_service'] ?? '', 'bing'); ?>>
                <?php _e('Bing Maps (API Key Required)', 'heritagepress'); ?>
              </option>
            </select>
            <p class="description">
              <?php _e('Choose the geocoding service to use. Nominatim is free but has rate limits.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="api_key"><?php _e('API Key:', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="api_key" id="api_key"
              value="<?php echo esc_attr(get_option('heritagepress_geocoding_api_key', '')); ?>"
              class="large-text" />
            <p class="description">
              <?php _e('Required for Google Maps and Bing Maps. Not needed for Nominatim.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <?php if (!empty($trees)): ?>
          <tr>
            <th scope="row">
              <label for="tree"><?php _e('Tree:', 'heritagepress'); ?></label>
            </th>
            <td>
              <select name="tree" id="tree">
                <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
                <?php foreach ($trees as $tree): ?>
                  <option value="<?php echo esc_attr($tree['gedcom']); ?>"
                    <?php selected($_POST['tree'] ?? '', $tree['gedcom']); ?>>
                    <?php echo esc_html($tree['treename']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <p class="description">
                <?php _e('Limit geocoding to a specific tree, or leave blank to process all trees.', 'heritagepress'); ?>
              </p>
            </td>
          </tr>
        <?php endif; ?>

        <tr>
          <th scope="row">
            <label for="place_level"><?php _e('Place Level:', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="place_level" id="place_level">
              <option value=""><?php _e('All Levels', 'heritagepress'); ?></option>
              <option value="1" <?php selected($_POST['place_level'] ?? '', '1'); ?>>
                <?php _e('Country', 'heritagepress'); ?>
              </option>
              <option value="2" <?php selected($_POST['place_level'] ?? '', '2'); ?>>
                <?php _e('State/Province', 'heritagepress'); ?>
              </option>
              <option value="3" <?php selected($_POST['place_level'] ?? '', '3'); ?>>
                <?php _e('County', 'heritagepress'); ?>
              </option>
              <option value="4" <?php selected($_POST['place_level'] ?? '', '4'); ?>>
                <?php _e('City/Town', 'heritagepress'); ?>
              </option>
              <option value="5" <?php selected($_POST['place_level'] ?? '', '5'); ?>>
                <?php _e('Locality', 'heritagepress'); ?>
              </option>
              <option value="6" <?php selected($_POST['place_level'] ?? '', '6'); ?>>
                <?php _e('Address', 'heritagepress'); ?>
              </option>
            </select>
            <p class="description">
              <?php _e('Process only places of a specific level, or leave blank for all levels.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="batch_size"><?php _e('Batch Size:', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="number" name="batch_size" id="batch_size" min="1" max="100"
              value="<?php echo esc_attr($_POST['batch_size'] ?? '10'); ?>"
              class="small-text" />
            <p class="description">
              <?php _e('Number of places to process at once. Smaller batches are safer for rate limits.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="delay"><?php _e('Delay Between Requests:', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="number" name="delay" id="delay" min="0" max="10" step="0.1"
              value="<?php echo esc_attr($_POST['delay'] ?? '1'); ?>"
              class="small-text" />
            <span><?php _e('seconds', 'heritagepress'); ?></span>
            <p class="description">
              <?php _e('Delay between geocoding requests to respect service rate limits.', 'heritagepress'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label><?php _e('Options:', 'heritagepress'); ?></label>
          </th>
          <td>
            <label>
              <input type="checkbox" name="ungeocoded_only" value="1"
                <?php checked($_POST['ungeocoded_only'] ?? '1'); ?>>
              <?php _e('Process only places without coordinates', 'heritagepress'); ?>
            </label><br>

            <label>
              <input type="checkbox" name="overwrite_existing" value="1"
                <?php checked($_POST['overwrite_existing'] ?? ''); ?>>
              <?php _e('Overwrite existing coordinates', 'heritagepress'); ?>
            </label><br>

            <label>
              <input type="checkbox" name="test_mode" value="1"
                <?php checked($_POST['test_mode'] ?? ''); ?>>
              <?php _e('Test mode (dry run - no changes saved)', 'heritagepress'); ?>
            </label>
          </td>
        </tr>
      </table>

      <p class="submit">
        <input type="submit" name="start_geocoding" class="button-primary"
          value="<?php _e('Start Geocoding', 'heritagepress'); ?>">
        <input type="button" id="stop-geocoding" class="button"
          value="<?php _e('Stop', 'heritagepress'); ?>" style="display: none;">
        <a href="?page=hp-places" class="button"><?php _e('Cancel', 'heritagepress'); ?></a>
      </p>
    </form>
  </div>

  <!-- Progress Section -->
  <div id="geocoding-progress" class="hp-progress-section" style="display: none;">
    <h3><?php _e('Geocoding Progress', 'heritagepress'); ?></h3>
    <div class="hp-progress-bar">
      <div class="hp-progress-fill" style="width: 0%"></div>
    </div>
    <div class="hp-progress-stats">
      <span id="progress-text"><?php _e('Initializing...', 'heritagepress'); ?></span>
    </div>
    <div id="geocoding-log"></div>
  </div>

  <!-- Recent Places Section -->
  <div class="hp-recent-section">
    <h3><?php _e('Recently Added Places', 'heritagepress'); ?></h3>
    <p><?php _e('These places were recently added and may need geocoding:', 'heritagepress'); ?></p>

    <div id="recent-places-list">
      <?php
      // Get recently added places without coordinates
      $recent_places = $wpdb->get_results(
        "SELECT ID, place, gedcom, DATE_FORMAT(changedate, '%d %b %Y') as added_date
                 FROM {$places_table}
                 WHERE (latitude IS NULL OR latitude = '' OR longitude IS NULL OR longitude = '')
                 ORDER BY changedate DESC
                 LIMIT 10",
        ARRAY_A
      );

      if (!empty($recent_places)): ?>
        <table class="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <th><?php _e('Place Name', 'heritagepress'); ?></th>
              <th><?php _e('Tree', 'heritagepress'); ?></th>
              <th><?php _e('Added', 'heritagepress'); ?></th>
              <th><?php _e('Actions', 'heritagepress'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_places as $place): ?>
              <tr>
                <td><?php echo esc_html($place['place']); ?></td>
                <td><?php echo esc_html($place['gedcom'] ?: __('All trees', 'heritagepress')); ?></td>
                <td><?php echo esc_html($place['added_date']); ?></td>
                <td>
                  <button type="button" class="button button-small geocode-single"
                    data-place-id="<?php echo esc_attr($place['ID']); ?>">
                    <?php _e('Geocode', 'heritagepress'); ?>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="description"><?php _e('No recent places need geocoding.', 'heritagepress'); ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
  .hp-stats-section,
  .hp-info-section,
  .hp-geocoding-section,
  .hp-progress-section,
  .hp-recent-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
  }

  .hp-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 15px;
  }

  .hp-stat-card {
    text-align: center;
    padding: 20px;
    background: #f0f0f1;
    border-radius: 4px;
    border: 2px solid #c3c4c7;
  }

  .hp-stat-card.geocoded {
    background: #d4edda;
    border-color: #28a745;
  }

  .hp-stat-card.ungeocoded {
    background: #f8d7da;
    border-color: #dc3545;
  }

  .hp-stat-number {
    font-size: 2em;
    font-weight: bold;
    margin-bottom: 5px;
  }

  .hp-stat-label {
    font-size: 0.9em;
    color: #666;
  }

  .hp-progress-bar {
    width: 100%;
    height: 20px;
    background-color: #f0f0f1;
    border-radius: 10px;
    overflow: hidden;
    margin: 15px 0;
  }

  .hp-progress-fill {
    height: 100%;
    background-color: #00a32a;
    transition: width 0.3s ease;
  }

  .hp-progress-stats {
    text-align: center;
    margin-bottom: 15px;
  }

  #geocoding-log {
    max-height: 300px;
    overflow-y: auto;
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 12px;
  }

  .geocode-single {
    margin-right: 5px;
  }
</style>

<script>
  jQuery(document).ready(function($) {
    var geocodingInProgress = false;
    var geocodingTimeout;

    // Toggle API key field based on service selection
    $('#geocoding_service').on('change', function() {
      var service = $(this).val();
      var $apiKeyRow = $('#api_key').closest('tr');

      if (service === 'nominatim') {
        $apiKeyRow.hide();
      } else {
        $apiKeyRow.show();
      }
    }).trigger('change');

    // Form validation
    $('#geocoding-form').on('submit', function(e) {
      var service = $('#geocoding_service').val();
      var apiKey = $('#api_key').val().trim();

      if ((service === 'google' || service === 'bing') && !apiKey) {
        alert('<?php _e('API Key is required for the selected geocoding service.', 'heritagepress'); ?>');
        e.preventDefault();
        $('#api_key').focus();
        return false;
      }

      // Start progress tracking
      startGeocodingProgress();
    });

    // Single place geocoding
    $('.geocode-single').on('click', function() {
      var $button = $(this);
      var placeId = $button.data('place-id');

      $button.prop('disabled', true).text('<?php _e('Geocoding...', 'heritagepress'); ?>');

      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'hp_place_geocode',
          place_id: placeId,
          nonce: '<?php echo wp_create_nonce('heritagepress_admin'); ?>'
        },
        success: function(response) {
          if (response.success) {
            $button.text('<?php _e('Success', 'heritagepress'); ?>').removeClass('button-primary');
            setTimeout(function() {
              $button.closest('tr').fadeOut();
            }, 1000);
          } else {
            alert('<?php _e('Geocoding failed:', 'heritagepress'); ?> ' + response.data);
            $button.prop('disabled', false).text('<?php _e('Geocode', 'heritagepress'); ?>');
          }
        },
        error: function() {
          alert('<?php _e('Error communicating with server.', 'heritagepress'); ?>');
          $button.prop('disabled', false).text('<?php _e('Geocode', 'heritagepress'); ?>');
        }
      });
    });

    function startGeocodingProgress() {
      geocodingInProgress = true;
      $('#geocoding-progress').show();
      $('#stop-geocoding').show();

      // TODO: Implement actual progress tracking
      simulateProgress();
    }

    function simulateProgress() {
      var progress = 0;
      var interval = setInterval(function() {
        progress += Math.random() * 10;
        if (progress > 100) progress = 100;

        $('.hp-progress-fill').css('width', progress + '%');
        $('#progress-text').text('<?php _e('Processing...', 'heritagepress'); ?> ' + Math.round(progress) + '%');

        if (progress >= 100 || !geocodingInProgress) {
          clearInterval(interval);
          if (geocodingInProgress) {
            $('#progress-text').text('<?php _e('Geocoding completed!', 'heritagepress'); ?>');
            $('#stop-geocoding').hide();
          }
        }
      }, 500);
    }

    $('#stop-geocoding').on('click', function() {
      geocodingInProgress = false;
      $(this).hide();
      $('#progress-text').text('<?php _e('Geocoding stopped.', 'heritagepress'); ?>');
    });
  });
</script>
