<?php

/**
 * DNA Tests Management Interface
 *
 * Complete replication of TNG admin_dna_tests.php and admin_new_dna_test.php functionality
 * Provides tabbed interface for listing, adding, and editing DNA tests
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'search';

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, ARRAY_A);

// Get available DNA groups
$dna_groups_table = $wpdb->prefix . 'hp_dna_groups';
$groups_query = "SELECT dna_group, description, test_type, gedcom FROM $dna_groups_table ORDER BY description";
$groups_result = $wpdb->get_results($groups_query, ARRAY_A);

// Check if editing a test
$test_id = isset($_GET['testID']) ? intval($_GET['testID']) : 0;
$is_editing = $test_id > 0;

// If editing, fetch test data
$test_data = null;
if ($is_editing) {
  $dna_tests_table = $wpdb->prefix . 'hp_dna_tests';
  $test_data = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $dna_tests_table WHERE testID = %d",
    $test_id
  ), ARRAY_A);

  if ($test_data) {
    $current_tab = 'edit';
  }
}

// If editing but no test found, redirect to search
if ($is_editing && !$test_data) {
  wp_redirect(admin_url('admin.php?page=heritagepress-dna-tests'));
  exit;
}

// Check user permissions
$allow_add = current_user_can('edit_genealogy');
$allow_edit = current_user_can('edit_genealogy');
$allow_delete = current_user_can('delete_genealogy');

?>

<div class="wrap heritagepress-dna-tests">
  <h1>
    <span class="dashicons dashicons-analytics" style="font-size: 24px; margin-right: 8px;"></span>
    <?php _e('DNA Tests', 'heritagepress'); ?>
  </h1>

  <!-- Tab Navigation -->
  <nav class="nav-tab-wrapper wp-clearfix">
    <a href="<?php echo admin_url('admin.php?page=heritagepress-dna-tests&tab=search'); ?>"
      class="nav-tab <?php echo ($current_tab === 'search') ? 'nav-tab-active' : ''; ?>">
      <span class="dashicons dashicons-search"></span>
      <?php _e('Search Tests', 'heritagepress'); ?>
    </a>

    <?php if ($allow_add): ?>
      <a href="<?php echo admin_url('admin.php?page=heritagepress-dna-tests&tab=add'); ?>"
        class="nav-tab <?php echo ($current_tab === 'add') ? 'nav-tab-active' : ''; ?>">
        <span class="dashicons dashicons-plus-alt2"></span>
        <?php _e('Add New Test', 'heritagepress'); ?>
      </a>
    <?php endif; ?>

    <?php if ($is_editing): ?>
      <a href="<?php echo admin_url('admin.php?page=heritagepress-dna-tests&tab=edit&testID=' . $test_id); ?>"
        class="nav-tab <?php echo ($current_tab === 'edit') ? 'nav-tab-active' : ''; ?>">
        <span class="dashicons dashicons-edit"></span>
        <?php printf(__('Edit Test #%d', 'heritagepress'), $test_id); ?>
      </a>
    <?php endif; ?>
  </nav>

  <div class="dna-tests-content">

    <?php if ($current_tab === 'search'): ?>
      <!-- Search/List DNA Tests Tab -->
      <div class="dna-tests-search-section">
        <div class="search-controls-card">
          <h2><?php _e('Search DNA Tests', 'heritagepress'); ?></h2>

          <form id="dna-tests-search-form" class="search-form">
            <?php wp_nonce_field('hp_dna_test_nonce', 'nonce'); ?>

            <div class="search-row">
              <div class="search-field">
                <label for="search-term"><?php _e('Search Term:', 'heritagepress'); ?></label>
                <input type="text" id="search-term" name="search" placeholder="<?php _e('Test number, person name...', 'heritagepress'); ?>">
              </div>

              <div class="search-field">
                <label for="filter-tree"><?php _e('Tree:', 'heritagepress'); ?></label>
                <select id="filter-tree" name="gedcom">
                  <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
                  <?php foreach ($trees_result as $tree): ?>
                    <option value="<?php echo esc_attr($tree['gedcom']); ?>">
                      <?php echo esc_html($tree['treename']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="search-field">
                <label for="filter-test-type"><?php _e('Test Type:', 'heritagepress'); ?></label>
                <select id="filter-test-type" name="test_type">
                  <option value=""><?php _e('All Types', 'heritagepress'); ?></option>
                  <option value="atDNA"><?php _e('Autosomal DNA', 'heritagepress'); ?></option>
                  <option value="Y-DNA"><?php _e('Y-DNA', 'heritagepress'); ?></option>
                  <option value="mtDNA"><?php _e('mtDNA', 'heritagepress'); ?></option>
                  <option value="X-DNA"><?php _e('X-DNA', 'heritagepress'); ?></option>
                </select>
              </div>

              <div class="search-field">
                <label for="filter-group"><?php _e('DNA Group:', 'heritagepress'); ?></label>
                <select id="filter-group" name="test_group">
                  <option value=""><?php _e('All Groups', 'heritagepress'); ?></option>
                  <?php foreach ($groups_result as $group): ?>
                    <option value="<?php echo esc_attr($group['dna_group']); ?>">
                      <?php echo esc_html($group['description'] . ' (' . $group['test_type'] . ')'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="search-actions">
              <button type="submit" class="button button-primary">
                <span class="dashicons dashicons-search"></span>
                <?php _e('Search Tests', 'heritagepress'); ?>
              </button>
              <button type="button" id="clear-search" class="button">
                <?php _e('Clear', 'heritagepress'); ?>
              </button>
            </div>
          </form>
        </div>

        <!-- Search Results -->
        <div class="search-results-card">
          <div id="dna-tests-table-container">
            <p class="description"><?php _e('Enter search criteria and click "Search Tests" to find DNA tests.', 'heritagepress'); ?></p>
          </div>
        </div>
      </div>

    <?php elseif ($current_tab === 'add' && $allow_add): ?>
      <!-- Add New DNA Test Tab -->
      <div class="add-dna-test-section">
        <?php include plugin_dir_path(__FILE__) . 'dna-tests-form.php'; ?>
      </div>

    <?php elseif ($current_tab === 'edit' && $is_editing): ?>
      <!-- Edit DNA Test Tab -->
      <div class="edit-dna-test-section">
        <?php include plugin_dir_path(__FILE__) . 'dna-tests-form.php'; ?>
      </div>

    <?php else: ?>
      <!-- Default/Fallback -->
      <div class="dna-tests-default">
        <p><?php _e('Invalid tab or insufficient permissions.', 'heritagepress'); ?></p>
      </div>
    <?php endif; ?>

  </div>
</div>

<!-- Loading Modal -->
<div id="dna-tests-loading" class="dna-modal" style="display: none;">
  <div class="dna-modal-content">
    <div class="dna-loading-spinner"></div>
    <p><?php _e('Processing...', 'heritagepress'); ?></p>
  </div>
</div>

<style>
  .heritagepress-dna-tests .nav-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .search-controls-card,
  .search-results-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
  }

  .search-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
  }

  .search-field label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
  }

  .search-field input,
  .search-field select {
    width: 100%;
    min-height: 30px;
  }

  .search-actions {
    display: flex;
    gap: 10px;
    align-items: center;
  }

  .dna-tests-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
  }

  .dna-tests-table th,
  .dna-tests-table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
  }

  .dna-tests-table th {
    background: #f0f0f1;
    font-weight: 600;
  }

  .dna-tests-table tr:nth-child(even) {
    background: #f9f9f9;
  }

  .dna-tests-table tr:hover {
    background: #e8f4fd;
  }

  .test-actions {
    display: flex;
    gap: 5px;
  }

  .test-actions .button {
    padding: 4px 8px;
    font-size: 12px;
    line-height: 1.2;
    height: auto;
  }

  .dna-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .dna-modal-content {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    text-align: center;
    min-width: 300px;
  }

  .dna-loading-spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #0073aa;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 15px;
  }

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }

    100% {
      transform: rotate(360deg);
    }
  }

  @media (max-width: 768px) {
    .search-row {
      grid-template-columns: 1fr;
      gap: 10px;
    }

    .search-actions {
      flex-direction: column;
      align-items: stretch;
    }
  }
</style>

<script type="text/javascript">
  jQuery(document).ready(function($) {

    // Search form submission
    $('#dna-tests-search-form').on('submit', function(e) {
      e.preventDefault();
      searchDNATests();
    });

    // Clear search
    $('#clear-search').on('click', function() {
      $('#dna-tests-search-form')[0].reset();
      $('#dna-tests-table-container').html('<p class="description"><?php _e('Enter search criteria and click "Search Tests" to find DNA tests.', 'heritagepress'); ?></p>');
    });

    // Search DNA tests function
    function searchDNATests() {
      var formData = {
        action: 'hp_search_dna_tests',
        nonce: $('#nonce').val(),
        search: $('#search-term').val(),
        gedcom: $('#filter-tree').val(),
        test_type: $('#filter-test-type').val(),
        test_group: $('#filter-group').val(),
        limit: 50,
        offset: 0
      };

      $('#dna-tests-loading').show();

      $.post(ajaxurl, formData, function(response) {
        $('#dna-tests-loading').hide();

        if (response.success) {
          displayDNATests(response.data.dna_tests);
        } else {
          $('#dna-tests-table-container').html('<p class="error">' + response.data + '</p>');
        }
      }).fail(function() {
        $('#dna-tests-loading').hide();
        $('#dna-tests-table-container').html('<p class="error"><?php _e('Search failed. Please try again.', 'heritagepress'); ?></p>');
      });
    }

    // Display DNA tests table
    function displayDNATests(tests) {
      if (tests.length === 0) {
        $('#dna-tests-table-container').html('<p><?php _e('No DNA tests found matching your criteria.', 'heritagepress'); ?></p>');
        return;
      }

      var html = '<table class="dna-tests-table wp-list-table widefat fixed striped">';
      html += '<thead><tr>';
      html += '<th><?php _e('Test ID', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Test Type', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Test Number', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Person', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Test Date', 'heritagepress'); ?></th>';
      html += '<th><?php _e('DNA Group', 'heritagepress'); ?></th>';
      html += '<th><?php _e('Actions', 'heritagepress'); ?></th>';
      html += '</tr></thead><tbody>';

      $.each(tests, function(index, test) {
        var personName = test.person_name || (test.firstname + ' ' + test.lastname).trim() || '<?php _e('Unknown', 'heritagepress'); ?>';
        var testDate = test.test_date && test.test_date !== '0000-00-00' ? test.test_date : '<?php _e('Not specified', 'heritagepress'); ?>';
        var dnaGroup = test.dna_group_desc || test.dna_group || '<?php _e('None', 'heritagepress'); ?>';

        html += '<tr>';
        html += '<td>' + test.testID + '</td>';
        html += '<td>' + (test.test_type || '') + '</td>';
        html += '<td>' + (test.test_number || '<?php _e('No number', 'heritagepress'); ?>') + '</td>';
        html += '<td>' + personName + '</td>';
        html += '<td>' + testDate + '</td>';
        html += '<td>' + dnaGroup + '</td>';
        html += '<td class="test-actions">';

        if (test.allow_edit) {
          html += '<a href="<?php echo admin_url('admin.php?page=heritagepress-dna-tests&tab=edit&testID='); ?>' + test.testID + '" class="button button-small"><?php _e('Edit', 'heritagepress'); ?></a>';
        }

        if (test.allow_delete) {
          html += '<button class="button button-small delete-test" data-test-id="' + test.testID + '"><?php _e('Delete', 'heritagepress'); ?></button>';
        }

        html += '</td>';
        html += '</tr>';
      });

      html += '</tbody></table>';
      $('#dna-tests-table-container').html(html);
    }

    // Delete test handler
    $(document).on('click', '.delete-test', function() {
      if (!confirm('<?php _e('Are you sure you want to delete this DNA test? This action cannot be undone.', 'heritagepress'); ?>')) {
        return;
      }

      var testId = $(this).data('test-id');
      var $row = $(this).closest('tr');

      $.post(ajaxurl, {
        action: 'hp_delete_dna_test',
        nonce: $('#nonce').val(),
        testID: testId
      }, function(response) {
        if (response.success) {
          $row.fadeOut(function() {
            $(this).remove();
            // Check if table is now empty
            if ($('.dna-tests-table tbody tr:visible').length === 0) {
              $('#dna-tests-table-container').html('<p><?php _e('No DNA tests found.', 'heritagepress'); ?></p>');
            }
          });
          alert('<?php _e('DNA test deleted successfully.', 'heritagepress'); ?>');
        } else {
          alert('<?php _e('Failed to delete DNA test:', 'heritagepress'); ?> ' + response.data);
        }
      });
    });

  });
</script>
