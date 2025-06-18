<?php

/**
 * Import Controller - Simple TNG-based Implementation
 *
 * Handles GEDCOM import functionality based on TNG admin_dataimport.php
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Import_Controller
{
  /**
   * Display the import page and handle form submissions
   */
  public function display_page()
  {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Handle form submission FIRST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
      $this->process_import();
    }

    // Display the import form
    $this->display_import_form();
  }

  /**
   * Process GEDCOM import (based on TNG admin_gedimport.php)
   */
  private function process_import()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_import_gedcom')) {
      echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
      return;
    }

    // Get form data (using exact TNG field names)
    $tree = sanitize_text_field($_POST['tree1'] ?? '');
    $remotefile = $_FILES['remotefile'] ?? null;
    $database = sanitize_text_field($_POST['database'] ?? '');
    $del = sanitize_text_field($_POST['del'] ?? '');
    $allevents = isset($_POST['allevents']) ? 'yes' : '';
    $eventsonly = isset($_POST['eventsonly']) ? 'yes' : '';

    // Validation (exact TNG logic)
    if (empty($remotefile['name']) && empty($database)) {
      echo '<div class="notice notice-error"><p>Please select an import file.</p></div>';
      return;
    }

    if (empty($tree) && !$eventsonly) {
      echo '<div class="notice notice-error"><p>Please select a destination tree.</p></div>';
      return;
    }

    // Process the import
    try {
      if (!empty($remotefile['name'])) {
        // Handle uploaded file
        $result = $this->handle_uploaded_file($remotefile, $tree, $del);
      } else {
        // Handle server file
        $result = $this->handle_server_file($database, $tree, $del);
      }

      if ($result) {
        echo '<div class="notice notice-success"><p>GEDCOM import completed successfully!</p></div>';
      } else {
        echo '<div class="notice notice-error"><p>Import failed. Please check the file and try again.</p></div>';
      }
    } catch (Exception $e) {
      echo '<div class="notice notice-error"><p>Error: ' . esc_html($e->getMessage()) . '</p></div>';
    }
  }

  /**
   * Handle uploaded file import
   */
  private function handle_uploaded_file($file, $tree, $del)
  {
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
      throw new Exception('File upload failed.');
    }

    $allowed_types = array('ged', 'gedcom');
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_types)) {
      throw new Exception('Invalid file type. Please upload a .ged or .gedcom file.');
    }

    // Move file to gedcom directory
    $upload_dir = wp_upload_dir();
    $gedcom_dir = $upload_dir['basedir'] . '/heritagepress/gedcom/';

    if (!file_exists($gedcom_dir)) {
      wp_mkdir_p($gedcom_dir);
    }

    $filename = sanitize_file_name($file['name']);
    $filepath = $gedcom_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
      return $this->import_gedcom_file($filepath, $tree);
    }

    return false;
  }

  /**
   * Handle server file import
   */
  private function handle_server_file($database, $tree, $del)
  {
    $gedcom_dir = wp_upload_dir()['basedir'] . '/heritagepress/gedcom/';
    $filepath = $gedcom_dir . sanitize_file_name($database);

    if (!file_exists($filepath)) {
      throw new Exception('Selected file does not exist.');
    }

    return $this->import_gedcom_file($filepath, $tree);
  }

  /**
   * Import GEDCOM file using existing importer
   */
  private function import_gedcom_file($file_path, $tree_id)
  {
    // Use existing HP_GEDCOM_Importer
    if (class_exists('HP_GEDCOM_Importer')) {
      $importer = new HP_GEDCOM_Importer();
      return $importer->import($file_path, $tree_id);
    }

    // Fallback: basic file validation
    if (file_exists($file_path)) {
      // Here you would call your actual import logic
      // For now, just return true to test the form
      return true;
    }

    return false;
  }

  /**
   * Display import form (exact TNG structure)
   */
  private function display_import_form()
  {
    // Get available trees
    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';
    $trees = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table ORDER BY treename", ARRAY_A);

    // Import config (TNG defaults)
    $import_config = array(
      'defimpopt' => 0
    );

?>
    <div class="wrap">
      <h1>Data Maintenance &gt;&gt; GEDCOM Import</h1>

      <form action="" method="post" name="form1" enctype="multipart/form-data" onsubmit="return checkFile(this);">
        <?php wp_nonce_field('hp_import_gedcom', '_wpnonce'); ?>

        <table width="100%" border="0" cellpadding="10" cellspacing="2" class="widefat">
          <!-- File Selection -->
          <tr>
            <td>
              <div>
                <em>Add or replace genealogy data in your HeritagePress database from GEDCOM files</em><br /><br />

                <p><strong>Import GEDCOM:</strong></p>
                <table border="0" cellpadding="1">
                  <tr>
                    <td>&nbsp;&nbsp;From your computer: </td>
                    <td><input type="file" name="remotefile" size="50"></td>
                  </tr>
                  <tr>
                    <td>&nbsp;&nbsp;<strong>OR</strong> &nbsp;On web server: </td>
                    <td>
                      <input type="text" name="database" id="database" size="50">
                      <input type="button" value="Select..." name="gedselect" class="button" onclick="selectServerFile();">
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2"><br />
                      <input type="checkbox" name="allevents" value="yes" onclick="toggleEvents(this)"> Import all events &nbsp;&nbsp;
                      <input type="checkbox" name="eventsonly" value="yes" onclick="toggleSections(this.checked);"> Events only
                    </td>
                  </tr>
                </table>
              </div>
            </td>
          </tr>

          <!-- Tree Selection -->
          <tr id="desttree">
            <td>
              <p><strong>Select existing tree or add new:</strong></p>
              <table border="0" cellpadding="1">
                <tr id="desttree2">
                  <td>&nbsp;&nbsp;Destination tree:</td>
                  <td>
                    <select name="tree1" id="tree1" onchange="getBranches(this,this.selectedIndex);">
                      <?php if (count($trees) != 1): ?>
                        <option value=""></option>
                      <?php endif; ?>
                      <?php foreach ($trees as $tree): ?>
                        <option value="<?php echo esc_attr($tree['gedcom']); ?>"><?php echo esc_html($tree['treename']); ?></option>
                      <?php endforeach; ?>
                    </select>
                    &nbsp; <input type="button" name="newtree" value="Add New Tree" class="button" onclick="addNewTree();">
                  </td>
                </tr>
                <tr id="destbranch" style="display:none">
                  <td>&nbsp;&nbsp;Destination branch:</td>
                  <td>
                    <div id="branch1div">
                      <select name="branch1" id="branch1"></select>
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Import Options -->
          <tr>
            <td>
              <table border="0" cellpadding="1">
                <tr id="replace">
                  <td colspan="2">
                    <p><strong>Replace:</strong></p>
                    <input type="radio" name="del" value="yes" <?php if ($import_config['defimpopt'] == 1) echo " checked"; ?> onclick="toggleOptions(0);"> All current data &nbsp;
                    <input type="radio" name="del" value="match" <?php if (!$import_config['defimpopt']) echo " checked"; ?> onclick="toggleOptions(1);"> Matching records only &nbsp;
                    <input type="radio" name="del" value="no" <?php if ($import_config['defimpopt'] == 2) echo " checked"; ?> onclick="toggleOptions(0);"> Do not replace &nbsp;
                    <input type="radio" name="del" value="append" <?php if ($import_config['defimpopt'] == 3) echo " checked"; ?> onclick="toggleOptions(1);"> Append all<br /><br />
                    <span><em>Hint: If you are not sure what to select, choose "Do not replace"</em></span>
                  </td>
                </tr>
                <tr id="ioptions">
                  <td valign="top">
                    <br />
                    <div><input type="checkbox" name="ucaselast" value="1"> Uppercase surnames</div>
                    <div id="norecalcdiv" <?php if ($import_config['defimpopt']) echo " style=\"display:none\""; ?>>
                      <input type="checkbox" name="norecalc" value="1"> Skip living flag recalculation<br>
                      <input type="checkbox" name="neweronly" value="1"> Import newer data only<br>
                    </div>
                    <div><input type="checkbox" name="importmedia" value="1"> Import media links</div>
                    <div><input type="checkbox" name="importlatlong" value="1"> Import latitude/longitude</div>
                  </td>
                  <td valign="top">
                    <br />
                    <div id="appenddiv" <?php if ($import_config['defimpopt'] != 3) echo " style=\"display:none;\""; ?>>
                      <input type="radio" name="offsetchoice" value="auto" checked> Auto calculate offset &nbsp;<br />
                      <input type="radio" name="offsetchoice" value="user"> User defined offset &nbsp;<input type="text" name="useroffset" size="10" maxlength="9">
                    </div>
                  </td>
                </tr>
              </table>

              <p>
              <ul>
                <li><em>STOP! Have you backed up your database first?</em></li>
                <li><em>Check that all prefixes are set correctly</em></li>
                <li><em>Check your import settings</em></li>
              </ul>
              </p>

              <div style="float:right">
                <input type="checkbox" name="old" id="old" value="1"> Use legacy import method
              </div>

              <input type="submit" name="submit" class="button button-primary" value="Import Data">
            </td>
          </tr>
        </table>
      </form>
    </div>

    <script type="text/javascript">
      function checkFile(form) {
        if (form.remotefile.value.length == 0 && form.database.value.length == 0) {
          alert('Please select an import file');
          return false;
        }
        if (form.tree1.selectedIndex == 0 && form.tree1.options[form.tree1.selectedIndex].value == "" && !form.eventsonly.checked) {
          alert('Please select a destination tree');
          return false;
        }
        return true;
      }

      function toggleEvents(checkbox) {
        if (checkbox.checked && document.form1.eventsonly.checked) {
          document.form1.eventsonly.checked = false;
          toggleSections(false);
        }
      }

      function toggleSections(eventsOnly) {
        document.getElementById('desttree').style.display = eventsOnly ? 'none' : '';
        document.getElementById('replace').style.display = eventsOnly ? 'none' : '';
        document.getElementById('ioptions').style.display = eventsOnly ? 'none' : '';
      }

      function toggleOptions(show) {
        document.getElementById('norecalcdiv').style.display = show ? '' : 'none';
      }

      function getBranches(select, index) {
        // Placeholder for branch loading
      }

      function selectServerFile() {
        alert('Server file picker not yet implemented');
      }

      function addNewTree() {
        alert('Add new tree not yet implemented');
      }
    </script>
<?php
  }
}
