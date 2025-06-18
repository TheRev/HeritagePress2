<?php

/**
 * Branch Management Admin Template
 *
 * Provides interface for managing branches within genealogy trees
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}
?>

<div class="wrap heritagepress-branches">
  <h1><?php _e('Tree Branches', 'heritagepress'); ?></h1>

  <div class="branch-management">
    <div class="branch-form-container">
      <h2><?php _e('Add New Branch', 'heritagepress'); ?></h2>

      <form id="add-branch-form" class="branch-form">
        <?php wp_nonce_field('hp_branch_nonce', 'nonce'); ?>

        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="gedcom"><?php _e('Tree', 'heritagepress'); ?></label>
              </th>
              <td>
                <select id="gedcom" name="gedcom" required>
                  <option value=""><?php _e('Select Tree', 'heritagepress'); ?></option>
                  <?php
                  // Get available trees
                  global $wpdb;
                  $trees_table = $wpdb->prefix . 'hp_trees';
                  $trees = $wpdb->get_results("SELECT * FROM $trees_table ORDER BY tree_name");
                  foreach ($trees as $tree) {
                    echo '<option value="' . esc_attr($tree->gedcom) . '">' .
                      esc_html($tree->tree_name) . '</option>';
                  }
                  ?>
                </select>
                <p class="description"><?php _e('Select the tree for this branch.', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="branch"><?php _e('Branch ID', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" id="branch" name="branch" class="regular-text">
                <p class="description"><?php _e('Optional. A unique identifier for this branch. If left empty, one will be generated.', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="description"><?php _e('Branch Description', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" id="description" name="description" class="regular-text" required>
                <p class="description"><?php _e('A descriptive name for this branch.', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="personID"><?php _e('Starting Person', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" id="personID" name="personID" class="regular-text" required>
                <button type="button" id="search-person" class="button"><?php _e('Search', 'heritagepress'); ?></button>
                <p class="description"><?php _e('The person from whom this branch starts.', 'heritagepress'); ?></p>
                <div id="person-search-results" class="search-results"></div>
              </td>
            </tr>

            <tr>
              <th scope="row"><?php _e('Generation Limits', 'heritagepress'); ?></th>
              <td>
                <label for="agens">
                  <?php _e('Ancestor Generations:', 'heritagepress'); ?>
                  <input type="number" id="agens" name="agens" value="0" min="0" class="small-text">
                </label>
                <br>
                <label for="dgens">
                  <?php _e('Descendant Generations:', 'heritagepress'); ?>
                  <input type="number" id="dgens" name="dgens" value="0" min="0" class="small-text">
                </label>
                <br>
                <label for="dagens">
                  <?php _e('Descendant Ancestor Generations:', 'heritagepress'); ?>
                  <input type="number" id="dagens" name="dagens" value="0" min="0" class="small-text">
                </label>
                <p class="description"><?php _e('How many generations to include in each direction. Use 0 for unlimited.', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="inclspouses"><?php _e('Include Spouses', 'heritagepress'); ?></label>
              </th>
              <td>
                <label>
                  <input type="checkbox" id="inclspouses" name="inclspouses" value="1">
                  <?php _e('Include all spouses of people in this branch', 'heritagepress'); ?>
                </label>
              </td>
            </tr>
          </tbody>
        </table>

        <p class="submit">
          <button type="submit" class="button button-primary"><?php _e('Add Branch', 'heritagepress'); ?></button>
          <button type="button" id="reset-form" class="button"><?php _e('Reset Form', 'heritagepress'); ?></button>
        </p>
      </form>
    </div>

    <div class="branch-list-container">
      <h2><?php _e('Existing Branches', 'heritagepress'); ?></h2>

      <div class="branch-filters">
        <label for="filter-tree"><?php _e('Filter by Tree:', 'heritagepress'); ?></label>
        <select id="filter-tree" name="filter-tree">
          <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
          <?php
          foreach ($trees as $tree) {
            echo '<option value="' . esc_attr($tree->gedcom) . '">' .
              esc_html($tree->tree_name) . '</option>';
          }
          ?>
        </select>

        <button type="button" id="load-branches" class="button"><?php _e('Load Branches', 'heritagepress'); ?></button>
      </div>

      <div id="branches-table-container">
        <p><?php _e('Select a tree to view its branches.', 'heritagepress'); ?></p>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {

    // Add branch form submission
    $('#add-branch-form').on('submit', function(e) {
      e.preventDefault();

      var formData = {
        action: 'hp_add_branch',
        nonce: $('#nonce').val(),
        gedcom: $('#gedcom').val(),
        branch: $('#branch').val(),
        description: $('#description').val(),
        personID: $('#personID').val(),
        agens: $('#agens').val(),
        dgens: $('#dgens').val(),
        dagens: $('#dagens').val(),
        inclspouses: $('#inclspouses').is(':checked') ? 1 : 0
      };

      $.post(ajaxurl, formData, function(response) {
        if (response.success) {
          alert('<?php _e('Branch added successfully!', 'heritagepress'); ?>');
          $('#add-branch-form')[0].reset();
          // Reload branches if viewing same tree
          if ($('#filter-tree').val() === formData.gedcom) {
            loadBranches();
          }
        } else {
          alert('<?php _e('Error:', 'heritagepress'); ?> ' + (response.data.message || response.data));
        }
      });
    });

    // Reset form
    $('#reset-form').on('click', function() {
      $('#add-branch-form')[0].reset();
      $('.search-results').empty();
    });

    // Load branches
    $('#load-branches').on('click', function() {
      loadBranches();
    });

    // Load branches function
    function loadBranches() {
      var gedcom = $('#filter-tree').val();

      if (!gedcom) {
        alert('<?php _e('Please select a tree.', 'heritagepress'); ?>');
        return;
      }

      var data = {
        action: 'hp_get_branches',
        nonce: '<?php echo wp_create_nonce('hp_branch_nonce'); ?>',
        tree_id: gedcom
      };

      $.post(ajaxurl, data, function(response) {
        if (response.success) {
          displayBranches(response.data.branches);
        } else {
          $('#branches-table-container').html('<p class="error">' + response.data + '</p>');
        }
      });
    }

    // Display branches table
    function displayBranches(branches) {
      if (!branches || branches.length === 0) {
        $('#branches-table-container').html('<p><?php _e('No branches found.', 'heritagepress'); ?></p>');
        return;
      }

      var html = '<table class="wp-list-table widefat fixed striped">';
      html += '<thead><tr>';
      html += '<th><?php _e('Branch ID', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Description', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Starting Person', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Generations', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Tree', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Actions', 'heritagepress'); ?></th>';
      html += '</tr></thead><tbody>';

      branches.forEach(function(branch) {
        var generations = '↑' + branch.agens + ' ↓' + branch.dgens;
        if (branch.dagens > 0) {
          generations += ' ←' + branch.dagens;
        }

        html += '<tr>';
        html += '<td>' + branch.branch + '</td>';
        html += '<td>' + branch.description + '</td>';
        html += '<td>' + branch.personID + '</td>';
        html += '<td>' + generations + '</td>';
        html += '<td>' + branch.gedcom + '</td>';
        html += '<td>';
        html += '<button type="button" class="edit-branch button button-small" data-branch="' + branch.branch + '" data-tree="' + branch.gedcom + '"><?php _e('Edit', 'heritagepress'); ?></button> ';
        html += '<button type="button" class="delete-branch button button-small" data-branch="' + branch.branch + '" data-tree="' + branch.gedcom + '"><?php _e('Delete', 'heritagepress'); ?></button>';
        html += '</td>';
        html += '</tr>';
      });

      html += '</tbody></table>';
      $('#branches-table-container').html(html);
    }

    // Edit branch (basic implementation)
    $(document).on('click', '.edit-branch', function() {
      var branchId = $(this).data('branch');
      var treeId = $(this).data('tree');
      alert('<?php _e('Branch editing functionality would be implemented here.', 'heritagepress'); ?>');
      // TODO: Implement branch editing
    });

    // Delete branch
    $(document).on('click', '.delete-branch', function() {
      if (!confirm('<?php _e('Are you sure you want to delete this branch?', 'heritagepress'); ?>')) {
        return;
      }

      var branchId = $(this).data('branch');
      var treeId = $(this).data('tree');

      var data = {
        action: 'hp_delete_branch',
        nonce: '<?php echo wp_create_nonce('hp_branch_nonce'); ?>',
        gedcom: treeId,
        branch: branchId
      };

      $.post(ajaxurl, data, function(response) {
        if (response.success) {
          alert('<?php _e('Branch deleted successfully!', 'heritagepress'); ?>');
          loadBranches();
        } else {
          alert('<?php _e('Error:', 'heritagepress'); ?> ' + response.data);
        }
      });
    });

    // Person search functionality (basic implementation)
    $('#search-person').on('click', function() {
      alert('<?php _e('Person search functionality would be implemented here.', 'heritagepress'); ?>');
      // TODO: Implement person search AJAX
    });
  });
</script>

<style>
  .heritagepress-branches .branch-management {
    display: flex;
    gap: 30px;
    margin-top: 20px;
  }

  .branch-form-container,
  .branch-list-container {
    flex: 1;
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
  }

  .branch-form-container h2,
  .branch-list-container h2 {
    margin-top: 0;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
  }

  .search-results {
    max-height: 150px;
    overflow-y: auto;
    border: 1px solid #ddd;
    background: #f9f9f9;
    padding: 10px;
    margin-top: 5px;
    display: none;
  }

  .search-results.has-results {
    display: block;
  }

  .branch-filters {
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
  }

  .branch-filters label {
    margin-right: 10px;
    font-weight: bold;
  }

  .branch-filters select {
    margin-right: 15px;
  }

  #branches-table-container {
    min-height: 200px;
  }

  .error {
    color: #d63638;
    font-weight: bold;
  }

  /* Generation inputs */
  .small-text {
    width: 60px !important;
  }
</style>
