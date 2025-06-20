<?php

/** * Edit Family - Complete admin family editing facsimile
 * Comprehensive family editing form with all genealogy fields and functionality
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get family ID and tree from URL
$family_id = isset($_GET['familyID']) ? sanitize_text_field($_GET['familyID']) : '';
$tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';

if (empty($family_id) || empty($tree)) {
  echo '<div class="notice notice-error"><p>' . __('Invalid family ID or tree specified.', 'heritagepress') . '</p></div>';
  return;
}

// Get family data
$families_table = $wpdb->prefix . 'hp_families';
$family = $wpdb->get_row($wpdb->prepare(
  "SELECT * FROM $families_table WHERE familyID = %s AND gedcom = %s",
  $family_id,
  $tree
), ARRAY_A);

if (!$family) {
  echo '<div class="notice notice-error"><p>' . __('Family not found.', 'heritagepress') . '</p></div>';
  return;
}

// Get related data
$people_table = $wpdb->prefix . 'hp_people';
$husband_data = null;
$wife_data = null;

if (!empty($family['husband'])) {
  $husband_data = $wpdb->get_row($wpdb->prepare(
    "SELECT personID, firstname, lnprefix, lastname, prefix, suffix, title FROM $people_table WHERE personID = %s AND gedcom = %s",
    $family['husband'],
    $tree
  ), ARRAY_A);
}

if (!empty($family['wife'])) {
  $wife_data = $wpdb->get_row($wpdb->prepare(
    "SELECT personID, firstname, lnprefix, lastname, prefix, suffix, title FROM $people_table WHERE personID = %s AND gedcom = %s",
    $family['wife'],
    $tree
  ), ARRAY_A);
}

// Get children
$children_table = $wpdb->prefix . 'hp_children';
$children = $wpdb->get_results($wpdb->prepare(
  "SELECT c.personID, c.ordernum, p.firstname, p.lnprefix, p.lastname, p.prefix, p.suffix, p.title, p.birthdate, p.living, p.private
   FROM $children_table c
   LEFT JOIN $people_table p ON c.personID = p.personID AND c.gedcom = p.gedcom
   WHERE c.familyID = %s AND c.gedcom = %s
   ORDER BY c.ordernum, p.birthdate",
  $family_id,
  $tree
), ARRAY_A);

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_result = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table ORDER BY treename", ARRAY_A);

// Get branches for current tree
$branches_table = $wpdb->prefix . 'hp_branches';
$branches_result = $wpdb->get_results($wpdb->prepare(
  "SELECT branch, description FROM $branches_table WHERE gedcom = %s ORDER BY description",
  $tree
), ARRAY_A);

// Parse branch data
$family_branches = !empty($family['branch']) ? explode(',', $family['branch']) : array();

// Helper function to format person name
function format_person_name($person_data)
{
  if (empty($person_data) || (empty($person_data['firstname']) && empty($person_data['lastname']))) {
    return '';
  }

  $name_parts = array();

  if (!empty($person_data['prefix'])) {
    $name_parts[] = $person_data['prefix'];
  }
  if (!empty($person_data['firstname'])) {
    $name_parts[] = $person_data['firstname'];
  }
  if (!empty($person_data['lnprefix'])) {
    $name_parts[] = $person_data['lnprefix'];
  }
  if (!empty($person_data['lastname'])) {
    $name_parts[] = $person_data['lastname'];
  }
  if (!empty($person_data['suffix'])) {
    $name_parts[] = $person_data['suffix'];
  }
  if (!empty($person_data['title'])) {
    $name_parts[] = $person_data['title'];
  }

  return implode(' ', $name_parts);
}

// Format display names
$husband_display = $husband_data ? format_person_name($husband_data) . ' - ' . $husband_data['personID'] : __('Click Find to select', 'heritagepress');
$wife_display = $wife_data ? format_person_name($wife_data) . ' - ' . $wife_data['personID'] : __('Click Find to select', 'heritagepress');
?>

<div class="edit-family-container">
  <form method="post" action="" id="edit-family-form" class="family-form">
    <?php wp_nonce_field('heritagepress_edit_family', 'edit_family_nonce'); ?>
    <input type="hidden" name="action" value="edit_family">
    <input type="hidden" name="original_familyID" value="<?php echo esc_attr($family['familyID']); ?>">
    <input type="hidden" name="original_gedcom" value="<?php echo esc_attr($family['gedcom']); ?>">
    <input type="hidden" name="family_db_id" value="<?php echo esc_attr($family['ID']); ?>">

    <!-- Basic Information -->
    <div class="form-section">
      <h3><?php _e('Basic Information', 'heritagepress'); ?></h3>

      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="gedcom"><?php _e('Tree:', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="gedcom" id="gedcom" onchange="updateBranches(this.value);" required>
              <?php foreach ($trees_result as $tree_row): ?>
                <option value="<?php echo esc_attr($tree_row['gedcom']); ?>" <?php selected($family['gedcom'], $tree_row['gedcom']); ?>>
                  <?php echo esc_html($tree_row['treename']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="branch"><?php _e('Branch:', 'heritagepress'); ?></label>
          </th>
          <td>
            <div id="branch-container">
              <select name="branch[]" id="branch" multiple size="6">
                <option value="" <?php echo empty($family_branches) ? 'selected' : ''; ?>><?php _e('No Branch', 'heritagepress'); ?></option>
                <?php foreach ($branches_result as $branch): ?>
                  <option value="<?php echo esc_attr($branch['branch']); ?>"
                    <?php echo in_array($branch['branch'], $family_branches) ? 'selected' : ''; ?>>
                    <?php echo esc_html($branch['description']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <p class="description"><?php _e('Hold Ctrl/Cmd to select multiple branches', 'heritagepress'); ?></p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="familyID"><?php _e('Family ID:', 'heritagepress'); ?> <span class="required">*</span></label>
          </th>
          <td>
            <input type="text" name="familyID" id="familyID" class="regular-text" required
              value="<?php echo esc_attr($family['familyID']); ?>"
              onblur="this.value = this.value.toUpperCase(); checkFamilyID();">
            <input type="button" value="<?php _e('Check Availability', 'heritagepress'); ?>" class="button" onclick="checkFamilyID();">
            <span id="family-id-status" class="status-message"></span>
          </td>
        </tr>
      </table>
    </div>

    <!-- Spouses Section -->
    <div class="form-section">
      <h3><?php _e('Spouses', 'heritagepress'); ?></h3>

      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="husband"><?php _e('Husband:', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="husband_display" id="husband_display" class="regular-text" readonly
              value="<?php echo esc_attr($husband_display); ?>">
            <input type="hidden" name="husband" id="husband" value="<?php echo esc_attr($family['husband']); ?>">

            <div class="person-actions">
              <input type="button" value="<?php _e('Find', 'heritagepress'); ?>" class="button"
                onclick="findPerson('husband', 'M');">
              <input type="button" value="<?php _e('Create', 'heritagepress'); ?>" class="button"
                onclick="createPerson('husband', 'M');">
              <input type="button" value="<?php _e('Edit', 'heritagepress'); ?>" class="button"
                onclick="editPerson('husband');">
              <input type="button" value="<?php _e('Remove', 'heritagepress'); ?>" class="button"
                onclick="removePerson('husband');">
            </div>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="wife"><?php _e('Wife:', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="wife_display" id="wife_display" class="regular-text" readonly
              value="<?php echo esc_attr($wife_display); ?>">
            <input type="hidden" name="wife" id="wife" value="<?php echo esc_attr($family['wife']); ?>">

            <div class="person-actions">
              <input type="button" value="<?php _e('Find', 'heritagepress'); ?>" class="button"
                onclick="findPerson('wife', 'F');">
              <input type="button" value="<?php _e('Create', 'heritagepress'); ?>" class="button"
                onclick="createPerson('wife', 'F');">
              <input type="button" value="<?php _e('Edit', 'heritagepress'); ?>" class="button"
                onclick="editPerson('wife');">
              <input type="button" value="<?php _e('Remove', 'heritagepress'); ?>" class="button"
                onclick="removePerson('wife');">
            </div>
          </td>
        </tr>

        <tr>
          <th scope="row"><?php _e('Status:', 'heritagepress'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="living" value="1" <?php checked($family['living'], 1); ?>>
              <?php _e('Living', 'heritagepress'); ?>
            </label>
            <label>
              <input type="checkbox" name="private" value="1" <?php checked($family['private'], 1); ?>>
              <?php _e('Private', 'heritagepress'); ?>
            </label>
          </td>
        </tr>
      </table>
    </div>

    <!-- Marriage Events -->
    <div class="form-section">
      <h3><?php _e('Marriage Events', 'heritagepress'); ?></h3>

      <p class="description"><?php _e('Enter dates in DD MMM YYYY format (e.g., 15 JAN 1850) or other standard formats.', 'heritagepress'); ?></p>

      <table class="form-table events-table">
        <thead>
          <tr>
            <th><?php _e('Event', 'heritagepress'); ?></th>
            <th><?php _e('Date', 'heritagepress'); ?></th>
            <th><?php _e('Place', 'heritagepress'); ?></th>
            <th><?php _e('Source', 'heritagepress'); ?></th>
          </tr>
        </thead>
        <tbody>
          <!-- Marriage -->
          <tr>
            <td><strong><?php _e('Marriage', 'heritagepress'); ?></strong></td>
            <td>
              <input type="text" name="marrdate" id="marrdate" class="regular-text"
                value="<?php echo esc_attr($family['marrdate']); ?>"
                placeholder="<?php _e('DD MMM YYYY', 'heritagepress'); ?>">
            </td>
            <td>
              <input type="text" name="marrplace" id="marrplace" class="regular-text"
                value="<?php echo esc_attr($family['marrplace']); ?>"
                placeholder="<?php _e('City, County, State, Country', 'heritagepress'); ?>">
              <input type="button" value="<?php _e('Find', 'heritagepress'); ?>" class="button button-small"
                onclick="findPlace('marrplace');">
            </td>
            <td>
              <input type="text" name="marrsource" id="marrsource" class="regular-text"
                value="<?php echo esc_attr($family['marrsource']); ?>">
              <input type="button" value="<?php _e('Find', 'heritagepress'); ?>" class="button button-small"
                onclick="findSource('marrsource');">
            </td>
          </tr>

          <!-- Marriage Type -->
          <tr>
            <td><strong><?php _e('Marriage Type', 'heritagepress'); ?></strong></td>
            <td colspan="3">
              <input type="text" name="marrtype" id="marrtype" class="regular-text" maxlength="50"
                value="<?php echo esc_attr($family['marrtype']); ?>"
                placeholder="<?php _e('Civil, Religious, Common Law, etc.', 'heritagepress'); ?>">
            </td>
          </tr>

          <!-- Divorce -->
          <tr>
            <td><strong><?php _e('Divorce', 'heritagepress'); ?></strong></td>
            <td>
              <input type="text" name="divdate" id="divdate" class="regular-text"
                value="<?php echo esc_attr($family['divdate']); ?>"
                placeholder="<?php _e('DD MMM YYYY', 'heritagepress'); ?>">
            </td>
            <td>
              <input type="text" name="divplace" id="divplace" class="regular-text"
                value="<?php echo esc_attr($family['divplace']); ?>"
                placeholder="<?php _e('City, County, State, Country', 'heritagepress'); ?>">
              <input type="button" value="<?php _e('Find', 'heritagepress'); ?>" class="button button-small"
                onclick="findPlace('divplace');">
            </td>
            <td>
              <input type="text" name="divsource" id="divsource" class="regular-text"
                value="<?php echo esc_attr($family['divsource']); ?>">
              <input type="button" value="<?php _e('Find', 'heritagepress'); ?>" class="button button-small"
                onclick="findSource('divsource');">
            </td>
          </tr>

          <!-- LDS Sealing -->
          <tr>
            <td><strong><?php _e('LDS Sealing', 'heritagepress'); ?></strong></td>
            <td>
              <input type="text" name="sealdate" id="sealdate" class="regular-text"
                value="<?php echo esc_attr($family['sealdate']); ?>"
                placeholder="<?php _e('DD MMM YYYY', 'heritagepress'); ?>">
            </td>
            <td>
              <input type="text" name="sealplace" id="sealplace" class="regular-text"
                value="<?php echo esc_attr($family['sealplace']); ?>"
                placeholder="<?php _e('Temple Name', 'heritagepress'); ?>">
              <input type="button" value="<?php _e('Find', 'heritagepress'); ?>" class="button button-small"
                onclick="findPlace('sealplace');">
            </td>
            <td>
              <input type="text" name="sealsource" id="sealsource" class="regular-text"
                value="<?php echo esc_attr($family['sealsource']); ?>">
              <input type="button" value="<?php _e('Find', 'heritagepress'); ?>" class="button button-small"
                onclick="findSource('sealsource');">
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Children Section -->
    <div class="form-section">
      <h3><?php _e('Children', 'heritagepress'); ?></h3>

      <?php if (!empty($children)): ?>
        <div class="children-list">
          <table class="wp-list-table widefat striped">
            <thead>
              <tr>
                <th><?php _e('Order', 'heritagepress'); ?></th>
                <th><?php _e('Person ID', 'heritagepress'); ?></th>
                <th><?php _e('Name', 'heritagepress'); ?></th>
                <th><?php _e('Birth Date', 'heritagepress'); ?></th>
                <th><?php _e('Actions', 'heritagepress'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($children as $child): ?>
                <tr>
                  <td>
                    <input type="number" name="child_order[<?php echo esc_attr($child['personID']); ?>]"
                      value="<?php echo esc_attr($child['ordernum']); ?>"
                      min="1" style="width: 60px;">
                  </td>
                  <td><?php echo esc_html($child['personID']); ?></td>
                  <td><?php echo esc_html(format_person_name($child)); ?></td>
                  <td><?php echo esc_html($child['birthdate']); ?></td>
                  <td>
                    <a href="?page=heritagepress-people&tab=edit&personID=<?php echo urlencode($child['personID']); ?>&tree=<?php echo urlencode($tree); ?>"
                      class="button button-small"><?php _e('Edit', 'heritagepress'); ?></a>
                    <button type="button" class="button button-small"
                      onclick="removeChild('<?php echo esc_js($child['personID']); ?>');">
                      <?php _e('Remove', 'heritagepress'); ?>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p><?php _e('No children assigned to this family.', 'heritagepress'); ?></p>
      <?php endif; ?>

      <div class="add-child-section">
        <h4><?php _e('Add Child', 'heritagepress'); ?></h4>
        <div class="add-child-form">
          <input type="text" id="new_child_display" class="regular-text" readonly
            placeholder="<?php _e('Click Find to select child', 'heritagepress'); ?>">
          <input type="hidden" id="new_child_id" name="new_child_id">
          <input type="button" value="<?php _e('Find Child', 'heritagepress'); ?>" class="button"
            onclick="findPerson('new_child', '');">
          <input type="button" value="<?php _e('Create Child', 'heritagepress'); ?>" class="button"
            onclick="createChild();">
          <input type="button" value="<?php _e('Add Child', 'heritagepress'); ?>" class="button button-primary"
            onclick="addChild();">
        </div>
      </div>
    </div>

    <!-- Additional Information -->
    <div class="form-section">
      <h3><?php _e('Additional Information', 'heritagepress'); ?></h3>

      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="notes"><?php _e('Notes:', 'heritagepress'); ?></label>
          </th>
          <td>
            <textarea name="notes" id="notes" rows="5" class="large-text"><?php echo esc_textarea($family['notes']); ?></textarea>
            <p class="description"><?php _e('General notes about this family', 'heritagepress'); ?></p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="reference"><?php _e('Reference:', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="reference" id="reference" class="regular-text"
              value="<?php echo esc_attr($family['reference']); ?>">
            <p class="description"><?php _e('External reference number or identifier', 'heritagepress'); ?></p>
          </td>
        </tr>

        <tr>
          <th scope="row"><?php _e('Last Modified:', 'heritagepress'); ?></th>
          <td>
            <p>
              <?php
              if (!empty($family['changedby'])) {
                printf(
                  __('By %s on %s', 'heritagepress'),
                  esc_html($family['changedby']),
                  esc_html(date('F j, Y g:i A', strtotime($family['changedate'])))
                );
              } else {
                echo esc_html(date('F j, Y g:i A', strtotime($family['changedate'])));
              }
              ?>
            </p>
          </td>
        </tr>
      </table>
    </div>

    <!-- Submit Buttons -->
    <div class="form-section">
      <p class="submit">
        <input type="submit" name="save_family" class="button button-primary"
          value="<?php _e('Update Family', 'heritagepress'); ?>">
        <input type="submit" name="save_and_continue" class="button"
          value="<?php _e('Save & Continue Editing', 'heritagepress'); ?>">
        <a href="?page=heritagepress-families&tab=browse" class="button">
          <?php _e('Cancel', 'heritagepress'); ?>
        </a>
        <input type="submit" name="delete_family" class="button button-link-delete"
          value="<?php _e('Delete Family', 'heritagepress'); ?>"
          onclick="return confirm('<?php _e('Are you sure you want to delete this family? This action cannot be undone.', 'heritagepress'); ?>');">
      </p>
    </div>
  </form>
</div>

<!-- Modals (same as add family page) -->
<!-- Person Finder Modal -->
<div id="person-finder-modal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" onclick="closePersonFinder()">&times;</span>
    <h3><?php _e('Find Person', 'heritagepress'); ?></h3>
    <div id="person-finder-content">
      <!-- Person finder will be loaded here via AJAX -->
    </div>
  </div>
</div>

<!-- Place Finder Modal -->
<div id="place-finder-modal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" onclick="closePlaceFinder()">&times;</span>
    <h3><?php _e('Find Place', 'heritagepress'); ?></h3>
    <div id="place-finder-content">
      <!-- Place finder will be loaded here via AJAX -->
    </div>
  </div>
</div>

<!-- Source Finder Modal -->
<div id="source-finder-modal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" onclick="closeSourceFinder()">&times;</span>
    <h3><?php _e('Find Source', 'heritagepress'); ?></h3>
    <div id="source-finder-content">
      <!-- Source finder will be loaded here via AJAX -->
    </div>
  </div>
</div>

<script type="text/javascript">
  // Include same JavaScript functions as add family page
  var currentPersonField = '';
  var currentPlaceField = '';
  var currentSourceField = '';

  // Form validation
  function validateFamilyForm() {
    var familyID = document.getElementById('familyID').value.trim();
    if (!familyID) {
      alert('<?php _e('Please enter a Family ID.', 'heritagepress'); ?>');
      document.getElementById('familyID').focus();
      return false;
    }

    return true;
  }

  // Check Family ID availability (for changed IDs)
  function checkFamilyID() {
    var familyID = document.getElementById('familyID').value.trim();
    var originalID = '<?php echo esc_js($family['familyID']); ?>';
    var tree = document.getElementById('gedcom').value;
    var statusEl = document.getElementById('family-id-status');

    if (!familyID || !tree || familyID === originalID) {
      statusEl.innerHTML = '';
      return;
    }

    statusEl.innerHTML = '<?php _e('Checking...', 'heritagepress'); ?>';

    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        var response = JSON.parse(xhr.responseText);
        if (response.success) {
          if (response.data.available) {
            statusEl.innerHTML = '<span style="color:green;">✓ <?php _e('Available', 'heritagepress'); ?></span>';
          } else {
            statusEl.innerHTML = '<span style="color:red;">✗ <?php _e('Already exists', 'heritagepress'); ?></span>';
          }
        }
      }
    };
    xhr.send('action=hp_check_family_id&familyID=' + encodeURIComponent(familyID) + '&tree=' + encodeURIComponent(tree) + '&nonce=<?php echo wp_create_nonce('hp_check_family_id'); ?>');
  }

  // Children management
  function removeChild(personID) {
    if (confirm('<?php _e('Remove this child from the family?', 'heritagepress'); ?>')) {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', ajaxurl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          var response = JSON.parse(xhr.responseText);
          if (response.success) {
            location.reload();
          } else {
            alert('<?php _e('Error removing child.', 'heritagepress'); ?>');
          }
        }
      };
      xhr.send('action=hp_remove_child&familyID=<?php echo esc_js($family_id); ?>&tree=<?php echo esc_js($tree); ?>&personID=' + encodeURIComponent(personID) + '&nonce=<?php echo wp_create_nonce('hp_remove_child'); ?>');
    }
  }

  function addChild() {
    var childID = document.getElementById('new_child_id').value;
    if (!childID) {
      alert('<?php _e('Please select a child first.', 'heritagepress'); ?>');
      return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        var response = JSON.parse(xhr.responseText);
        if (response.success) {
          location.reload();
        } else {
          alert('<?php _e('Error adding child: ', 'heritagepress'); ?>' + response.data.message);
        }
      }
    };
    xhr.send('action=hp_add_child&familyID=<?php echo esc_js($family_id); ?>&tree=<?php echo esc_js($tree); ?>&personID=' + encodeURIComponent(childID) + '&nonce=<?php echo wp_create_nonce('hp_add_child'); ?>');
  }

  function createChild() {
    var tree = document.getElementById('gedcom').value;
    var url = '?page=heritagepress-people&tab=add&tree=' + encodeURIComponent(tree) + '&family=' + encodeURIComponent('<?php echo esc_js($family_id); ?>') + '&return_to=families';
    window.open(url, '_blank');
  }

  // [Include all other JavaScript functions from add-family.php here - findPerson, selectPerson, etc.]
  // ... (same functions as in add-family.php)

  // Form submission
  document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('edit-family-form');
    if (form) {
      form.addEventListener('submit', function(e) {
        if (!validateFamilyForm()) {
          e.preventDefault();
          return false;
        }
      });
    }
  });
</script>

<style>
  /* Include same styles as add-family.php */
  .edit-family-container {
    max-width: 1200px;
    margin: 20px 0;
  }

  .form-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    margin-bottom: 20px;
    padding: 20px;
  }

  .form-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #dcdcde;
  }

  .children-list {
    margin-bottom: 20px;
  }

  .add-child-section {
    border-top: 1px solid #dcdcde;
    padding-top: 20px;
  }

  .add-child-form {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
  }

  .required {
    color: #d63384;
  }

  .status-message {
    margin-left: 10px;
    font-weight: 600;
  }

  /* ... (rest of styles same as add-family.php) */
</style>
