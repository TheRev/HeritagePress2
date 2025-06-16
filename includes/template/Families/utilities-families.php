<?php

/**
 * Family Utilities - Advanced family management tools
 * Merge, delete, validate, and other utility functions
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;
?>

<div class="family-utilities-container">

  <!-- Merge Families -->
  <div class="utility-section">
    <h3><?php _e('Merge Families', 'heritagepress'); ?></h3>
    <p class="description">
      <?php _e('Merge duplicate family records. The primary family will retain all data, and the duplicate will be removed.', 'heritagepress'); ?>
    </p>

    <form method="post" action="" class="merge-families-form">
      <?php wp_nonce_field('heritagepress_merge_families', 'merge_families_nonce'); ?>
      <input type="hidden" name="action" value="merge_families">

      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="primary_family"><?php _e('Primary Family (Keep):', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="primary_family_display" id="primary_family_display" class="regular-text" readonly
              placeholder="<?php _e('Click Find to select primary family', 'heritagepress'); ?>">
            <input type="hidden" name="primary_family" id="primary_family">
            <input type="button" value="<?php _e('Find Family', 'heritagepress'); ?>" class="button"
              onclick="findFamily('primary_family');">
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="duplicate_family"><?php _e('Duplicate Family (Remove):', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="text" name="duplicate_family_display" id="duplicate_family_display" class="regular-text" readonly
              placeholder="<?php _e('Click Find to select duplicate family', 'heritagepress'); ?>">
            <input type="hidden" name="duplicate_family" id="duplicate_family">
            <input type="button" value="<?php _e('Find Family', 'heritagepress'); ?>" class="button"
              onclick="findFamily('duplicate_family');">
          </td>
        </tr>
      </table>

      <p class="submit">
        <input type="submit" name="merge_families" class="button button-primary"
          value="<?php _e('Merge Families', 'heritagepress'); ?>"
          onclick="return confirm('<?php _e('Are you sure you want to merge these families? This action cannot be undone.', 'heritagepress'); ?>');">
      </p>
    </form>
  </div>

  <!-- Delete Multiple Families -->
  <div class="utility-section">
    <h3><?php _e('Delete Multiple Families', 'heritagepress'); ?></h3>
    <p class="description">
      <?php _e('Delete multiple families at once. Use with caution.', 'heritagepress'); ?>
    </p>

    <form method="post" action="" class="delete-families-form">
      <?php wp_nonce_field('heritagepress_bulk_delete_families', 'bulk_delete_families_nonce'); ?>
      <input type="hidden" name="action" value="bulk_delete_families">

      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="delete_criteria"><?php _e('Delete Criteria:', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="delete_criteria" id="delete_criteria" onchange="toggleDeleteOptions();">
              <option value=""><?php _e('Select criteria...', 'heritagepress'); ?></option>
              <option value="no_spouses"><?php _e('Families with no spouses', 'heritagepress'); ?></option>
              <option value="no_children"><?php _e('Families with no children or events', 'heritagepress'); ?></option>
              <option value="tree"><?php _e('All families in specific tree', 'heritagepress'); ?></option>
              <option value="branch"><?php _e('All families in specific branch', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <tr id="tree_selection" style="display:none;">
          <th scope="row">
            <label for="delete_tree"><?php _e('Tree:', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="delete_tree" id="delete_tree">
              <option value=""><?php _e('Select tree...', 'heritagepress'); ?></option>
              <?php
              $trees = $wpdb->get_results("SELECT gedcom, treename FROM {$wpdb->prefix}hp_trees ORDER BY treename", ARRAY_A);
              foreach ($trees as $tree):
              ?>
                <option value="<?php echo esc_attr($tree['gedcom']); ?>">
                  <?php echo esc_html($tree['treename']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr id="branch_selection" style="display:none;">
          <th scope="row">
            <label for="delete_branch"><?php _e('Branch:', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="delete_branch" id="delete_branch">
              <option value=""><?php _e('Select branch...', 'heritagepress'); ?></option>
              <!-- Branches will be populated via AJAX -->
            </select>
          </td>
        </tr>
      </table>

      <div id="delete_preview" style="display:none;">
        <h4><?php _e('Families to be deleted:', 'heritagepress'); ?></h4>
        <div id="delete_preview_list"></div>
      </div>

      <p class="submit">
        <input type="button" name="preview_delete" class="button" value="<?php _e('Preview Deletion', 'heritagepress'); ?>" onclick="previewDeletion();">
        <input type="submit" name="confirm_delete" class="button button-primary"
          value="<?php _e('Delete Families', 'heritagepress'); ?>" disabled
          onclick="return confirm('<?php _e('Are you sure you want to delete these families? This action cannot be undone.', 'heritagepress'); ?>');">
      </p>
    </form>
  </div>

  <!-- Validate Family Data -->
  <div class="utility-section">
    <h3><?php _e('Validate Family Data', 'heritagepress'); ?></h3>
    <p class="description">
      <?php _e('Check for and report data inconsistencies in family records.', 'heritagepress'); ?>
    </p>

    <form method="post" action="" class="validate-families-form">
      <?php wp_nonce_field('heritagepress_validate_families', 'validate_families_nonce'); ?>
      <input type="hidden" name="action" value="validate_families">

      <table class="form-table">
        <tr>
          <th scope="row"><?php _e('Validation Checks:', 'heritagepress'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="checks[]" value="missing_spouses" checked>
              <?php _e('Families with missing spouse records', 'heritagepress'); ?>
            </label><br>
            <label>
              <input type="checkbox" name="checks[]" value="invalid_dates" checked>
              <?php _e('Invalid or inconsistent dates', 'heritagepress'); ?>
            </label><br>
            <label>
              <input type="checkbox" name="checks[]" value="orphaned_children" checked>
              <?php _e('Children with invalid family links', 'heritagepress'); ?>
            </label><br>
            <label>
              <input type="checkbox" name="checks[]" value="duplicate_families" checked>
              <?php _e('Potential duplicate families', 'heritagepress'); ?>
            </label><br>
            <label>
              <input type="checkbox" name="checks[]" value="circular_references" checked>
              <?php _e('Circular family references', 'heritagepress'); ?>
            </label>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="validate_tree"><?php _e('Tree (optional):', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="validate_tree" id="validate_tree">
              <option value=""><?php _e('All trees', 'heritagepress'); ?></option>
              <?php foreach ($trees as $tree): ?>
                <option value="<?php echo esc_attr($tree['gedcom']); ?>">
                  <?php echo esc_html($tree['treename']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
      </table>

      <p class="submit">
        <input type="submit" name="validate_families" class="button button-primary"
          value="<?php _e('Run Validation', 'heritagepress'); ?>">
      </p>
    </form>

    <div id="validation_results" style="display:none;">
      <!-- Validation results will be displayed here -->
    </div>
  </div>

  <!-- Renumber Family IDs -->
  <div class="utility-section">
    <h3><?php _e('Renumber Family IDs', 'heritagepress'); ?></h3>
    <p class="description">
      <?php _e('Renumber all family IDs in a tree to follow a consistent pattern.', 'heritagepress'); ?>
    </p>

    <form method="post" action="" class="renumber-families-form">
      <?php wp_nonce_field('heritagepress_renumber_families', 'renumber_families_nonce'); ?>
      <input type="hidden" name="action" value="renumber_families">

      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="renumber_tree"><?php _e('Tree:', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="renumber_tree" id="renumber_tree" required>
              <option value=""><?php _e('Select tree...', 'heritagepress'); ?></option>
              <?php foreach ($trees as $tree): ?>
                <option value="<?php echo esc_attr($tree['gedcom']); ?>">
                  <?php echo esc_html($tree['treename']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="id_pattern"><?php _e('ID Pattern:', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="id_pattern" id="id_pattern" required>
              <option value="F{n}"><?php _e('F1, F2, F3... (F{n})', 'heritagepress'); ?></option>
              <option value="F{nnnn}"><?php _e('F0001, F0002, F0003... (F{nnnn})', 'heritagepress'); ?></option>
              <option value="{tree}F{n}"><?php _e('TreeF1, TreeF2... ({tree}F{n})', 'heritagepress'); ?></option>
              <option value="FAM{n}"><?php _e('FAM1, FAM2, FAM3... (FAM{n})', 'heritagepress'); ?></option>
            </select>
            <p class="description">
              <?php _e('{n} = number, {nnnn} = zero-padded number, {tree} = tree code', 'heritagepress'); ?>
            </p>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="start_number"><?php _e('Start Number:', 'heritagepress'); ?></label>
          </th>
          <td>
            <input type="number" name="start_number" id="start_number" value="1" min="1" required>
          </td>
        </tr>
      </table>

      <p class="submit">
        <input type="button" name="preview_renumber" class="button" value="<?php _e('Preview Changes', 'heritagepress'); ?>" onclick="previewRenumbering();">
        <input type="submit" name="confirm_renumber" class="button button-primary"
          value="<?php _e('Renumber IDs', 'heritagepress'); ?>" disabled
          onclick="return confirm('<?php _e('Are you sure you want to renumber all family IDs? This action cannot be undone.', 'heritagepress'); ?>');">
      </p>
    </form>

    <div id="renumber_preview" style="display:none;">
      <!-- Renumbering preview will be displayed here -->
    </div>
  </div>

  <!-- Export Families -->
  <div class="utility-section">
    <h3><?php _e('Export Families', 'heritagepress'); ?></h3>
    <p class="description">
      <?php _e('Export family data to various formats for backup or analysis.', 'heritagepress'); ?>
    </p>

    <form method="post" action="" class="export-families-form">
      <?php wp_nonce_field('heritagepress_export_families', 'export_families_nonce'); ?>
      <input type="hidden" name="action" value="export_families">

      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="export_format"><?php _e('Export Format:', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="export_format" id="export_format" required>
              <option value="csv"><?php _e('CSV (Comma Separated Values)', 'heritagepress'); ?></option>
              <option value="excel"><?php _e('Excel Spreadsheet', 'heritagepress'); ?></option>
              <option value="gedcom"><?php _e('GEDCOM', 'heritagepress'); ?></option>
              <option value="json"><?php _e('JSON', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="export_tree"><?php _e('Tree (optional):', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="export_tree" id="export_tree">
              <option value=""><?php _e('All trees', 'heritagepress'); ?></option>
              <?php foreach ($trees as $tree): ?>
                <option value="<?php echo esc_attr($tree['gedcom']); ?>">
                  <?php echo esc_html($tree['treename']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php _e('Include:', 'heritagepress'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="include[]" value="events" checked>
              <?php _e('Marriage and divorce events', 'heritagepress'); ?>
            </label><br>
            <label>
              <input type="checkbox" name="include[]" value="children" checked>
              <?php _e('Children relationships', 'heritagepress'); ?>
            </label><br>
            <label>
              <input type="checkbox" name="include[]" value="notes" checked>
              <?php _e('Notes and references', 'heritagepress'); ?>
            </label><br>
            <label>
              <input type="checkbox" name="include[]" value="sources">
              <?php _e('Source citations', 'heritagepress'); ?>
            </label>
          </td>
        </tr>
      </table>

      <p class="submit">
        <input type="submit" name="export_families" class="button button-primary"
          value="<?php _e('Export Families', 'heritagepress'); ?>">
      </p>
    </form>
  </div>

</div>

<!-- Family Finder Modal -->
<div id="family-finder-modal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" onclick="closeFamilyFinder()">&times;</span>
    <h3><?php _e('Find Family', 'heritagepress'); ?></h3>
    <div id="family-finder-content">
      <!-- Family finder will be loaded here via AJAX -->
    </div>
  </div>
</div>

<script type="text/javascript">
  var currentFamilyField = '';

  // Family finder functions
  function findFamily(field) {
    currentFamilyField = field;
    var modal = document.getElementById('family-finder-modal');
    var content = document.getElementById('family-finder-content');

    content.innerHTML = '<?php _e('Loading...', 'heritagepress'); ?>';
    modal.style.display = 'block';

    // Load family finder via AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        content.innerHTML = xhr.responseText;
      }
    };
    xhr.send('action=hp_family_finder&nonce=<?php echo wp_create_nonce('hp_family_finder'); ?>');
  }

  function selectFamily(familyID, familyDisplay) {
    document.getElementById(currentFamilyField).value = familyID;
    document.getElementById(currentFamilyField + '_display').value = familyDisplay;
    closeFamilyFinder();
  }

  function closeFamilyFinder() {
    document.getElementById('family-finder-modal').style.display = 'none';
  }

  // Delete options toggle
  function toggleDeleteOptions() {
    var criteria = document.getElementById('delete_criteria').value;
    var treeRow = document.getElementById('tree_selection');
    var branchRow = document.getElementById('branch_selection');

    treeRow.style.display = 'none';
    branchRow.style.display = 'none';

    if (criteria === 'tree') {
      treeRow.style.display = 'table-row';
    } else if (criteria === 'branch') {
      branchRow.style.display = 'table-row';
      // Load branches for selected tree
      updateBranchesForDelete();
    }
  }

  function updateBranchesForDelete() {
    // Update branches when tree is selected for deletion
    var tree = document.getElementById('delete_tree').value;
    if (!tree) return;

    var branchSelect = document.getElementById('delete_branch');
    branchSelect.innerHTML = '<option value=""><?php _e('Loading...', 'heritagepress'); ?></option>';

    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        var response = JSON.parse(xhr.responseText);
        if (response.success) {
          branchSelect.innerHTML = response.data.html;
        }
      }
    };
    xhr.send('action=hp_get_tree_branches_select&tree=' + encodeURIComponent(tree) + '&nonce=<?php echo wp_create_nonce('hp_get_tree_branches'); ?>');
  }

  // Preview deletion
  function previewDeletion() {
    var form = document.querySelector('.delete-families-form');
    var formData = new FormData(form);
    formData.append('action', 'preview_family_deletion');
    formData.append('nonce', '<?php echo wp_create_nonce('preview_family_deletion'); ?>');

    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxurl, true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        var response = JSON.parse(xhr.responseText);
        if (response.success) {
          document.getElementById('delete_preview_list').innerHTML = response.data.html;
          document.getElementById('delete_preview').style.display = 'block';
          document.querySelector('input[name="confirm_delete"]').disabled = false;
        } else {
          alert('<?php _e('Error previewing deletion: ', 'heritagepress'); ?>' + response.data.message);
        }
      }
    };
    xhr.send(formData);
  }

  // Preview renumbering
  function previewRenumbering() {
    var form = document.querySelector('.renumber-families-form');
    var formData = new FormData(form);
    formData.append('action', 'preview_family_renumbering');
    formData.append('nonce', '<?php echo wp_create_nonce('preview_family_renumbering'); ?>');

    var xhr = new XMLHttpRequest();
    xhr.open('POST', ajaxurl, true);
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4 && xhr.status === 200) {
        var response = JSON.parse(xhr.responseText);
        if (response.success) {
          document.getElementById('renumber_preview').innerHTML = response.data.html;
          document.getElementById('renumber_preview').style.display = 'block';
          document.querySelector('input[name="confirm_renumber"]').disabled = false;
        } else {
          alert('<?php _e('Error previewing renumbering: ', 'heritagepress'); ?>' + response.data.message);
        }
      }
    };
    xhr.send(formData);
  }
</script>

<style>
  .family-utilities-container {
    max-width: 1200px;
    margin: 20px 0;
  }

  .utility-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    margin-bottom: 30px;
    padding: 20px;
  }

  .utility-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #dcdcde;
    color: #1d2327;
  }

  .utility-section .description {
    margin-bottom: 20px;
    color: #646970;
  }

  .form-table th {
    width: 200px;
    padding: 15px 10px 15px 0;
    vertical-align: top;
  }

  .form-table td {
    padding: 15px 0;
    vertical-align: top;
  }

  #delete_preview,
  #renumber_preview {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    padding: 15px;
    margin-top: 20px;
    border-radius: 4px;
  }

  #delete_preview h4,
  #renumber_preview h4 {
    margin-top: 0;
    color: #d63384;
  }

  #validation_results {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    padding: 15px;
    margin-top: 20px;
    border-radius: 4px;
  }

  /* Modal styles (same as other pages) */
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
</style>
