<?php

/**
 * Branch Management Admin Interface
 *
 * Replicates HeritagePress admin_branches.php functionality for WordPress
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

// Initialize controller
$controller = new HP_Branch_Controller();

// Handle messages
$message = '';
if (isset($_GET['message'])) {
  switch ($_GET['message']) {
    case 'added':
      $message = __('Branch added successfully.', 'heritagepress');
      break;
    case 'updated':
      $message = __('Branch updated successfully.', 'heritagepress');
      break;
    case 'deleted':
      $message = __('Branch deleted successfully.', 'heritagepress');
      break;
  }
}

// Get trees for dropdown
global $wpdb;
$trees_table = $wpdb->prefix . 'hp_trees';
$trees = $wpdb->get_results("SELECT gedcom, tree_name FROM $trees_table ORDER BY tree_name");
?>

<div class="wrap heritagepress-branches">
  <h1 class="wp-heading-inline"><?php _e('Tree Branches', 'heritagepress'); ?></h1>
  <a href="#" class="page-title-action" id="add-new-branch-btn"><?php _e('Add New', 'heritagepress'); ?></a>
  <hr class="wp-header-end">

  <?php if ($message): ?>
    <div class="notice notice-success is-dismissible">
      <p><?php echo esc_html($message); ?></p>
    </div>
  <?php endif; ?>
  <!-- Tab Navigation -->
  <nav class="nav-tab-wrapper wp-clearfix">
    <a href="#search-branches" class="nav-tab nav-tab-active" id="search-tab"><?php _e('Search', 'heritagepress'); ?></a>
    <a href="#add-branch" class="nav-tab" id="add-tab"><?php _e('Add New', 'heritagepress'); ?></a>
    <a href="#label-branches" class="nav-tab" id="label-tab"><?php _e('Label Branches', 'heritagepress'); ?></a>
  </nav>

  <!-- Search Branches Tab -->
  <div id="search-branches" class="tab-content active">
    <div class="branches-search-container">

      <!-- Search Form -->
      <div class="search-form-container">
        <form id="branch-search-form" class="branch-search-form">
          <table class="form-table">
            <tbody>
              <tr>
                <th scope="row">
                  <label for="search-tree"><?php _e('Search for:', 'heritagepress'); ?></label>
                </th>
                <td>
                  <select id="search-tree" name="tree" style="width: 200px;">
                    <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
                    <?php foreach ($trees as $tree): ?>
                      <option value="<?php echo esc_attr($tree->gedcom); ?>">
                        <?php echo esc_html($tree->tree_name); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>

                  <input type="text" id="search-string" name="searchstring" placeholder="<?php _e('Search branches...', 'heritagepress'); ?>" class="regular-text" style="width: 300px;">

                  <button type="submit" class="button"><?php _e('Search', 'heritagepress'); ?></button>
                  <button type="button" id="reset-search" class="button"><?php _e('Reset', 'heritagepress'); ?></button>
                </td>
              </tr>
            </tbody>
          </table>

          <input type="hidden" name="action" value="hp_search_branches">
          <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('hp_ajax_nonce'); ?>">
          <input type="hidden" name="offset" value="0" id="search-offset">
          <input type="hidden" name="order" value="desc" id="search-order">
        </form>
      </div>

      <!-- Results Container -->
      <div id="branch-results" class="branch-results">
        <p class="description"><?php _e('Use the search form above to find branches.', 'heritagepress'); ?></p>
      </div>
    </div>
  </div>

  <!-- Add Branch Tab -->
  <div id="add-branch" class="tab-content">
    <div class="add-branch-container">
      <h2><?php _e('Add New Branch', 'heritagepress'); ?></h2>

      <form id="add-branch-form" class="branch-form">
        <?php wp_nonce_field('hp_branch_nonce', 'nonce'); ?>
        <input type="hidden" name="action" value="add_branch">

        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="add-gedcom"><?php _e('Tree', 'heritagepress'); ?></label>
              </th>
              <td>
                <select id="add-gedcom" name="gedcom" required>
                  <option value=""><?php _e('Select Tree', 'heritagepress'); ?></option>
                  <?php foreach ($trees as $tree): ?>
                    <option value="<?php echo esc_attr($tree->gedcom); ?>">
                      <?php echo esc_html($tree->tree_name); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Select the tree for this branch.', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="add-branch-id"><?php _e('Branch ID', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" id="add-branch-id" name="branch" class="regular-text" maxlength="20">
                <p class="description"><?php _e('Optional. A unique identifier for this branch (letters, numbers, hyphens, underscores only). If left empty, one will be generated.', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="add-description"><?php _e('Description', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" id="add-description" name="description" class="regular-text" required>
                <p class="description"><?php _e('A descriptive name for this branch.', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="add-person-id"><?php _e('Starting Individual', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" id="add-person-id" name="personID" class="regular-text" required>
                <button type="button" id="find-person" class="button"><?php _e('Find', 'heritagepress'); ?></button>
                <p class="description"><?php _e('The person ID from whom this branch starts.', 'heritagepress'); ?></p>
                <div id="person-search-results" class="search-results"></div>
              </td>
            </tr>

            <tr>
              <th scope="row"><?php _e('Number of Generations', 'heritagepress'); ?></th>
              <td>
                <table class="widefat" style="width: auto;">
                  <tr>
                    <td><?php _e('Ancestors:', 'heritagepress'); ?></td>
                    <td><input type="number" id="add-agens" name="agens" value="0" min="0" max="999" class="small-text"></td>
                    <td><?php _e('Descendants of Ancestors:', 'heritagepress'); ?></td>
                    <td>
                      <select name="dagens" id="add-dagens">
                        <option value="0">0</option>
                        <option value="1" selected>1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td><?php _e('Descendants:', 'heritagepress'); ?></td>
                    <td><input type="number" id="add-dgens" name="dgens" value="0" min="0" max="999" class="small-text"></td>
                    <td colspan="2">
                      <label>
                        <input type="checkbox" id="add-inclspouses" name="inclspouses" value="1" checked>
                        <?php _e('Include Spouses', 'heritagepress'); ?>
                      </label>
                    </td>
                  </tr>
                </table>
                <p class="description"><?php _e('Specify the number of generations to include in each direction. Use 0 for no limit.', 'heritagepress'); ?></p>
              </td>
            </tr>
          </tbody>
        </table>

        <p class="submit">
          <button type="submit" name="submitx" class="button button-primary"><?php _e('Save & Return', 'heritagepress'); ?></button>
          <button type="submit" name="submit" class="button"><?php _e('Save & Continue', 'heritagepress'); ?></button>
          <button type="button" id="cancel-add" class="button"><?php _e('Cancel', 'heritagepress'); ?></button>
        </p>
      </form>
    </div>
  </div>
</div>

<!-- Label Branches Tab -->
<div id="label-branches" class="tab-content">
  <div class="label-branches-container">
    <div class="card">
      <h2><?php _e('Apply Branch Labels', 'heritagepress'); ?></h2>
      <p class="description">
        <?php _e('Apply, clear, or delete branch labels for people and families in your genealogy trees. You can work with all records or specific genealogical relationships.', 'heritagepress'); ?>
      </p>

      <form id="label-branches-form" method="post">
        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label for="label-tree"><?php _e('Tree', 'heritagepress'); ?></label>
              </th>
              <td>
                <select id="label-tree" name="tree" required style="width: 200px;">
                  <option value=""><?php _e('Select Tree...', 'heritagepress'); ?></option>
                  <?php foreach ($trees as $tree): ?>
                    <option value="<?php echo esc_attr($tree->gedcom); ?>">
                      <?php echo esc_html($tree->tree_name); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="label-branch"><?php _e('Branch', 'heritagepress'); ?></label>
              </th>
              <td>
                <select id="label-branch" name="branch" required style="width: 300px;">
                  <option value=""><?php _e('Select Tree First...', 'heritagepress'); ?></option>
                </select>
                <span class="description"><?php _e('Select the tree first to load available branches', 'heritagepress'); ?></span>
              </td>
            </tr>

            <tr>
              <th scope="row"><?php _e('Action', 'heritagepress'); ?></th>
              <td>
                <fieldset>
                  <legend class="screen-reader-text"><?php _e('Choose labeling action', 'heritagepress'); ?></legend>
                  <label>
                    <input type="radio" name="branchaction" value="add" checked>
                    <?php _e('Add Labels', 'heritagepress'); ?>
                  </label><br>
                  <label>
                    <input type="radio" name="branchaction" value="clear">
                    <?php _e('Clear Labels', 'heritagepress'); ?>
                  </label><br>
                  <label>
                    <input type="radio" name="branchaction" value="delete">
                    <?php _e('Delete Branch Records', 'heritagepress'); ?>
                  </label>
                  <p class="description">
                    <?php _e('<strong>Warning:</strong> Delete action will permanently remove all people and families in the branch!', 'heritagepress'); ?>
                  </p>
                </fieldset>
              </td>
            </tr>
            <tr>
              <th scope="row"><?php _e('Apply To', 'heritagepress'); ?></th>
              <td>
                <fieldset>
                  <legend class="screen-reader-text"><?php _e('Choose scope of application', 'heritagepress'); ?></legend>
                  <label>
                    <input type="radio" name="set" value="partial" checked>
                    <?php _e('Starting Individual and Relatives', 'heritagepress'); ?>
                  </label><br>
                  <label>
                    <input type="radio" name="set" value="all">
                    <?php _e('All Records in Branch', 'heritagepress'); ?>
                  </label>
                  <p class="description">
                    <?php _e('For individual-based processing, specify the starting person and generation parameters below.', 'heritagepress'); ?>
                  </p>
                </fieldset>
              </td>
            </tr>

            <tr id="starting-individual-row">
              <th scope="row">
                <label for="personID"><?php _e('Starting Individual', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" name="personID" id="personID" size="10" placeholder="I1">
                <button type="button" class="button" id="find-person-btn"><?php _e('Find Person', 'heritagepress'); ?></button>
                <p class="description">
                  <?php _e('Enter a person ID (e.g., I1) or use Find Person to select. Required for individual-based processing.', 'heritagepress'); ?>
                </p>
              </td>
            </tr>

            <tr id="generations-row">
              <th scope="row"><?php _e('Number of Generations', 'heritagepress'); ?></th>
              <td>
                <table class="form-table-inner">
                  <tr>
                    <td style="padding: 5px 10px 5px 0;">
                      <label for="agens"><?php _e('Ancestors:', 'heritagepress'); ?></label>
                      <input type="number" name="agens" id="agens" min="0" max="99" value="0" size="3" style="width: 60px;">
                    </td>
                    <td style="padding: 5px 10px;">
                      <label for="dagens"><?php _e('Descendants of Ancestors:', 'heritagepress'); ?></label>
                      <select name="dagens" id="dagens" style="width: 60px;">
                        <option value="0">0</option>
                        <option value="1" selected>1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td style="padding: 5px 10px 5px 0;">
                      <label for="dgens"><?php _e('Descendants:', 'heritagepress'); ?></label>
                      <input type="number" name="dgens" id="dgens" min="0" max="99" value="0" size="3" style="width: 60px;">
                    </td>
                    <td style="padding: 5px 10px;">
                      <label>
                        <input type="checkbox" name="dospouses" value="1" checked>
                        <?php _e('Include spouses', 'heritagepress'); ?>
                      </label>
                    </td>
                  </tr>
                </table>
                <p class="description">
                  <?php _e('Set to 0 to skip that type of relative. Ancestors traces back through parents. Descendants traces forward through children.', 'heritagepress'); ?>
                </p>
              </td>
            </tr>

            <tr id="overwrite-options" style="display: none;">
              <th scope="row"><?php _e('Overwrite Mode', 'heritagepress'); ?></th>
              <td>
                <fieldset>
                  <legend class="screen-reader-text"><?php _e('Choose overwrite behavior', 'heritagepress'); ?></legend>
                  <label>
                    <input type="radio" name="overwrite" value="1">
                    <?php _e('Overwrite - Replace all existing branch labels', 'heritagepress'); ?>
                  </label><br>
                  <label>
                    <input type="radio" name="overwrite" value="2" checked>
                    <?php _e('Append - Add to existing branch labels', 'heritagepress'); ?>
                  </label><br>
                  <label>
                    <input type="radio" name="overwrite" value="0">
                    <?php _e('Leave - Only label records without existing branch labels', 'heritagepress'); ?>
                  </label>
                </fieldset>
              </td>
            </tr>
            </td>
            </tr>
          </tbody>
        </table>

        <div id="label-progress" style="display: none;">
          <h3><?php _e('Processing...', 'heritagepress'); ?></h3>
          <div id="label-progress-bar">
            <div id="label-progress-fill"></div>
          </div>
          <div id="label-progress-text"></div>
        </div>

        <div id="label-results" style="display: none;">
          <h3><?php _e('Results', 'heritagepress'); ?></h3>
          <div id="label-results-content"></div>
        </div>
        <p class="submit">
          <button type="submit" class="button button-primary" id="apply-labels-btn">
            <?php _e('Apply Branch Labels', 'heritagepress'); ?>
          </button>
          <button type="button" class="button" id="show-branch-people-btn">
            <?php _e('Show People in Branch', 'heritagepress'); ?>
          </button>
          <button type="button" class="button" id="reset-label-form">
            <?php _e('Reset Form', 'heritagepress'); ?>
          </button>
        </p>

        <input type="hidden" name="action" value="hp_apply_branch_labels">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('hp_ajax_nonce'); ?>">
      </form>
    </div>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {

    // Tab switching
    $('.nav-tab').on('click', function(e) {
      e.preventDefault();

      // Update tab appearance
      $('.nav-tab').removeClass('nav-tab-active');
      $(this).addClass('nav-tab-active');

      // Show corresponding content
      $('.tab-content').removeClass('active');
      var target = $(this).attr('href');
      $(target).addClass('active');
    });

    // Add new branch button
    $('#add-new-branch-btn').on('click', function(e) {
      e.preventDefault();
      $('#add-tab').click();
    });

    // Branch search form
    $('#branch-search-form').on('submit', function(e) {
      e.preventDefault();
      searchBranches();
    });

    // Reset search
    $('#reset-search').on('click', function() {
      $('#branch-search-form')[0].reset();
      $('#branch-results').html('<p class="description"><?php _e('Use the search form above to find branches.', 'heritagepress'); ?></p>');
    });

    // Add branch form
    $('#add-branch-form').on('submit', function(e) {
      e.preventDefault();

      // Validate form
      if (!validateAddBranchForm()) {
        return;
      }

      addBranch();
    });

    // Cancel add branch
    $('#cancel-add').on('click', function() {
      $('#search-tab').click();
    });

    // Branch ID validation
    $('#add-branch-id').on('input', function() {
      // Remove invalid characters
      this.value = this.value.replace(/[^a-zA-Z0-9-_]/g, '');
    });

    /**
     * Search branches
     */
    function searchBranches(offset = 0) {
      var formData = $('#branch-search-form').serialize() + '&offset=' + offset;

      $('#branch-results').html('<div class="spinner is-active" style="float: none; margin: 20px auto;"></div>');

      $.post(ajaxurl, formData, function(response) {
        if (response.success) {
          displayBranchResults(response.data, offset);
        } else {
          $('#branch-results').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
        }
      });
    }

    /**
     * Display branch search results
     */
    function displayBranchResults(data, offset) {
      var html = '';

      if (data.total > 0) {
        // Pagination info
        var start = offset + 1;
        var end = Math.min(offset + data.limit, data.total);
        html += '<p class="search-results-info">';
        html += '<?php _e('Showing', 'heritagepress'); ?> ' + start + '-' + end + ' <?php _e('of', 'heritagepress'); ?> ' + data.total + ' <?php _e('branches', 'heritagepress'); ?>';
        html += '</p>';

        // Bulk actions
        html += '<div class="tablenav top">';
        html += '<div class="alignleft actions bulkactions">';
        html += '<select name="action" id="bulk-action-selector-top">';
        html += '<option value="-1"><?php _e('Bulk Actions', 'heritagepress'); ?></option>';
        html += '<option value="delete"><?php _e('Delete', 'heritagepress'); ?></option>';
        html += '</select>';
        html += '<input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'heritagepress'); ?>">';
        html += '</div>';
        html += '</div>';

        // Results table
        html += '<table class="wp-list-table widefat fixed striped branches">';
        html += '<thead>';
        html += '<tr>';
        html += '<td class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all"></td>';
        html += '<th class="manage-column column-action"><?php _e('Action', 'heritagepress'); ?></th>';
        html += '<th class="manage-column column-branch-id sortable"><a href="#" data-order="id"><span><?php _e('Branch ID', 'heritagepress'); ?></span></a></th>';
        html += '<th class="manage-column column-description sortable"><a href="#" data-order="desc"><span><?php _e('Description', 'heritagepress'); ?></span></a></th>';
        html += '<th class="manage-column column-tree"><?php _e('Tree', 'heritagepress'); ?></th>';
        html += '<th class="manage-column column-starting-person"><?php _e('Starting Individual', 'heritagepress'); ?></th>';
        html += '<th class="manage-column column-people"><?php _e('People', 'heritagepress'); ?></th>';
        html += '<th class="manage-column column-families"><?php _e('Families', 'heritagepress'); ?></th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';

        $.each(data.branches, function(index, branch) {
          html += '<tr id="branch-' + branch.branch + '">';
          html += '<th scope="row" class="check-column">';
          html += '<input type="checkbox" name="branch[]" value="' + branch.branch + '&' + branch.gedcom + '">';
          html += '</th>';
          html += '<td class="column-action">';
          html += '<div class="row-actions">';
          html += '<span class="edit"><a href="' + ajaxurl.replace('admin-ajax.php', 'admin.php?page=hp-edit-branch&branch=') + branch.branch + '&tree=' + branch.gedcom + '"><?php _e('Edit', 'heritagepress'); ?></a> | </span>';
          html += '<span class="delete"><a href="#" onclick="deleteBranch(\'' + branch.branch + '\', \'' + branch.gedcom + '\')" class="submitdelete"><?php _e('Delete', 'heritagepress'); ?></a></span>';
          html += '</div>';
          html += '</td>';
          html += '<td class="column-branch-id"><strong>' + branch.branch + '</strong></td>';
          html += '<td class="column-description">' + branch.description + '</td>';
          html += '<td class="column-tree">' + (branch.tree_name || '') + '</td>';
          html += '<td class="column-starting-person">' + (branch.personID || '') + '</td>';
          html += '<td class="column-people">' + (branch.people_count || 0) + '</td>';
          html += '<td class="column-families">' + (branch.families_count || 0) + '</td>';
          html += '</tr>';
        });

        html += '</tbody>';
        html += '</table>';

        // Pagination
        if (data.total > data.limit) {
          html += '<div class="tablenav bottom">';
          html += '<div class="tablenav-pages">';

          var totalPages = Math.ceil(data.total / data.limit);
          var currentPage = Math.floor(offset / data.limit) + 1;

          // Previous page
          if (currentPage > 1) {
            html += '<a class="prev-page button" href="#" data-offset="' + (offset - data.limit) + '">&laquo;</a>';
          }

          // Page info
          html += '<span class="paging-input">';
          html += currentPage + ' <?php _e('of', 'heritagepress'); ?> ' + totalPages;
          html += '</span>';

          // Next page
          if (currentPage < totalPages) {
            html += '<a class="next-page button" href="#" data-offset="' + (offset + data.limit) + '">&raquo;</a>';
          }

          html += '</div>';
          html += '</div>';
        }
      } else {
        html = '<p><?php _e('No branches found.', 'heritagepress'); ?></p>';
      }

      $('#branch-results').html(html);
    }

    /**
     * Validate add branch form
     */
    function validateAddBranchForm() {
      var branchId = $('#add-branch-id').val().trim();
      var description = $('#add-description').val().trim();
      var gedcom = $('#add-gedcom').val();
      var personID = $('#add-person-id').val().trim();

      // Clean branch ID
      branchId = branchId.replace(/[^a-zA-Z0-9-_]/g, '');
      $('#add-branch-id').val(branchId);

      if (!gedcom) {
        alert('<?php _e('Please select a tree.', 'heritagepress'); ?>');
        return false;
      }

      if (!description) {
        alert('<?php _e('Please enter a description.', 'heritagepress'); ?>');
        return false;
      }

      if (!personID) {
        alert('<?php _e('Please enter a starting individual ID.', 'heritagepress'); ?>');
        return false;
      }

      return true;
    }

    /**
     * Add branch
     */
    function addBranch() {
      var formData = $('#add-branch-form').serialize();

      $.post(ajaxurl, formData, function(response) {
        if (response.success) {
          alert('<?php _e('Branch added successfully!', 'heritagepress'); ?>');
          $('#add-branch-form')[0].reset();
          $('#search-tab').click();
        } else {
          alert('<?php _e('Error:', 'heritagepress'); ?> ' + response.data);
        }
      });
    }

    // Event handlers for pagination
    $(document).on('click', '.prev-page, .next-page', function(e) {
      e.preventDefault();
      var offset = parseInt($(this).data('offset'));
      searchBranches(offset);
    });

    // Event handlers for sorting
    $(document).on('click', '.column-branch-id a, .column-description a', function(e) {
      e.preventDefault();
      var order = $(this).data('order');
      $('#search-order').val(order);
      searchBranches();
    });

    // Select all checkbox
    $(document).on('click', '#cb-select-all', function() {
      $('input[name="branch[]"]').prop('checked', this.checked);
    });

    // Bulk actions
    $(document).on('click', '#doaction', function(e) {
      e.preventDefault();
      var action = $('#bulk-action-selector-top').val();
      var selected = $('input[name="branch[]"]:checked').map(function() {
        return this.value;
      }).get();

      if (action === 'delete' && selected.length > 0) {
        if (confirm('<?php _e('Are you sure you want to delete the selected branches?', 'heritagepress'); ?>')) {
          deleteSelectedBranches(selected);
        }
      }
    });

    /**
     * Delete selected branches
     */
    function deleteSelectedBranches(branches) {
      $.post(ajaxurl, {
        action: 'hp_delete_selected_branches',
        nonce: '<?php echo wp_create_nonce('hp_ajax_nonce'); ?>',
        branches: branches
      }, function(response) {
        if (response.success) {
          alert(response.data);
          searchBranches(); // Refresh results
        } else {
          alert('<?php _e('Error:', 'heritagepress'); ?> ' + response.data);
        }
      });
    } // Initial search if there are parameters
    <?php if (isset($_GET['search']) && $_GET['search']): ?>
      searchBranches();
    <?php endif; ?>

    // === LABEL BRANCHES TAB FUNCTIONALITY ===

    // Tree selection change - load branches
    $('#label-tree').on('change', function() {
      var tree = $(this).val();
      var branchSelect = $('#label-branch');

      if (tree) {
        branchSelect.html('<option value=""><?php _e('Loading...', 'heritagepress'); ?></option>');

        $.post(ajaxurl, {
          action: 'hp_get_tree_branches',
          nonce: '<?php echo wp_create_nonce('hp_ajax_nonce'); ?>',
          tree: tree
        }, function(response) {
          if (response.success) {
            var options = '<option value=""><?php _e('Select Branch...', 'heritagepress'); ?></option>';
            $.each(response.data, function(index, branch) {
              options += '<option value="' + branch.branch + '">' + branch.branch + ' - ' + branch.description + '</option>';
            });
            branchSelect.html(options);
          } else {
            branchSelect.html('<option value=""><?php _e('Error loading branches', 'heritagepress'); ?></option>');
          }
        });
      } else {
        branchSelect.html('<option value=""><?php _e('Select Tree First...', 'heritagepress'); ?></option>');
      }
    }); // Branch action change - toggle options
    $('input[name="branchaction"]').on('change', function() {
      var action = $(this).val();
      var overwriteRow = $('#overwrite-options');

      if (action === 'add') {
        overwriteRow.show();
      } else {
        overwriteRow.hide();
      }

      // Update button text
      var btn = $('#apply-labels-btn');
      switch (action) {
        case 'add':
          btn.text('<?php _e('Apply Branch Labels', 'heritagepress'); ?>');
          break;
        case 'clear':
          btn.text('<?php _e('Clear Branch Labels', 'heritagepress'); ?>');
          break;
        case 'delete':
          btn.text('<?php _e('Delete Branch Records', 'heritagepress'); ?>');
          break;
      }
    });

    // Apply set change - toggle individual-based options
    $('input[name="set"]').on('change', function() {
      var set = $(this).val();
      var startingRow = $('#starting-individual-row');
      var generationsRow = $('#generations-row');

      if (set === 'partial') {
        startingRow.show();
        generationsRow.show();
      } else {
        startingRow.hide();
        generationsRow.hide();
      }
    });

    // Label branches form submission
    $('#label-branches-form').on('submit', function(e) {
      e.preventDefault();

      var form = $(this);
      var formData = form.serialize();
      var action = $('input[name="branchaction"]:checked').val(); // Validation
      if (!$('#label-tree').val()) {
        alert('<?php _e('Please select a tree.', 'heritagepress'); ?>');
        return;
      }

      if (!$('#label-branch').val()) {
        alert('<?php _e('Please select a branch.', 'heritagepress'); ?>');
        return;
      }

      // Additional validation for partial application
      var applyTo = $('input[name="set"]:checked').val();
      if (applyTo === 'partial' && !$('#personID').val()) {
        alert('<?php _e('Please enter a starting individual ID for individual-based processing.', 'heritagepress'); ?>');
        return;
      }

      // Confirm destructive actions
      if (action === 'delete') {
        if (!confirm('<?php _e('WARNING: This will permanently delete all people and families in this branch. This action cannot be undone. Are you sure?', 'heritagepress'); ?>')) {
          return;
        }
      } else if (action === 'clear') {
        if (!confirm('<?php _e('This will remove branch labels from all records in this branch. Continue?', 'heritagepress'); ?>')) {
          return;
        }
      }

      // Show progress
      $('#label-progress').show();
      $('#label-results').hide();
      $('#apply-labels-btn').prop('disabled', true).text('<?php _e('Processing...', 'heritagepress'); ?>');

      // Start progress simulation
      simulateProgress();

      // Submit request
      $.post(ajaxurl, formData, function(response) {
        // Hide progress
        $('#label-progress').hide();
        $('#apply-labels-btn').prop('disabled', false);

        // Reset button text
        var action = $('input[name="branchaction"]:checked').val();
        switch (action) {
          case 'add':
            $('#apply-labels-btn').text('<?php _e('Apply Branch Labels', 'heritagepress'); ?>');
            break;
          case 'clear':
            $('#apply-labels-btn').text('<?php _e('Clear Branch Labels', 'heritagepress'); ?>');
            break;
          case 'delete':
            $('#apply-labels-btn').text('<?php _e('Delete Branch Records', 'heritagepress'); ?>');
            break;
        }

        // Show results
        if (response.success) {
          $('#label-results-content').html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
        } else {
          $('#label-results-content').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
        }
        $('#label-results').show();

        // Scroll to results
        $('html, body').animate({
          scrollTop: $('#label-results').offset().top - 100
        }, 500);
      }).fail(function() {
        $('#label-progress').hide();
        $('#apply-labels-btn').prop('disabled', false).text('<?php _e('Apply Branch Labels', 'heritagepress'); ?>');
        $('#label-results-content').html('<div class="notice notice-error"><p><?php _e('An error occurred while processing the request.', 'heritagepress'); ?></p></div>');
        $('#label-results').show();
      });
    }); // Reset label form
    $('#reset-label-form').on('click', function() {
      $('#label-branches-form')[0].reset();
      $('#label-branch').html('<option value=""><?php _e('Select Tree First...', 'heritagepress'); ?></option>');
      $('#overwrite-options').hide();
      $('#starting-individual-row').hide();
      $('#generations-row').hide();
      $('#label-progress').hide();
      $('#label-results').hide();
      $('#apply-labels-btn').text('<?php _e('Apply Branch Labels', 'heritagepress'); ?>');
    });

    // Show people in branch
    $('#show-branch-people-btn').on('click', function() {
      var tree = $('#label-tree').val();
      var branch = $('#label-branch').val();

      if (!tree || !branch) {
        alert('<?php _e('Please select a tree and branch first.', 'heritagepress'); ?>');
        return;
      }

      // Create a popup window or modal to show branch people
      var url = ajaxurl.replace('admin-ajax.php', 'admin.php?page=hp-branches&action=show_people&tree=' + encodeURIComponent(tree) + '&branch=' + encodeURIComponent(branch));
      window.open(url, 'BranchPeople', 'width=800,height=600,scrollbars=yes,resizable=yes');
    });

    // Progress simulation
    function simulateProgress() {
      var progress = 0;
      var progressBar = $('#label-progress-fill');
      var progressText = $('#label-progress-text');

      var interval = setInterval(function() {
        progress += Math.random() * 10;
        if (progress > 90) progress = 90; // Don't go to 100% until actually done

        progressBar.css('width', progress + '%');
        progressText.text(Math.round(progress) + '% <?php _e('complete', 'heritagepress'); ?>');
      }, 200);

      // Store interval ID to clear it later
      $('#label-branches-form').data('progress-interval', interval);
    }

  });

  /**
   * Delete single branch
   */
  function deleteBranch(branchId, tree) {
    if (confirm('<?php _e('Are you sure you want to delete this branch?', 'heritagepress'); ?>')) {
      jQuery.post(ajaxurl, {
        action: 'hp_delete_branch',
        nonce: '<?php echo wp_create_nonce('hp_ajax_nonce'); ?>',
        branch_id: branchId,
        tree: tree
      }, function(response) {
        if (response.success) {
          alert(response.data);
          jQuery('#branch-search-form').trigger('submit'); // Refresh results
        } else {
          alert('<?php _e('Error:', 'heritagepress'); ?> ' + response.data);
        }
      });
    }
  }
