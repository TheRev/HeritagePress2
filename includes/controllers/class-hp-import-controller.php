<?php

/** * Import Controller - Enhanced Implementation
 *
 * Handles GEDCOM import functionality with advanced features
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
   * Process GEDCOM import
   */
  private function process_import()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_import_gedcom')) {
      echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
      return;
    }

    // Get form data (using standard field names)
    $tree = sanitize_text_field($_POST['tree1'] ?? '');
    $remotefile = $_FILES['remotefile'] ?? null;
    $database = sanitize_text_field($_POST['database'] ?? '');
    $del = sanitize_text_field($_POST['del'] ?? 'match');
    $allevents = isset($_POST['allevents']) ? 'yes' : '';
    $eventsonly = isset($_POST['eventsonly']) ? 'yes' : '';
    $ucaselast = isset($_POST['ucaselast']) ? 1 : 0;
    $norecalc = isset($_POST['norecalc']) ? 1 : 0;
    $neweronly = isset($_POST['neweronly']) ? 1 : 0;
    $importmedia = isset($_POST['importmedia']) ? 1 : 0;
    $importlatlong = isset($_POST['importlatlong']) ? 1 : 0;
    $offsetchoice = sanitize_text_field($_POST['offsetchoice'] ?? 'auto');
    $useroffset = intval($_POST['useroffset'] ?? 0);
    $branch = sanitize_text_field($_POST['branch1'] ?? '');

    // Build genealogy-style import options
    $import_options = array(
      'del' => $del,
      'allevents' => $allevents,
      'eventsonly' => $eventsonly,
      'ucaselast' => $ucaselast,
      'norecalc' => $norecalc,
      'neweronly' => $neweronly,
      'importmedia' => $importmedia,
      'importlatlong' => $importlatlong,
      'offsetchoice' => $offsetchoice,
      'useroffset' => $useroffset,
      'branch' => $branch
    );

    // Validation (standard logic)
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
        $result = $this->handle_uploaded_file($remotefile, $tree, $import_options);
      } else {
        // Handle server file
        $result = $this->handle_server_file($database, $tree, $import_options);
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
  private function handle_uploaded_file($file, $tree, $import_options)
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
      return $this->import_gedcom_file($filepath, $tree, $import_options);
    }

    return false;
  }

  /**
   * Handle server file import
   */
  private function handle_server_file($database, $tree, $import_options)
  {
    $gedcom_dir = wp_upload_dir()['basedir'] . '/heritagepress/gedcom/';
    $filepath = $gedcom_dir . sanitize_file_name($database);

    if (!file_exists($filepath)) {
      throw new Exception('Selected file does not exist.');
    }

    return $this->import_gedcom_file($filepath, $tree, $import_options);
  }
  /**
   * Import GEDCOM file using enhanced genealogy parser with full validation
   */
  private function import_gedcom_file($file_path, $tree_id, $import_options = array())
  {
    // Scan and auto-add event types before import
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/class-hp-gedcom-importer.php';
    $importer = new HP_GEDCOM_Importer_Original();
    $importer->scan_and_add_event_types($file_path, $tree_id);

    // Load the enhanced genealogy GEDCOM parser
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-enhanced-gedcom-parser.php';
    try {
      // Create enhanced parser instance with options
      $parser = new HP_Enhanced_GEDCOM_Parser($file_path, $tree_id, $import_options);

      // Run the import (validation is done internally)
      $result = $parser->parse();

      // Display detailed results
      if ($result && $result['success']) {
        echo '<div class="notice notice-success">';
        echo '<p><strong>GEDCOM import completed successfully!</strong></p>';
        echo '<ul>';
        echo '<li>Individuals imported: ' . ($result['stats']['individuals'] ?? 0) . '</li>';
        echo '<li>Families imported: ' . ($result['stats']['families'] ?? 0) . '</li>';
        echo '<li>Sources imported: ' . ($result['stats']['sources'] ?? 0) . '</li>';
        echo '<li>Media imported: ' . ($result['stats']['media'] ?? 0) . '</li>';
        echo '<li>Events imported: ' . ($result['stats']['events'] ?? 0) . '</li>';
        echo '<li>Notes imported: ' . ($result['stats']['notes'] ?? 0) . '</li>';
        echo '</ul>';
        echo '</div>';

        // Show any warnings
        if (!empty($result['warnings'])) {
          echo '<div class="notice notice-warning">';
          echo '<p><strong>Import warnings:</strong></p>';
          echo '<ul>';
          foreach ($result['warnings'] as $warning) {
            echo '<li>' . esc_html($warning) . '</li>';
          }
          echo '</ul>';
          echo '</div>';
        }

        return true;
      } else {
        echo '<div class="notice notice-error">';
        echo '<p><strong>Import failed!</strong></p>';

        if (!empty($result['errors'])) {
          echo '<p>Errors encountered:</p>';
          echo '<ul>';
          foreach ($result['errors'] as $error) {
            echo '<li>' . esc_html($error) . '</li>';
          }
          echo '</ul>';
        }

        if (is_array($result) && isset($result['error'])) {
          echo '<p>Error: ' . esc_html($result['error']) . '</p>';
        }
        echo '</div>';

        return false;
      }
    } catch (Exception $e) {
      echo '<div class="notice notice-error">';
      echo '<p><strong>Import error:</strong> ' . esc_html($e->getMessage()) . '</p>';
      echo '</div>';
      return false;
    }
  }

  /**
   * Display import form (standard structure)
   */
  private function display_import_form()
  {
    // Get available trees
    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';
    $trees = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table ORDER BY treename", ARRAY_A);

    // Import config (default values)
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
        var tree = select.value;
        var branchSelect = document.getElementById('branch1');
        if (!tree) {
          branchSelect.innerHTML = '<option value="">All branches</option>';
          return;
        }
        var data = new FormData();
        data.append('action', 'hp_get_branch_options');
        data.append('tree', tree);
        data.append('nonce', hp_ajax_vars.nonce);
        fetch(ajaxurl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
          })
          .then(response => response.text())
          .then(html => {
            branchSelect.innerHTML = html || '<option value="">All branches</option>';
          });
      }

      function selectServerFile() {
        alert('Server file picker not yet implemented');
      }

      function addNewTree() {
        alert('Add new tree not yet implemented');
      }
    </script>

    <script type="text/javascript">
      // Localize AJAX variables for security
      window.hp_ajax_vars = {
        nonce: '<?php echo wp_create_nonce('hp_ajax_nonce'); ?>',
      };
    </script>
<?php
  }
}
