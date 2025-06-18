<?php

/**
 * Add New Person Tab - Complete TNG Facsimile
 * Complete replication of TNG admin_newperson.php functionality
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Include date utilities and validation
require_once __DIR__ . '/../../helpers/class-hp-date-utils.php';

// Table references
$trees_table = $wpdb->prefix . 'hp_trees';
$branches_table = $wpdb->prefix . 'hp_branches';
$people_table = $wpdb->prefix . 'hp_people';

// Get available trees
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, ARRAY_A);

// Get first tree for default selection
$first_tree = !empty($trees_result) ? $trees_result[0]['gedcom'] : '';

// Get branches for first tree
$branches_query = $wpdb->prepare("SELECT branch, description FROM $branches_table WHERE gedcom = %s ORDER BY description", $first_tree);
$branches_result = $wpdb->get_results($branches_query, ARRAY_A);

// Check user permissions
$allow_add = current_user_can('edit_genealogy');
$allow_lds = get_option('heritagepress_allow_lds_events', true); // Default to true to match TNG
$lnprefixes = get_option('heritagepress_enable_name_prefixes', true);

// Default values for new person - Complete TNG field set
$person_data = array(
  'personID' => '',
  'gedcom' => $first_tree,
  'branch' => '',
  'firstname' => '',
  'lastname' => '',
  'prefix' => '',
  'suffix' => '',
  'nickname' => '',
  'title' => '',
  'nameorder' => '0', // 0=Default, 1=First Name First, 2=Surname First (Without Commas), 3=Surname First (With Commas)
  'sex' => 'U', // TNG default: U=Unknown
  'other_gender' => '',
  'living' => get_option('heritagepress_living_default', '1'),
  'private' => '0',

  // Birth events
  'birthdate' => '',
  'birthplace' => '',
  'altbirthdate' => '',
  'altbirthplace' => '',

  // Death events
  'deathdate' => '',
  'deathplace' => '',
  'burialdate' => '',
  'burialplace' => '',
  'burialtype' => '0', // 0=burial, 1=cremation

  // LDS events
  'baptdate' => '',
  'baptplace' => '',
  'confdate' => '',
  'confplace' => '',
  'initdate' => '',
  'initplace' => '',
  'endldate' => '',
  'endlplace' => '',

  // Additional fields
  'notes' => '',
  'gedcom_id' => '',
  'famc' => '',
  'fams' => ''
);

// Handle form submission
if (isset($_POST['action']) && $_POST['action'] === 'add_person') {  // Pre-fill form with submitted data on error
  foreach ($person_data as $key => $default) {
    if (isset($_POST[$key])) {
      $person_data[$key] = sanitize_text_field($_POST[$key]);
    }
  }
}

// Enqueue TNG-style CSS for this page
wp_enqueue_style(
  'heritagepress-add-person',
  plugin_dir_url(__FILE__) . '../../../public/css/add-person.css',
  array(),
  '1.0.0'
);

// Enqueue TNG-style JavaScript for enhanced functionality
wp_enqueue_script(
  'heritagepress-add-person',
  plugin_dir_url(__FILE__) . '../../../public/js/add-person.js',
  array('jquery'),
  '1.0.0',
  true
);

// Localize script for AJAX
wp_localize_script('heritagepress-add-person', 'hp_ajax_object', array(
  'ajax_url' => admin_url('admin-ajax.php'),
  'nonce' => wp_create_nonce('hp_ajax_nonce')
));
?>

<!-- Modern Elegant Add Person Interface -->
<div class="add-person-section">
  <!-- Master Expand/Collapse Control -->
  <div class="master-toggle-controls">
    <button type="button" id="master-toggle" class="button button-secondary">
      <span class="dashicons dashicons-arrow-down-alt2" id="master-toggle-icon"></span>
      <span id="master-toggle-text"><?php _e('Collapse All Sections', 'heritagepress'); ?></span>
    </button>
  </div>

  <form action="<?php echo admin_url('admin.php?page=heritagepress-people&tab=add'); ?>" method="post" name="form1" id="add-person-form" onSubmit="return validateForm();">
    <?php wp_nonce_field('heritagepress_people_action', '_wpnonce'); ?>
    <input type="hidden" name="action" value="add_person">
    <input type="hidden" name="cw" value="">
    <?php if (!$lnprefixes): ?>
      <input type="hidden" name="lnprefix" value="">
    <?php endif; ?>

    <!-- Tree and Person ID Section -->
    <div class="person-form-card" id="tree-identification-card">
      <div class="person-form-card-header">
        <h3 class="person-form-card-title">
          <span class="dashicons dashicons-networking"></span>
          <?php _e('Tree & Identification', 'heritagepress'); ?>
        </h3>
      </div>
      <div class="person-form-card-body">
        <table class="hp-form-table">
          <tr>
            <th><label for="gedcom"><?php _e('Tree:', 'heritagepress'); ?></label></th>
            <td>
              <select name="tree1" id="gedcom" class="large-text" onChange="swapBranches();">
                <?php foreach ($trees_result as $tree): ?>
                  <option value="<?php echo esc_attr($tree['gedcom']); ?>" <?php echo ($first_tree == $tree['gedcom']) ? ' selected="selected"' : ''; ?>>
                    <?php echo esc_html($tree['treename']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <p class="description"><?php _e('Select the genealogy tree for this person', 'heritagepress'); ?></p>
            </td>
          </tr>

          <tr>
            <th><label for="branch"><?php _e('Branch:', 'heritagepress'); ?></label></th>
            <td>
              <select name="branch" id="branch" class="large-text">
                <option value=""><?php _e('(no branch)', 'heritagepress'); ?></option>
                <?php foreach ($branches_result as $branch): ?>
                  <option value="<?php echo esc_attr($branch['branch']); ?>" <?php echo ($person_data['branch'] == $branch['branch']) ? ' selected="selected"' : ''; ?>><?php echo esc_html($branch['description']); ?></option>
                <?php endforeach; ?>
              </select>
              <p class="description"><?php _e('Optional: Assign person to a specific branch', 'heritagepress'); ?></p>
            </td>
          </tr>

          <tr>
            <th><label for="personID"><?php _e('Person ID:', 'heritagepress'); ?></label></th>
            <td>
              <div class="person-id-controls">
                <input type="text" name="personID" id="personID" class="medium-text" onblur="this.value=this.value.toUpperCase()">
                <input type="button" class="button" value="<?php _e('Generate', 'heritagepress'); ?>" onclick="generateID('person',document.form1.personID,document.form1.tree1);">
                <input type="button" class="button" value="<?php _e('Check', 'heritagepress'); ?>" onclick="checkID(document.form1.personID.value,'person','checkmsg',document.form1.tree1);">
                <input type="button" class="button button-primary" value="<?php _e('Lock ID', 'heritagepress'); ?>" onclick="lockPersonID();">
              </div>
              <span id="checkmsg" class="description"></span>
              <p class="description"><?php _e('Enter a unique identifier or generate one automatically', 'heritagepress'); ?></p>
            </td>
          </tr>
        </table>
      </div> <!-- Personal Information Section -->
      <div class="person-form-card collapsible-card" data-card-name="personal-information">
        <div class="person-form-card-header clickable-header">
          <h3 class="person-form-card-title">
            <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
            <span class="dashicons dashicons-admin-users"></span>
            <?php _e('Personal Information', 'heritagepress'); ?>
          </h3>
        </div>
        <div class="person-form-card-body collapsible-content">
          <table class="hp-form-table">
            <tr>
              <th><label for="firstname"><?php _e('First/Given Names:', 'heritagepress'); ?></label></th>
              <td>
                <input type="text" name="firstname" id="firstname" class="large-text" value="<?php echo esc_attr($person_data['firstname']); ?>">
                <p class="description"><?php _e('Enter the first and middle names', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th><label for="lastname"><?php _e('Last/Surname:', 'heritagepress'); ?></label></th>
              <td>
                <input type="text" name="lastname" id="lastname" class="large-text" value="<?php echo esc_attr($person_data['lastname']); ?>">
                <p class="description"><?php _e('Enter the family surname', 'heritagepress'); ?></p>
              </td>
            </tr>
            <tr>
              <th><label for="additional_names"><?php _e('Additional Names:', 'heritagepress'); ?></label></th>
              <td>
                <div class="additional-names-grid">
                  <div class="name-field">
                    <label for="prefix"><?php _e('Prefix:', 'heritagepress'); ?></label>
                    <input type="text" name="prefix" id="prefix" class="medium-text" value="<?php echo esc_attr($person_data['prefix']); ?>">
                  </div>
                  <div class="name-field">
                    <label for="title"><?php _e('Title:', 'heritagepress'); ?></label>
                    <input type="text" name="title" id="title" class="medium-text" value="<?php echo esc_attr($person_data['title']); ?>">
                  </div>
                  <div class="name-field">
                    <label for="suffix"><?php _e('Suffix:', 'heritagepress'); ?></label>
                    <input type="text" name="suffix" id="suffix" class="medium-text" value="<?php echo esc_attr($person_data['suffix']); ?>">
                  </div>
                  <div class="name-field">
                    <label for="nickname"><?php _e('Nickname:', 'heritagepress'); ?></label>
                    <input type="text" name="nickname" id="nickname" class="medium-text" value="<?php echo esc_attr($person_data['nickname']); ?>">
                  </div>
                </div>
                <p class="description"><?php _e('Optional: Dr., Jr., Sr., III, etc.', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th><label for="pnameorder"><?php _e('Name Display Order:', 'heritagepress'); ?></label></th>
              <td>
                <select name="pnameorder" id="pnameorder" class="medium-text">
                  <option value=""><?php _e('Default', 'heritagepress'); ?></option>
                  <option value="1"><?php _e('First Name First', 'heritagepress'); ?></option>
                  <option value="2"><?php _e('Surname First (Without Commas)', 'heritagepress'); ?></option>
                  <option value="3"><?php _e('Surname First (With Commas)', 'heritagepress'); ?></option>
                </select>
                <p class="description"><?php _e('Choose how this person\'s name should be displayed', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th><label for="sex"><?php _e('Gender:', 'heritagepress'); ?></label></th>
              <td>
                <select name="sex" id="sex" class="medium-text" onchange="onGenderChange(this);">
                  <option value="U" <?php echo ($person_data['sex'] == 'U') ? ' selected' : ''; ?>><?php _e('Unknown', 'heritagepress'); ?></option>
                  <option value="M" <?php echo ($person_data['sex'] == 'M') ? ' selected' : ''; ?>><?php _e('Male', 'heritagepress'); ?></option>
                  <option value="F" <?php echo ($person_data['sex'] == 'F') ? ' selected' : ''; ?>><?php _e('Female', 'heritagepress'); ?></option>
                  <option value="" <?php echo (empty($person_data['sex']) && $person_data['sex'] !== 'U') ? ' selected' : ''; ?>><?php _e('Other', 'heritagepress'); ?></option>
                </select>
                <input type="text" name="other_gender" id="other_gender" class="medium-text" style="<?php echo (empty($person_data['sex']) && $person_data['sex'] !== 'U') ? 'display: block; margin-top: 10px;' : 'display: none; margin-top: 10px;'; ?>" value="<?php echo esc_attr($person_data['other_gender']); ?>" placeholder="<?php _e('Specify other gender', 'heritagepress'); ?>" />
                <p class="description"><?php _e('Select the person\'s gender', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th><label><?php _e('Privacy Settings:', 'heritagepress'); ?></label></th>
              <td>
                <div class="privacy-options">
                  <label class="checkbox-field">
                    <input type="checkbox" name="living" value="1" <?php echo ($person_data['living'] == '1') ? ' checked="checked"' : ''; ?>>
                    <?php _e('Living Person', 'heritagepress'); ?>
                  </label>
                  <label class="checkbox-field">
                    <input type="checkbox" name="private" value="1" <?php echo ($person_data['private'] == '1') ? ' checked="checked"' : ''; ?>>
                    <?php _e('Private Record', 'heritagepress'); ?>
                  </label>
                </div>
                <p class="description"><?php _e('Check if this person is still living or if their information should be kept private', 'heritagepress'); ?></p>
              </td>
            </tr>
          </table>
        </div>
      </div> <!-- Life Events Section -->
      <div class="person-form-card collapsible-card" data-card-name="life-events">
        <div class="person-form-card-header clickable-header">
          <h3 class="person-form-card-title">
            <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php _e('Life Events', 'heritagepress'); ?>
          </h3>
        </div>
        <div class="person-form-card-body collapsible-content">
          <div class="events-help">
            <p class="description"><?php _e('Dates may be entered as exact dates, partial dates, or date ranges. Examples: "2 OCT 1822", "OCT 1822", "ABT 1820", "BEF 1828", "1820-1825"', 'heritagepress'); ?></p>
          </div>

          <table class="hp-form-table events-table">
        </div>

        <table class="hp-form-table events-table">
          <!-- Birth Event -->
          <tr class="event-row birth-event">
            <th><label for="birthdate"><?php _e('Birth:', 'heritagepress'); ?></label></th>
            <td>
              <div class="event-fields">
                <div class="date-field">
                  <label for="birthdate"><?php _e('Date:', 'heritagepress'); ?></label>
                  <input type="text" name="birthdate" id="birthdate" class="medium-text datefield" value="<?php echo esc_attr($person_data['birthdate']); ?>">
                </div>
                <div class="place-field">
                  <label for="birthplace"><?php _e('Place:', 'heritagepress'); ?></label>
                  <input type="text" name="birthplace" id="birthplace" class="large-text placefield" value="<?php echo esc_attr($person_data['birthplace']); ?>">
                </div>
              </div>
            </td>
          </tr>

          <!-- Alternative Birth Event -->
          <tr class="event-row alt-birth-event">
            <th><label for="altbirthdate"><?php _e('Christening/Baptism:', 'heritagepress'); ?></label></th>
            <td>
              <div class="event-fields">
                <div class="date-field">
                  <label for="altbirthdate"><?php _e('Date:', 'heritagepress'); ?></label>
                  <input type="text" name="altbirthdate" id="altbirthdate" class="medium-text datefield" value="<?php echo esc_attr($person_data['altbirthdate']); ?>">
                </div>
                <div class="place-field">
                  <label for="altbirthplace"><?php _e('Place:', 'heritagepress'); ?></label>
                  <input type="text" name="altbirthplace" id="altbirthplace" class="large-text placefield" value="<?php echo esc_attr($person_data['altbirthplace']); ?>">
                </div>
              </div>
            </td>
          </tr>

          <!-- Death Event -->
          <tr class="event-row death-event">
            <th><label for="deathdate"><?php _e('Death:', 'heritagepress'); ?></label></th>
            <td>
              <div class="event-fields">
                <div class="date-field">
                  <label for="deathdate"><?php _e('Date:', 'heritagepress'); ?></label>
                  <input type="text" name="deathdate" id="deathdate" class="medium-text datefield" value="<?php echo esc_attr($person_data['deathdate']); ?>">
                </div>
                <div class="place-field">
                  <label for="deathplace"><?php _e('Place:', 'heritagepress'); ?></label>
                  <input type="text" name="deathplace" id="deathplace" class="large-text placefield" value="<?php echo esc_attr($person_data['deathplace']); ?>">
                </div>
              </div>
            </td>
          </tr>

          <!-- Burial Event -->
          <tr class="event-row burial-event">
            <th><label for="burialdate"><?php _e('Burial:', 'heritagepress'); ?></label></th>
            <td>
              <div class="event-fields">
                <div class="date-field">
                  <label for="burialdate"><?php _e('Date:', 'heritagepress'); ?></label>
                  <input type="text" name="burialdate" id="burialdate" class="medium-text datefield" value="<?php echo esc_attr($person_data['burialdate']); ?>">
                </div>
                <div class="place-field">
                  <label for="burialplace"><?php _e('Place:', 'heritagepress'); ?></label>
                  <input type="text" name="burialplace" id="burialplace" class="large-text placefield" value="<?php echo esc_attr($person_data['burialplace']); ?>">
                </div>
                <div class="burial-options">
                  <label class="checkbox-field">
                    <input type="checkbox" name="burialtype" id="burialtype" value="1" <?php echo ($person_data['burialtype'] == '1') ? ' checked' : ''; ?>>
                    <?php _e('Cremated', 'heritagepress'); ?>
                  </label>
                </div>
              </div>
            </td>
          </tr>
        </table>
      </div>
    </div> <?php if ($allow_lds): ?>
      <!-- LDS Events Section -->
      <div class="person-form-card collapsible-card lds-events-card" data-card-name="lds-events">
        <div class="person-form-card-header clickable-header">
          <h3 class="person-form-card-title">
            <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
            <span class="dashicons dashicons-building"></span>
            <?php _e('LDS Temple Events', 'heritagepress'); ?>
          </h3>
        </div>
        <div class="person-form-card-body collapsible-content">
          <table class="hp-form-table events-table">
            <!-- LDS Baptism -->
            <tr class="event-row lds-event">
              <th><label for="baptdate"><?php _e('Baptism (LDS):', 'heritagepress'); ?></label></th>
              <td>
                <div class="event-fields">
                  <div class="date-field">
                    <label for="baptdate"><?php _e('Date:', 'heritagepress'); ?></label>
                    <input type="text" name="baptdate" id="baptdate" class="medium-text datefield" value="<?php echo esc_attr($person_data['baptdate']); ?>">
                  </div>
                  <div class="place-field">
                    <label for="baptplace"><?php _e('Temple:', 'heritagepress'); ?></label>
                    <input type="text" name="baptplace" id="baptplace" class="large-text placefield" value="<?php echo esc_attr($person_data['baptplace']); ?>">
                  </div>
                </div>
              </td>
            </tr>

            <!-- LDS Confirmation -->
            <tr class="event-row lds-event">
              <th><label for="confdate"><?php _e('Confirmation (LDS):', 'heritagepress'); ?></label></th>
              <td>
                <div class="event-fields">
                  <div class="date-field">
                    <label for="confdate"><?php _e('Date:', 'heritagepress'); ?></label>
                    <input type="text" name="confdate" id="confdate" class="medium-text datefield" value="<?php echo esc_attr($person_data['confdate']); ?>">
                  </div>
                  <div class="place-field">
                    <label for="confplace"><?php _e('Temple:', 'heritagepress'); ?></label>
                    <input type="text" name="confplace" id="confplace" class="large-text placefield" value="<?php echo esc_attr($person_data['confplace']); ?>">
                  </div>
                </div>
              </td>
            </tr>

            <!-- LDS Initiatory -->
            <tr class="event-row lds-event">
              <th><label for="initdate"><?php _e('Initiatory (LDS):', 'heritagepress'); ?></label></th>
              <td>
                <div class="event-fields">
                  <div class="date-field">
                    <label for="initdate"><?php _e('Date:', 'heritagepress'); ?></label>
                    <input type="text" name="initdate" id="initdate" class="medium-text datefield" value="<?php echo esc_attr($person_data['initdate']); ?>">
                  </div>
                  <div class="place-field">
                    <label for="initplace"><?php _e('Temple:', 'heritagepress'); ?></label>
                    <input type="text" name="initplace" id="initplace" class="large-text placefield" value="<?php echo esc_attr($person_data['initplace']); ?>">
                  </div>
                </div>
              </td>
            </tr>

            <!-- LDS Endowment -->
            <tr class="event-row lds-event">
              <th><label for="endldate"><?php _e('Endowment (LDS):', 'heritagepress'); ?></label></th>
              <td>
                <div class="event-fields">
                  <div class="date-field">
                    <label for="endldate"><?php _e('Date:', 'heritagepress'); ?></label>
                    <input type="text" name="endldate" id="endldate" class="medium-text datefield" value="<?php echo esc_attr($person_data['endldate']); ?>">
                  </div>
                  <div class="place-field">
                    <label for="endlplace"><?php _e('Temple:', 'heritagepress'); ?></label>
                    <input type="text" name="endlplace" id="endlplace" class="large-text placefield" value="<?php echo esc_attr($person_data['endlplace']); ?>">
                  </div>
                </div>
              </td>
            </tr>
          </table>
        </div>
      </div>
</div>
<?php endif; ?> <!-- Submit Section -->
<div class="person-form-card" id="submit-card">
  <div class="person-form-card-header">
    <h3 class="person-form-card-title">
      <span class="dashicons dashicons-yes"></span>
      <?php _e('Submit', 'heritagepress'); ?>
    </h3>
  </div>
  <div class="person-form-card-body">
    <div class="submit-info">
      <p class="description">
        <span class="dashicons dashicons-info"></span>
        <strong><?php _e('You can add additional events, sources, media, and family relationships after saving this person.', 'heritagepress'); ?></strong>
      </p>
    </div>
    <div class="submit-buttons">
      <input type="submit" class="button button-primary button-large" name="save" accesskey="s" value="<?php _e('Save & Continue', 'heritagepress'); ?>">
      <input type="button" name="cancel" class="button button-large" value="<?php _e('Cancel', 'heritagepress'); ?>" onClick="window.location.href='<?php echo admin_url('admin.php?page=heritagepress-people&tab=browse'); ?>';">
    </div>
  </div>
</div>

</form>
</div>

<?php
/**
 * TNG-style Event Row Function - Complete TNG Facsimile
 * Exact replication of TNG's showEventRow functionality
 */
