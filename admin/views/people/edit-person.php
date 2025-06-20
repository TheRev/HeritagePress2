<?php

/**
 * Edit Person Tab
 * Complete person editing form
 * Handles person data retrieval, form submission, and validation
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get person data
$person_id = isset($_GET['personID']) ? sanitize_text_field($_GET['personID']) : '';
$tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';

if (empty($person_id) || empty($tree)) {
  echo '<div class="notice notice-error"><p>' . __('Invalid person ID or tree specified.', 'heritagepress') . '</p></div>';
  return;
}

// Fetch person data
$people_table = $wpdb->prefix . 'hp_people';
$person_query = $wpdb->prepare(
  "SELECT * FROM $people_table WHERE personID = %s AND gedcom = %s",
  $person_id,
  $tree
);
$person_data = $wpdb->get_row($person_query, ARRAY_A);

if (!$person_data) {
  echo '<div class="notice notice-error"><p>' . __('Person not found.', 'heritagepress') . '</p></div>';
  return;
}

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, ARRAY_A);

// Handle form submission
if (isset($_POST['action']) && $_POST['action'] === 'update_person') {
  // Pre-fill form with submitted data on error
  foreach ($person_data as $key => $value) {
    if (isset($_POST[$key])) {
      $person_data[$key] = sanitize_text_field($_POST[$key]);
    }
  }
}
?>

<div class="edit-person-section"> <!-- Master Expand/Collapse Control -->
  <div class="master-toggle-controls">
    <button type="button" id="master-toggle" class="button button-secondary">
      <span class="dashicons dashicons-arrow-right-alt2" id="master-toggle-icon"></span>
      <span id="master-toggle-text"><?php _e('Expand All Sections', 'heritagepress'); ?></span>
    </button>
  </div>

  <form method="post" id="edit-person-form" class="person-form">
    <?php wp_nonce_field('heritagepress_people_action', '_wpnonce'); ?>
    <input type="hidden" name="action" value="update_person">
    <input type="hidden" name="original_personID" value="<?php echo esc_attr($person_id); ?>">
    <input type="hidden" name="original_gedcom" value="<?php echo esc_attr($tree); ?>">

    <!-- Header Information Card -->
    <div class="person-form-card" id="header-card">
      <div class="person-form-card-header">
        <h3 class="person-form-card-title">
          <span class="dashicons dashicons-admin-users"></span>
          <?php printf(__('Edit Person: %s', 'heritagepress'), esc_html($person_data['firstname'] . ' ' . $person_data['lastname'])); ?>
        </h3>
      </div>
      <div class="person-form-card-body">
        <p class="description"><?php _e('Modify the person\'s information below. Fields marked with * are required.', 'heritagepress'); ?></p>
        <div class="person-summary">
          <div class="person-meta">
            <span class="person-id"><strong><?php _e('ID:', 'heritagepress'); ?></strong> <?php echo esc_html($person_data['personID']); ?></span>
            <span class="tree-name"><strong><?php _e('Tree:', 'heritagepress'); ?></strong> <?php echo esc_html($tree); ?></span>
            <?php if (!empty($person_data['changedate'])): ?>
              <span class="last-modified"><strong><?php _e('Last Modified:', 'heritagepress'); ?></strong> <?php echo esc_html($person_data['changedate']); ?></span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Tree Assignment Card -->
    <div class="person-form-card collapsible-card" data-card-name="tree-assignment">
      <div class="person-form-card-header clickable-header">
        <h3 class="person-form-card-title">
          <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
          <span class="dashicons dashicons-networking"></span>
          <?php _e('Tree Assignment', 'heritagepress'); ?>
        </h3>
      </div>
      <div class="person-form-card-body collapsible-content">
        <table class="hp-form-table">
          <tr>
            <th><label for="gedcom"><?php _e('Tree:', 'heritagepress'); ?> *</label></th>
            <td>
              <select id="gedcom" name="gedcom" class="large-text" required>
                <?php foreach ($trees_result as $tree_row): ?>
                  <option value="<?php echo esc_attr($tree_row['gedcom']); ?>" <?php selected($person_data['gedcom'], $tree_row['gedcom']); ?>>
                    <?php echo esc_html($tree_row['treename']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <p class="description"><?php _e('Select the genealogy tree for this person', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="personID"><?php _e('Person ID:', 'heritagepress'); ?> *</label></th>
            <td>
              <div class="person-id-controls">
                <input type="text" id="personID" name="personID" class="medium-text" value="<?php echo esc_attr($person_data['personID']); ?>" required>
                <button type="button" id="check-person-id" class="button button-secondary"><?php _e('Check Availability', 'heritagepress'); ?></button>
              </div>
              <p class="description"><?php _e('Unique identifier for this person', 'heritagepress'); ?></p>
            </td>
          </tr>
        </table>
      </div>
    </div> <!-- Name Information Card -->
    <div class="person-form-card collapsible-card" data-card-name="name-information">
      <div class="person-form-card-header clickable-header">
        <h3 class="person-form-card-title">
          <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
          <span class="dashicons dashicons-id"></span>
          <?php _e('Name Information', 'heritagepress'); ?>
        </h3>
      </div>
      <div class="person-form-card-body collapsible-content">
        <table class="hp-form-table">
          <tr>
            <th><label for="prefix"><?php _e('Name Prefix:', 'heritagepress'); ?></label></th>
            <td>
              <input type="text" id="prefix" name="prefix" class="large-text" value="<?php echo esc_attr($person_data['prefix']); ?>" placeholder="<?php _e('Dr., Rev., etc.', 'heritagepress'); ?>">
              <p class="description"><?php _e('Title or honorific prefix', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="firstname"><?php _e('First Name:', 'heritagepress'); ?> *</label></th>
            <td>
              <input type="text" id="firstname" name="firstname" class="large-text" value="<?php echo esc_attr($person_data['firstname']); ?>" required>
              <p class="description"><?php _e('Enter the first and middle names', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="lnprefix"><?php _e('Last Name Prefix:', 'heritagepress'); ?></label></th>
            <td>
              <input type="text" id="lnprefix" name="lnprefix" class="large-text" value="<?php echo esc_attr($person_data['lnprefix']); ?>" placeholder="<?php _e('von, de, van, etc.', 'heritagepress'); ?>">
              <p class="description"><?php _e('Nobility or regional prefix', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="lastname"><?php _e('Last Name:', 'heritagepress'); ?> *</label></th>
            <td>
              <input type="text" id="lastname" name="lastname" class="large-text" value="<?php echo esc_attr($person_data['lastname']); ?>" required>
              <p class="description"><?php _e('Family surname', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="suffix"><?php _e('Name Suffix:', 'heritagepress'); ?></label></th>
            <td>
              <input type="text" id="suffix" name="suffix" class="large-text" value="<?php echo esc_attr($person_data['suffix']); ?>" placeholder="<?php _e('Jr., Sr., III, etc.', 'heritagepress'); ?>">
              <p class="description"><?php _e('Generational or professional suffix', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="nickname"><?php _e('Nickname:', 'heritagepress'); ?></label></th>
            <td>
              <input type="text" id="nickname" name="nickname" class="large-text" value="<?php echo esc_attr($person_data['nickname']); ?>">
              <p class="description"><?php _e('Common name or alias', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="nameorder"><?php _e('Name Order:', 'heritagepress'); ?></label></th>
            <td>
              <select id="nameorder" name="nameorder" class="large-text">
                <option value="1" <?php selected($person_data['nameorder'], '1'); ?>><?php _e('First Last', 'heritagepress'); ?></option>
                <option value="0" <?php selected($person_data['nameorder'], '0'); ?>><?php _e('Last, First', 'heritagepress'); ?></option>
              </select>
              <p class="description"><?php _e('Display format preference', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="sex"><?php _e('Gender:', 'heritagepress'); ?></label></th>
            <td>
              <select id="sex" name="sex" class="large-text">
                <option value=""><?php _e('Unknown', 'heritagepress'); ?></option>
                <option value="M" <?php selected($person_data['sex'], 'M'); ?>><?php _e('Male', 'heritagepress'); ?></option>
                <option value="F" <?php selected($person_data['sex'], 'F'); ?>><?php _e('Female', 'heritagepress'); ?></option>
              </select>
              <p class="description"><?php _e('Biological gender', 'heritagepress'); ?></p>
            </td>
          </tr>
        </table>
      </div>
    </div> <!-- Birth Information Card -->
    <div class="person-form-card collapsible-card" data-card-name="birth-information">
      <div class="person-form-card-header clickable-header">
        <h3 class="person-form-card-title">
          <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
          <span class="dashicons dashicons-calendar-alt"></span>
          <?php _e('Birth Information', 'heritagepress'); ?>
        </h3>
      </div>
      <div class="person-form-card-body collapsible-content">
        <table class="hp-form-table">
          <tr>
            <th><label for="birthdate"><?php _e('Birth Date:', 'heritagepress'); ?></label></th>
            <td>
              <?php echo HP_Date_Validator::render_date_field([
                'id' => 'birthdate',
                'name' => 'birthdate',
                'value' => $person_data['birthdate'],
                'placeholder' => __('DD MMM YYYY or partial dates', 'heritagepress'),
                'class' => 'large-text'
              ]); ?>
              <p class="description"><?php _e('Enter birth date in genealogy format', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="birthplace"><?php _e('Birth Place:', 'heritagepress'); ?></label></th>
            <td>
              <input type="text" id="birthplace" name="birthplace" class="large-text" value="<?php echo esc_attr($person_data['birthplace']); ?>">
              <p class="description"><?php _e('City, County, State, Country format preferred', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="altbirthdate"><?php _e('Alt. Birth Date:', 'heritagepress'); ?></label></th>
            <td>
              <?php echo HP_Date_Validator::render_date_field([
                'id' => 'altbirthdate',
                'name' => 'altbirthdate',
                'value' => $person_data['altbirthdate'],
                'placeholder' => __('Alternative birth date', 'heritagepress'),
                'class' => 'large-text'
              ]); ?>
              <p class="description"><?php _e('Alternative birth date if multiple sources exist', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="altbirthplace"><?php _e('Alt. Birth Place:', 'heritagepress'); ?></label></th>
            <td>
              <input type="text" id="altbirthplace" name="altbirthplace" class="large-text" value="<?php echo esc_attr($person_data['altbirthplace']); ?>">
              <p class="description"><?php _e('Alternative birth place if multiple sources exist', 'heritagepress'); ?></p>
            </td>
          </tr>
        </table>
      </div>
    </div> <!-- Death Information Card -->
    <div class="person-form-card collapsible-card" data-card-name="death-information">
      <div class="person-form-card-header clickable-header">
        <h3 class="person-form-card-title">
          <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
          <span class="dashicons dashicons-rest"></span>
          <?php _e('Death Information', 'heritagepress'); ?>
        </h3>
      </div>
      <div class="person-form-card-body collapsible-content">
        <table class="hp-form-table">
          <tr>
            <th><label for="deathdate"><?php _e('Death Date:', 'heritagepress'); ?></label></th>
            <td>
              <?php echo HP_Date_Validator::render_date_field([
                'id' => 'deathdate',
                'name' => 'deathdate',
                'value' => $person_data['deathdate'],
                'placeholder' => __('DD MMM YYYY or partial dates', 'heritagepress'),
                'class' => 'large-text'
              ]); ?>
              <p class="description"><?php _e('Enter death date in genealogy format', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="deathplace"><?php _e('Death Place:', 'heritagepress'); ?></label></th>
            <td>
              <input type="text" id="deathplace" name="deathplace" class="large-text" value="<?php echo esc_attr($person_data['deathplace']); ?>">
              <p class="description"><?php _e('Location where person died', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="burialdate"><?php _e('Burial Date:', 'heritagepress'); ?></label></th>
            <td>
              <?php echo HP_Date_Validator::render_date_field([
                'id' => 'burialdate',
                'name' => 'burialdate',
                'value' => $person_data['burialdate'],
                'placeholder' => __('DD MMM YYYY or partial dates', 'heritagepress'),
                'class' => 'large-text'
              ]); ?>
              <p class="description"><?php _e('Date of burial or interment', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="burialplace"><?php _e('Burial Place:', 'heritagepress'); ?></label></th>
            <td>
              <input type="text" id="burialplace" name="burialplace" class="large-text" value="<?php echo esc_attr($person_data['burialplace']); ?>">
              <p class="description"><?php _e('Cemetery or burial location', 'heritagepress'); ?></p>
            </td>
          </tr>
        </table>
      </div>
    </div> <!-- Privacy Settings Card -->
    <div class="person-form-card collapsible-card" data-card-name="privacy-status">
      <div class="person-form-card-header clickable-header">
        <h3 class="person-form-card-title">
          <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
          <span class="dashicons dashicons-lock"></span>
          <?php _e('Privacy & Status', 'heritagepress'); ?>
        </h3>
      </div>
      <div class="person-form-card-body collapsible-content">
        <table class="hp-form-table">
          <tr>
            <th><label for="living"><?php _e('Living Status:', 'heritagepress'); ?></label></th>
            <td>
              <label class="checkbox-field">
                <input type="checkbox" id="living" name="living" value="1" <?php checked($person_data['living'], '1'); ?>>
                <?php _e('This person is living', 'heritagepress'); ?>
              </label>
              <p class="description"><?php _e('Check if person is currently alive', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="private"><?php _e('Privacy:', 'heritagepress'); ?></label></th>
            <td>
              <label class="checkbox-field">
                <input type="checkbox" id="private" name="private" value="1" <?php checked($person_data['private'], '1'); ?>>
                <?php _e('Mark as private', 'heritagepress'); ?>
              </label>
              <p class="description"><?php _e('Hide from public view', 'heritagepress'); ?></p>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Additional Information Card -->
    <div class="person-form-card collapsible-card" data-card-name="additional-information">
      <div class="person-form-card-header clickable-header">
        <h3 class="person-form-card-title">
          <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
          <span class="dashicons dashicons-edit-large"></span>
          <?php _e('Additional Information', 'heritagepress'); ?>
        </h3>
      </div>
      <div class="person-form-card-body collapsible-content">
        <table class="hp-form-table">
          <tr>
            <th><label for="notes"><?php _e('Notes:', 'heritagepress'); ?></label></th>
            <td>
              <textarea id="notes" name="notes" class="large-text" rows="4"><?php echo esc_textarea(isset($person_data['notes']) ? $person_data['notes'] : ''); ?></textarea>
              <p class="description"><?php _e('Additional notes and information about this person', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="gedcom_id"><?php _e('GEDCOM ID:', 'heritagepress'); ?></label></th>
            <td>
              <input type="text" id="gedcom_id" name="gedcom_id" class="large-text" value="<?php echo esc_attr(isset($person_data['gedcom_id']) ? $person_data['gedcom_id'] : $person_data['personID']); ?>" readonly>
              <p class="description"><?php _e('Original GEDCOM identifier (read-only)', 'heritagepress'); ?></p>
            </td>
          </tr>
          <tr>
            <th><label for="changedby"><?php _e('Last Changed By:', 'heritagepress'); ?></label></th>
            <td>
              <input type="text" class="large-text" value="<?php echo esc_attr($person_data['changedby']); ?>" readonly>
              <p class="description"><?php _e('User who last modified this record', 'heritagepress'); ?></p>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <?php
    // Add Change Tree functionality
    heritagepress_add_change_tree_button('person', $person_data['personID'], $person_data['gedcom']);
    ?>

    <!-- Submit Actions Card -->
    <div class="person-form-card" id="submit-actions-card">
      <div class="person-form-card-body">
        <div class="form-actions">
          <button type="submit" class="button button-primary button-large"><?php _e('Update Person', 'heritagepress'); ?></button>
          <a href="<?php echo admin_url('admin.php?page=heritagepress-people&tab=browse'); ?>" class="button button-secondary"><?php _e('Cancel', 'heritagepress'); ?></a>

          <div class="form-actions-secondary">
            <button type="button" id="duplicate-person" class="button"><?php _e('Duplicate Person', 'heritagepress'); ?></button>
            <button type="button" id="delete-person" class="button button-danger"><?php _e('Delete Person', 'heritagepress'); ?></button>
          </div>
        </div>
      </div>
    </div>
  </form>

  <!-- Related Information Panels -->
  <div class="related-info-panels">
    <!-- Family Relationships -->
    <div class="panel">
      <h4><?php _e('Family Relationships', 'heritagepress'); ?></h4>
      <div class="relationship-list">
        <!-- Parents, Spouses, Children will be loaded here -->
        <p><em><?php _e('Family relationship management will be available in a future update.', 'heritagepress'); ?></em></p>
      </div>
    </div>

    <!-- Events -->
    <div class="panel">
      <h4><?php _e('Events', 'heritagepress'); ?></h4>
      <div class="event-list">
        <!-- Events will be loaded here -->
        <p><em><?php _e('Event management will be available in a future update.', 'heritagepress'); ?></em></p>
      </div>
    </div>

    <!-- Media -->
    <div class="panel">
      <h4><?php _e('Media', 'heritagepress'); ?></h4>
      <div class="media-list">
        <button type="button" id="hp-find-link-media-btn" class="button button-secondary" style="margin-bottom:10px;">
          <?php _e('Find and Link Media', 'heritagepress'); ?>
        </button>
        <div id="hp-linked-media-list"></div>
        <!-- Modal -->
        <div id="hp-find-link-media-modal" style="display:none;position:fixed;z-index:10000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);">
          <div style="background:#fff;max-width:600px;margin:60px auto;padding:20px;position:relative;box-shadow:0 2px 16px #333;">
            <button type="button" id="hp-close-media-modal" style="position:absolute;top:10px;right:10px;" class="button">&times;</button>
            <h3><?php _e('Find and Link Media', 'heritagepress'); ?></h3>
            <form id="hp-media-search-form" style="margin-bottom:10px;">
              <input type="text" id="hp-media-search-input" placeholder="<?php esc_attr_e('Search media...', 'heritagepress'); ?>" style="width:60%;">
              <select id="hp-media-search-tree">
                <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
                <?php foreach ($trees_result as $tree_row): ?>
                  <option value="<?php echo esc_attr($tree_row['gedcom']); ?>"><?php echo esc_html($tree_row['treename']); ?></option>
                <?php endforeach; ?>
              </select>
              <select id="hp-media-search-type">
                <option value=""><?php _e('All Types', 'heritagepress'); ?></option>
                <!-- TODO: Populate with media types -->
              </select>
              <button type="submit" class="button">Search</button>
            </form>
            <div id="hp-media-search-results" style="max-height:300px;overflow:auto;"></div>
            <button type="button" id="hp-link-media-btn" class="button button-primary" style="margin-top:10px;">Link Selected Media</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Sources -->
    <div class="panel">
      <h4><?php _e('Sources & Citations', 'heritagepress'); ?></h4>
      <div class="source-list">
        <!-- Sources will be loaded here -->
        <p><em><?php _e('Source management will be available in a future update.', 'heritagepress'); ?></em></p>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {
    // Collapsible Cards Functionality

    // Initialize card states from localStorage
    var cardStates = JSON.parse(localStorage.getItem('hp_edit_person_card_states') || '{}');
    var allCollapsedState = false;

    // Set initial card states - DEFAULT TO COLLAPSED (ignore localStorage for initial state)
    $('.collapsible-card').each(function() {
      const cardName = $(this).data('card-name');
      // Always start collapsed - don't use stored state for initial load
      $(this).addClass('collapsed');
      $(this).find('.collapsible-content').hide();
      $(this).find('.toggle-icon').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
      // Reset stored state to collapsed
      cardStates[cardName] = true;
    });

    // Save the default collapsed state
    localStorage.setItem('hp_edit_person_card_states', JSON.stringify(cardStates));

    // Update master toggle button state
    function updateMasterToggle() {
      const totalCards = $('.collapsible-card').length;
      const collapsedCards = $('.collapsible-card.collapsed').length;
      if (collapsedCards === totalCards) {
        allCollapsedState = true;
        $('#master-toggle-text').text('<?php _e('Expand All Sections', 'heritagepress'); ?>');
        $('#master-toggle-icon').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
      } else {
        allCollapsedState = false;
        $('#master-toggle-text').text('<?php _e('Collapse All Sections', 'heritagepress'); ?>');
        $('#master-toggle-icon').removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
      }
    }

    // Initial master toggle state
    updateMasterToggle();

    // Master toggle functionality
    $('#master-toggle').on('click', function() {
      if (allCollapsedState) {
        // Expand all
        $('.collapsible-card').removeClass('collapsed');
        $('.collapsible-content').slideDown(300);
        $('.collapsible-card .toggle-icon').removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');

        // Update stored states
        $('.collapsible-card').each(function() {
          const cardName = $(this).data('card-name');
          cardStates[cardName] = false;
        });
      } else {
        // Collapse all
        $('.collapsible-card').addClass('collapsed');
        $('.collapsible-content').slideUp(300);
        $('.collapsible-card .toggle-icon').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');

        // Update stored states
        $('.collapsible-card').each(function() {
          const cardName = $(this).data('card-name');
          cardStates[cardName] = true;
        });
      }

      // Save states and update master toggle
      localStorage.setItem('hp_edit_person_card_states', JSON.stringify(cardStates));
      setTimeout(updateMasterToggle, 350);
    });

    // Individual card toggle functionality
    $('.clickable-header').on('click', function() {
      const card = $(this).closest('.collapsible-card');
      const cardName = card.data('card-name');
      const content = card.find('.collapsible-content');
      const toggleIcon = card.find('.toggle-icon');

      if (card.hasClass('collapsed')) {
        // Expand
        card.removeClass('collapsed');
        content.slideDown(300);
        toggleIcon.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
        cardStates[cardName] = false;
      } else {
        // Collapse
        card.addClass('collapsed');
        content.slideUp(300);
        toggleIcon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
        cardStates[cardName] = true;
      }

      // Save state and update master toggle
      localStorage.setItem('hp_edit_person_card_states', JSON.stringify(cardStates));
      setTimeout(updateMasterToggle, 350);
    });

    // Check Person ID availability
    $('#check-person-id').on('click', function() {
      var personID = $('#personID').val().trim();
      var gedcom = $('#gedcom').val();
      var originalID = $('input[name="original_personID"]').val();

      if (!personID) {
        alert('<?php _e('Please enter a Person ID to check.', 'heritagepress'); ?>');
        return;
      }

      if (personID === originalID) {
        alert('<?php _e('This is the current Person ID for this person.', 'heritagepress'); ?>');
        return;
      }

      // AJAX call to check person ID
      $.post(ajaxurl, {
        action: 'hp_check_person_id',
        personID: personID,
        gedcom: gedcom,
        _wpnonce: '<?php echo wp_create_nonce('hp_check_person_id'); ?>'
      }, function(response) {
        if (response.success) {
          if (response.data.available) {
            alert('<?php _e('Person ID is available.', 'heritagepress'); ?>');
          } else {
            alert('<?php _e('Person ID is already in use. Please choose a different ID.', 'heritagepress'); ?>');
          }
        } else {
          alert('<?php _e('Failed to check Person ID availability.', 'heritagepress'); ?>');
        }
      });
    });

    // Form validation
    $('#edit-person-form').on('submit', function(e) {
      var firstname = $('#firstname').val().trim();
      var lastname = $('#lastname').val().trim();
      var gedcom = $('#gedcom').val();
      var personID = $('#personID').val().trim();

      if (!firstname || !lastname || !gedcom || !personID) {
        e.preventDefault();
        alert('<?php _e('Please fill in all required fields.', 'heritagepress'); ?>');
        return false;
      }
    });

    // Duplicate person functionality
    $('#duplicate-person').on('click', function() {
      if (confirm('<?php _e('This will create a copy of this person with a new Person ID. Continue?', 'heritagepress'); ?>')) {
        var form = $('#edit-person-form');
        var originalAction = form.attr('action') || '';

        // Change action to add_person and remove the original ID
        $('input[name="action"]').val('add_person');
        $('input[name="original_personID"]').remove();
        $('input[name="original_gedcom"]').remove();
        $('#personID').val(''); // Clear person ID for duplication

        form.submit();
      }
    });

    // Delete person functionality
    $('#delete-person').on('click', function() {
      if (confirm('<?php _e('Are you sure you want to delete this person? This action cannot be undone.', 'heritagepress'); ?>')) {
        var personID = $('input[name="original_personID"]').val();
        var gedcom = $('input[name="original_gedcom"]').val();

        // Create and submit delete form
        var form = $('<form method="post">')
          .append($('<input type="hidden" name="action" value="delete_person">'))
          .append($('<input type="hidden" name="personID" value="' + personID + '">'))
          .append($('<input type="hidden" name="gedcom" value="' + gedcom + '">'))
          .append('<?php echo wp_nonce_field('heritagepress_delete_person', '_wpnonce', true, false); ?>');

        $('body').append(form);
        form.submit();
      }
    });

    // Auto-check availability when Person ID changes
    var personIDTimeout;
    $('#personID').on('input', function() {
      clearTimeout(personIDTimeout);
      var personID = $(this).val().trim();
      var originalID = $('input[name="original_personID"]').val();

      if (personID && personID !== originalID) {
        personIDTimeout = setTimeout(function() {
          $('#check-person-id').click();
        }, 1000); // Check after 1 second of no typing
      }
    });

    // Warn about unsaved changes
    var formChanged = false;
    $('#edit-person-form input, #edit-person-form select, #edit-person-form textarea').on('change', function() {
      formChanged = true;
    });
    $(window).on('beforeunload', function() {
      if (formChanged) {
        return '<?php _e('You have unsaved changes. Are you sure you want to leave?', 'heritagepress'); ?>';
      }
    });

    $('#edit-person-form').on('submit', function() {
      formChanged = false; // Don't warn when actually submitting
    });

    // Media Find and Link functionality
    $('#hp-find-link-media-btn').on('click', function() {
      $('#hp-find-link-media-modal').fadeIn(200);
    });

    $('#hp-close-media-modal').on('click', function() {
      $('#hp-find-link-media-modal').fadeOut(200);
    });

    // Media search form submission
    $('#hp-media-search-form').on('submit', function(e) {
      e.preventDefault();
      var searchTerm = $('#hp-media-search-input').val().trim();
      var tree = $('#hp-media-search-tree').val();
      var type = $('#hp-media-search-type').val();

      // AJAX call to search media
      $.post(ajaxurl, {
        action: 'hp_search_media',
        term: searchTerm,
        tree: tree,
        type: type,
        _wpnonce: '<?php echo wp_create_nonce('hp_search_media'); ?>'
      }, function(response) {
        if (response.success) {
          var resultsContainer = $('#hp-media-search-results');
          resultsContainer.empty();

          if (response.data.length > 0) {
            $.each(response.data, function(index, media) {
              resultsContainer.append(
                '<div class="media-result">' +
                '<label>' +
                '<input type="checkbox" class="media-select" value="' + media.id + '"> ' +
                media.title +
                '</label>' +
                '</div>'
              );
            });
          } else {
            resultsContainer.append('<p><?php _e('No media found matching the criteria.', 'heritagepress'); ?></p>');
          }
        } else {
          alert('<?php _e('Media search failed. Please try again.', 'heritagepress'); ?>');
        }
      });
    });

    // Link selected media
    $('#hp-link-media-btn').on('click', function() {
      var selectedMedia = [];
      $('.media-select:checked').each(function() {
        selectedMedia.push($(this).val());
      });

      if (selectedMedia.length === 0) {
        alert('<?php _e('No media selected. Please select media to link.', 'heritagepress'); ?>');
        return;
      }

      // AJAX call to link media to person
      $.post(ajaxurl, {
        action: 'hp_link_media_to_person',
        personID: '<?php echo $person_id; ?>',
        mediaIDs: selectedMedia,
        _wpnonce: '<?php echo wp_create_nonce('hp_link_media_to_person'); ?>'
      }, function(response) {
        if (response.success) {
          // Update linked media list
          var linkedMediaContainer = $('#hp-linked-media-list');
          linkedMediaContainer.empty();

          $.each(response.data.linked_media, function(index, media) {
            linkedMediaContainer.append(
              '<div class="linked-media-item">' +
              '<img src="' + media.thumbnail + '" alt="' + media.title + '" class="media-thumbnail">' +
              '<span class="media-title">' + media.title + '</span>' +
              '<button type="button" class="button button-small button-danger remove-linked-media" data-media-id="' + media.id + '"><?php _e('Remove', 'heritagepress'); ?></button>' +
              '</div>'
            );
          });

          alert('<?php _e('Media linked successfully.', 'heritagepress'); ?>');
          $('#hp-find-link-media-modal').fadeOut(200);
        } else {
          alert('<?php _e('Failed to link media. Please try again.', 'heritagepress'); ?>');
        }
      });
    });

    // Remove linked media
    $(document).on('click', '.remove-linked-media', function() {
      var mediaID = $(this).data('media-id');
      var confirmation = confirm('<?php _e('Are you sure you want to remove this media link?', 'heritagepress'); ?>');

      if (confirmation) {
        // AJAX call to remove media link
        $.post(ajaxurl, {
          action: 'hp_remove_linked_media',
          personID: '<?php echo $person_id; ?>',
          mediaID: mediaID,
          _wpnonce: '<?php echo wp_create_nonce('hp_remove_linked_media'); ?>'
        }, function(response) {
          if (response.success) {
            // Remove media item from the list
            $('.linked-media-item').filter('[data-media-id="' + mediaID + '"]').remove();
            alert('<?php _e('Media link removed.', 'heritagepress'); ?>');
          } else {
            alert('<?php _e('Failed to remove media link. Please try again.', 'heritagepress'); ?>');
          }
        });
      }
    });
  });
</script>
