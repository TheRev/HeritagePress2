<?php

/**
 * GEDCOM Import - People Settings Tab
 *
 * Fourth step of GEDCOM import process - configure people-specific import settings
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get import session data
$upload_data = isset($_SESSION['hp_gedcom_upload']) ? $_SESSION['hp_gedcom_upload'] : null;
$validation_results = isset($_SESSION['hp_gedcom_validation']) ? $_SESSION['hp_gedcom_validation'] : null;
$config_data = isset($_SESSION['hp_gedcom_config']) ? $_SESSION['hp_gedcom_config'] : null;
$file_path = isset($upload_data['file_path']) ? $upload_data['file_path'] : '';

// If no config data, redirect to config tab
if (!$config_data) {
  echo '<div class="error-box">';
  echo '<p>' . __('Import configuration has not been set. Please return to the Configure Import tab.', 'heritagepress') . '</p>';
  echo '<p><a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=config" class="button">' . __('Go to Configuration', 'heritagepress') . '</a></p>';
  echo '</div>';
  return;
}

// Program information
$program = isset($validation_results['program']) ? $validation_results['program'] : array('name' => __('Unknown', 'heritagepress'), 'version' => '');
$program_name = $program['name'];
$program_version = $program['version'];

// Get default settings based on program
$default_settings = hp_get_default_people_settings($program_name);

// Individual counts
$individual_count = isset($validation_results['stats']['individuals']) ? intval($validation_results['stats']['individuals']) : 0;
?>

<h2><?php _e('People Import Settings', 'heritagepress'); ?></h2>

<div class="message-box">
  <p><?php _e('Configure how individual records are imported and displayed. These settings control name formatting, events, facts, and relationships.', 'heritagepress'); ?></p>
  <p><strong><?php _e('Individuals to Import:', 'heritagepress'); ?></strong> <?php echo number_format($individual_count); ?></p>
</div>

<form method="post" id="gedcom-people-form" class="hp-form">
  <?php wp_nonce_field('heritagepress_gedcom_people', 'gedcom_people_nonce'); ?>
  <input type="hidden" name="action" value="hp_save_gedcom_people">
  <input type="hidden" name="file_path" value="<?php echo esc_attr($file_path); ?>">

  <div class="hp-tabs-container">
    <ul class="hp-tabs-nav">
      <li class="active"><a href="#tab-names"><?php _e('Names', 'heritagepress'); ?></a></li>
      <li><a href="#tab-events"><?php _e('Events & Facts', 'heritagepress'); ?></a></li>
      <li><a href="#tab-relationships"><?php _e('Relationships', 'heritagepress'); ?></a></li>
      <li><a href="#tab-advanced-people"><?php _e('Advanced', 'heritagepress'); ?></a></li>
    </ul>

    <div class="hp-tabs-content">
      <!-- Names Tab -->
      <div id="tab-names" class="hp-tab-panel active">
        <table class="form-table">
          <tr>
            <th scope="row"><?php _e('Name Format', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Name format options', 'heritagepress'); ?></legend>

                <label>
                  <input type="radio" name="name_format" value="surname_first" <?php checked($default_settings['name_format'], 'surname_first'); ?>>
                  <?php _e('Surname first (SURNAME, Given names)', 'heritagepress'); ?>
                </label><br>

                <label>
                  <input type="radio" name="name_format" value="given_first" <?php checked($default_settings['name_format'], 'given_first'); ?>>
                  <?php _e('Given names first (Given names SURNAME)', 'heritagepress'); ?>
                </label>

                <p class="description"><?php _e('Determines how names are displayed throughout the site.', 'heritagepress'); ?></p>
              </fieldset>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php _e('Name Options', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Name handling options', 'heritagepress'); ?></legend>

                <label for="capitalize_surnames">
                  <input type="checkbox" name="capitalize_surnames" id="capitalize_surnames" value="1" <?php checked($default_settings['capitalize_surnames'], true); ?>>
                  <?php _e('Capitalize surnames', 'heritagepress'); ?>
                </label><br>

                <label for="extract_nicknames">
                  <input type="checkbox" name="extract_nicknames" id="extract_nicknames" value="1" <?php checked($default_settings['extract_nicknames'], true); ?>>
                  <?php _e('Extract nicknames from names (text in quotes)', 'heritagepress'); ?>
                </label><br>

                <label for="import_alternate_names">
                  <input type="checkbox" name="import_alternate_names" id="import_alternate_names" value="1" <?php checked($default_settings['import_alternate_names'], true); ?>>
                  <?php _e('Import alternate names and name variations', 'heritagepress'); ?>
                </label>
              </fieldset>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php _e('Prefix/Suffix', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Prefix and suffix options', 'heritagepress'); ?></legend>

                <label for="import_name_prefixes">
                  <input type="checkbox" name="import_name_prefixes" id="import_name_prefixes" value="1" <?php checked($default_settings['import_name_prefixes'], true); ?>>
                  <?php _e('Import name prefixes (Dr., Rev., etc.)', 'heritagepress'); ?>
                </label><br>

                <label for="import_name_suffixes">
                  <input type="checkbox" name="import_name_suffixes" id="import_name_suffixes" value="1" <?php checked($default_settings['import_name_suffixes'], true); ?>>
                  <?php _e('Import name suffixes (Jr., Sr., III, etc.)', 'heritagepress'); ?>
                </label>
              </fieldset>
            </td>
          </tr>
        </table>
      </div>

      <!-- Events & Facts Tab -->
      <div id="tab-events" class="hp-tab-panel">
        <table class="form-table">
          <tr>
            <th scope="row"><?php _e('Standard Events', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Standard events options', 'heritagepress'); ?></legend>

                <div class="event-columns">
                  <div class="event-column">
                    <label for="import_birth">
                      <input type="checkbox" name="import_birth" id="import_birth" value="1" <?php checked($default_settings['import_birth'], true); ?>>
                      <?php _e('Birth', 'heritagepress'); ?>
                    </label><br>

                    <label for="import_christening">
                      <input type="checkbox" name="import_christening" id="import_christening" value="1" <?php checked($default_settings['import_christening'], true); ?>>
                      <?php _e('Christening/Baptism', 'heritagepress'); ?>
                    </label><br>

                    <label for="import_death">
                      <input type="checkbox" name="import_death" id="import_death" value="1" <?php checked($default_settings['import_death'], true); ?>>
                      <?php _e('Death', 'heritagepress'); ?>
                    </label><br>

                    <label for="import_burial">
                      <input type="checkbox" name="import_burial" id="import_burial" value="1" <?php checked($default_settings['import_burial'], true); ?>>
                      <?php _e('Burial', 'heritagepress'); ?>
                    </label>
                  </div>

                  <div class="event-column">
                    <label for="import_occupation">
                      <input type="checkbox" name="import_occupation" id="import_occupation" value="1" <?php checked($default_settings['import_occupation'], true); ?>>
                      <?php _e('Occupation', 'heritagepress'); ?>
                    </label><br>

                    <label for="import_education">
                      <input type="checkbox" name="import_education" id="import_education" value="1" <?php checked($default_settings['import_education'], true); ?>>
                      <?php _e('Education', 'heritagepress'); ?>
                    </label><br>

                    <label for="import_residence">
                      <input type="checkbox" name="import_residence" id="import_residence" value="1" <?php checked($default_settings['import_residence'], true); ?>>
                      <?php _e('Residence', 'heritagepress'); ?>
                    </label><br>

                    <label for="import_religion">
                      <input type="checkbox" name="import_religion" id="import_religion" value="1" <?php checked($default_settings['import_religion'], true); ?>>
                      <?php _e('Religion', 'heritagepress'); ?>
                    </label>
                  </div>
                </div>
              </fieldset>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php _e('Additional Facts', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Additional facts options', 'heritagepress'); ?></legend>

                <div class="event-columns">
                  <div class="event-column">
                    <label for="import_military">
                      <input type="checkbox" name="import_military" id="import_military" value="1" <?php checked($default_settings['import_military'], true); ?>>
                      <?php _e('Military service', 'heritagepress'); ?>
                    </label><br>

                    <label for="import_medical">
                      <input type="checkbox" name="import_medical" id="import_medical" value="1" <?php checked($default_settings['import_medical'], true); ?>>
                      <?php _e('Medical information', 'heritagepress'); ?>
                    </label><br>
                  </div>

                  <div class="event-column">
                    <label for="import_physical">
                      <input type="checkbox" name="import_physical" id="import_physical" value="1" <?php checked($default_settings['import_physical'], true); ?>>
                      <?php _e('Physical descriptions', 'heritagepress'); ?>
                    </label><br>

                    <label for="import_immigration">
                      <input type="checkbox" name="import_immigration" id="import_immigration" value="1" <?php checked($default_settings['import_immigration'], true); ?>>
                      <?php _e('Immigration/Emigration', 'heritagepress'); ?>
                    </label><br>
                  </div>
                </div>
              </fieldset>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php _e('Fact Handling', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Fact handling options', 'heritagepress'); ?></legend>

                <label for="merge_same_type_events">
                  <input type="checkbox" name="merge_same_type_events" id="merge_same_type_events" value="1" <?php checked($default_settings['merge_same_type_events'], true); ?>>
                  <?php _e('Merge multiple events of same type (e.g., multiple residences)', 'heritagepress'); ?>
                </label><br>

                <label for="standardize_event_names">
                  <input type="checkbox" name="standardize_event_names" id="standardize_event_names" value="1" <?php checked($default_settings['standardize_event_names'], true); ?>>
                  <?php _e('Standardize event and fact names', 'heritagepress'); ?>
                </label>
              </fieldset>
            </td>
          </tr>
        </table>
      </div>

      <!-- Relationships Tab -->
      <div id="tab-relationships" class="hp-tab-panel">
        <table class="form-table">
          <tr>
            <th scope="row"><?php _e('Family Events', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Family events options', 'heritagepress'); ?></legend>

                <div class="event-columns">
                  <div class="event-column">
                    <label for="import_marriage">
                      <input type="checkbox" name="import_marriage" id="import_marriage" value="1" <?php checked($default_settings['import_marriage'], true); ?>>
                      <?php _e('Marriage', 'heritagepress'); ?>
                    </label><br>

                    <label for="import_divorce">
                      <input type="checkbox" name="import_divorce" id="import_divorce" value="1" <?php checked($default_settings['import_divorce'], true); ?>>
                      <?php _e('Divorce', 'heritagepress'); ?>
                    </label><br>
                  </div>

                  <div class="event-column">
                    <label for="import_engagement">
                      <input type="checkbox" name="import_engagement" id="import_engagement" value="1" <?php checked($default_settings['import_engagement'], true); ?>>
                      <?php _e('Engagement', 'heritagepress'); ?>
                    </label><br>

                    <label for="import_family_events">
                      <input type="checkbox" name="import_family_events" id="import_family_events" value="1" <?php checked($default_settings['import_family_events'], true); ?>>
                      <?php _e('Other family events', 'heritagepress'); ?>
                    </label><br>
                  </div>
                </div>
              </fieldset>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php _e('Relationship Types', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Relationship types options', 'heritagepress'); ?></legend>

                <label for="import_parent_relationships">
                  <input type="checkbox" name="import_parent_relationships" id="import_parent_relationships" value="1" <?php checked($default_settings['import_parent_relationships'], true); ?>>
                  <?php _e('Import parent-child relationships', 'heritagepress'); ?>
                </label><br>

                <label for="import_spouse_relationships">
                  <input type="checkbox" name="import_spouse_relationships" id="import_spouse_relationships" value="1" <?php checked($default_settings['import_spouse_relationships'], true); ?>>
                  <?php _e('Import spouse relationships', 'heritagepress'); ?>
                </label><br>

                <label for="import_sibling_relationships">
                  <input type="checkbox" name="import_sibling_relationships" id="import_sibling_relationships" value="1" <?php checked($default_settings['import_sibling_relationships'], true); ?>>
                  <?php _e('Calculate and import sibling relationships', 'heritagepress'); ?>
                </label><br>

                <label for="import_step_relationships">
                  <input type="checkbox" name="import_step_relationships" id="import_step_relationships" value="1" <?php checked($default_settings['import_step_relationships'], true); ?>>
                  <?php _e('Calculate and import step-relationships', 'heritagepress'); ?>
                </label>
              </fieldset>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php _e('Relationship Options', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Relationship options', 'heritagepress'); ?></legend>

                <label for="import_adoption">
                  <input type="checkbox" name="import_adoption" id="import_adoption" value="1" <?php checked($default_settings['import_adoption'], true); ?>>
                  <?php _e('Import adoption information', 'heritagepress'); ?>
                </label><br>

                <label for="import_relationship_notes">
                  <input type="checkbox" name="import_relationship_notes" id="import_relationship_notes" value="1" <?php checked($default_settings['import_relationship_notes'], true); ?>>
                  <?php _e('Import relationship notes', 'heritagepress'); ?>
                </label>
              </fieldset>
            </td>
          </tr>
        </table>
      </div>

      <!-- Advanced Tab -->
      <div id="tab-advanced-people" class="hp-tab-panel">
        <table class="form-table">
          <tr>
            <th scope="row"><?php _e('Data Integration', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('Data integration options', 'heritagepress'); ?></legend>

                <label for="merge_individuals">
                  <input type="checkbox" name="merge_individuals" id="merge_individuals" value="1" <?php checked($default_settings['merge_individuals'], true); ?>>
                  <?php _e('Merge duplicate individuals (by ID)', 'heritagepress'); ?>
                </label><br>

                <label for="link_existing_sources">
                  <input type="checkbox" name="link_existing_sources" id="link_existing_sources" value="1" <?php checked($default_settings['link_existing_sources'], true); ?>>
                  <?php _e('Link to existing sources when possible', 'heritagepress'); ?>
                </label>
              </fieldset>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php _e('ID Handling', 'heritagepress'); ?></th>
            <td>
              <fieldset>
                <legend class="screen-reader-text"><?php _e('ID handling options', 'heritagepress'); ?></legend>

                <label>
                  <input type="radio" name="id_handling" value="preserve" <?php checked($default_settings['id_handling'], 'preserve'); ?>>
                  <?php _e('Preserve original IDs from GEDCOM if possible', 'heritagepress'); ?>
                </label><br>

                <label>
                  <input type="radio" name="id_handling" value="generate" <?php checked($default_settings['id_handling'], 'generate'); ?>>
                  <?php _e('Generate new IDs for all records', 'heritagepress'); ?>
                </label><br>

                <label>
                  <input type="radio" name="id_handling" value="prefix" <?php checked($default_settings['id_handling'], 'prefix'); ?>>
                  <?php _e('Add prefix to original IDs', 'heritagepress'); ?>
                </label>

                <div id="id-prefix-container" <?php echo ($default_settings['id_handling'] === 'prefix') ? '' : 'style="display:none"'; ?>>
                  <label for="id_prefix">
                    <?php _e('ID Prefix:', 'heritagepress'); ?>
                    <input type="text" name="id_prefix" id="id_prefix" value="<?php echo esc_attr($default_settings['id_prefix']); ?>" class="small-text">
                  </label>
                </div>
              </fieldset>
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <div class="hp-form-actions">
    <?php submit_button(__('Save People Settings', 'heritagepress'), 'primary', 'save_people_settings', false); ?>
    &nbsp;<a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=config" class="button"><?php _e('Back to Configuration', 'heritagepress'); ?></a>
    &nbsp;<a href="?page=heritagepress&section=import-export&tab=gedcom-import&step=media" class="button button-secondary"><?php _e('Skip to Media Options', 'heritagepress'); ?></a>
  </div>
</form>

<script>
  jQuery(document).ready(function($) {
    // Toggle ID prefix container based on selection
    $('input[name="id_handling"]').on('change', function() {
      if ($(this).val() === 'prefix') {
        $('#id-prefix-container').show();
      } else {
        $('#id-prefix-container').hide();
      }
    });

    // Tab navigation
    $('.hp-tabs-nav a').on('click', function(e) {
      e.preventDefault();
      var target = $(this).attr('href');

      // Update active states
      $('.hp-tabs-nav li').removeClass('active');
      $(this).parent().addClass('active');

      // Show selected panel
      $('.hp-tab-panel').removeClass('active');
      $(target).addClass('active');
    });

    // Form submission
    $('#gedcom-people-form').on('submit', function() {
      $('<div class="loading-overlay"><span class="spinner is-active"></span> <?php _e('Saving people settings...', 'heritagepress'); ?></div>').appendTo('body');
    });
  });
</script>

<style>
  .event-columns {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
  }

  .event-column {
    min-width: 200px;
  }

  .hp-tabs-container {
    margin-bottom: 30px;
  }

  .hp-tabs-nav {
    display: flex;
    margin: 0;
    padding: 0;
    list-style: none;
    border-bottom: 1px solid #ddd;
  }

  .hp-tabs-nav li {
    margin: 0 0.5em 0 0;
  }

  .hp-tabs-nav li a {
    display: block;
    padding: 0.5em 1em;
    text-decoration: none;
    border: 1px solid #ddd;
    border-bottom: none;
    background: #f5f5f5;
  }

  .hp-tabs-nav li.active a {
    background: #fff;
    border-bottom: 1px solid #fff;
    margin-bottom: -1px;
  }

  .hp-tab-panel {
    display: none;
    padding: 20px;
    border: 1px solid #ddd;
    border-top: none;
  }

  .hp-tab-panel.active {
    display: block;
  }
</style>
