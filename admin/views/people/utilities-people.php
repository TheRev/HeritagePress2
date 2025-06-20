<?php

/**
 * People Utilities Tab
 * Data maintenance and utility functions
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, ARRAY_A);

// Available utility functions
$utilities = array(
  'reindex_names' => array(
    'title' => __('Reindex Names', 'heritagepress'),
    'description' => __('Rebuild the name search indexes for better performance.', 'heritagepress'),
    'icon' => 'dashicons-update',
    'category' => 'maintenance'
  ),
  'check_duplicates' => array(
    'title' => __('Find Duplicates', 'heritagepress'),
    'description' => __('Scan for potential duplicate person records.', 'heritagepress'),
    'icon' => 'dashicons-search',
    'category' => 'data_quality'
  ),
  'fix_dates' => array(
    'title' => __('Standardize Dates', 'heritagepress'),
    'description' => __('Convert dates to standard format and fix common issues.', 'heritagepress'),
    'icon' => 'dashicons-calendar-alt',
    'category' => 'data_quality'
  ),
  'update_soundex' => array(
    'title' => __('Update Soundex', 'heritagepress'),
    'description' => __('Generate Soundex codes for improved name searching.', 'heritagepress'),
    'icon' => 'dashicons-text',
    'category' => 'maintenance'
  ),
  'merge_people' => array(
    'title' => __('Merge People', 'heritagepress'),
    'description' => __('Merge duplicate or related person records.', 'heritagepress'),
    'icon' => 'dashicons-admin-links',
    'category' => 'data_management'
  ),
  'bulk_privacy' => array(
    'title' => __('Bulk Privacy Update', 'heritagepress'),
    'description' => __('Update privacy settings for multiple people at once.', 'heritagepress'),
    'icon' => 'dashicons-lock',
    'category' => 'privacy'
  ),
  'cleanup_orphans' => array(
    'title' => __('Clean Orphaned Data', 'heritagepress'),
    'description' => __('Remove orphaned records and fix data consistency.', 'heritagepress'),
    'icon' => 'dashicons-admin-tools',
    'category' => 'maintenance'
  ),
  'export_people' => array(
    'title' => __('Export People Data', 'heritagepress'),
    'description' => __('Export people data in various formats (CSV, Excel, GEDCOM).', 'heritagepress'),
    'icon' => 'dashicons-download',
    'category' => 'data_management'
  ),
  'import_corrections' => array(
    'title' => __('Import Corrections', 'heritagepress'),
    'description' => __('Import bulk corrections from spreadsheet files.', 'heritagepress'),
    'icon' => 'dashicons-upload',
    'category' => 'data_management'
  ),
  'verify_relationships' => array(
    'title' => __('Verify Relationships', 'heritagepress'),
    'description' => __('Check and verify family relationships for consistency.', 'heritagepress'),
    'icon' => 'dashicons-networking',
    'category' => 'data_quality'
  ),
  'assign_default_photos' => array(
    'title' => __('Assign Default Photos', 'heritagepress'),
    'description' => __('Automatically assign the first available photo as the default for each person who does not already have one. Optionally overwrite existing defaults.', 'heritagepress'),
    'icon' => 'dashicons-format-image',
    'category' => 'maintenance'
  )
);

// Group utilities by category
$utility_categories = array(
  'maintenance' => __('Database Maintenance', 'heritagepress'),
  'data_quality' => __('Data Quality', 'heritagepress'),
  'data_management' => __('Data Management', 'heritagepress'),
  'privacy' => __('Privacy & Security', 'heritagepress')
);
?>

<div class="utilities-people-section">
  <div class="utilities-intro-card">
    <div class="intro-header">
      <h3><?php _e('People Data Utilities', 'heritagepress'); ?></h3>
      <p class="description"><?php _e('Maintain and optimize your genealogy data with these utility functions.', 'heritagepress'); ?></p>
    </div>

    <div class="safety-notice">
      <div class="notice notice-warning">
        <p><strong><?php _e('Important:', 'heritagepress'); ?></strong> <?php _e('Some utilities make permanent changes to your data. Always backup your database before running maintenance utilities.', 'heritagepress'); ?></p>
      </div>
    </div>
  </div>

  <!-- Utility Categories -->
  <?php foreach ($utility_categories as $category_key => $category_name): ?>
    <div class="utility-category-section">
      <h4 class="category-title"><?php echo esc_html($category_name); ?></h4>

      <div class="utilities-grid">
        <?php foreach ($utilities as $utility_key => $utility_data): ?>
          <?php if ($utility_data['category'] === $category_key): ?>
            <div class="utility-card" data-utility="<?php echo esc_attr($utility_key); ?>">
              <div class="utility-icon">
                <span class="dashicons <?php echo esc_attr($utility_data['icon']); ?>"></span>
              </div>
              <div class="utility-content">
                <h5><?php echo esc_html($utility_data['title']); ?></h5>
                <p><?php echo esc_html($utility_data['description']); ?></p>
              </div>
              <div class="utility-actions">
                <button type="button" class="button button-primary run-utility" data-utility="<?php echo esc_attr($utility_key); ?>">
                  <?php _e('Run', 'heritagepress'); ?>
                </button>
                <button type="button" class="button button-secondary utility-info" data-utility="<?php echo esc_attr($utility_key); ?>">
                  <?php _e('Info', 'heritagepress'); ?>
                </button>
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- Utility Results Panel -->
  <div class="utility-results-panel" id="utility-results" style="display: none;">
    <div class="results-header">
      <h4><?php _e('Utility Results', 'heritagepress'); ?></h4>
      <button type="button" class="button-link close-results"><?php _e('Close', 'heritagepress'); ?></button>
    </div>
    <div class="results-content" id="results-content">
      <!-- Results will be loaded here -->
    </div>
  </div>

  <!-- Quick Actions Panel -->
  <div class="quick-actions-panel">
    <h4><?php _e('Quick Actions', 'heritagepress'); ?></h4>

    <div class="quick-action-grid">
      <div class="quick-action-item">
        <h5><?php _e('Database Backup', 'heritagepress'); ?></h5>
        <p><?php _e('Create a backup before running utilities.', 'heritagepress'); ?></p>
        <button type="button" id="create-backup" class="button"><?php _e('Create Backup', 'heritagepress'); ?></button>
      </div>

      <div class="quick-action-item">
        <h5><?php _e('System Check', 'heritagepress'); ?></h5>
        <p><?php _e('Run a comprehensive system health check.', 'heritagepress'); ?></p>
        <button type="button" id="system-check" class="button"><?php _e('Run Check', 'heritagepress'); ?></button>
      </div>

      <div class="quick-action-item">
        <h5><?php _e('Data Statistics', 'heritagepress'); ?></h5>
        <p><?php _e('View detailed statistics about your data.', 'heritagepress'); ?></p>
        <a href="<?php echo admin_url('admin.php?page=heritagepress-people&tab=reports&report=statistics'); ?>" class="button"><?php _e('View Stats', 'heritagepress'); ?></a>
      </div>
    </div>
  </div>
</div>

<!-- Utility Confirmation Modal -->
<div id="utility-modal" class="utility-modal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <h4 id="modal-title"><?php _e('Confirm Utility', 'heritagepress'); ?></h4>
      <span class="close-modal">&times;</span>
    </div>
    <div class="modal-body">
      <div id="modal-description"></div>

      <div class="tree-selection">
        <label for="modal-tree"><?php _e('Select Tree:', 'heritagepress'); ?></label>
        <select id="modal-tree">
          <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
          <?php foreach ($trees_result as $tree): ?>
            <option value="<?php echo esc_attr($tree['gedcom']); ?>">
              <?php echo esc_html($tree['treename']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="confirmation-checkbox">
        <label>
          <input type="checkbox" id="confirm-backup">
          <?php _e('I have created a backup of my database', 'heritagepress'); ?>
        </label>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" id="confirm-utility" class="button button-primary"><?php _e('Run Utility', 'heritagepress'); ?></button>
      <button type="button" class="button button-secondary cancel-utility"><?php _e('Cancel', 'heritagepress'); ?></button>
    </div>
  </div>
</div>

<!-- Progress Modal -->
<div id="progress-modal" class="utility-modal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <h4><?php _e('Running Utility...', 'heritagepress'); ?></h4>
    </div>
    <div class="modal-body">
      <div class="progress-bar">
        <div class="progress-fill" id="progress-fill"></div>
      </div>
      <div id="progress-text"><?php _e('Initializing...', 'heritagepress'); ?></div>
    </div>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {
    var currentUtility = '';

    // Run utility button click
    $('.run-utility').on('click', function() {
      var utility = $(this).data('utility');
      currentUtility = utility;

      // Show confirmation modal
      $('#modal-title').text($('[data-utility="' + utility + '"] h5').text());
      $('#modal-description').html('<p>' + $('[data-utility="' + utility + '"] p').text() + '</p>');

      // Add specific warnings for certain utilities
      if (utility === 'merge_people' || utility === 'cleanup_orphans' || utility === 'fix_dates') {
        $('#modal-description').append('<div class="warning"><strong><?php _e('Warning:', 'heritagepress'); ?></strong> <?php _e('This utility may make permanent changes to your data.', 'heritagepress'); ?></div>');
      }

      $('#utility-modal').show();
    });

    // Utility info button click
    $('.utility-info').on('click', function() {
      var utility = $(this).data('utility');

      // Show detailed information about the utility
      var infoContent = getUtilityInfo(utility);

      $('#results-content').html(infoContent);
      $('#utility-results').show();
    });

    // Confirm utility execution
    $('#confirm-utility').on('click', function() {
      if (!$('#confirm-backup').is(':checked')) {
        alert('<?php _e('Please confirm that you have created a backup before proceeding.', 'heritagepress'); ?>');
        return;
      }

      $('#utility-modal').hide();
      $('#progress-modal').show();

      var tree = $('#modal-tree').val();

      // Run the utility via AJAX
      runUtility(currentUtility, tree);
    });

    // Cancel utility
    $('.cancel-utility, .close-modal').on('click', function() {
      $('#utility-modal').hide();
    });

    // Close results
    $('.close-results').on('click', function() {
      $('#utility-results').hide();
    });

    // Quick actions
    $('#create-backup').on('click', function() {
      if (confirm('<?php _e('This will create a backup of your genealogy data. Continue?', 'heritagepress'); ?>')) {
        $('#progress-modal').show();

        $.post(ajaxurl, {
          action: 'hp_create_backup',
          _wpnonce: '<?php echo wp_create_nonce('hp_create_backup'); ?>'
        }, function(response) {
          $('#progress-modal').hide();

          if (response.success) {
            alert('<?php _e('Backup created successfully.', 'heritagepress'); ?>');
          } else {
            alert('<?php _e('Failed to create backup.', 'heritagepress'); ?>');
          }
        });
      }
    });

    $('#system-check').on('click', function() {
      $('#progress-modal').show();

      $.post(ajaxurl, {
        action: 'hp_system_check',
        _wpnonce: '<?php echo wp_create_nonce('hp_system_check'); ?>'
      }, function(response) {
        $('#progress-modal').hide();

        if (response.success) {
          $('#results-content').html(response.data.report);
          $('#utility-results').show();
        } else {
          alert('<?php _e('System check failed.', 'heritagepress'); ?>');
        }
      });
    });

    // Utility execution function
    function runUtility(utility, tree) {
      var data = {
        action: 'hp_run_people_utility',
        utility: utility,
        tree: tree,
        _wpnonce: '<?php echo wp_create_nonce('hp_run_utility'); ?>'
      };

      $.post(ajaxurl, data, function(response) {
        $('#progress-modal').hide();

        if (response.success) {
          $('#results-content').html(response.data.report);
          $('#utility-results').show();
        } else {
          alert('<?php _e('Utility execution failed:', 'heritagepress'); ?> ' + (response.data || 'Unknown error'));
        }
      }).fail(function() {
        $('#progress-modal').hide();
        alert('<?php _e('Failed to run utility. Please try again.', 'heritagepress'); ?>');
      });
    }

    // Get utility information
    function getUtilityInfo(utility) {
      var info = {
        'reindex_names': '<h5><?php _e('Reindex Names', 'heritagepress'); ?></h5><p><?php _e('This utility rebuilds the search indexes for person names, improving search performance and accuracy. It processes all name fields including soundex codes.', 'heritagepress'); ?></p>',
        'check_duplicates': '<h5><?php _e('Find Duplicates', 'heritagepress'); ?></h5><p><?php _e('Scans for potential duplicate person records based on name similarity, dates, and other criteria. Results are provided for manual review.', 'heritagepress'); ?></p>',
        'fix_dates': '<h5><?php _e('Standardize Dates', 'heritagepress'); ?></h5><p><?php _e('Converts dates to standard format and fixes common issues like invalid dates, inconsistent formatting, and missing date components.', 'heritagepress'); ?></p>',
        'update_soundex': '<h5><?php _e('Update Soundex', 'heritagepress'); ?></h5><p><?php _e('Generates Soundex codes for all names to improve phonetic name searching. Useful for finding variant spellings of names.', 'heritagepress'); ?></p>',
        'merge_people': '<h5><?php _e('Merge People', 'heritagepress'); ?></h5><p><?php _e('Provides tools to merge duplicate or related person records while preserving all associated data like events, sources, and media.', 'heritagepress'); ?></p>',
        'bulk_privacy': '<h5><?php _e('Bulk Privacy Update', 'heritagepress'); ?></h5><p><?php _e('Updates privacy settings for multiple people based on criteria like living status, birth year, or other factors.', 'heritagepress'); ?></p>',
        'cleanup_orphans': '<h5><?php _e('Clean Orphaned Data', 'heritagepress'); ?></h5><p><?php _e('Removes orphaned records and fixes data consistency issues. This includes unused media references, broken family links, and invalid relationships.', 'heritagepress'); ?></p>',
        'export_people': '<h5><?php _e('Export People Data', 'heritagepress'); ?></h5><p><?php _e('Exports people data in various formats including CSV for spreadsheets, Excel files, or GEDCOM for genealogy programs.', 'heritagepress'); ?></p>',
        'import_corrections': '<h5><?php _e('Import Corrections', 'heritagepress'); ?></h5><p><?php _e('Imports bulk corrections from properly formatted spreadsheet files. Useful for making many changes at once.', 'heritagepress'); ?></p>',
        'verify_relationships': '<h5><?php _e('Verify Relationships', 'heritagepress'); ?></h5><p><?php _e('Checks family relationships for logical consistency, such as impossible birth dates, circular relationships, and missing connections.', 'heritagepress'); ?></p>',
        'assign_default_photos': '<h5><?php _e('Assign Default Photos', 'heritagepress'); ?></h5><p><?php _e('Automatically assigns the first available photo as the default for each person who does not already have one. Optionally overwrites existing defaults.', 'heritagepress'); ?></p>'
      };

      return info[utility] || '<p><?php _e('Information not available for this utility.', 'heritagepress'); ?></p>';
    }

    // Modal outside click to close
    $(window).on('click', function(event) {
      if ($(event.target).hasClass('utility-modal')) {
        $('.utility-modal').hide();
      }
    });
  });
</script>

<style>
  .utilities-people-section {
    max-width: 1200px;
  }

  .utilities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
  }

  .utility-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    background: #fff;
    transition: box-shadow 0.3s ease;
  }

  .utility-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  .utility-icon {
    text-align: center;
    margin-bottom: 15px;
  }

  .utility-icon .dashicons {
    font-size: 32px;
    color: #0073aa;
  }

  .utility-content h5 {
    margin: 0 0 10px 0;
    font-size: 16px;
  }

  .utility-content p {
    margin: 0 0 15px 0;
    color: #666;
    font-size: 14px;
  }

  .utility-actions {
    display: flex;
    gap: 10px;
  }

  .utility-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .modal-content {
    background: #fff;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
  }

  .modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .modal-body {
    padding: 20px;
  }

  .modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
  }

  .progress-bar {
    width: 100%;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 10px;
  }

  .progress-fill {
    height: 100%;
    background: #0073aa;
    transition: width 0.3s ease;
    width: 0;
  }

  .quick-action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
  }

  .quick-action-item {
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 6px;
    background: #f9f9f9;
  }

  .quick-action-item h5 {
    margin: 0 0 8px 0;
  }

  .quick-action-item p {
    margin: 0 0 12px 0;
    font-size: 13px;
    color: #666;
  }

  .warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    padding: 10px;
    border-radius: 4px;
    margin-top: 10px;
  }

  .confirmation-checkbox {
    margin-top: 15px;
  }

  .tree-selection {
    margin-bottom: 15px;
  }

  .close-modal {
    cursor: pointer;
    font-size: 24px;
    color: #999;
  }

  .close-modal:hover {
    color: #333;
  }
</style>
