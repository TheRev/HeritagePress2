<?php

/**
 * DNA Groups Management Admin Template
 *
 * Complete replication of TNG admin_dna_groups.php interface
 * Provides tabbed navigation with browse, add new, and help sections
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, ARRAY_A);

// Check if editing a DNA group
$dna_group_id = isset($_GET['dna_group']) ? sanitize_text_field($_GET['dna_group']) : '';
$tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
$is_editing = !empty($dna_group_id) && !empty($tree);

// If editing, show edit tab
if ($is_editing) {
  $current_tab = 'edit';
}

?>

<div class="wrap heritagepress-dna-groups">
  <h1>
    <span class="dashicons dashicons-dna" style="margin-right: 8px;"></span>
    <?php _e('DNA Groups', 'heritagepress'); ?>
  </h1>

  <!-- Tab Navigation (TNG Style) -->
  <nav class="nav-tab-wrapper wp-clearfix">
    <a href="?page=heritagepress-dna-groups&tab=browse"
      class="nav-tab <?php echo $current_tab === 'browse' ? 'nav-tab-active' : ''; ?>">
      <span class="dashicons dashicons-search"></span>
      <?php _e('Browse Groups', 'heritagepress'); ?>
    </a>

    <a href="?page=heritagepress-dna-groups&tab=add"
      class="nav-tab <?php echo $current_tab === 'add' ? 'nav-tab-active' : ''; ?>">
      <span class="dashicons dashicons-plus-alt"></span>
      <?php _e('Add New Group', 'heritagepress'); ?>
    </a>

    <?php if ($is_editing): ?>
      <a href="#" class="nav-tab nav-tab-active">
        <span class="dashicons dashicons-edit"></span>
        <?php _e('Edit Group', 'heritagepress'); ?>
      </a>
    <?php endif; ?>

    <a href="?page=heritagepress-dna-groups&tab=tests"
      class="nav-tab <?php echo $current_tab === 'tests' ? 'nav-tab-active' : ''; ?>">
      <span class="dashicons dashicons-list-view"></span>
      <?php _e('DNA Tests', 'heritagepress'); ?>
    </a>

    <a href="#" onclick="return openDNAHelp();" class="nav-tab">
      <span class="dashicons dashicons-editor-help"></span>
      <?php _e('Help', 'heritagepress'); ?>
    </a>
  </nav>

  <!-- Tab Content -->
  <div class="tab-content">

    <?php if ($current_tab === 'browse' && !$is_editing): ?>
      <!-- Browse DNA Groups Tab -->
      <div class="dna-groups-browse">
        <div class="admin-block">

          <!-- Search Form -->
          <form id="dna-groups-search-form" class="search-form">
            <?php wp_nonce_field('hp_dna_nonce', 'nonce'); ?>
            <table class="form-table">
              <tr>
                <th scope="row">
                  <label for="search-tree"><?php _e('Tree:', 'heritagepress'); ?></label>
                </th>
                <td>
                  <select id="search-tree" name="tree">
                    <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
                    <?php foreach ($trees_result as $tree_row): ?>
                      <option value="<?php echo esc_attr($tree_row['gedcom']); ?>">
                        <?php echo esc_html($tree_row['treename']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td>
                  <button type="submit" class="button">
                    <?php _e('Search', 'heritagepress'); ?>
                  </button>
                  <button type="button" id="reset-search" class="button">
                    <?php _e('Reset', 'heritagepress'); ?>
                  </button>
                </td>
              </tr>
            </table>
          </form>

          <!-- Results Navigation -->
          <div id="results-navigation" class="tablenav top" style="display: none;">
            <div class="alignleft actions bulkactions">
              <select id="bulk-action-selector-top" name="action">
                <option value="-1"><?php _e('Bulk Actions', 'heritagepress'); ?></option>
                <option value="delete"><?php _e('Delete', 'heritagepress'); ?></option>
              </select>
              <button type="button" id="bulk-action-submit" class="button action">
                <?php _e('Apply', 'heritagepress'); ?>
              </button>
            </div>
            <div class="tablenav-pages">
              <span class="displaying-num" id="results-count"></span>
              <span class="pagination-links" id="pagination-links"></span>
            </div>
          </div>

          <!-- DNA Groups Table -->
          <table id="dna-groups-table" class="wp-list-table widefat fixed striped">
            <thead>
              <tr>
                <th scope="col" class="manage-column column-cb check-column">
                  <input type="checkbox" id="cb-select-all-1">
                </th>
                <th scope="col" class="manage-column column-actions">
                  <?php _e('Actions', 'heritagepress'); ?>
                </th>
                <th scope="col" class="manage-column column-group-id">
                  <?php _e('Group ID', 'heritagepress'); ?>
                </th>
                <th scope="col" class="manage-column column-description">
                  <?php _e('Description', 'heritagepress'); ?>
                </th>
                <th scope="col" class="manage-column column-tree">
                  <?php _e('Tree', 'heritagepress'); ?>
                </th>
                <th scope="col" class="manage-column column-test-type">
                  <?php _e('Test Type', 'heritagepress'); ?>
                </th>
                <th scope="col" class="manage-column column-test-count">
                  <?php _e('DNA Tests', 'heritagepress'); ?>
                </th>
              </tr>
            </thead>
            <tbody id="dna-groups-tbody">
              <tr class="no-items">
                <td class="colspanchange" colspan="7">
                  <?php _e('No DNA groups found.', 'heritagepress'); ?>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Bottom Navigation -->
          <div class="tablenav bottom" id="bottom-navigation" style="display: none;">
            <div class="tablenav-pages">
              <span class="displaying-num" id="bottom-results-count"></span>
              <span class="pagination-links" id="bottom-pagination-links"></span>
            </div>
          </div>

        </div>
      </div>

    <?php elseif ($current_tab === 'add'): ?>
      <!-- Add New DNA Group Tab -->
      <div class="dna-groups-add">
        <div class="admin-block">

          <h2><?php _e('Add New DNA Group', 'heritagepress'); ?></h2>

          <form id="add-dna-group-form" class="dna-group-form">
            <?php wp_nonce_field('hp_dna_nonce', 'nonce'); ?>

            <table class="form-table">
              <tbody>
                <tr>
                  <th scope="row">
                    <label for="gedcom"><?php _e('Tree', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <select id="gedcom" name="gedcom" required>
                      <option value=""><?php _e('Select Tree', 'heritagepress'); ?></option>
                      <?php foreach ($trees_result as $tree_row): ?>
                        <option value="<?php echo esc_attr($tree_row['gedcom']); ?>">
                          <?php echo esc_html($tree_row['treename']); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                </tr>

                <tr>
                  <th scope="row">
                    <label for="dna_group"><?php _e('Group ID', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <input type="text" id="dna_group" name="dna_group" maxlength="20" class="regular-text" required>
                    <p class="description">
                      <?php _e('Enter a unique identifier for this DNA group (letters, numbers, underscores, and hyphens only).', 'heritagepress'); ?>
                    </p>
                  </td>
                </tr>

                <tr>
                  <th scope="row">
                    <label for="test_type"><?php _e('Test Type', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <select id="test_type" name="test_type" required>
                      <option value=""><?php _e('Select Test Type', 'heritagepress'); ?></option>
                      <option value="atDNA"><?php _e('Autosomal DNA (atDNA)', 'heritagepress'); ?></option>
                      <option value="Y-DNA"><?php _e('Y-Chromosome DNA (Y-DNA)', 'heritagepress'); ?></option>
                      <option value="mtDNA"><?php _e('Mitochondrial DNA (mtDNA)', 'heritagepress'); ?></option>
                      <option value="X-DNA"><?php _e('X-Chromosome DNA (X-DNA)', 'heritagepress'); ?></option>
                    </select>
                  </td>
                </tr>

                <tr>
                  <th scope="row">
                    <label for="description"><?php _e('Description', 'heritagepress'); ?></label>
                  </th>
                  <td>
                    <input type="text" id="description" name="description" class="large-text" required>
                    <p class="description">
                      <?php _e('Enter a descriptive name for this DNA group.', 'heritagepress'); ?>
                    </p>
                  </td>
                </tr>
              </tbody>
            </table>

            <p class="submit">
              <button type="submit" class="button button-primary">
                <?php _e('Add DNA Group', 'heritagepress'); ?>
              </button>
              <button type="button" class="button" onclick="window.location.href='?page=heritagepress-dna-groups'">
                <?php _e('Cancel', 'heritagepress'); ?>
              </button>
            </p>
          </form>

        </div>
      </div>

    <?php elseif ($is_editing): ?>
      <!-- Edit DNA Group Tab -->
      <div class="dna-groups-edit">
        <?php include 'dna-groups-edit.php'; ?>
      </div>

    <?php elseif ($current_tab === 'tests'): ?>
      <!-- DNA Tests Tab -->
      <div class="dna-tests">
        <div class="admin-block">
          <h2><?php _e('DNA Tests', 'heritagepress'); ?></h2>
          <p><?php _e('DNA tests functionality will be implemented in a future update.', 'heritagepress'); ?></p>
          <p>
            <a href="?page=heritagepress-dna-groups" class="button">
              <?php _e('Back to DNA Groups', 'heritagepress'); ?>
            </a>
          </p>
        </div>
      </div>

    <?php endif; ?>

  </div>
</div>

<!-- DNA Groups Management CSS -->
<style>
  .heritagepress-dna-groups {
    margin-top: 20px;
  }

  .heritagepress-dna-groups .nav-tab-wrapper {
    margin-bottom: 20px;
  }

  .heritagepress-dna-groups .nav-tab .dashicons {
    margin-right: 5px;
    vertical-align: text-top;
  }

  .admin-block {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
  }

  .search-form .form-table th {
    width: 150px;
    font-weight: 600;
  }

  .search-form .form-table td {
    padding-left: 10px;
  }

  .dna-group-form .form-table th {
    width: 200px;
    font-weight: 600;
  }

  .dna-group-form .form-table td {
    padding-left: 20px;
  }

  .dna-group-form .description {
    margin-top: 5px;
    font-style: italic;
    color: #666;
  }

  #dna-groups-table .column-cb {
    width: 50px;
  }

  #dna-groups-table .column-actions {
    width: 120px;
  }

  #dna-groups-table .column-group-id {
    width: 120px;
  }

  #dna-groups-table .column-test-type {
    width: 150px;
  }

  #dna-groups-table .column-test-count {
    width: 100px;
    text-align: center;
  }

  .action-buttons {
    display: flex;
    gap: 5px;
  }

  .action-button {
    padding: 3px 8px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    text-decoration: none;
    font-size: 11px;
    line-height: 1.4;
  }

  .action-button.edit {
    background: #2271b1;
    color: white;
  }

  .action-button.delete {
    background: #d63638;
    color: white;
  }

  .action-button:hover {
    opacity: 0.8;
  }

  .no-items td {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-style: italic;
  }

  /* Loading state */
  .loading {
    opacity: 0.6;
    pointer-events: none;
  }

  .loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #2271b1;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }

    100% {
      transform: rotate(360deg);
    }
  }
