<?php

/**
 * GEDCOM Import - Places Settings Tab
 *
 * Sixth step of GEDCOM import process - configure place-related import settings
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get import session data
$upload_data = isset($_SESSION['hp_gedcom_upload']) ? $_SESSION['hp_gedcom_upload'] : null;
$validation_results = isset($_SESSION['hp_gedcom_validation']) ? $_SESSION['hp_gedcom_validation'] : null;
$config_data = isset($_SESSION['hp_gedcom_config']) ? $_SESSION['hp_gedcom_config'] : null;
$media_data = isset($_SESSION['hp_gedcom_media']) ? $_SESSION['hp_gedcom_media'] : null;
$file_path = isset($upload_data['file_path']) ? $upload_data['file_path'] : '';

// If no media data, redirect to media tab
if (!$media_data && !isset($_GET['skip'])) {
  echo '<div class="error-box">';
  echo '<p>' . __('Media settings have not been configured. Please return to the Media Options tab.', 'heritagepress') . '</p>';
  echo '<p><a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=media" class="button">' . __('Go to Media Options', 'heritagepress') . '</a></p>';
  echo '</div>';
  return;
}

// Program information
$program = isset($validation_results['program']) ? $validation_results['program'] : array('name' => __('Unknown', 'heritagepress'), 'version' => '');
$program_name = $program['name'];
$program_version = $program['version'];

// Places statistics
$places_count = isset($validation_results['stats']['places']) ? intval($validation_results['stats']['places']) : 0;

// Get default settings based on program
$default_settings = hp_get_default_places_settings($program_name);
?>

<h2><?php _e('Places Import Settings', 'heritagepress'); ?></h2>

<div class="message-box">
  <p><?php _e('Configure how places and locations from your GEDCOM file are imported and processed. These settings control place formatting, standardization, and geocoding.', 'heritagepress'); ?></p>
  <p><strong><?php _e('Places in GEDCOM:', 'heritagepress'); ?></strong> <?php echo number_format($places_count); ?></p>
</div>

<form method="post" id="gedcom-places-form" class="hp-form">
  <?php wp_nonce_field('heritagepress_gedcom_places', 'gedcom_places_nonce'); ?>
  <input type="hidden" name="action" value="hp_save_gedcom_places">
  <input type="hidden" name="file_path" value="<?php echo esc_attr($file_path); ?>">

  <table class="form-table">
    <tr>
      <th scope="row">
        <label for="place_handling"><?php _e('Place Handling', 'heritagepress'); ?></label>
      </th>
      <td>
        <select name="place_handling" id="place_handling">
          <option value="exact" <?php selected($default_settings['place_handling'], 'exact'); ?>><?php _e('Import places exactly as in GEDCOM', 'heritagepress'); ?></option>
          <option value="standardize" <?php selected($default_settings['place_handling'], 'standardize'); ?>><?php _e('Standardize place names during import', 'heritagepress'); ?></option>
          <option value="prioritize_existing" <?php selected($default_settings['place_handling'], 'prioritize_existing'); ?>><?php _e('Prioritize existing places in database', 'heritagepress'); ?></option>
        </select>
        <p class="description"><?php _e('Select how place names should be handled during import.', 'heritagepress'); ?></p>
      </td>
    </tr>

    <tr>
      <th scope="row">
        <label for="place_format"><?php _e('Place Format', 'heritagepress'); ?></label>
      </th>
      <td>
        <select name="place_format" id="place_format">
          <option value="original" <?php selected($default_settings['place_format'], 'original'); ?>><?php _e('Keep original format', 'heritagepress'); ?></option>
          <option value="smallest_first" <?php selected($default_settings['place_format'], 'smallest_first'); ?>><?php _e('Smallest to largest (Town, County, State, Country)', 'heritagepress'); ?></option>
          <option value="largest_first" <?php selected($default_settings['place_format'], 'largest_first'); ?>><?php _e('Largest to smallest (Country, State, County, Town)', 'heritagepress'); ?></option>
        </select>
        <p class="description"><?php _e('Select how place hierarchies should be formatted.', 'heritagepress'); ?></p>
      </td>
    </tr>

    <tr>
      <th scope="row">
        <label for="place_separator"><?php _e('Place Separator', 'heritagepress'); ?></label>
      </th>
      <td>
        <select name="place_separator" id="place_separator">
          <option value="comma" <?php selected($default_settings['place_separator'], 'comma'); ?>><?php _e('Comma (City, State, Country)', 'heritagepress'); ?></option>
          <option value="dash" <?php selected($default_settings['place_separator'], 'dash'); ?>><?php _e('Dash (City - State - Country)', 'heritagepress'); ?></option>
          <option value="arrow" <?php selected($default_settings['place_separator'], 'arrow'); ?>><?php _e('Arrow (City > State > Country)', 'heritagepress'); ?></option>
        </select>
        <p class="description"><?php _e('Select the separator to use between place hierarchy levels.', 'heritagepress'); ?></p>
      </td>
    </tr>

    <tr>
      <th scope="row">
        <?php _e('Place Extraction', 'heritagepress'); ?>
      </th>
      <td>
        <fieldset>
          <legend class="screen-reader-text"><?php _e('Place extraction options', 'heritagepress'); ?></legend>

          <label for="extract_place_hierarchy">
            <input type="checkbox" name="extract_place_hierarchy" id="extract_place_hierarchy" value="1" <?php checked($default_settings['extract_place_hierarchy'], true); ?>>
            <?php _e('Extract place hierarchy (parse places into town, county, state, country)', 'heritagepress'); ?>
          </label><br>

          <label for="index_places">
            <input type="checkbox" name="index_places" id="index_places" value="1" <?php checked($default_settings['index_places'], true); ?>>
            <?php _e('Build place index for searching', 'heritagepress'); ?>
          </label>
        </fieldset>
      </td>
    </tr>

    <tr>
      <th scope="row">
        <?php _e('Geocoding', 'heritagepress'); ?>
      </th>
      <td>
        <fieldset>
          <legend class="screen-reader-text"><?php _e('Geocoding options', 'heritagepress'); ?></legend>

          <label for="geocode_places">
            <input type="checkbox" name="geocode_places" id="geocode_places" value="1" <?php checked($default_settings['geocode_places'], true); ?>>
            <?php _e('Geocode places (find latitude/longitude coordinates)', 'heritagepress'); ?>
          </label>

          <div id="geocode-options" <?php echo $default_settings['geocode_places'] ? '' : 'style="display:none"'; ?>>
            <p class="geocode-notice">
              <?php _e('Note: Geocoding will be performed in the background after import and may take some time to complete for large datasets.', 'heritagepress'); ?>
            </p>

            <label for="geocode_service">
              <?php _e('Geocoding Service:', 'heritagepress'); ?>
              <select name="geocode_service" id="geocode_service">
                <option value="nominatim" <?php selected($default_settings['geocode_service'], 'nominatim'); ?>><?php _e('OpenStreetMap Nominatim (free)', 'heritagepress'); ?></option>
                <option value="google" <?php selected($default_settings['geocode_service'], 'google'); ?>><?php _e('Google Maps (requires API key)', 'heritagepress'); ?></option>
              </select>
            </label>

            <div id="google-api-key" <?php echo ($default_settings['geocode_service'] == 'google') ? '' : 'style="display:none"'; ?>>
              <label for="google_maps_api_key">
                <?php _e('Google Maps API Key:', 'heritagepress'); ?>
                <input type="text" name="google_maps_api_key" id="google_maps_api_key" value="<?php echo esc_attr($default_settings['google_maps_api_key']); ?>" class="regular-text">
              </label>
              <p class="description"><?php _e('Required for Google Maps geocoding. Get a key from the Google Cloud Console.', 'heritagepress'); ?></p>
            </div>

            <label for="geocode_priority">
              <?php _e('Geocoding Priority:', 'heritagepress'); ?>
              <select name="geocode_priority" id="geocode_priority">
                <option value="all" <?php selected($default_settings['geocode_priority'], 'all'); ?>><?php _e('Geocode all places', 'heritagepress'); ?></option>
                <option value="important" <?php selected($default_settings['geocode_priority'], 'important'); ?>><?php _e('Geocode only important places (birth, death, marriage)', 'heritagepress'); ?></option>
                <option value="none" <?php selected($default_settings['geocode_priority'], 'none'); ?>><?php _e('Do not geocode automatically (manual only)', 'heritagepress'); ?></option>
              </select>
            </label>
          </div>
        </fieldset>
      </td>
    </tr>

    <tr>
      <th scope="row">
        <?php _e('Map Display', 'heritagepress'); ?>
      </th>
      <td>
        <fieldset>
          <legend class="screen-reader-text"><?php _e('Map display options', 'heritagepress'); ?></legend>

          <label for="default_map_type">
            <?php _e('Default Map Type:', 'heritagepress'); ?>
            <select name="default_map_type" id="default_map_type">
              <option value="road" <?php selected($default_settings['default_map_type'], 'road'); ?>><?php _e('Road Map', 'heritagepress'); ?></option>
              <option value="satellite" <?php selected($default_settings['default_map_type'], 'satellite'); ?>><?php _e('Satellite', 'heritagepress'); ?></option>
              <option value="hybrid" <?php selected($default_settings['default_map_type'], 'hybrid'); ?>><?php _e('Hybrid (Satellite with Roads)', 'heritagepress'); ?></option>
              <option value="terrain" <?php selected($default_settings['default_map_type'], 'terrain'); ?>><?php _e('Terrain', 'heritagepress'); ?></option>
            </select>
          </label><br>

          <label for="show_place_markers">
            <input type="checkbox" name="show_place_markers" id="show_place_markers" value="1" <?php checked($default_settings['show_place_markers'], true); ?>>
            <?php _e('Show place markers on individual/family pages', 'heritagepress'); ?>
          </label><br>

          <label for="cluster_markers">
            <input type="checkbox" name="cluster_markers" id="cluster_markers" value="1" <?php checked($default_settings['cluster_markers'], true); ?>>
            <?php _e('Cluster nearby markers on maps', 'heritagepress'); ?>
          </label>
        </fieldset>
      </td>
    </tr>
  </table>

  <div class="hp-form-actions">
    <?php submit_button(__('Save Places Settings', 'heritagepress'), 'primary', 'save_places_settings', false); ?>
    &nbsp;<a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=media" class="button"><?php _e('Back to Media Options', 'heritagepress'); ?></a>
    &nbsp;<a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=process" class="button button-secondary"><?php _e('Skip to Process Import', 'heritagepress'); ?></a>
  </div>
</form>

<script>
  jQuery(document).ready(function($) {
    // Toggle geocoding options based on geocode places checkbox
    $('#geocode_places').on('change', function() {
      if ($(this).is(':checked')) {
        $('#geocode-options').show();
      } else {
        $('#geocode-options').hide();
      }
    });

    // Toggle Google API key field based on geocode service selection
    $('#geocode_service').on('change', function() {
      if ($(this).val() === 'google') {
        $('#google-api-key').show();
      } else {
        $('#google-api-key').hide();
      }
    });

    // Form submission
    $('#gedcom-places-form').on('submit', function() {
      $('<div class="loading-overlay"><span class="spinner is-active"></span> <?php _e('Saving places settings...', 'heritagepress'); ?></div>').appendTo('body');
    });
  });
</script>

<style>
  #geocode-options {
    margin: 15px 0 10px 25px;
    padding: 10px;
    border-left: 3px solid #ddd;
  }

  #geocode-options label {
    display: block;
    margin-bottom: 12px;
  }

  #geocode-options select {
    margin-left: 8px;
  }

  #google-api-key {
    margin: 12px 0 12px 25px;
    padding: 10px;
    border-left: 2px solid #ddd;
  }

  .geocode-notice {
    background: #f8f8f8;
    padding: 8px 12px;
    border-left: 4px solid #646970;
    margin-bottom: 15px;
  }
</style>