function showEventRow($datefield, $placefield, $label, $persfamID, $person_data = array())
{
  global $allow_lds;
  $event_labels = array(
    'BIRT' => __('Birth', 'heritagepress'),
    'ALTBE' => __('Alt. Birth', 'heritagepress'),
    'CHR' => __('Christening', 'heritagepress'),
    'DEAT' => __('Death', 'heritagepress'),
    'BURI' => __('Burial', 'heritagepress'),
    'BAPL' => __('Baptism (LDS)', 'heritagepress'),
    'CONL' => __('Confirmation (LDS)', 'heritagepress'),
    'INIT' => __('Initiatory (LDS)', 'heritagepress'),
    'ENDL' => __('Endowment (LDS)', 'heritagepress')
  );

  $date_value = isset($person_data[$datefield]) ? htmlspecialchars($person_data[$datefield]) : '';
  $place_value = isset($person_data[$placefield]) ? htmlspecialchars($person_data[$placefield]) : '';

  // Handle alternative birth event type selector
  if ($datefield == 'altbirthdate') {
    $altbirthtype = "<input type=\"hidden\" name=\"altbirthtype\" id=\"altbirthtype\" value=\"$label\" />";
    $type_selector = getAltBirthTypes($label);
    $fieldlabel = "<span id=\"altbirthlabel\">" . (isset($event_labels[$label]) ? $event_labels[$label] : $label) . "</span>";
    $dloglabel = "ALTBE";
  } else {
    $altbirthtype = "";
    $type_selector = "";
    $fieldlabel = isset($event_labels[$label]) ? $event_labels[$label] : $label;
    $dloglabel = $label;
  }

  // LDS events array for temple selector
  $ldsarray = array("BAPL", "CONL", "INIT", "ENDL");

  // Death/burial events should update living status
  $blurAction = ($label == "DEAT" || $label == "BURI") ? " updateLivingBox(document.form1,this);" : "";
  $onblur = $blurAction ? " onblur=\"checkDate(this);{$blurAction}\"" : " onblur=\"checkDate(this);\"";

  $tr = "<tr>\n";
  $tr .= "<td>" . $fieldlabel . ":{$type_selector}</td>\n";
  $tr .= "<td><input type=\"text\" value=\"{$date_value}\" name=\"{$datefield}\" {$onblur} maxlength=\"50\" class=\"shortfield\">{$altbirthtype}</td>\n";
  $tr .= "<td><input type=\"text\" class=\"verylongfield\" value=\"{$place_value}\" name=\"{$placefield}\" id=\"{$placefield}\"></td>\n";

  // Find place icon - temple for LDS events, regular for others
  if (in_array($label, $ldsarray)) {
    $tr .= "<td><a href=\"#\" onclick=\"return openFindPlaceForm('{$placefield}', 1);\" title=\"" . __('Find Temple', 'heritagepress') . "\" class=\"smallicon admin-temp-icon\"></a></td>\n";
  } else {
    $tr .= "<td><a href=\"#\" onclick=\"return openFindPlaceForm('{$placefield}');\" title=\"" . __('Find Place', 'heritagepress') . "\" class=\"smallicon admin-find-icon\"></a></td>\n";
  }

  // More details icon (placeholder for future functionality)
  $tr .= "<td><a href=\"#\" onclick=\"return showMore('{$dloglabel}','{$persfamID}');\" title=\"" . __('More Details', 'heritagepress') . "\" id=\"moreicon{$label}\" class=\"smallicon admin-more-off-icon\"></a></td>\n";

  // Notes icon (placeholder for future functionality)
  $tr .= "<td><a href=\"#\" onclick=\"return showNotes('{$dloglabel}','{$persfamID}');\" title=\"" . __('Notes', 'heritagepress') . "\" id=\"notesicon{$label}\" class=\"smallicon admin-note-off-icon\"></a></td>\n";

  // Sources/Citations icon (placeholder for future functionality)
  $tr .= "<td><a href=\"#\" onclick=\"return showCitations('{$dloglabel}','{$persfamID}');\" title=\"" . __('Sources', 'heritagepress') . "\" id=\"citesicon{$label}\" class=\"smallicon admin-cite-off-icon\"></a></td>\n";

  $tr .= "</tr>\n";

  return $tr;
}

