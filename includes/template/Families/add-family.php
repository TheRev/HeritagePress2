<?php

/**
 * Add New Family - Complete TNG admin_newfamily.php facsimile
 * Comprehensive family creation form with all TNG fields and functionality
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, ARRAY_A);

// Get branches for first tree (will be updated via AJAX)
$branches_table = $wpdb->prefix . 'hp_branches';
$first_tree = !empty($trees_result) ? $trees_result[0]['gedcom'] : '';
$branches_query = $wpdb->prepare("SELECT branch, description FROM $branches_table WHERE gedcom = %s ORDER BY description", $first_tree);
$branches_result = $wpdb->get_results($branches_query, ARRAY_A);

// Pre-populate fields if coming from person
$husband_id = isset($_GET['husband']) ? sanitize_text_field($_GET['husband']) : '';
$wife_id = isset($_GET['wife']) ? sanitize_text_field($_GET['wife']) : '';
$child_id = isset($_GET['child']) ? sanitize_text_field($_GET['child']) : '';
$tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : $first_tree;

// Get person details if provided
$husband_name = '';
$wife_name = '';
if (!empty($husband_id) && !empty($tree)) {
  $people_table = $wpdb->prefix . 'hp_people';
  $husband = $wpdb->get_row($wpdb->prepare(
    "SELECT personID, firstname, lnprefix, lastname, prefix, suffix, title FROM $people_table WHERE personID = %s AND gedcom = %s",
    $husband_id,
    $tree
  ), ARRAY_A);
  if ($husband) {
    $husband_name = format_person_name($husband) . ' - ' . $husband['personID'];
  }
}

if (!empty($wife_id) && !empty($tree)) {
  $people_table = $wpdb->prefix . 'hp_people';
  $wife = $wpdb->get_row($wpdb->prepare(
    "SELECT personID, firstname, lnprefix, lastname, prefix, suffix, title FROM $people_table WHERE personID = %s AND gedcom = %s",
    $wife_id,
    $tree
  ), ARRAY_A);
  if ($wife) {
    $wife_name = format_person_name($wife) . ' - ' . $wife['personID'];
  }
}

// Helper function to format person name
function format_person_name($person_data)
{
  if (empty($person_data['firstname']) && empty($person_data['lastname'])) {
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
?>

<div class="add-family-container">
  <form method="post" action="" id="add-family-form" class="family-form">
    <?php wp_nonce_field('heritagepress_add_family', 'add_family_nonce'); ?>
    <input type="hidden" name="action" value="add_family">
    <input type="hidden" name="lastperson" value="<?php echo esc_attr($child_id); ?>">

    <!-- Basic Information -->
    <div class="form-section">
      <h3><?php _e('Basic Information', 'heritagepress'); ?></h3>

      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="gedcom"><?php _e('Tree:', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="gedcom" id="gedcom" onchange="updateBranches(this.value); generateFamilyID();" required>
              <?php foreach ($trees_result as $tree_row): ?>
                <option value="<?php echo esc_attr($tree_row['gedcom']); ?>" <?php selected($tree, $tree_row['gedcom']); ?>>
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
                <option value="" selected><?php _e('No Branch', 'heritagepress'); ?></option>
                <?php foreach ($branches_result as $branch): ?>
                  <option value="<?php echo esc_attr($branch['branch']); ?>">
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
              onblur="this.value = this.value.toUpperCase();">
            <input type="button" value="<?php _e('Generate', 'heritagepress'); ?>" class="button" onclick="generateFamilyID();">
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
              value="<?php echo esc_attr($husband_name ?: __('Click Find to select', 'heritagepress')); ?>">
            <input type="hidden" name="husband" id="husband" value="<?php echo esc_attr($husband_id); ?>">

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
              value="<?php echo esc_attr($wife_name ?: __('Click Find to select', 'heritagepress')); ?>">
            <input type="hidden" name="wife" id="wife" value="<?php echo esc_attr($wife_id); ?>">

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
              <input type="checkbox" name="living" value="1" checked>
              <?php _e('Living', 'heritagepress'); ?>
            </label>
            <label>
              <input type="checkbox" name="private" value="1">
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
                placeholder="<?php _e('DD MMM YYYY', 'heritagepress'); ?>">
            </td>
            <td>
              <input type="text" name="marrplace" id="marrplace" class="regular-text"
                placeholder="<?php _e('City, County, State, Country', 'heritagepress'); ?>">
              <input type="button" value="<?php _e('Find', 'heritagepress'); ?>" class="button button-small"
                onclick="findPlace('marrplace');">
            </td>
            <td>
              <input type="text" name="marrsource" id="marrsource" class="regular-text">
              <input type="button" value="<?php _e('Find', 'heritagepress'); ?>" class="button button-small"
                onclick="findSource('marrsource');">
            </td>
          </tr>

          <!-- Marriage Type -->
          <tr>
            <td><strong><?php _e('Marriage Type', 'heritagepress'); ?></strong></td>
            <td colspan="3">
              <input type="text" name="marrtype" id="marrtype" class="regular-text" maxlength="50"
                placeholder="<?php _e('Civil, Religious, Common Law, etc.', 'heritagepress'); ?>">
            </td>
          </tr>

          <!-- Divorce -->
          <tr>
            <td><strong><?php _e('Divorce', 'heritagepress'); ?></strong></td>
            <td>
              <input type="text" name="divdate" id="divdate" class="regular-text"
                placeholder="<?php _e('DD MMM YYYY', 'heritagepress'); ?>">
            </td>
            <td>
              <input type="text" name="divplace" id="divplace" class="regular-text"
                placeholder="<?php _e('City, County, State, Country', 'heritagepress'); ?>">
              <input type="button" value="<?php _e('Find', 'heritagepress'); ?>" class="button button-small"
                onclick="findPlace('divplace');">
            </td>
            <td>
              <input type="text" name="divsource" id="divsource" class="regular-text">
              <input type="button" value="<?php _e('Find', 'heritagepress'); ?>" class="button button-small"
                onclick="findSource('divsource');">
            </td>
          </tr>

          <!-- LDS Sealing -->
          <tr>
            <td><strong><?php _e('LDS Sealing', 'heritagepress'); ?></strong></td>
            <td>
              <input type="text" name="sealdate" id="sealdate" class="regular-text"
                placeholder="<?php _e('DD MMM YYYY', 'heritagepress'); ?>">
            </td>
            <td>
              <input type="text" name="sealplace" id="sealplace" class="regular-text"
                placeholder="<?php _e('Temple Name', 'heritagepress'); ?>">
              <input type="button" value="<?php _e('Find', 'heritagepress'); ?>" class="button button-small"
                onclick="findPlace('sealplace');">
            </td>
            <td>
              <input type="text" name="sealsource" id="sealsource" class="regular-text">
              <input type="button" value="<?php _e('Find', 'heritagepress'); ?>" class="button button-small"
                onclick="findSource('sealsource');">
            </td>
          </tr>
        </tbody>
      </table>
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
            <textarea name="notes" id="notes" rows="5" class="large-text"></textarea>
            <p class="description"><?php _e('General notes about this family', 'heritagepress'); ?></p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="reference"><?php _e('Reference:', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="reference" id="reference" class="regular-text">
            <p class="description"><?php _e('External reference number or identifier', 'heritagepress'); ?></p>
          </td>
        </tr>
      </table>
    </div>

    <!-- Submit Buttons -->
    <div class="form-section">
      <p class="submit">
        <input type="submit" name="save_family" class="button button-primary"
          value="<?php _e('Save Family', 'heritagepress'); ?>">
        <input type="submit" name="save_and_continue" class="button"
          value="<?php _e('Save & Continue Editing', 'heritagepress'); ?>">
        <a href="?page=heritagepress-families&tab=browse" class="button">
          <?php _e('Cancel', 'heritagepress'); ?>
        </a>
      </p>

      <p class="description">
        <strong><?php _e('Note:', 'heritagepress'); ?></strong>
        <?php _e('After saving, you can add events, media, sources, and notes to this family.', 'heritagepress'); ?>
      </p>
    </div>
  </form>
</div>

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

    var husband = document.getElementById('husband').value;
    var wife = document.getElementById('wife').value;
    if (!husband && !wife) {
      alert('<?php _e('Please select at least one spouse.', 'heritagepress'); ?>');
      return false;
    }

    return true;
  }

  // Generate Family ID
  function generateFamilyID() {
    var tree = document.getElementById('gedcom').value;
    if (!tree) return;

    // Make AJAX call to generate unique family ID
    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        var response = JSON.parse(xhr.responseText);
        if (response.success) {
          document.getElementById('familyID').value = response.data.familyID;
          checkFamilyID();
        }
      }
    };
    xhr.send('action=hp_generate_family_id&tree=' + encodeURIComponent(tree) + '&nonce=<?php echo wp_create_nonce('hp_generate_family_id'); ?>');
  }

  // Check Family ID availability
  function checkFamilyID() {
    var familyID = document.getElementById('familyID').value.trim();
    var tree = document.getElementById('gedcom').value;
    var statusEl = document.getElementById('family-id-status');

    if (!familyID || !tree) {
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

  // Update branches when tree changes
  function updateBranches(tree) {
    var branchContainer = document.getElementById('branch-container');

    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        var response = JSON.parse(xhr.responseText);
        if (response.success) {
          branchContainer.innerHTML = response.data.html;
        }
      }
    };
    xhr.send('action=hp_get_tree_branches&tree=' + encodeURIComponent(tree) + '&nonce=<?php echo wp_create_nonce('hp_get_tree_branches'); ?>');
  }

  // Person management functions
  function findPerson(field, gender) {
    currentPersonField = field;
    var tree = document.getElementById('gedcom').value;

    var modal = document.getElementById('person-finder-modal');
    var content = document.getElementById('person-finder-content');

    content.innerHTML = '<?php _e('Loading...', 'heritagepress'); ?>';
    modal.style.display = 'block';

    // Load person finder via AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        content.innerHTML = xhr.responseText;
      }
    };
    xhr.send('action=hp_person_finder&tree=' + encodeURIComponent(tree) + '&gender=' + encodeURIComponent(gender) + '&nonce=<?php echo wp_create_nonce('hp_person_finder'); ?>');
  }

  function selectPerson(personID, personName) {
    document.getElementById(currentPersonField).value = personID;
    document.getElementById(currentPersonField + '_display').value = personName + ' - ' + personID;
    closePersonFinder();
  }

  function createPerson(field, gender) {
    var tree = document.getElementById('gedcom').value;
    var url = '?page=heritagepress-people&tab=add&tree=' + encodeURIComponent(tree) + '&gender=' + encodeURIComponent(gender) + '&return_to=families';
    window.open(url, '_blank');
  }

  function editPerson(field) {
    var personID = document.getElementById(field).value;
    var tree = document.getElementById('gedcom').value;

    if (!personID) {
      alert('<?php _e('No person selected to edit.', 'heritagepress'); ?>');
      return;
    }

    var url = '?page=heritagepress-people&tab=edit&personID=' + encodeURIComponent(personID) + '&tree=' + encodeURIComponent(tree);
    window.open(url, '_blank');
  }

  function removePerson(field) {
    document.getElementById(field).value = '';
    document.getElementById(field + '_display').value = '<?php _e('Click Find to select', 'heritagepress'); ?>';
  }

  function closePersonFinder() {
    document.getElementById('person-finder-modal').style.display = 'none';
  }

  // Place management functions
  function findPlace(field) {
    currentPlaceField = field;
    var modal = document.getElementById('place-finder-modal');
    var content = document.getElementById('place-finder-content');

    content.innerHTML = '<?php _e('Loading...', 'heritagepress'); ?>';
    modal.style.display = 'block';

    // Load place finder via AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        content.innerHTML = xhr.responseText;
      }
    };
    xhr.send('action=hp_place_finder&nonce=<?php echo wp_create_nonce('hp_place_finder'); ?>');
  }

  function selectPlace(placeName) {
    document.getElementById(currentPlaceField).value = placeName;
    closePlaceFinder();
  }

  function closePlaceFinder() {
    document.getElementById('place-finder-modal').style.display = 'none';
  }

  // Source management functions
  function findSource(field) {
    currentSourceField = field;
    var modal = document.getElementById('source-finder-modal');
    var content = document.getElementById('source-finder-content');

    content.innerHTML = '<?php _e('Loading...', 'heritagepress'); ?>';
    modal.style.display = 'block';

    // Load source finder via AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        content.innerHTML = xhr.responseText;
      }
    };
    xhr.send('action=hp_source_finder&nonce=<?php echo wp_create_nonce('hp_source_finder'); ?>');
  }

  function selectSource(sourceID, sourceTitle) {
    document.getElementById(currentSourceField).value = sourceID;
    closeSourceFinder();
  }

  function closeSourceFinder() {
    document.getElementById('source-finder-modal').style.display = 'none';
  }

  // Form submission
  document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('add-family-form');
    if (form) {
      form.addEventListener('submit', function(e) {
        if (!validateFamilyForm()) {
          e.preventDefault();
          return false;
        }
      });
    }

    // Generate initial family ID
    generateFamilyID();
  });
</script>

<style>
  .add-family-container {
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

  .form-table th {
    width: 150px;
    padding: 15px 10px 15px 0;
    vertical-align: top;
  }

  .form-table td {
    padding: 15px 0;
    vertical-align: top;
  }

  .person-actions {
    margin-top: 5px;
  }

  .person-actions .button {
    margin-right: 5px;
    margin-bottom: 5px;
  }

  .events-table {
    width: 100%;
    border-collapse: collapse;
  }

  .events-table th,
  .events-table td {
    padding: 10px;
    border: 1px solid #dcdcde;
    vertical-align: top;
  }

  .events-table th {
    background: #f6f7f7;
    font-weight: 600;
  }

  .events-table input[type="text"] {
    width: 100%;
  }

  .required {
    color: #d63384;
  }

  .status-message {
    margin-left: 10px;
    font-weight: 600;
  }

  /* Modal Styles */
  .modal {
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
  }

  .modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 800px;
    max-height: 80%;
    overflow-y: auto;
    position: relative;
  }

  .close {
    position: absolute;
    right: 10px;
    top: 10px;
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
  }

  .close:hover,
  .close:focus {
    color: #000;
  }

  @media (max-width: 782px) {

    .form-table th,
    .form-table td {
      display: block;
      width: 100%;
      padding: 10px 0;
    }

    .person-actions .button {
      display: block;
      width: 100%;
      margin-bottom: 5px;
    }
  }
</style>
