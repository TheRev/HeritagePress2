<?php

/**
 * GEDCOM Import - Configure Tab
 *
 * Third step of GEDCOM import process - configure import settings
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get import session data
$upload_data = isset($_SESSION['hp_gedcom_upload']) ? $_SESSION['hp_gedcom_upload'] : null;
$validation_results = isset($_SESSION['hp_gedcom_validation']) ? $_SESSION['hp_gedcom_validation'] : null;
$file_path = isset($upload_data['file_path']) ? $upload_data['file_path'] : '';

// If no validation data, redirect to validate tab
if (!$validation_results) {
  echo '<div class="error-box">';
  echo '<p>' . __('The GEDCOM file has not been validated. Please return to the Validate tab.', 'heritagepress') . '</p>';
  echo '<p><a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=validate" class="button">' . __('Go to Validation', 'heritagepress') . '</a></p>';
  echo '</div>';
  return;
}

// Program information
$program = isset($validation_results['program']) ? $validation_results['program'] : array('name' => __('Unknown', 'heritagepress'), 'version' => '');
$program_name = $program['name'];
$program_version = $program['version'];

// Get default settings based on program
$default_settings = hp_get_default_import_settings($program_name);
?>

<h2><?php _e('Configure GEDCOM Import', 'heritagepress'); ?></h2>

<div class="message-box">
  <p><?php _e('Configure how you want to import your GEDCOM file. These settings control what data is imported and how it is processed.', 'heritagepress'); ?></p>
  <p><strong><?php _e('Source Program:', 'heritagepress'); ?></strong> <?php echo esc_html($program_name . ($program_version ? ' ' . $program_version : '')); ?></p>
</div>

<form method="post" id="gedcom-config-form" class="hp-form">
  <?php wp_nonce_field('heritagepress_gedcom_config', 'gedcom_config_nonce'); ?>
  <input type="hidden" name="action" value="hp_save_gedcom_config">
  <input type="hidden" name="file_path" value="<?php echo esc_attr($file_path); ?>">

  <div class="hp-tabs-container">
    <ul class="hp-tabs-nav">
      <li class="active"><a href="#tab-general"><?php _e('General', 'heritagepress'); ?></a></li>
      <li><a href="#tab-privacy"><?php _e('Privacy', 'heritagepress'); ?></a></li>
      <li><a href="#tab-duplicates"><?php _e('Duplicates', 'heritagepress'); ?></a></li>
      <li><a href="#tab-dates"><?php _e('Dates', 'heritagepress'); ?></a></li>
      <li><a href="#tab-advanced"><?php _e('Advanced', 'heritagepress'); ?></a></li>
    </ul>

    <div class="hp-tabs-content">
      <!-- General Tab -->
      <div id="tab-general" class="hp-tab-panel active">
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="import_type"><?php _e('Import Type', 'heritagepress'); ?></label>
            </th>
            <td>
              <select name="import_type" id="import_type">
                <option value="all" <?php selected($default_settings['import_type'], 'all'); ?>><?php _e('All Records (Default)', 'heritagepress'); ?></option>
                <option value="update" <?php selected($default_settings['import_type'], 'update'); ?>><?php _e('Update Existing Records Only', 'heritagepress'); ?></option>
                <option value="missing" <?php selected($default_settings['import_type'], 'missing'); ?>><?php _e('Add Missing Records Only', 'heritagepress'); ?></option>
              </select>
              <p class="description"><?php _e('Select what type of import to perform.', 'heritagepress'); ?></p>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php _e('Record Types', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Record Types to Import', 'heritagepress'); ?></legend>

                <label for="import_individuals">
                  <input type="checkbox" name="import_individuals" id="import_individuals" value="1" <?php checked($default_settings['import_individuals'], true); ?>>
                  <?php _e('Individuals (INDI records)', 'heritagepress'); ?>
                </label><br>

                <label for="import_families">
                  <input type="checkbox" name="import_families" id="import_families" value="1" <?php checked($default_settings['import_families'], true); ?>>
                  <?php _e('Families (FAM records)', 'heritagepress'); ?>
                </label><br>

                <label for="import_sources">
                  <input type="checkbox" name="import_sources" id="import_sources" value="1" <?php checked($default_settings['import_sources'], true); ?>>
                  <?php _e('Sources (SOUR records)', 'heritagepress'); ?>
                </label><br>

                <label for="import_repositories">
                  <input type="checkbox" name="import_repositories" id="import_repositories" value="1" <?php checked($default_settings['import_repositories'], true); ?>>
                  <?php _e('Repositories (REPO records)', 'heritagepress'); ?>
                </label><br>

                <label for="import_notes">
                  <input type="checkbox" name="import_notes" id="import_notes" value="1" <?php checked($default_settings['import_notes'], true); ?>>
                  <?php _e('Notes (NOTE records)', 'heritagepress'); ?>
                </label><br>

                <label for="import_media">
                  <input type="checkbox" name="import_media" id="import_media" value="1" <?php checked($default_settings['import_media'], true); ?>>
                  <?php _e('Media (OBJE records)', 'heritagepress'); ?>
                </label><br>

                <label for="import_places">
                  <input type="checkbox" name="import_places" id="import_places" value="1" <?php checked($default_settings['import_places'], true); ?>>
                  <?php _e('Extract and index places', 'heritagepress'); ?>
                </label><br>
              </fieldset>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php _e('Events', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Event handling options', 'heritagepress'); ?></legend>

                <label for="import_events">
                  <input type="checkbox" name="import_events" id="import_events" value="1" <?php checked($default_settings['import_events'], true); ?>>
                  <?php _e('Import events for individuals and families', 'heritagepress'); ?>
                </label><br>

                <label for="import_custom_events">
                  <input type="checkbox" name="import_custom_events" id="import_custom_events" value="1" <?php checked($default_settings['import_custom_events'], true); ?>>
                  <?php _e('Import custom/non-standard events', 'heritagepress'); ?>
                </label>
              </fieldset>
            </td>
          </tr>
        </table>
      </div>

      <!-- Privacy Tab -->
      <div id="tab-privacy" class="hp-tab-panel">
        <table class="form-table">
          <tr>
            <th scope="row"><?php _e('Living People', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Living people options', 'heritagepress'); ?></legend>

                <label>
                  <input type="radio" name="living_option" value="import_full" <?php checked($default_settings['living_option'], 'import_full'); ?>>
                  <?php _e('Import living people with full details', 'heritagepress'); ?>
                </label><br>

                <label>
                  <input type="radio" name="living_option" value="import_partial" <?php checked($default_settings['living_option'], 'import_partial'); ?>>
                  <?php _e('Import living people with limited details', 'heritagepress'); ?>
                </label><br>

                <label>
                  <input type="radio" name="living_option" value="skip" <?php checked($default_settings['living_option'], 'skip'); ?>>
                  <?php _e('Skip living people entirely', 'heritagepress'); ?>
                </label>

                <p class="description"><?php _e('Determines how living people are handled during import.', 'heritagepress'); ?></p>
              </fieldset>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php _e('Privacy Rules', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Privacy rules', 'heritagepress'); ?></legend>

                <label for="apply_privacy">
                  <input type="checkbox" name="apply_privacy" id="apply_privacy" value="1" <?php checked($default_settings['apply_privacy'], true); ?>>
                  <?php _e('Apply privacy rules to imported data', 'heritagepress'); ?>
                </label><br>

                <div id="privacy-options" <?php echo $default_settings['apply_privacy'] ? '' : 'style="display:none"'; ?>>
                  <label for="years_death">
                    <?php _e('Consider people deceased after', 'heritagepress'); ?>
                    <input type="number" name="years_death" id="years_death" value="<?php echo intval($default_settings['years_death']); ?>" min="1" max="200" step="1" style="width: 60px;">
                    <?php _e('years', 'heritagepress'); ?>
                  </label><br>

                  <label for="years_birth">
                    <?php _e('Consider people deceased if born more than', 'heritagepress'); ?>
                    <input type="number" name="years_birth" id="years_birth" value="<?php echo intval($default_settings['years_birth']); ?>" min="1" max="200" step="1" style="width: 60px;">
                    <?php _e('years ago', 'heritagepress'); ?>
                  </label>
                </div>

                <p class="description"><?php _e('Set rules for determining if a person is considered living or deceased.', 'heritagepress'); ?></p>
              </fieldset>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php _e('Private Data', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Private data options', 'heritagepress'); ?></legend>

                <label for="import_private">
                  <input type="checkbox" name="import_private" id="import_private" value="1" <?php checked($default_settings['import_private'], true); ?>>
                  <?php _e('Import data marked as private in GEDCOM', 'heritagepress'); ?>
                </label>

                <p class="description"><?php _e('If checked, data marked with GEDCOM privacy tags will be imported. Otherwise, it will be skipped.', 'heritagepress'); ?></p>
              </fieldset>
            </td>
          </tr>
        </table>
      </div>

      <!-- Duplicates Tab -->
      <div id="tab-duplicates" class="hp-tab-panel">
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="duplicate_handling"><?php _e('Duplicate Handling', 'heritagepress'); ?></label>
            </th>
            <td>
              <select name="duplicate_handling" id="duplicate_handling">
                <option value="replace" <?php selected($default_settings['duplicate_handling'], 'replace'); ?>><?php _e('Replace existing records', 'heritagepress'); ?></option>
                <option value="merge" <?php selected($default_settings['duplicate_handling'], 'merge'); ?>><?php _e('Merge with existing records', 'heritagepress'); ?></option>
                <option value="keep_both" <?php selected($default_settings['duplicate_handling'], 'keep_both'); ?>><?php _e('Keep both (create new records)', 'heritagepress'); ?></option>
                <option value="skip" <?php selected($default_settings['duplicate_handling'], 'skip'); ?>><?php _e('Skip duplicates', 'heritagepress'); ?></option>
              </select>
              <p class="description"><?php _e('How to handle duplicate records found during import.', 'heritagepress'); ?></p>
            </td>
          </tr>

          <tr>
            <th scope="row">
              <label for="duplicate_detection"><?php _e('Duplicate Detection', 'heritagepress'); ?></label>
            </th>
            <td>
              <select name="duplicate_detection" id="duplicate_detection">
                <option value="exact" <?php selected($default_settings['duplicate_detection'], 'exact'); ?>><?php _e('Exact match (ID/key only)', 'heritagepress'); ?></option>
                <option value="standard" <?php selected($default_settings['duplicate_detection'], 'standard'); ?>><?php _e('Standard (name, dates, places)', 'heritagepress'); ?></option>
                <option value="fuzzy" <?php selected($default_settings['duplicate_detection'], 'fuzzy'); ?>><?php _e('Fuzzy matching (more aggressive)', 'heritagepress'); ?></option>
              </select>
              <p class="description"><?php _e('Method used to detect duplicate records.', 'heritagepress'); ?></p>
            </td>
          </tr>
        </table>
      </div>

      <!-- Dates Tab -->
      <div id="tab-dates" class="hp-tab-panel">
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="date_format"><?php _e('Date Format', 'heritagepress'); ?></label>
            </th>
            <td>
              <select name="date_format" id="date_format">
                <option value="standard" <?php selected($default_settings['date_format'], 'standard'); ?>><?php _e('Standard (DD MMM YYYY)', 'heritagepress'); ?></option>
                <option value="us" <?php selected($default_settings['date_format'], 'us'); ?>><?php _e('US Format (MM/DD/YYYY)', 'heritagepress'); ?></option>
                <option value="euro" <?php selected($default_settings['date_format'], 'euro'); ?>><?php _e('European (DD/MM/YYYY)', 'heritagepress'); ?></option>
              </select>
              <p class="description"><?php _e('Date display format for imported dates.', 'heritagepress'); ?></p>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php _e('Date Conversion', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Date conversion options', 'heritagepress'); ?></legend>

                <label for="convert_gregorian">
                  <input type="checkbox" name="convert_gregorian" id="convert_gregorian" value="1" <?php checked($default_settings['convert_gregorian'], true); ?>>
                  <?php _e('Convert Julian to Gregorian calendar dates', 'heritagepress'); ?>
                </label><br>

                <label for="keep_original_dates">
                  <input type="checkbox" name="keep_original_dates" id="keep_original_dates" value="1" <?php checked($default_settings['keep_original_dates'], true); ?>>
                  <?php _e('Keep original date text in notes', 'heritagepress'); ?>
                </label>

                <p class="description"><?php _e('Options for handling historical dates and calendars.', 'heritagepress'); ?></p>
              </fieldset>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php _e('Year Options', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Year options', 'heritagepress'); ?></legend>

                <label for="infer_year">
                  <input type="checkbox" name="infer_year" id="infer_year" value="1" <?php checked($default_settings['infer_year'], true); ?>>
                  <?php _e('Try to infer years for records missing dates', 'heritagepress'); ?>
                </label><br>

                <label for="minimum_year">
                  <?php _e('Minimum acceptable year:', 'heritagepress'); ?>
                  <input type="number" name="minimum_year" id="minimum_year" value="<?php echo intval($default_settings['minimum_year']); ?>" min="0" max="2100" step="1" style="width: 80px;">
                </label><br>

                <label for="maximum_year">
                  <?php _e('Maximum acceptable year:', 'heritagepress'); ?>
                  <input type="number" name="maximum_year" id="maximum_year" value="<?php echo intval($default_settings['maximum_year']); ?>" min="0" max="2100" step="1" style="width: 80px;">
                </label>

                <p class="description"><?php _e('Options for handling year values in dates.', 'heritagepress'); ?></p>
              </fieldset>
            </td>
          </tr>
        </table>
      </div>

      <!-- Advanced Tab -->
      <div id="tab-advanced" class="hp-tab-panel">
        <table class="form-table">
          <tr>
            <th scope="row"><?php _e('Character Set', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Character set options', 'heritagepress'); ?></legend>

                <label for="override_charset">
                  <input type="checkbox" name="override_charset" id="override_charset" value="1" <?php checked($default_settings['override_charset'], true); ?>>
                  <?php _e('Override character set detected in GEDCOM', 'heritagepress'); ?>
                </label>

                <div id="charset-options" <?php echo $default_settings['override_charset'] ? '' : 'style="display:none"'; ?>>
                  <select name="charset" id="charset">
                    <option value="UTF-8" <?php selected($default_settings['charset'], 'UTF-8'); ?>><?php _e('UTF-8', 'heritagepress'); ?></option>
                    <option value="ANSI" <?php selected($default_settings['charset'], 'ANSI'); ?>><?php _e('ANSI (Windows-1252)', 'heritagepress'); ?></option>
                    <option value="ASCII" <?php selected($default_settings['charset'], 'ASCII'); ?>><?php _e('ASCII', 'heritagepress'); ?></option>
                    <option value="ANSEL" <?php selected($default_settings['charset'], 'ANSEL'); ?>><?php _e('ANSEL (GEDCOM standard)', 'heritagepress'); ?></option>
                    <option value="UTF-16" <?php selected($default_settings['charset'], 'UTF-16'); ?>><?php _e('UTF-16', 'heritagepress'); ?></option>
                    <option value="Windows-1250" <?php selected($default_settings['charset'], 'Windows-1250'); ?>><?php _e('Windows-1250 (Central European)', 'heritagepress'); ?></option>
                    <option value="Windows-1251" <?php selected($default_settings['charset'], 'Windows-1251'); ?>><?php _e('Windows-1251 (Cyrillic)', 'heritagepress'); ?></option>
                    <option value="ISO-8859-1" <?php selected($default_settings['charset'], 'ISO-8859-1'); ?>><?php _e('ISO-8859-1 (Western European)', 'heritagepress'); ?></option>
                  </select>
                </div>
              </fieldset>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php _e('Import Method', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Import method options', 'heritagepress'); ?></legend>

                <label>
                  <input type="radio" name="import_method" value="standard" <?php checked($default_settings['import_method'], 'standard'); ?>>
                  <?php _e('Standard (process entire file at once)', 'heritagepress'); ?>
                </label><br>

                <label>
                  <input type="radio" name="import_method" value="chunked" <?php checked($default_settings['import_method'], 'chunked'); ?>>
                  <?php _e('Chunked (process in batches - better for large files)', 'heritagepress'); ?>
                </label><br>

                <div id="chunk-options" <?php echo $default_settings['import_method'] == 'chunked' ? '' : 'style="display:none"'; ?>>
                  <label for="chunk_size">
                    <?php _e('Records per batch:', 'heritagepress'); ?>
                    <input type="number" name="chunk_size" id="chunk_size" value="<?php echo intval($default_settings['chunk_size']); ?>" min="50" max="5000" step="50" style="width: 80px;">
                  </label>
                </div>

                <p class="description"><?php _e('Method used to process the GEDCOM file. For very large files, chunked processing is recommended.', 'heritagepress'); ?></p>
              </fieldset>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php _e('Connection Handling', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Connection handling options', 'heritagepress'); ?></legend>

                <label for="transaction_mode">
                  <input type="checkbox" name="transaction_mode" id="transaction_mode" value="1" <?php checked($default_settings['transaction_mode'], true); ?>>
                  <?php _e('Use database transactions (all-or-nothing import)', 'heritagepress'); ?>
                </label><br>

                <label for="timeout">
                  <?php _e('Script timeout in seconds:', 'heritagepress'); ?>
                  <input type="number" name="timeout" id="timeout" value="<?php echo intval($default_settings['timeout']); ?>" min="30" max="900" step="30" style="width: 80px;">
                </label>

                <p class="description"><?php _e('Advanced options for handling database connections during import.', 'heritagepress'); ?></p>
              </fieldset>
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <div class="hp-form-actions">
    <input type="submit" name="save_config" id="save_config" class="button button-primary" value="<?php esc_attr_e('Save and Continue', 'heritagepress'); ?>">
    &nbsp;<a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=validate" class="button"><?php _e('Back to Validation', 'heritagepress'); ?></a>
  </div>
</form>

<script>
  jQuery(document).ready(function($) {
    // Tab navigation
    $('.hp-tabs-nav a').on('click', function(e) {
      e.preventDefault();

      // Set active tab
      $('.hp-tabs-nav li').removeClass('active');
      $(this).parent().addClass('active');

      // Show active panel
      var target = $(this).attr('href');
      $('.hp-tab-panel').removeClass('active');
      $(target).addClass('active');
    });

    // Toggle displays based on checkbox selections
    $('#apply_privacy').on('change', function() {
      $('#privacy-options').toggle($(this).is(':checked'));
    });

    $('#override_charset').on('change', function() {
      $('#charset-options').toggle($(this).is(':checked'));
    });

    $('input[name="import_method"]').on('change', function() {
      $('#chunk-options').toggle($('input[name="import_method"]:checked').val() === 'chunked');
    });

    // Form submission
    $('#gedcom-config-form').on('submit', function() {
      $('<div class="loading-overlay"><span class="spinner is-active"></span> <?php _e('Saving configuration...', 'heritagepress'); ?></div>').appendTo('body');
    });
  });
</script>

<style>
  .hp-tabs-container {
    margin-top: 20px;
  }

  .hp-tabs-nav {
    display: flex;
    margin: 0;
    padding: 0;
    border-bottom: 1px solid #ccc;
  }

  .hp-tabs-nav li {
    list-style: none;
    margin: 0 0.5em 0 0;
    padding: 0;
  }

  .hp-tabs-nav a {
    display: block;
    padding: 8px 12px;
    background: #f1f1f1;
    text-decoration: none;
    border: 1px solid #ccc;
    border-bottom: none;
    color: #555;
  }

  .hp-tabs-nav li.active a {
    background: #fff;
    border-bottom-color: #fff;
    color: #000;
    margin-bottom: -1px;
    padding-bottom: 9px;
  }

  .hp-tab-panel {
    border: 1px solid #ccc;
    border-top: none;
    padding: 20px;
    background: #fff;
    display: none;
  }

  .hp-tab-panel.active {
    display: block;
  }

  #privacy-options,
  #charset-options,
  #chunk-options {
    margin-top: 10px;
    margin-left: 20px;
    padding-top: 10px;
  }
</style>