/**
 * TNG-style Alternative Birth Types Selector
 * Replicates TNG's getAltBirthTypes functionality
 */
function getAltBirthTypes($currentType)
{
  $event_labels = array(
    'CHR' => __('Christening', 'heritagepress'),
    'BAPM' => __('Baptism', 'heritagepress'),
    'ADOP' => __('Adoption', 'heritagepress'),
    '_BRTM' => __('Brit Milah', 'heritagepress')
  );

  $typestr = " &nbsp;<span class=\"nw\"><a href=\"#\" onclick=\"showEdit('altbirthedit'); quitEdit('altbirthedit'); return false;\"><img src=\"../../../public/images/ArrowDown.gif\" border=\"0\" style=\"margin-left:-4px;margin-right:-2px\"></a></span>";

  $typestr .= "<div id=\"altbirthedit\" class=\"lightback pad5 rounded4\" style=\"position:absolute;display:none;\" onmouseover=\"clearTimeout(dtimer);\" onmouseout=\"closeEdit('altbirth','altbirthedit','altbirthlist');\">\n";

  // Default alternative birth types (can be made configurable)
  $types = array('CHR', 'BAPM', 'ADOP', '_BRTM');
  $numtypes = count($types);

  $typestr .= "<select name=\"altbirth\" id=\"altbirth\" size=\"{$numtypes}\" onchange=\"changeAltBirthType();\">\n";

  foreach ($types as $type) {
    $label = isset($event_labels[$type]) ? $event_labels[$type] : $type;
    $typestr .= "  <option value=\"$type\"";
    if ($currentType == $type) {
      $typestr .= " selected";
    }
    $typestr .= ">$label</option>\n";
  }

  $typestr .= "</select>\n";
  $typestr .= "</div>\n";

  return $typestr;
}

