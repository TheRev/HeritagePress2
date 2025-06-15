<?php

/**
 * Edit Person Tab
 * Complete person editing form with all TNG fields
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

<div class="edit-person-section">
  <div class="person-form-card">
    <form method="post" id="edit-person-form" class="person-form">
      <?php wp_nonce_field('heritagepress_people_action', '_wpnonce'); ?>
      <input type="hidden" name="action" value="update_person">
      <input type="hidden" name="original_personID" value="<?php echo esc_attr($person_id); ?>">
      <input type="hidden" name="original_gedcom" value="<?php echo esc_attr($tree); ?>">

      <div class="form-header">
        <h3><?php printf(__('Edit Person: %s', 'heritagepress'), esc_html($person_data['firstname'] . ' ' . $person_data['lastname'])); ?></h3>
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

      <!-- Tree Selection -->
      <div class="form-section">
        <h4><?php _e('Tree Assignment', 'heritagepress'); ?></h4>
        <div class="form-row">
          <div class="form-field">
            <label for="gedcom"><?php _e('Tree:', 'heritagepress'); ?> *</label>
            <select id="gedcom" name="gedcom" required>
              <?php foreach ($trees_result as $tree_row): ?>
                <option value="<?php echo esc_attr($tree_row['gedcom']); ?>" <?php selected($person_data['gedcom'], $tree_row['gedcom']); ?>>
                  <?php echo esc_html($tree_row['treename']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-field">
            <label for="personID"><?php _e('Person ID:', 'heritagepress'); ?> *</label>
            <input type="text" id="personID" name="personID" value="<?php echo esc_attr($person_data['personID']); ?>" required>
            <button type="button" id="check-person-id" class="button button-secondary"><?php _e('Check Availability', 'heritagepress'); ?></button>
          </div>
        </div>
      </div>

      <!-- Name Information -->
      <div class="form-section">
        <h4><?php _e('Name Information', 'heritagepress'); ?></h4>

        <div class="form-row">
          <div class="form-field">
            <label for="prefix"><?php _e('Name Prefix:', 'heritagepress'); ?></label>
            <input type="text" id="prefix" name="prefix" value="<?php echo esc_attr($person_data['prefix']); ?>" placeholder="<?php _e('Dr., Rev., etc.', 'heritagepress'); ?>">
          </div>
          <div class="form-field">
            <label for="firstname"><?php _e('First Name:', 'heritagepress'); ?> *</label>
            <input type="text" id="firstname" name="firstname" value="<?php echo esc_attr($person_data['firstname']); ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="lnprefix"><?php _e('Last Name Prefix:', 'heritagepress'); ?></label>
            <input type="text" id="lnprefix" name="lnprefix" value="<?php echo esc_attr($person_data['lnprefix']); ?>" placeholder="<?php _e('von, de, van, etc.', 'heritagepress'); ?>">
          </div>
          <div class="form-field">
            <label for="lastname"><?php _e('Last Name:', 'heritagepress'); ?> *</label>
            <input type="text" id="lastname" name="lastname" value="<?php echo esc_attr($person_data['lastname']); ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="suffix"><?php _e('Name Suffix:', 'heritagepress'); ?></label>
            <input type="text" id="suffix" name="suffix" value="<?php echo esc_attr($person_data['suffix']); ?>" placeholder="<?php _e('Jr., Sr., III, etc.', 'heritagepress'); ?>">
          </div>
          <div class="form-field">
            <label for="nickname"><?php _e('Nickname:', 'heritagepress'); ?></label>
            <input type="text" id="nickname" name="nickname" value="<?php echo esc_attr($person_data['nickname']); ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="nameorder"><?php _e('Name Order:', 'heritagepress'); ?></label>
            <select id="nameorder" name="nameorder">
              <option value="1" <?php selected($person_data['nameorder'], '1'); ?>><?php _e('First Last', 'heritagepress'); ?></option>
              <option value="0" <?php selected($person_data['nameorder'], '0'); ?>><?php _e('Last, First', 'heritagepress'); ?></option>
            </select>
          </div>
          <div class="form-field">
            <label for="sex"><?php _e('Gender:', 'heritagepress'); ?></label>
            <select id="sex" name="sex">
              <option value=""><?php _e('Unknown', 'heritagepress'); ?></option>
              <option value="M" <?php selected($person_data['sex'], 'M'); ?>><?php _e('Male', 'heritagepress'); ?></option>
              <option value="F" <?php selected($person_data['sex'], 'F'); ?>><?php _e('Female', 'heritagepress'); ?></option>
            </select>
          </div>
        </div>
      </div>

      <!-- Birth Information -->
      <div class="form-section">
        <h4><?php _e('Birth Information', 'heritagepress'); ?></h4>

        <div class="form-row">
          <div class="form-field">
            <?php echo HP_Date_Validator::render_date_field([
              'id' => 'birthdate',
              'name' => 'birthdate',
              'value' => $person_data['birthdate'],
              'label' => __('Birth Date:', 'heritagepress'),
              'placeholder' => __('DD MMM YYYY or partial dates', 'heritagepress'),
              'help_text' => __('Enter birth date in genealogy format', 'heritagepress')
            ]); ?>
          </div>
          <div class="form-field">
            <label for="birthplace"><?php _e('Birth Place:', 'heritagepress'); ?></label>
            <input type="text" id="birthplace" name="birthplace" value="<?php echo esc_attr($person_data['birthplace']); ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <?php echo HP_Date_Validator::render_date_field([
              'id' => 'altbirthdate',
              'name' => 'altbirthdate',
              'value' => $person_data['altbirthdate'],
              'label' => __('Alt. Birth Date:', 'heritagepress'),
              'placeholder' => __('Christening date, etc.', 'heritagepress'),
              'show_examples' => false
            ]); ?>
          </div>
          <div class="form-field">
            <label for="altbirthplace"><?php _e('Alt. Birth Place:', 'heritagepress'); ?></label>
            <input type="text" id="altbirthplace" name="altbirthplace" value="<?php echo esc_attr($person_data['altbirthplace']); ?>">
          </div>
        </div>
      </div>

      <!-- Death Information -->
      <div class="form-section">
        <h4><?php _e('Death Information', 'heritagepress'); ?></h4>

        <div class="form-row">
          <div class="form-field">
            <?php echo HP_Date_Validator::render_date_field([
              'id' => 'deathdate',
              'name' => 'deathdate',
              'value' => $person_data['deathdate'],
              'label' => __('Death Date:', 'heritagepress'),
              'placeholder' => __('DD MMM YYYY or partial dates', 'heritagepress'),
              'show_examples' => false
            ]); ?>
          </div>
          <div class="form-field">
            <label for="deathplace"><?php _e('Death Place:', 'heritagepress'); ?></label>
            <input type="text" id="deathplace" name="deathplace" value="<?php echo esc_attr($person_data['deathplace']); ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <?php echo HP_Date_Validator::render_date_field([
              'id' => 'burialdate',
              'name' => 'burialdate',
              'value' => $person_data['burialdate'],
              'label' => __('Burial Date:', 'heritagepress'),
              'placeholder' => __('DD MMM YYYY or partial dates', 'heritagepress'),
              'show_examples' => false
            ]); ?>
          </div>
          <div class="form-field">
            <label for="burialplace"><?php _e('Burial Place:', 'heritagepress'); ?></label>
            <input type="text" id="burialplace" name="burialplace" value="<?php echo esc_attr($person_data['burialplace']); ?>">
          </div>
        </div>
      </div>

      <!-- Privacy Settings -->
      <div class="form-section">
        <h4><?php _e('Privacy & Status', 'heritagepress'); ?></h4>

        <div class="form-row">
          <div class="form-field checkbox-field">
            <label>
              <input type="checkbox" id="living" name="living" value="1" <?php checked($person_data['living'], '1'); ?>>
              <?php _e('This person is living', 'heritagepress'); ?>
            </label>
          </div>
          <div class="form-field checkbox-field">
            <label>
              <input type="checkbox" id="private" name="private" value="1" <?php checked($person_data['private'], '1'); ?>>
              <?php _e('Mark as private', 'heritagepress'); ?>
            </label>
          </div>
        </div>
      </div>

      <!-- Additional Information -->
      <div class="form-section">
        <h4><?php _e('Additional Information', 'heritagepress'); ?></h4>

        <div class="form-row">
          <div class="form-field full-width">
            <label for="notes"><?php _e('Notes:', 'heritagepress'); ?></label>
            <textarea id="notes" name="notes" rows="4"><?php echo esc_textarea($person_data['notes']); ?></textarea>
          </div>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="gedcom_id"><?php _e('GEDCOM ID:', 'heritagepress'); ?></label>
            <input type="text" id="gedcom_id" name="gedcom_id" value="<?php echo esc_attr($person_data['gedcom_id']); ?>" readonly>
            <small class="description"><?php _e('Original GEDCOM identifier (read-only)', 'heritagepress'); ?></small>
          </div>
          <div class="form-field">
            <label for="changedby"><?php _e('Last Changed By:', 'heritagepress'); ?></label>
            <input type="text" value="<?php echo esc_attr($person_data['changedby']); ?>" readonly>
            <small class="description"><?php _e('User who last modified this record', 'heritagepress'); ?></small>
          </div>
        </div>
      </div>

      <!-- Submit Actions -->
      <div class="form-actions">
        <button type="submit" class="button button-primary button-large"><?php _e('Update Person', 'heritagepress'); ?></button>
        <a href="<?php echo admin_url('admin.php?page=heritagepress-people&tab=browse'); ?>" class="button button-secondary"><?php _e('Cancel', 'heritagepress'); ?></a>

        <div class="form-actions-secondary">
          <button type="button" id="duplicate-person" class="button"><?php _e('Duplicate Person', 'heritagepress'); ?></button>
          <button type="button" id="delete-person" class="button button-danger"><?php _e('Delete Person', 'heritagepress'); ?></button>
        </div>
      </div>
    </form>
  </div>

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
        <!-- Media will be loaded here -->
        <p><em><?php _e('Media management will be available in a future update.', 'heritagepress'); ?></em></p>
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
  });
</script>
