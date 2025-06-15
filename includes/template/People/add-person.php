<?php

/**
 * Add New Person Tab
 * Complete person creation form with all TNG fields
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, ARRAY_A);

// Default values for new person
$person_data = array(
  'personID' => '',
  'gedcom' => !empty($trees_result) ? $trees_result[0]['gedcom'] : '',
  'firstname' => '',
  'lastname' => '',
  'lnprefix' => '',
  'prefix' => '',
  'suffix' => '',
  'nickname' => '',
  'nameorder' => '1',
  'sex' => '',
  'birthdate' => '',
  'birthplace' => '',
  'altbirthdate' => '',
  'altbirthplace' => '',
  'deathdate' => '',
  'deathplace' => '',
  'burialdate' => '',
  'burialplace' => '',
  'living' => '0',
  'private' => '0',
  'notes' => '',
  'gedcom_id' => '',
  'famc' => '',
  'fams' => ''
);

// Handle form submission
if (isset($_POST['action']) && $_POST['action'] === 'add_person') {
  // Pre-fill form with submitted data on error
  foreach ($person_data as $key => $default) {
    if (isset($_POST[$key])) {
      $person_data[$key] = sanitize_text_field($_POST[$key]);
    }
  }
}
?>

<div class="add-person-section">
  <div class="person-form-card">
    <form method="post" id="add-person-form" class="person-form">
      <?php wp_nonce_field('heritagepress_people_action', '_wpnonce'); ?>
      <input type="hidden" name="action" value="add_person">

      <div class="form-header">
        <h3><?php _e('Add New Person', 'heritagepress'); ?></h3>
        <p class="description"><?php _e('Enter the person\'s information below. Fields marked with * are required.', 'heritagepress'); ?></p>
      </div>

      <!-- Tree Selection -->
      <div class="form-section">
        <h4><?php _e('Tree Assignment', 'heritagepress'); ?></h4>
        <div class="form-row">
          <div class="form-field">
            <label for="gedcom"><?php _e('Tree:', 'heritagepress'); ?> *</label>
            <select id="gedcom" name="gedcom" required>
              <?php foreach ($trees_result as $tree): ?>
                <option value="<?php echo esc_attr($tree['gedcom']); ?>" <?php selected($person_data['gedcom'], $tree['gedcom']); ?>>
                  <?php echo esc_html($tree['treename']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-field">
            <label for="personID"><?php _e('Person ID:', 'heritagepress'); ?></label>
            <input type="text" id="personID" name="personID" value="<?php echo esc_attr($person_data['personID']); ?>" placeholder="<?php _e('Leave blank to auto-generate', 'heritagepress'); ?>">
            <button type="button" id="generate-person-id" class="button button-secondary"><?php _e('Generate ID', 'heritagepress'); ?></button>
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
              'help_text' => __('Enter birth date in genealogy format. Examples: "2 OCT 1822", "OCT 1822", "ABT 1820", "BEF 1828"', 'heritagepress')
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
              'help_text' => __('Alternative date such as christening or baptism date', 'heritagepress'),
              'show_examples' => false
            ]); ?>
          </div>
          <div class="form-field">
            <label for="altbirthplace"><?php _e('Alt. Birth Place:', 'heritagepress'); ?></label>
            <input type="text" id="altbirthplace" name="altbirthplace" value="<?php echo esc_attr($person_data['altbirthplace']); ?>">
          </div>
        </div>
      </div> <!-- Death Information -->
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
      </div>

      <!-- Submit Actions -->
      <div class="form-actions">
        <button type="submit" class="button button-primary button-large"><?php _e('Add Person', 'heritagepress'); ?></button>
        <a href="<?php echo admin_url('admin.php?page=heritagepress-people&tab=browse'); ?>" class="button button-secondary"><?php _e('Cancel', 'heritagepress'); ?></a>

        <div class="form-actions-secondary">
          <button type="button" id="add-and-continue" class="button"><?php _e('Add & Add Another', 'heritagepress'); ?></button>
          <button type="button" id="preview-person" class="button"><?php _e('Preview', 'heritagepress'); ?></button>
        </div>
      </div>
    </form>
  </div>

  <!-- Quick Actions Panel -->
  <div class="quick-actions-panel">
    <h4><?php _e('Quick Actions', 'heritagepress'); ?></h4>
    <div class="action-links">
      <a href="#" class="action-link" data-action="add-spouse">
        <span class="dashicons dashicons-heart"></span>
        <?php _e('Add Spouse/Partner', 'heritagepress'); ?>
      </a>
      <a href="#" class="action-link" data-action="add-child">
        <span class="dashicons dashicons-groups"></span>
        <?php _e('Add Child', 'heritagepress'); ?>
      </a>
      <a href="#" class="action-link" data-action="add-parent">
        <span class="dashicons dashicons-admin-users"></span>
        <?php _e('Add Parent', 'heritagepress'); ?>
      </a>
    </div>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {
    // Generate Person ID
    $('#generate-person-id').on('click', function() {
      var gedcom = $('#gedcom').val();
      if (!gedcom) {
        alert('<?php _e('Please select a tree first.', 'heritagepress'); ?>');
        return;
      }

      // AJAX call to generate person ID
      $.post(ajaxurl, {
        action: 'hp_generate_person_id',
        gedcom: gedcom,
        _wpnonce: '<?php echo wp_create_nonce('hp_generate_person_id'); ?>'
      }, function(response) {
        if (response.success) {
          $('#personID').val(response.data.personID);
        } else {
          alert('<?php _e('Failed to generate Person ID.', 'heritagepress'); ?>');
        }
      });
    });

    // Form validation
    $('#add-person-form').on('submit', function(e) {
      var firstname = $('#firstname').val().trim();
      var lastname = $('#lastname').val().trim();
      var gedcom = $('#gedcom').val();

      if (!firstname || !lastname || !gedcom) {
        e.preventDefault();
        alert('<?php _e('Please fill in all required fields (Tree, First Name, Last Name).', 'heritagepress'); ?>');
        return false;
      }

      // Check if Person ID is provided when needed
      var personID = $('#personID').val().trim();
      if (!personID) {
        var generateId = confirm('<?php _e('No Person ID specified. Generate one automatically?', 'heritagepress'); ?>');
        if (generateId) {
          e.preventDefault();
          $('#generate-person-id').click();
          return false;
        }
      }
    });

    // Add and continue functionality
    $('#add-and-continue').on('click', function() {
      var form = $('#add-person-form');
      var originalAction = form.attr('action') || '';

      // Add a flag to indicate "add and continue"
      form.append('<input type="hidden" name="add_continue" value="1">');
      form.submit();
    });

    // Preview functionality
    $('#preview-person').on('click', function() {
      var formData = $('#add-person-form').serialize();

      // Open preview in a popup or modal
      var previewWindow = window.open('', 'preview', 'width=600,height=500,scrollbars=yes');
      previewWindow.document.write('<h3><?php _e('Person Preview', 'heritagepress'); ?></h3>');

      // Build preview content
      var preview = '<div style="font-family: Arial, sans-serif; padding: 20px;">';
      preview += '<h3>' + $('#firstname').val() + ' ' + $('#lastname').val() + '</h3>';
      preview += '<p><strong><?php _e('Tree:', 'heritagepress'); ?></strong> ' + $('#gedcom option:selected').text() + '</p>';

      if ($('#birthdate').val()) {
        preview += '<p><strong><?php _e('Born:', 'heritagepress'); ?></strong> ' + $('#birthdate').val();
        if ($('#birthplace').val()) {
          preview += ' in ' + $('#birthplace').val();
        }
        preview += '</p>';
      }

      if ($('#deathdate').val()) {
        preview += '<p><strong><?php _e('Died:', 'heritagepress'); ?></strong> ' + $('#deathdate').val();
        if ($('#deathplace').val()) {
          preview += ' in ' + $('#deathplace').val();
        }
        preview += '</p>';
      }

      preview += '</div>';
      previewWindow.document.write(preview);
    });

    // Quick action handlers
    $('.action-link').on('click', function(e) {
      e.preventDefault();
      var action = $(this).data('action');

      // These would open forms for adding related people
      switch (action) {
        case 'add-spouse':
          alert('<?php _e('Spouse/Partner functionality will be available after adding this person.', 'heritagepress'); ?>');
          break;
        case 'add-child':
          alert('<?php _e('Child functionality will be available after adding this person.', 'heritagepress'); ?>');
          break;
        case 'add-parent':
          alert('<?php _e('Parent functionality will be available after adding this person.', 'heritagepress'); ?>');
          break;
      }
    });

    // Auto-update Person ID when tree changes
    $('#gedcom').on('change', function() {
      if ($('#personID').val() === '') {
        // Optionally auto-generate ID when tree changes
      }
    });
  });
</script>