</style>

<!-- DNA Groups Management JavaScript -->
<script>
  jQuery(document).ready(function($) {

    // Initialize
    loadDNAGroups();

    // Search form submission
    $('#dna-groups-search-form').on('submit', function(e) {
      e.preventDefault();
      loadDNAGroups();
    });

    // Reset search
    $('#reset-search').on('click', function() {
      $('#search-tree').val('');
      loadDNAGroups();
    });

    // Add DNA group form submission
    $('#add-dna-group-form').on('submit', function(e) {
      e.preventDefault();
      addDNAGroup();
    });

    // Bulk actions
    $('#bulk-action-submit').on('click', function() {
      var action = $('#bulk-action-selector-top').val();
      if (action === '-1') {
        alert('<?php echo esc_js(__('Please select an action.', 'heritagepress')); ?>');
        return;
      }

      var selectedGroups = [];
      $('#dna-groups-table input[type="checkbox"]:checked').not('#cb-select-all-1').each(function() {
        selectedGroups.push($(this).val());
      });

      if (selectedGroups.length === 0) {
        alert('<?php echo esc_js(__('Please select at least one DNA group.', 'heritagepress')); ?>');
        return;
      }

      if (action === 'delete') {
        if (!confirm('<?php echo esc_js(__('Are you sure you want to delete the selected DNA groups?', 'heritagepress')); ?>')) {
          return;
        }
        deleteDNAGroups(selectedGroups);
      }
    });

    // Select all checkbox
    $('#cb-select-all-1').on('change', function() {
      $('#dna-groups-table input[type="checkbox"]').not(this).prop('checked', this.checked);
    });

    // Validate DNA group ID format
    $('#dna_group').on('input', function() {
      var value = $(this).val();
      var sanitized = value.replace(/[^a-zA-Z0-9_-]/g, '');
      if (value !== sanitized) {
        $(this).val(sanitized);
      }
    });

    /**
     * Load DNA groups
     */
    function loadDNAGroups(offset = 0) {
      var $table = $('#dna-groups-table');
      var $tbody = $('#dna-groups-tbody');

      $table.addClass('loading');

      $.post(ajaxurl, {
          action: 'hp_get_dna_groups',
          nonce: $('#nonce').val(),
          gedcom: $('#search-tree').val(),
          offset: offset,
          limit: 25
        })
        .done(function(response) {
          if (response.success) {
            renderDNAGroups(response.data.dna_groups);
            updateNavigation(response.data);
          } else {
            showError(response.data || '<?php echo esc_js(__('Failed to load DNA groups.', 'heritagepress')); ?>');
          }
        })
        .fail(function() {
          showError('<?php echo esc_js(__('Error loading DNA groups.', 'heritagepress')); ?>');
        })
        .always(function() {
          $table.removeClass('loading');
        });
    }

    /**
     * Render DNA groups in table
     */
    function renderDNAGroups(groups) {
      var $tbody = $('#dna-groups-tbody');
      $tbody.empty();

      if (groups.length === 0) {
        $tbody.append(
          '<tr class="no-items">' +
          '<td class="colspanchange" colspan="7">' +
          '<?php echo esc_js(__('No DNA groups found.', 'heritagepress')); ?>' +
          '</td>' +
          '</tr>'
        );
        return;
      }

      groups.forEach(function(group) {
        var actions = '';

        if (group.allow_edit) {
          actions += '<a href="?page=heritagepress-dna-groups&dna_group=' +
            encodeURIComponent(group.dna_group) + '&tree=' +
            encodeURIComponent(group.gedcom) + '" ' +
            'class="action-button edit" title="<?php echo esc_js(__('Edit', 'heritagepress')); ?>">' +
            '<?php echo esc_js(__('Edit', 'heritagepress')); ?></a>';
        }

        if (group.allow_delete) {
          actions += '<button type="button" ' +
            'class="action-button delete" ' +
            'onclick="deleteDNAGroup(\'' + group.dna_group + '\', \'' + group.gedcom + '\')" ' +
            'title="<?php echo esc_js(__('Delete', 'heritagepress')); ?>">' +
            '<?php echo esc_js(__('Delete', 'heritagepress')); ?></button>';
        }

        var row = '<tr>' +
          '<th scope="row" class="check-column">' +
          '<input type="checkbox" value="' + group.dna_group + '" data-tree="' + group.gedcom + '">' +
          '</th>' +
          '<td class="column-actions">' +
          '<div class="action-buttons">' + actions + '</div>' +
          '</td>' +
          '<td class="column-group-id"><strong>' + escapeHtml(group.dna_group) + '</strong></td>' +
          '<td class="column-description">' + escapeHtml(group.description) + '</td>' +
          '<td class="column-tree">' + escapeHtml(group.treename || group.gedcom) + '</td>' +
          '<td class="column-test-type">' + escapeHtml(group.test_type) + '</td>' +
          '<td class="column-test-count" style="text-align: center;">' + group.test_count + '</td>' +
          '</tr>';

        $tbody.append(row);
      });
    }

    /**
     * Update navigation
     */
    function updateNavigation(data) {
      // Show/hide navigation based on results
      if (data.dna_groups && data.dna_groups.length > 0) {
        $('#results-navigation, #bottom-navigation').show();
      } else {
        $('#results-navigation, #bottom-navigation').hide();
      }
    }

    /**
     * Add DNA group
     */
    function addDNAGroup() {
      var $form = $('#add-dna-group-form');
      var $submit = $form.find('button[type="submit"]');

      $submit.prop('disabled', true).text('<?php echo esc_js(__('Adding...', 'heritagepress')); ?>');

      $.post(ajaxurl, {
          action: 'hp_add_dna_group',
          nonce: $('#nonce').val(),
          gedcom: $('#gedcom').val(),
          dna_group: $('#dna_group').val(),
          test_type: $('#test_type').val(),
          description: $('#description').val()
        })
        .done(function(response) {
          if (response.success) {
            showSuccess('<?php echo esc_js(__('DNA Group added successfully!', 'heritagepress')); ?>');
            $form[0].reset();
            // Redirect to browse tab
            setTimeout(function() {
              window.location.href = '?page=heritagepress-dna-groups';
            }, 1500);
          } else {
            showError(response.data || '<?php echo esc_js(__('Failed to add DNA group.', 'heritagepress')); ?>');
          }
        })
        .fail(function() {
          showError('<?php echo esc_js(__('Error adding DNA group.', 'heritagepress')); ?>');
        })
        .always(function() {
          $submit.prop('disabled', false).text('<?php echo esc_js(__('Add DNA Group', 'heritagepress')); ?>');
        });
    }

    /**
     * Delete single DNA group
     */
    window.deleteDNAGroup = function(dnaGroupId, gedcom) {
      if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this DNA group?', 'heritagepress')); ?>')) {
        return;
      }

      $.post(ajaxurl, {
          action: 'hp_delete_dna_group',
          nonce: $('#nonce').val(),
          dna_group: dnaGroupId,
          gedcom: gedcom
        })
        .done(function(response) {
          if (response.success) {
            showSuccess('<?php echo esc_js(__('DNA Group deleted successfully!', 'heritagepress')); ?>');
            loadDNAGroups();
          } else {
            showError(response.data || '<?php echo esc_js(__('Failed to delete DNA group.', 'heritagepress')); ?>');
          }
        })
        .fail(function() {
          showError('<?php echo esc_js(__('Error deleting DNA group.', 'heritagepress')); ?>');
        });
    };

    /**
     * Delete multiple DNA groups
     */
    function deleteDNAGroups(groupIds) {
      // Implementation for bulk delete
      showError('<?php echo esc_js(__('Bulk delete not yet implemented.', 'heritagepress')); ?>');
    }

    /**
     * Open DNA help
     */
    window.openDNAHelp = function() {
      alert('<?php echo esc_js(__('DNA help documentation will be available in a future update.', 'heritagepress')); ?>');
      return false;
    };

    /**
     * Utility functions
     */
    function escapeHtml(text) {
      if (!text) return '';
      return $('<div>').text(text).html();
    }

    function showSuccess(message) {
      // Add WordPress admin notice
      $('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>')
        .insertAfter('.wrap h1')
        .delay(3000)
        .fadeOut();
    }

    function showError(message) {
      // Add WordPress admin notice
      $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>')
        .insertAfter('.wrap h1')
        .delay(5000)
        .fadeOut();
    }

  });
</script>
