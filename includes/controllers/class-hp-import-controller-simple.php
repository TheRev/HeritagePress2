<?php

/**
 * Import Controller - Based on genealogy admin import
 * Handles GEDCOM import functionality using genealogy structure
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Import_Controller
{
  /**
   * Display the import page
   */
  public function display_page()
  {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $this->process_import();
    }

    // Get available trees
    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';
    $trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
    $trees_result = $wpdb->get_results($trees_query, ARRAY_A);

    // Display the import form
    $this->render_import_form($trees_result);
  }

  /**
   * Process the import form submission
   */
  private function process_import()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_import_gedcom')) {
      wp_die(__('Security check failed.', 'heritagepress'));
    }

    // Get form data exactly as expected
    $tree1 = sanitize_text_field($_POST['tree1'] ?? '');
    $branch1 = sanitize_text_field($_POST['branch1'] ?? '');
    $del = sanitize_text_field($_POST['del'] ?? '');
    $allevents = isset($_POST['allevents']) ? 'yes' : '';
    $eventsonly = isset($_POST['eventsonly']) ? 'yes' : '';
    $ucaselast = isset($_POST['ucaselast']) ? '1' : '';
    $norecalc = isset($_POST['norecalc']) ? '1' : '';
    $neweronly = isset($_POST['neweronly']) ? '1' : '';
    $importmedia = isset($_POST['importmedia']) ? '1' : '';
    $importlatlong = isset($_POST['importlatlong']) ? '1' : '';
    $offsetchoice = sanitize_text_field($_POST['offsetchoice'] ?? '');
    $useroffset = sanitize_text_field($_POST['useroffset'] ?? '');
    $database = sanitize_text_field($_POST['database'] ?? '');

    // Validate required fields
    if (empty($tree1)) {
      echo '<div class="notice notice-error"><p>' . __('Please select a destination tree.', 'heritagepress') . '</p></div>';
      return;
    }

    if (empty($del)) {
      echo '<div class="notice notice-error"><p>' . __('Please select a replace option.', 'heritagepress') . '</p></div>';
      return;
    }

    // Handle file upload or server file
    if (isset($_FILES['remotefile']) && $_FILES['remotefile']['error'] === UPLOAD_ERR_OK) {
      $this->process_file_upload(
        $_FILES['remotefile'],
        $tree1,
        $del,
        $allevents,
        $eventsonly,
        $ucaselast,
        $norecalc,
        $neweronly,
        $importmedia,
        $importlatlong,
        $offsetchoice,
        $useroffset
      );
    } elseif (!empty($database)) {
      $this->process_server_file(
        $database,
        $tree1,
        $del,
        $allevents,
        $eventsonly,
        $ucaselast,
        $norecalc,
        $neweronly,
        $importmedia,
        $importlatlong,
        $offsetchoice,
        $useroffset
      );
    } else {
      echo '<div class="notice notice-error"><p>' . __('Please select a GEDCOM file to import.', 'heritagepress') . '</p></div>';
    }
  }

  /**
   * Process uploaded file
   */
  private function process_file_upload(
    $file,
    $tree1,
    $del,
    $allevents,
    $eventsonly,
    $ucaselast,
    $norecalc,
    $neweronly,
    $importmedia,
    $importlatlong,
    $offsetchoice,
    $useroffset
  ) {
    // Validate file type
    $allowed_extensions = array('ged', 'gedcom');
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_extension, $allowed_extensions)) {
      echo '<div class="notice notice-error"><p>' . __('Invalid file type. Please upload a .ged or .gedcom file.', 'heritagepress') . '</p></div>';
      return;
    }

    // Move uploaded file to temp location
    $upload_dir = wp_upload_dir();
    $gedcom_dir = $upload_dir['basedir'] . '/heritagepress/gedcom/';

    if (!file_exists($gedcom_dir)) {
      wp_mkdir_p($gedcom_dir);
    }

    $filename = sanitize_file_name($file['name']);
    $filepath = $gedcom_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
      $this->run_gedcom_import(
        $filepath,
        $tree1,
        $del,
        $allevents,
        $eventsonly,
        $ucaselast,
        $norecalc,
        $neweronly,
        $importmedia,
        $importlatlong,
        $offsetchoice,
        $useroffset
      );
    } else {
      echo '<div class="notice notice-error"><p>' . __('Failed to upload file.', 'heritagepress') . '</p></div>';
    }
  }

  /**
   * Process server file
   */
  private function process_server_file(
    $database,
    $tree1,
    $del,
    $allevents,
    $eventsonly,
    $ucaselast,
    $norecalc,
    $neweronly,
    $importmedia,
    $importlatlong,
    $offsetchoice,
    $useroffset
  ) {
    $upload_dir = wp_upload_dir();
    $gedcom_dir = $upload_dir['basedir'] . '/heritagepress/gedcom/';
    $filepath = $gedcom_dir . sanitize_file_name($database);

    if (file_exists($filepath)) {
      $this->run_gedcom_import(
        $filepath,
        $tree1,
        $del,
        $allevents,
        $eventsonly,
        $ucaselast,
        $norecalc,
        $neweronly,
        $importmedia,
        $importlatlong,
        $offsetchoice,
        $useroffset
      );
    } else {
      echo '<div class="notice notice-error"><p>' . __('Selected file does not exist on server.', 'heritagepress') . '</p></div>';
    }
  }

  /**
   * Run the actual GEDCOM import
   */
  private function run_gedcom_import(
    $filepath,
    $tree1,
    $del,
    $allevents,
    $eventsonly,
    $ucaselast,
    $norecalc,
    $neweronly,
    $importmedia,
    $importlatlong,
    $offsetchoice,
    $useroffset
  ) {
    // Load the GEDCOM importer
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/class-hp-gedcom-importer.php';

    $importer = new HP_GEDCOM_Importer();

    // Set import options exactly as needed
    $import_options = array(
      'tree_id' => $tree1,
      'delete_mode' => $del,
      'all_events' => $allevents,
      'events_only' => $eventsonly,
      'uppercase_last' => $ucaselast,
      'no_recalc' => $norecalc,
      'newer_only' => $neweronly,
      'import_media' => $importmedia,
      'import_latlong' => $importlatlong,
      'offset_choice' => $offsetchoice,
      'user_offset' => $useroffset
    );

    try {
      $result = $importer->import_gedcom_file($filepath, $import_options);

      if ($result) {
        echo '<div class="notice notice-success"><p>' . __('GEDCOM import completed successfully!', 'heritagepress') . '</p></div>';
      } else {
        echo '<div class="notice notice-error"><p>' . __('Import failed. Please check the file and try again.', 'heritagepress') . '</p></div>';
      }
    } catch (Exception $e) {
      echo '<div class="notice notice-error"><p>' . sprintf(__('Import error: %s', 'heritagepress'), $e->getMessage()) . '</p></div>';
    }
  }

  /**
   * Render the import form with standard layout
   */
  private function render_import_form($trees_result)
  {
    $numtrees = count($trees_result);    // Import configuration defaults (standard values)
    $importcfg = array(
      'defimpopt' => 0  // Default import option
    );

?>
    <div class="wrap">
      <h1><?php _e('Import GEDCOM Data', 'heritagepress'); ?></h1>

      <form action="<?php echo admin_url('admin.php?page=heritagepress-import'); ?>" name="form1" method="post" enctype="multipart/form-data" onsubmit="return checkFile(this);">
        <?php wp_nonce_field('hp_import_gedcom', '_wpnonce'); ?>

        <table width="100%" border="0" cellpadding="10" cellspacing="2" class="widefat">
          <tr>
            <td>
              <div>
                <em><?php _e('Add or replace genealogy data in your HeritagePress database from GEDCOM files', 'heritagepress'); ?></em><br /><br />

                <p><strong><?php _e('Import GEDCOM:', 'heritagepress'); ?></strong></p>
                <table border="0" cellpadding="1">
                  <tr>
                    <td>&nbsp;&nbsp;<?php _e('From your computer:', 'heritagepress'); ?> </td>
                    <td><input type="file" name="remotefile" size="50" accept=".ged,.gedcom"></td>
                  </tr>
                  <tr>
                    <td>&nbsp;&nbsp;<strong><?php _e('OR', 'heritagepress'); ?></strong> &nbsp;<?php _e('On web server:', 'heritagepress'); ?> </td>
                    <td><input type="text" name="database" id="database" size="50"> <input type="button" value="<?php _e('Select...', 'heritagepress'); ?>" name="gedselect" onclick="selectServerFile();"></td>
                  </tr>
                  <tr>
                    <td colspan="2"><br />
                      <input type="checkbox" name="allevents" value="yes" onclick="if(document.form1.allevents.checked && document.form1.eventsonly.checked) {document.form1.eventsonly.checked ='';toggleSections(false)}" /> <?php _e('Import all events', 'heritagepress'); ?>&nbsp;&nbsp;
                      <input type="checkbox" name="eventsonly" value="yes" onclick="toggleSections(this.checked);" /> <?php _e('Import events only', 'heritagepress'); ?>
                    </td>
                  </tr>
                </table>
              </div>
            </td>
          </tr>
          <tr>
            <td id="desttree">
              <p><strong><?php _e('Select destination tree:', 'heritagepress'); ?></strong></p>
              <table border="0" cellpadding="1">
                <tr id="desttree2">
                  <td>&nbsp;&nbsp;<?php _e('Destination tree:', 'heritagepress'); ?>:</td>
                  <td>
                    <select name="tree1" id="tree1" onchange="getBranches(this,this.selectedIndex);">
                      <?php if ($numtrees != 1): ?>
                        <option value=""><?php _e('Select a tree...', 'heritagepress'); ?></option>
                      <?php endif; ?>
                      <?php foreach ($trees_result as $tree): ?>
                        <option value="<?php echo esc_attr($tree['gedcom']); ?>"><?php echo esc_html($tree['treename']); ?></option>
                      <?php endforeach; ?>
                    </select>
                    &nbsp; <input type="button" name="newtree" value="<?php _e('Add New Tree', 'heritagepress'); ?>" onclick="openAddTreeModal();">
                  </td>
                </tr>
                <tr id="destbranch" style="display:none">
                  <td>&nbsp;&nbsp;<?php _e('Destination branch:', 'heritagepress'); ?>:</td>
                  <td>
                    <div id="branch1div">
                      <select name="branch1" id="branch1">
                      </select>
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td>
              <table border="0" cellpadding="1">
                <tr id="replace">
                  <td colspan="2">
                    <p><strong><?php _e('Replace existing data:', 'heritagepress'); ?></strong></p>
                    <input type="radio" name="del" value="yes" <?php if ($importcfg['defimpopt'] == 1) echo " checked=\"checked\""; ?> onclick="document.form1.norecalc.checked = false; toggleNorecalcdiv(0); toggleAppenddiv(0);"> <?php _e('All current data in tree', 'heritagepress'); ?> &nbsp;
                    <input type="radio" name="del" value="match" <?php if (!$importcfg['defimpopt']) echo " checked=\"checked\""; ?> onclick="toggleNorecalcdiv(1); toggleAppenddiv(0);"> <?php _e('Matching records only', 'heritagepress'); ?> &nbsp;
                    <input type="radio" name="del" value="no" <?php if ($importcfg['defimpopt'] == 2) echo " checked=\"checked\""; ?> onclick="document.form1.norecalc.checked = false; toggleNorecalcdiv(0); toggleAppenddiv(0);"> <?php _e('Do not replace any data', 'heritagepress'); ?> &nbsp;
                    <input type="radio" name="del" value="append" <?php if ($importcfg['defimpopt'] == 3) echo " checked=\"checked\""; ?> onclick="document.form1.norecalc.checked = false; toggleNorecalcdiv(0); toggleAppenddiv(1);"> <?php _e('Append all imadapted records', 'heritagepress'); ?><br /><br />
                    <span><em><?php _e('Select the appropriate option for how you want to handle existing data.', 'heritagepress'); ?></em></span>
                  </td>
                </tr>
                <tr id="ioptions">
                  <td valign="top">
                    <br />
                    <div><input type="checkbox" name="ucaselast" value="1"> <?php _e('Uppercase last names', 'heritagepress'); ?></div>
                    <div id="norecalcdiv" <?php if ($importcfg['defimpopt']) echo " style=\"display:none\""; ?>>
                      <input type="checkbox" name="norecalc" value="1"> <?php _e('Do not recalculate relationships', 'heritagepress'); ?><br>
                      <input type="checkbox" name="neweronly" value="1"> <?php _e('Import newer records only', 'heritagepress'); ?><br>
                    </div>
                    <div><input type="checkbox" name="importmedia" value="1"> <?php _e('Import media links', 'heritagepress'); ?></div>
                    <div><input type="checkbox" name="importlatlong" value="1"> <?php _e('Import latitude/longitude', 'heritagepress'); ?></div>
                  </td>
                  <td valign="top">
                    <br />
                    <div id="appenddiv" <?php if ($importcfg['defimpopt'] != 3) echo " style=\"display:none;\""; ?>>
                      <input type="radio" name="offsetchoice" value="auto" checked> <?php _e('Auto calculate ID offset', 'heritagepress'); ?>&nbsp;<br />
                      <input type="radio" name="offsetchoice" value="user"> <?php _e('User defined offset:', 'heritagepress'); ?>&nbsp;<input type="text" name="useroffset" size="10" maxlength="9">
                    </div>
                  </td>
                </tr>
              </table>
              <p>
              <ul>
                <li><em><?php _e('Remember to backup your database before imadapting.', 'heritagepress'); ?></em></li>
                <li><em><?php _e('Check your import settings carefully.', 'heritagepress'); ?></em></li>
                <li><em><?php _e('Large files may take several minutes to import.', 'heritagepress'); ?></em></li>
              </ul>
              </p>
              <input type="submit" name="submit" class="button button-primary" value="<?php _e('Import Data', 'heritagepress'); ?>">
            </td>
          </tr>
        </table>
      </form>
    </div>

    <script type="text/javascript">
      function checkFile(form) {
        if (!form.remotefile.value && !form.database.value) {
          alert('<?php _e('Please select a GEDCOM file to import.', 'heritagepress'); ?>');
          return false;
        }
        if (!form.tree1.value) {
          alert('<?php _e('Please select a destination tree.', 'heritagepress'); ?>');
          return false;
        }
        return true;
      }

      function toggleSections(checked) {
        // Toggle sections based on events only checkbox
      }

      function toggleNorecalcdiv(show) {
        document.getElementById('norecalcdiv').style.display = show ? 'block' : 'none';
      }

      function toggleAppenddiv(show) {
        document.getElementById('appenddiv').style.display = show ? 'block' : 'none';
      }

      function getBranches(select, index) {
        // Get branches for selected tree
      }

      function selectServerFile() {
        // Open file picker for server files
      }

      function openAddTreeModal() {
        // Open modal to add new tree
      }
    </script>
<?php
  }
}
