<?php

/**
 * Add Person Modal Interface
 * Inline person creation for family and other contexts
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get passed parameters
$tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
$context_type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$family_id = isset($_GET['familyID']) ? sanitize_text_field($_GET['familyID']) : '';
$gender = isset($_GET['gender']) ? sanitize_text_field($_GET['gender']) : '';
$father = isset($_GET['father']) ? sanitize_text_field($_GET['father']) : '';
$mother = isset($_GET['mother']) ? sanitize_text_field($_GET['mother']) : '';

// Initialize person data with defaults
$person_data = array(
  'personID' => '',
  'gedcom' => $tree,
  'firstname' => '',
  'lastname' => '',
  'lnprefix' => '',
  'nickname' => '',
  'prefix' => '',
  'suffix' => '',
  'title' => '',
  'gender' => $gender,
  'birthdate' => '',
  'birthplace' => '',
  'deathdate' => '',
  'deathplace' => '',
  'burialdate' => '',
  'burialplace' => '',
  'living' => 1,
  'private' => 0
);

// Get tree information
$trees_table = $wpdb->prefix . 'hp_trees';
$tree_query = $wpdb->prepare("SELECT treename FROM $trees_table WHERE gedcom = %s", $tree);
$tree_row = $wpdb->get_row($tree_query, ARRAY_A);

// Pre-fill lastname from father if creating child
if ($context_type === 'child' && !empty($father)) {
  $people_table = $wpdb->prefix . 'hp_people';
  $father_query = $wpdb->prepare(
    "SELECT lnprefix, lastname, branch FROM $people_table WHERE gedcom = %s AND personID = %s",
    $tree,
    $father
  );
  $father_row = $wpdb->get_row($father_query, ARRAY_A);
  if ($father_row) {
    $person_data['lastname'] = $father_row['lastname'];
    $person_data['lnprefix'] = $father_row['lnprefix'];
  }
}

// Get branches for the tree
$branches_table = $wpdb->prefix . 'hp_branches';
$branches_query = $wpdb->prepare(
  "SELECT branch, description FROM $branches_table WHERE gedcom = %s ORDER BY description",
  $tree
);
$branches_result = $wpdb->get_results($branches_query, ARRAY_A);
?>

<div class="add-person-modal-container">
  <div class="modal-header">
    <h3><?php _e('Add New Person', 'heritagepress'); ?></h3>
    <span class="modal-close" onclick="closePersonModal()">&times;</span>
  </div>

  <form method="post" name="npform" id="add-person-modal-form" onSubmit="return saveNewPerson(this);">
    <?php wp_nonce_field('heritagepress_people_action', '_wpnonce'); ?>
    <input type="hidden" name="action" value="add_person_modal">
    <input type="hidden" name="tree" value="<?php echo esc_attr($tree); ?>">
    <input type="hidden" name="type" value="<?php echo esc_attr($context_type); ?>">
    <input type="hidden" name="familyID" value="<?php echo esc_attr($family_id); ?>">
    <input type="hidden" name="father" value="<?php echo esc_attr($father); ?>">
    <input type="hidden" name="mother" value="<?php echo esc_attr($mother); ?>">

    <div class="modal-body">
      <!-- Person ID Section -->
      <table class="form-table modal-table">
        <tr>
          <th><label for="modal_personID"><?php _e('Person ID:', 'heritagepress'); ?></label></th>
          <td>
            <input type="text" name="personID" id="modal_personID" class="regular-text"
              onblur="this.value=this.value.toUpperCase()" required>
            <input type="button" value="<?php _e('Generate', 'heritagepress'); ?>" class="button"
              onclick="generateModalPersonID();">
            <input type="button" value="<?php _e('Check', 'heritagepress'); ?>" class="button"
              onclick="checkModalPersonID();">
            <span id="modal_checkmsg" class="status-message"></span>
          </td>
        </tr>
      </table>

      <!-- Name Fields -->
      <table class="form-table modal-table">
        <tr>
          <th><label for="modal_firstname"><?php _e('First/Given Names:', 'heritagepress'); ?></label></th>
          <td>
            <input type="text" name="firstname" id="modal_firstname" class="regular-text"
              value="<?php echo esc_attr($person_data['firstname']); ?>" required>
          </td>
        </tr>

        <?php if (get_option('hp_enable_lnprefixes', false)): ?>
          <tr>
            <th><label for="modal_lnprefix"><?php _e('Last Name Prefix:', 'heritagepress'); ?></label></th>
            <td>
              <input type="text" name="lnprefix" id="modal_lnprefix" class="regular-text"
                value="<?php echo esc_attr($person_data['lnprefix']); ?>">
            </td>
          </tr>
        <?php endif; ?>

        <tr>
          <th><label for="modal_lastname"><?php _e('Last/Surname:', 'heritagepress'); ?></label></th>
          <td>
            <input type="text" name="lastname" id="modal_lastname" class="regular-text"
              value="<?php echo esc_attr($person_data['lastname']); ?>" required>
          </td>
        </tr>

        <tr>
          <th><label for="modal_gender"><?php _e('Gender:', 'heritagepress'); ?></label></th>
          <td>
            <select name="gender" id="modal_gender" onchange="onModalGenderChange(this);">
              <option value="U" <?php selected($person_data['gender'], 'U'); ?>><?php _e('Unknown', 'heritagepress'); ?></option>
              <option value="M" <?php selected($person_data['gender'], 'M'); ?>><?php _e('Male', 'heritagepress'); ?></option>
              <option value="F" <?php selected($person_data['gender'], 'F'); ?>><?php _e('Female', 'heritagepress'); ?></option>
              <option value="" <?php selected($person_data['gender'], ''); ?>><?php _e('Other', 'heritagepress'); ?></option>
            </select>
            <input type="text" name="other_gender" id="modal_other_gender" class="regular-text"
              style="display: none; margin-left: 10px;" placeholder="<?php _e('Specify', 'heritagepress'); ?>">
          </td>
        </tr>
      </table>

      <!-- Additional Name Fields -->
      <table class="form-table modal-table">
        <tr>
          <th><?php _e('Additional Names:', 'heritagepress'); ?></th>
          <td>
            <div class="name-fields-grid">
              <div class="name-field">
                <label for="modal_nickname"><?php _e('Nickname:', 'heritagepress'); ?></label>
                <input type="text" name="nickname" id="modal_nickname" class="medium-text">
              </div>
              <div class="name-field">
                <label for="modal_title"><?php _e('Title:', 'heritagepress'); ?></label>
                <input type="text" name="title" id="modal_title" class="medium-text">
              </div>
              <div class="name-field">
                <label for="modal_prefix"><?php _e('Prefix:', 'heritagepress'); ?></label>
                <input type="text" name="prefix" id="modal_prefix" class="medium-text">
              </div>
              <div class="name-field">
                <label for="modal_suffix"><?php _e('Suffix:', 'heritagepress'); ?></label>
                <input type="text" name="suffix" id="modal_suffix" class="medium-text">
              </div>
            </div>
          </td>
        </tr>
      </table>

      <!-- Basic Events -->
      <table class="form-table modal-table">
        <tr>
          <th><?php _e('Basic Events:', 'heritagepress'); ?></th>
          <td>
            <div class="event-fields-grid">
              <div class="event-field">
                <label for="modal_birthdate"><?php _e('Birth Date:', 'heritagepress'); ?></label>
                <input type="text" name="birthdate" id="modal_birthdate" class="medium-text"
                  placeholder="<?php _e('DD MMM YYYY', 'heritagepress'); ?>">
              </div>
              <div class="event-field">
                <label for="modal_birthplace"><?php _e('Birth Place:', 'heritagepress'); ?></label>
                <input type="text" name="birthplace" id="modal_birthplace" class="large-text">
              </div>
            </div>
          </td>
        </tr>
      </table>

      <!-- Privacy Settings -->
      <table class="form-table modal-table">
        <tr>
          <th><?php _e('Privacy:', 'heritagepress'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="living" value="1" <?php checked($person_data['living'], 1); ?>>
              <?php _e('Living Person', 'heritagepress'); ?>
            </label>
            <label style="margin-left: 20px;">
              <input type="checkbox" name="private" value="1" <?php checked($person_data['private'], 1); ?>>
              <?php _e('Private Record', 'heritagepress'); ?>
            </label>
          </td>
        </tr>
      </table>

      <!-- Tree and Branch Info -->
      <table class="form-table modal-table">
        <tr>
          <th><?php _e('Tree:', 'heritagepress'); ?></th>
          <td>
            <strong><?php echo esc_html($tree_row['treename'] ?? $tree); ?></strong>
          </td>
        </tr>
        <?php if (!empty($branches_result)): ?>
          <tr>
            <th><label for="modal_branch"><?php _e('Branch:', 'heritagepress'); ?></label></th>
            <td>
              <select name="branch[]" id="modal_branch" multiple size="4" class="regular-text">
                <option value=""><?php _e('(no branch)', 'heritagepress'); ?></option>
                <?php foreach ($branches_result as $branch): ?>
                  <option value="<?php echo esc_attr($branch['branch']); ?>">
                    <?php echo esc_html($branch['description']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
        <?php endif; ?>
      </table>

      <?php if ($context_type === 'child'): ?>
        <!-- Relationship Information for Children -->
        <table class="form-table modal-table">
          <tr>
            <th><?php _e('Relationships:', 'heritagepress'); ?></th>
            <td>
              <div class="relationship-fields">
                <label>
                  <?php _e('Relationship to Father:', 'heritagepress'); ?>
                  <select name="frel">
                    <option value=""><?php _e('Biological', 'heritagepress'); ?></option>
                    <option value="adopted"><?php _e('Adopted', 'heritagepress'); ?></option>
                    <option value="foster"><?php _e('Foster', 'heritagepress'); ?></option>
                    <option value="step"><?php _e('Step', 'heritagepress'); ?></option>
                  </select>
                </label>
                <label style="margin-left: 20px;">
                  <?php _e('Relationship to Mother:', 'heritagepress'); ?>
                  <select name="mrel">
                    <option value=""><?php _e('Biological', 'heritagepress'); ?></option>
                    <option value="adopted"><?php _e('Adopted', 'heritagepress'); ?></option>
                    <option value="foster"><?php _e('Foster', 'heritagepress'); ?></option>
                    <option value="step"><?php _e('Step', 'heritagepress'); ?></option>
                  </select>
                </label>
              </div>
            </td>
          </tr>
        </table>
      <?php endif; ?>

      <div id="modal_errormsg" class="error-message" style="display:none;"></div>
    </div>

    <div class="modal-footer">
      <input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Person', 'heritagepress'); ?>">
      <input type="button" class="button" value="<?php _e('Cancel', 'heritagepress'); ?>" onclick="closePersonModal();">

      <p class="description">
        <?php _e('You can add additional events, sources, media, and family relationships after saving.', 'heritagepress'); ?>
      </p>
    </div>
  </form>
</div>

<script type="text/javascript">
  // Modal-specific JavaScript functions
  function onModalGenderChange(gender) {
    if (gender.value == 'M' || gender.value == 'F' || gender.value == 'U') {
      jQuery('#modal_other_gender').hide();
    } else {
      jQuery('#modal_other_gender').show();
    }
  }

  function generateModalPersonID() {
    var tree = jQuery('input[name="tree"]').val();

    jQuery.post(hp_ajax_object.ajax_url, {
      action: 'hp_generate_person_id',
      gedcom: tree,
      _wpnonce: '<?php echo wp_create_nonce('hp_generate_person_id'); ?>'
    }, function(response) {
      if (response.success) {
        jQuery('#modal_personID').val(response.data.personID);
        checkModalPersonID();
      } else {
        alert('<?php _e('Failed to generate Person ID', 'heritagepress'); ?>');
      }
    });
  }

  function checkModalPersonID() {
    var personID = jQuery('#modal_personID').val();
    var tree = jQuery('input[name="tree"]').val();

    if (!personID) return;

    jQuery.post(hp_ajax_object.ajax_url, {
      action: 'hp_check_person_id',
      personID: personID,
      gedcom: tree,
      _wpnonce: '<?php echo wp_create_nonce('hp_check_person_id'); ?>'
    }, function(response) {
      var statusEl = jQuery('#modal_checkmsg');
      if (response.success) {
        if (response.data.exists) {
          statusEl.html('<span style="color:red;">✗ <?php _e('Already exists', 'heritagepress'); ?></span>');
        } else {
          statusEl.html('<span style="color:green;">✓ <?php _e('Available', 'heritagepress'); ?></span>');
        }
      }
    });
  }

  function saveNewPerson(form) {
    // Prevent default form submission
    event.preventDefault();

    // Validate required fields
    var personID = jQuery('#modal_personID').val().trim();
    var firstname = jQuery('#modal_firstname').val().trim();
    var lastname = jQuery('#modal_lastname').val().trim();

    if (!personID || !firstname || !lastname) {
      jQuery('#modal_errormsg').html('<?php _e('Please fill in all required fields.', 'heritagepress'); ?>').show();
      return false;
    }

    // Submit via AJAX
    var formData = jQuery(form).serialize();

    jQuery.post(hp_ajax_object.ajax_url, formData, function(response) {
      if (response.success) {
        // Call parent window function to handle the new person
        if (window.parent && window.parent.handleNewPersonCreated) {
          window.parent.handleNewPersonCreated(response.data);
        }
        closePersonModal();
      } else {
        jQuery('#modal_errormsg').html(response.data || '<?php _e('Failed to create person', 'heritagepress'); ?>').show();
      }
    }).fail(function() {
      jQuery('#modal_errormsg').html('<?php _e('Network error. Please try again.', 'heritagepress'); ?>').show();
    });

    return false;
  }

  function closePersonModal() {
    if (window.parent && window.parent.closePersonModal) {
      window.parent.closePersonModal();
    }
  }

  // Auto-generate ID on load
  jQuery(document).ready(function() {
    generateModalPersonID();
    onModalGenderChange(document.getElementById('modal_gender'));
  });
</script>

<style>
  .add-person-modal-container {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
    border-radius: 4px;
  }

  .modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .modal-header h3 {
    margin: 0;
  }

  .modal-close {
    font-size: 24px;
    font-weight: bold;
    color: #666;
    cursor: pointer;
    line-height: 1;
  }

  .modal-close:hover {
    color: #000;
  }

  .modal-body {
    padding: 20px;
    max-height: 500px;
    overflow-y: auto;
  }

  .modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    display: flex;
    gap: 10px;
    align-items: center;
  }

  .modal-table {
    margin-bottom: 15px;
  }

  .modal-table th {
    width: 150px;
    font-weight: 600;
  }

  .name-fields-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
  }

  .event-fields-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 15px;
  }

  .name-field label,
  .event-field label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
  }

  .relationship-fields {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
  }

  .relationship-fields label {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }

  .status-message {
    margin-left: 10px;
    font-weight: 600;
  }

  .error-message {
    color: #d63384;
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    padding: 10px;
    border-radius: 4px;
    margin-top: 15px;
  }

  .description {
    margin: 0;
    font-style: italic;
    color: #666;
    margin-left: auto;
  }
</style>
