<?php

/**
 * Branch Edit Admin Interface
 *
 * Replicates HeritagePress admin_editbranch.php functionality for WordPress
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

// Get branch data if editing
$branch_data = null;
$tree_name = '';

if (isset($_GET['branch']) && isset($_GET['tree'])) {
  $branch_id = sanitize_text_field($_GET['branch']);
  $tree_id = sanitize_text_field($_GET['tree']);

  global $wpdb;
  $branches_table = $wpdb->prefix . 'hp_branches';
  $trees_table = $wpdb->prefix . 'hp_trees';

  // Get branch data
  $branch_data = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $branches_table WHERE gedcom = %s AND branch = %s",
    $tree_id,
    $branch_id
  ));

  // Get tree name
  $tree = $wpdb->get_row($wpdb->prepare(
    "SELECT tree_name FROM $trees_table WHERE gedcom = %s",
    $tree_id
  ));

  $tree_name = $tree ? $tree->tree_name : $tree_id;
}

if (!$branch_data) {
  wp_die(__('Branch not found', 'heritagepress'));
}

// Get all trees for dropdowns if needed
$trees = $wpdb->get_results("SELECT gedcom, tree_name FROM $trees_table ORDER BY tree_name");
?>

<div class="wrap heritagepress-edit-branch">
  <h1 class="wp-heading-inline"><?php _e('Edit Branch', 'heritagepress'); ?></h1>
  <a href="<?php echo admin_url('admin.php?page=hp-branch-management'); ?>" class="page-title-action"><?php _e('Back to Branches', 'heritagepress'); ?></a>
  <hr class="wp-header-end">

  <!-- Tab Navigation -->
  <nav class="nav-tab-wrapper wp-clearfix">
    <a href="#edit-branch" class="nav-tab nav-tab-active" id="edit-tab"><?php _e('Edit', 'heritagepress'); ?></a>
    <a href="#branch-actions" class="nav-tab" id="actions-tab"><?php _e('Actions', 'heritagepress'); ?></a>
  </nav>

  <!-- Edit Branch Tab -->
  <div id="edit-branch" class="tab-content active">
    <div class="edit-branch-container">

      <form id="edit-branch-form" class="branch-form">
        <?php wp_nonce_field('hp_branch_nonce', 'nonce'); ?>
        <input type="hidden" name="action" value="update_branch">
        <input type="hidden" name="original_branch" value="<?php echo esc_attr($branch_data->branch); ?>">
        <input type="hidden" name="original_tree" value="<?php echo esc_attr($branch_data->gedcom); ?>">

        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label><?php _e('Tree', 'heritagepress'); ?></label>
              </th>
              <td>
                <strong><?php echo esc_html($tree_name); ?></strong>
                <input type="hidden" name="gedcom" value="<?php echo esc_attr($branch_data->gedcom); ?>">
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label><?php _e('Branch ID', 'heritagepress'); ?></label>
              </th>
              <td>
                <strong><?php echo esc_html($branch_data->branch); ?></strong>
                <input type="hidden" name="branch" value="<?php echo esc_attr($branch_data->branch); ?>">
                <p class="description"><?php _e('Branch ID cannot be changed after creation.', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="edit-description"><?php _e('Description', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" id="edit-description" name="description" class="regular-text" value="<?php echo esc_attr($branch_data->description); ?>" required>
                <p class="description"><?php _e('A descriptive name for this branch.', 'heritagepress'); ?></p>
              </td>
            </tr>

            <tr>
              <th scope="row">
                <label for="edit-person-id"><?php _e('Starting Individual', 'heritagepress'); ?></label>
              </th>
              <td>
                <input type="text" id="edit-person-id" name="personID" class="regular-text" value="<?php echo esc_attr($branch_data->personID); ?>" required>
                <button type="button" id="find-person-edit" class="button"><?php _e('Find', 'heritagepress'); ?></button>
                <p class="description"><?php _e('The person ID from whom this branch starts.', 'heritagepress'); ?></p>
                <div id="person-search-results-edit" class="search-results"></div>
              </td>
            </tr>

            <tr>
              <th scope="row"><?php _e('Number of Generations', 'heritagepress'); ?></th>
              <td>
                <table class="widefat" style="width: auto;">
                  <tr>
                    <td><?php _e('Ancestors:', 'heritagepress'); ?></td>
                    <td><input type="number" id="edit-agens" name="agens" value="<?php echo intval($branch_data->agens); ?>" min="0" max="999" class="small-text"></td>
                    <td><?php _e('Descendants of Ancestors:', 'heritagepress'); ?></td>
                    <td>
                      <select name="dagens" id="edit-dagens">
                        <?php
                        $dagens = intval($branch_data->dagens ?: 1);
                        for ($i = 0; $i <= 5; $i++) {
                          echo '<option value="' . $i . '"' . ($i == $dagens ? ' selected' : '') . '>' . $i . '</option>';
                        }
                        ?>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td><?php _e('Descendants:', 'heritagepress'); ?></td>
                    <td><input type="number" id="edit-dgens" name="dgens" value="<?php echo intval($branch_data->dgens); ?>" min="0" max="999" class="small-text"></td>
                    <td colspan="2">
                      <label>
                        <input type="checkbox" id="edit-inclspouses" name="inclspouses" value="1" <?php checked($branch_data->inclspouses, 1); ?>>
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
          <button type="button" id="cancel-edit" class="button"><?php _e('Cancel', 'heritagepress'); ?></button>
        </p>
      </form>
    </div>
  </div>

  <!-- Branch Actions Tab -->
  <div id="branch-actions" class="tab-content">
    <div class="branch-actions-container">
      <h2><?php _e('Branch Actions', 'heritagepress'); ?></h2>

      <div class="branch-actions-grid">
        <div class="action-card">
          <h3><?php _e('Add/Update Labels', 'heritagepress'); ?></h3>
          <p><?php _e('Apply branch labels to people and families in this branch.', 'heritagepress'); ?></p>
          <button type="button" id="update-labels" class="button button-secondary"><?php _e('Update Labels', 'heritagepress'); ?></button>
        </div>

        <div class="action-card">
          <h3><?php _e('Show Branch People', 'heritagepress'); ?></h3>
          <p><?php _e('View all people currently included in this branch.', 'heritagepress'); ?></p>
          <button type="button" id="show-people" class="button button-secondary"><?php _e('Show People', 'heritagepress'); ?></button>
        </div>

        <div class="action-card danger">
          <h3><?php _e('Delete Branch', 'heritagepress'); ?></h3>
          <p><?php _e('Permanently remove this branch. This action cannot be undone.', 'heritagepress'); ?></p>
          <button type="button" id="delete-branch" class="button button-secondary button-danger"><?php _e('Delete Branch', 'heritagepress'); ?></button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- People Modal -->
<div id="branch-people-modal" class="branch-modal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <h2><?php _e('People in Branch', 'heritagepress'); ?></h2>
      <span class="close-modal">&times;</span>
    </div>
    <div class="modal-body">
      <div id="people-list-container">
        <p><?php _e('Loading...', 'heritagepress'); ?></p>
      </div>
    </div>
  </div>
</div>

<!-- Labels Modal -->
<div id="labels-modal" class="branch-modal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <h2><?php _e('Update Branch Labels', 'heritagepress'); ?></h2>
      <span class="close-modal">&times;</span>
    </div>
    <div class="modal-body">
      <div id="labels-form-container">
        <form id="labels-form">
          <table class="form-table">
            <tr>
              <th scope="row"><?php _e('Action', 'heritagepress'); ?></th>
              <td>
                <label><input type="radio" name="label_action" value="add" checked> <?php _e('Add Labels', 'heritagepress'); ?></label><br>
                <label><input type="radio" name="label_action" value="remove"> <?php _e('Remove Labels', 'heritagepress'); ?></label>
              </td>
            </tr>
            <tr>
              <th scope="row"><?php _e('Apply to', 'heritagepress'); ?></th>
              <td>
                <label><input type="radio" name="apply_to" value="all" checked> <?php _e('All Records', 'heritagepress'); ?></label><br>
                <label><input type="radio" name="apply_to" value="partial"> <?php _e('Partial Records', 'heritagepress'); ?></label>
              </td>
            </tr>
            <tr id="overwrite-row" style="display: none;">
              <th scope="row"><?php _e('Overwrite', 'heritagepress'); ?></th>
              <td>
                <label><input type="checkbox" name="overwrite" value="1"> <?php _e('Overwrite existing branch labels', 'heritagepress'); ?></label>
              </td>
            </tr>
          </table>

          <div id="labels-results"></div>

          <p class="submit">
            <button type="submit" class="button button-primary"><?php _e('Apply Labels', 'heritagepress'); ?></button>
            <button type="button" class="button close-modal"><?php _e('Cancel', 'heritagepress'); ?></button>
          </p>
        </form>
      </div>
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

    // Edit branch form
    $('#edit-branch-form').on('submit', function(e) {
      e.preventDefault();

      // Validate form
      if (!validateEditBranchForm()) {
        return;
      }

      updateBranch();
    });

    // Cancel edit
    $('#cancel-edit').on('click', function() {
      window.location.href = '<?php echo admin_url('admin.php?page=hp-branch-management'); ?>';
    });

    // Show people action
    $('#show-people').on('click', function() {
      showBranchPeople();
    });

    // Update labels action
    $('#update-labels').on('click', function() {
      showLabelsModal();
    });

    // Delete branch action
    $('#delete-branch').on('click', function() {
      if (confirm('<?php _e('Are you sure you want to delete this branch? This action cannot be undone.', 'heritagepress'); ?>')) {
        deleteBranch();
      }
    });

    // Modal handlers
    $('.close-modal').on('click', function() {
      $('.branch-modal').hide();
    });

    // Labels form submission
    $('#labels-form').on('submit', function(e) {
      e.preventDefault();
      applyBranchLabels();
    });

    // Show/hide overwrite option
    $('input[name="label_action"]').on('change', function() {
      if ($(this).val() === 'add') {
        $('#overwrite-row').show();
      } else {
        $('#overwrite-row').hide();
      }
    });

    /**
     * Validate edit branch form
     */
    function validateEditBranchForm() {
      var description = $('#edit-description').val().trim();
      var personID = $('#edit-person-id').val().trim();

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
     * Update branch
     */
    function updateBranch() {
      var formData = $('#edit-branch-form').serialize();

      $.post(ajaxurl, formData, function(response) {
        if (response.success) {
          alert('<?php _e('Branch updated successfully!', 'heritagepress'); ?>');
          // Redirect based on button clicked
          window.location.href = '<?php echo admin_url('admin.php?page=hp-branch-management'); ?>';
        } else {
          alert('<?php _e('Error:', 'heritagepress'); ?> ' + response.data);
        }
      });
    }

    /**
     * Show branch people
     */
    function showBranchPeople() {
      $('#branch-people-modal').show();
      $('#people-list-container').html('<p><?php _e('Loading...', 'heritagepress'); ?></p>');

      $.post(ajaxurl, {
        action: 'hp_get_branch_people',
        nonce: '<?php echo wp_create_nonce('hp_ajax_nonce'); ?>',
        branch: '<?php echo esc_js($branch_data->branch); ?>',
        tree: '<?php echo esc_js($branch_data->gedcom); ?>'
      }, function(response) {
        if (response.success) {
          displayBranchPeople(response.data);
        } else {
          $('#people-list-container').html('<p class="error">' + response.data + '</p>');
        }
      });
    }

    /**
     * Display branch people
     */
    function displayBranchPeople(people) {
      var html = '<table class="wp-list-table widefat fixed striped">';
      html += '<thead><tr>';
      html += '<th><?php _e('ID', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Name', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Birth', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Death', 'heritagepress'); ?></th>';
      html += '</tr></thead>';
      html += '<tbody>';

      if (people.length > 0) {
        $.each(people, function(index, person) {
          html += '<tr>';
          html += '<td>' + person.personID + '</td>';
          html += '<td>' + (person.lastname + ', ' + person.firstname).trim() + '</td>';
          html += '<td>' + (person.birthdate || '') + '</td>';
          html += '<td>' + (person.deathdate || '') + '</td>';
          html += '</tr>';
        });
      } else {
        html += '<tr><td colspan="4"><?php _e('No people found in this branch.', 'heritagepress'); ?></td></tr>';
      }

      html += '</tbody></table>';
      $('#people-list-container').html(html);
    }

    /**
     * Show labels modal
     */
    function showLabelsModal() {
      $('#labels-modal').show();
    }

    /**
     * Apply branch labels
     */
    function applyBranchLabels() {
      var formData = $('#labels-form').serialize();
      formData += '&action=hp_apply_branch_labels';
      formData += '&nonce=<?php echo wp_create_nonce('hp_ajax_nonce'); ?>';
      formData += '&branch=<?php echo esc_js($branch_data->branch); ?>';
      formData += '&tree=<?php echo esc_js($branch_data->gedcom); ?>';

      $('#labels-results').html('<div class="spinner is-active"></div>');

      $.post(ajaxurl, formData, function(response) {
        if (response.success) {
          $('#labels-results').html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
        } else {
          $('#labels-results').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
        }
      });
    }

    /**
     * Delete branch
     */
    function deleteBranch() {
      $.post(ajaxurl, {
        action: 'hp_delete_branch',
        nonce: '<?php echo wp_create_nonce('hp_ajax_nonce'); ?>',
        branch_id: '<?php echo esc_js($branch_data->branch); ?>',
        tree: '<?php echo esc_js($branch_data->gedcom); ?>'
      }, function(response) {
        if (response.success) {
          alert(response.data);
          window.location.href = '<?php echo admin_url('admin.php?page=hp-branch-management'); ?>';
        } else {
          alert('<?php _e('Error:', 'heritagepress'); ?> ' + response.data);
        }
      });
    }
  });
</script>

<style type="text/css">
  .heritagepress-edit-branch .tab-content {
    display: none;
    padding: 20px 0;
  }

  .heritagepress-edit-branch .tab-content.active {
    display: block;
  }

  .branch-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
  }

  .action-card {
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
  }

  .action-card.danger {
    border-color: #dc3232;
  }

  .action-card h3 {
    margin-top: 0;
  }

  .button-danger {
    color: #721c24 !important;
    border-color: #dc3232 !important;
  }

  .button-danger:hover {
    background: #dc3232 !important;
    color: #fff !important;
  }

  .branch-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
  }

  .modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #888;
    width: 80%;
    max-width: 800px;
    border-radius: 4px;
  }

  .modal-header {
    padding: 20px;
    background: #f1f1f1;
    border-bottom: 1px solid #ddd;
    position: relative;
  }

  .modal-header h2 {
    margin: 0;
  }

  .close-modal {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
  }

  .close-modal:hover {
    color: #000;
  }

  .modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
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
</style>