</script>

<style type="text/css">
  .heritagepress-branches .tab-content {
    display: none;
    padding: 20px 0;
  }

  .heritagepress-branches .tab-content.active {
    display: block;
  }

  .branch-results {
    margin-top: 20px;
  }

  .search-results-info {
    margin: 10px 0;
    font-style: italic;
  }

  .branches table {
    margin-top: 10px;
  }

  .column-action {
    width: 80px;
  }

  .column-branch-id {
    width: 120px;
  }

  .column-people,
  .column-families {
    width: 80px;
    text-align: center;
  }

  .search-results {
    margin-top: 10px;
    padding: 10px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    display: none;
  }

  .spinner.is-active {
    visibility: visible;
  }

  /* Label Branches Tab Styles */
  .label-branches-container .card {
    max-width: none;
  }

  #label-progress {
    margin: 20px 0;
    padding: 20px;
    border: 1px solid #ddd;
    background: #f9f9f9;
    border-radius: 4px;
  }

  #label-progress-bar {
    width: 100%;
    height: 20px;
    background: #e1e1e1;
    border-radius: 10px;
    overflow: hidden;
    margin: 10px 0;
  }

  #label-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2271b1, #135e96);
    width: 0%;
    transition: width 0.3s ease;
    border-radius: 10px;
  }

  #label-progress-text {
    text-align: center;
    font-weight: bold;
    color: #135e96;
  }

  #label-results {
    margin: 20px 0;
  }

  .label-branches-container fieldset {
    border: none;
    padding: 0;
    margin: 0;
  }

  .label-branches-container fieldset label {
    display: block;
    margin: 5px 0;
  }

  .label-branches-container .description {
    margin-top: 5px;
    font-style: italic;
  }

  #overwrite-options,
  #include-spouses-row {
    background: #f6f7f7;
  }

  #overwrite-options td,
  #include-spouses-row td {
    padding: 15px;
    border-radius: 4px;
  }
</style>