/**
 * TNG-style Toggle Display Function - Exact TNG Facsimile
 * Creates collapsible sections exactly like TNG
 */
function displayToggle($id, $state, $target, $headline, $subhead, $append = "")
{
  $rval = "<span class=\"subhead\"><a href=\"#\" onclick=\"return toggleSection('$target','$id');\" class=\"togglehead\" style=\"color:black\"><img src=\"../../../public/images/" . ($state ? "collapse.gif" : "expand.gif") . "\" title=\"" . __('Toggle', 'heritagepress') . "\" alt=\"" . __('Toggle', 'heritagepress') . "\" width=\"15\" height=\"15\" border=\"0\" id=\"$id\">";
  $rval .= "<strong class=\"th-indent\">$headline</strong></a> $append</span><br />\n";
  if ($subhead) {
    $rval .= "<span class=\"normal tsh-indent\"><i>$subhead</i></span><br />\n";
  }

  return $rval;
}
?>

<script type="text/javascript">
  var tree = "<?php echo htmlspecialchars($first_tree); ?>";
  var allow_cites = false;
  var allow_notes = false;

  function toggleAll(display) {
    toggleSection('names', 'plus0', display);
    toggleSection('events', 'plus1', display);
    toggleSection('ldsevents', 'plus2', display);
    return false;
  }

  function validateForm() {
    var rval = true;

    document.form1.personID.value = TrimString(document.form1.personID.value);
    if (document.form1.personID.value.length == 0) {
      alert("<?php _e('Please enter a Person ID', 'heritagepress'); ?>");
      rval = false;
    }

    if (document.form1.firstname.value.trim().length == 0) {
      alert("<?php _e('Please enter a first name', 'heritagepress'); ?>");
      rval = false;
    }

    if (document.form1.lastname.value.trim().length == 0) {
      alert("<?php _e('Please enter a last name', 'heritagepress'); ?>");
      rval = false;
    }

    return rval;
  }

  function onGenderChange(gender) {
    if (gender.value == 'M' || gender.value == 'F' || gender.value == 'U') {
      jQuery('#other_gender').hide();
    } else {
      jQuery('#other_gender').show();
    }
  }

  function swapBranches() {
    // AJAX call to load branches for selected tree
    var selectedTree = document.getElementById('gedcom').value;
    var branchSelect = document.getElementById('branch');

    if (!selectedTree) {
      branchSelect.innerHTML = '<option value=""><?php _e('(no branch)', 'heritagepress'); ?></option>';
      return;
    } // AJAX call to get branches for the selected tree
    jQuery.post(hp_ajax_object.ajax_url, {
      action: 'hp_get_branches',
      gedcom: selectedTree,
      _wpnonce: '<?php echo wp_create_nonce('hp_get_branches'); ?>'
    }, function(response) {
      if (response.success && response.data.branches) {
        var options = '<option value=""><?php _e('(no branch)', 'heritagepress'); ?></option>';
        response.data.branches.forEach(function(branch) {
          options += '<option value="' + branch.branch + '">' + branch.description + '</option>';
        });
        branchSelect.innerHTML = options;
      }
    });
  }

  function generateID(type, field, treefield) {
    var tree = treefield.value;
    if (!tree) {
      alert("<?php _e('Please select a tree first', 'heritagepress'); ?>");
      return;
    }

    // Debug information
    console.log('Generating ID for tree:', tree);
    console.log('AJAX URL:', hp_ajax_object.ajax_url);

    // AJAX call to generate ID
    jQuery.post(hp_ajax_object.ajax_url, {
      action: 'hp_generate_person_id',
      gedcom: tree,
      _wpnonce: '<?php echo wp_create_nonce('hp_generate_person_id'); ?>'
    }, function(response) {
      console.log('AJAX Response:', response);
      if (response.success) {
        field.value = response.data.personID;
      } else {
        console.error('AJAX Error:', response);
        alert("<?php _e('Failed to generate Person ID', 'heritagepress'); ?>" + (response.data ? ': ' + response.data : ''));
      }
    }).fail(function(xhr, status, error) {
      console.error('AJAX Request Failed:', xhr, status, error);
      alert("<?php _e('Failed to generate Person ID', 'heritagepress'); ?>" + ': ' + error);
    });
  }

  function checkID(personID, type, msgSpan, treefield) {
    var tree = treefield.value;
    if (!personID || !tree) return; // AJAX call to check ID availability
    jQuery.post(hp_ajax_object.ajax_url, {
      action: 'hp_check_person_id',
      personID: personID,
      gedcom: tree,
      _wpnonce: '<?php echo wp_create_nonce('hp_check_person_id'); ?>'
    }, function(response) {
      var msgElement = document.getElementById(msgSpan);
      if (response.success) {
        if (response.data.available) {
          msgElement.innerHTML = '<span style="color: green;"><?php _e('ID is available', 'heritagepress'); ?></span>';
        } else {
          msgElement.innerHTML = '<span style="color: red;"><?php _e('ID already exists', 'heritagepress'); ?></span>';
        }
      }
    });
  }

  function lockPersonID() {
    var personID = document.form1.personID.value;
    var tree = document.form1.tree1.value;

    if (!personID) {
      alert("<?php _e('Please enter or generate a Person ID first', 'heritagepress'); ?>");
      return;
    }

    if (!tree) {
      alert("<?php _e('Please select a tree first', 'heritagepress'); ?>");
      return;
    }

    console.log('Locking Person ID:', personID, 'for tree:', tree);

    // AJAX call to lock/reserve the ID
    jQuery.post(hp_ajax_object.ajax_url, {
      action: 'hp_lock_person_id',
      personID: personID,
      gedcom: tree,
      _wpnonce: '<?php echo wp_create_nonce('hp_lock_person_id'); ?>'
    }, function(response) {
      console.log('Lock response:', response);
      if (response.success) {
        alert("<?php _e('Person ID locked successfully! You can now continue filling out the form.', 'heritagepress'); ?>");
        // Disable the Lock ID button and mark as locked
        var lockButton = document.querySelector('input[value="<?php _e('Lock ID', 'heritagepress'); ?>"]');
        if (lockButton) {
          lockButton.value = "<?php _e('ID Locked', 'heritagepress'); ?>";
          lockButton.disabled = true;
          lockButton.style.opacity = '0.6';
        }
        // Disable the Person ID field to prevent changes
        document.form1.personID.readOnly = true;
        document.form1.personID.style.backgroundColor = '#f0f0f0';
      } else {
        alert("<?php _e('Failed to lock Person ID', 'heritagepress'); ?>" + (response.data ? ': ' + response.data : ''));
      }
    }).fail(function(xhr, status, error) {
      console.error('Lock request failed:', xhr, status, error);
      alert("<?php _e('Failed to lock Person ID', 'heritagepress'); ?>" + ': ' + error);
    });
  }

  function toggleSection(divId, imgId, force) {
    var div = document.getElementById(divId);
    var img = document.getElementById(imgId);

    if (force === 'on') {
      div.style.display = 'block';
      img.src = img.src.replace('plus.gif', 'minus.gif');
    } else if (force === 'off') {
      div.style.display = 'none';
      img.src = img.src.replace('minus.gif', 'plus.gif');
    } else {
      if (div.style.display === 'none') {
        div.style.display = 'block';
        img.src = img.src.replace('plus.gif', 'minus.gif');
      } else {
        div.style.display = 'none';
        img.src = img.src.replace('minus.gif', 'plus.gif');
      }
    }
    return false;
  }

  function validateDate(field) {
    // Basic date validation - can be enhanced
    var value = field.value.trim();
    if (value && !value.match(/^\d{1,2}\s+(JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC)\s+\d{4}$/i)) {
      // Allow partial dates and qualifiers
      if (!value.match(/(ABT|BEF|AFT|BET|CAL|EST)\s+/i) && !value.match(/^\d{4}$/) && !value.match(/^\w{3}\s+\d{4}$/)) {
        field.style.backgroundColor = '#ffeeee';
      } else {
        field.style.backgroundColor = '';
      }
    } else {
      field.style.backgroundColor = '';
    }
  }

  function TrimString(str) {
    return str.replace(/^\s+|\s+$/g, '');
  }

  // TNG-style event functions (placeholders for future functionality)
  function openFindPlaceForm(placefield, isTemple) {
    // Placeholder for place finder functionality
    alert('<?php _e('Place finder will be implemented in a future version', 'heritagepress'); ?>');
    return false;
  }

  function showMore(eventType, persfamID) {
    // Placeholder for event details functionality
    alert('<?php _e('Event details will be implemented in a future version', 'heritagepress'); ?>');
    return false;
  }

  function showNotes(eventType, persfamID) {
    // Placeholder for notes functionality
    alert('<?php _e('Event notes will be implemented in a future version', 'heritagepress'); ?>');
    return false;
  }

  function showCitations(eventType, persfamID) {
    // Placeholder for citations functionality
    alert('<?php _e('Event sources will be implemented in a future version', 'heritagepress'); ?>');
    return false;
  }

  function changeAltBirthType() {
    // Update the alternative birth type label and hidden field
    var selector = document.getElementById('altbirth');
    var label = document.getElementById('altbirthlabel');
    var hidden = document.getElementById('altbirthtype');

    if (selector && label && hidden) {
      var selectedOption = selector.options[selector.selectedIndex];
      label.innerHTML = selectedOption.text;
      hidden.value = selectedOption.value;
    }
  }

  function showEdit(elementId) {
    var element = document.getElementById(elementId);
    if (element) {
      element.style.display = 'block';
    }
  }

  function quitEdit(elementId) {
    // Clear any timeout for closing the edit element
    if (typeof dtimer !== 'undefined') {
      clearTimeout(dtimer);
    }
  }

  function closeEdit(baseId, editId, listId) {
    // Set timeout to close edit element
    dtimer = setTimeout(function() {
      var element = document.getElementById(editId);
      if (element) {
        element.style.display = 'none';
      }
    }, 100);
  }

  function updateLivingBox(form, dateField) {
    // Auto-uncheck living status if death or burial date is entered
    if (dateField.value.trim() && form.living) {
      form.living.checked = false;
    }
  }

  function checkDate(field) {
    // Enhanced date validation
    validateDate(field);
  } // Initialize on page load
  jQuery(document).ready(function($) {
    // Set initial gender display
    onGenderChange(document.form1.sex); // Collapsible Cards Functionality for Add Person

    // Initialize card states from localStorage
    var cardStates = JSON.parse(localStorage.getItem('hp_add_person_card_states') || '{}');
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
    localStorage.setItem('hp_add_person_card_states', JSON.stringify(cardStates));

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
      localStorage.setItem('hp_add_person_card_states', JSON.stringify(cardStates));
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
      localStorage.setItem('hp_add_person_card_states', JSON.stringify(cardStates));
      setTimeout(updateMasterToggle, 350);
    });
  });
</script>
