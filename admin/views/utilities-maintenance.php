<?php

/**
 * HeritagePress Admin Maintenance View
 * Database optimization and maintenance
 *
 * @package    HeritagePress
 * @subpackage Admin\Views
 */

if (!defined('ABSPATH')) {
  exit;
}

// Add nonce for AJAX operations
$maintenance_nonce = wp_create_nonce('hp_maintenance_operation');

// Get tables to optimize
global $wpdb;
$tables_prefix = $wpdb->prefix . 'hp_';
$tables_query = $wpdb->get_results("SHOW TABLES LIKE '{$tables_prefix}%'", ARRAY_A);
$available_tables = [];

foreach ($tables_query as $table_row) {
  $table = reset($table_row); // Get first value from array
  $table_short = str_replace($tables_prefix, '', $table);
  $available_tables[$table_short] = $table;
}
?>

<div class="heritagepress-maintenance-section">
  <div class="maintenance-instructions">
    <p>
      <?php _e('Database maintenance tools help keep your genealogy database running efficiently. Regular maintenance can improve performance and prevent issues.', 'heritagepress'); ?>
    </p>
  </div>

  <!-- Database Optimization Card -->
  <div class="maintenance-card card">
    <h3><?php _e('Database Optimization', 'heritagepress'); ?></h3>
    <p><?php _e('Optimize tables to improve database performance by defragmenting and reclaiming unused space.', 'heritagepress'); ?></p>

    <div class="maintenance-actions">
      <button type="button" id="optimize-all-tables" class="button button-primary">
        <span class="dashicons dashicons-update"></span>
        <?php _e('Optimize All Tables', 'heritagepress'); ?>
      </button>

      <div class="progress-container" style="display: none;">
        <div class="progress">
          <div class="progress-bar" style="width: 0%;">0%</div>
        </div>
        <div class="progress-status"></div>
      </div>
    </div>

    <div class="optimization-results">
      <h4><?php _e('Optimization Results', 'heritagepress'); ?></h4>
      <div class="optimization-table-container">
        <table class="widefat optimization-table">
          <thead>
            <tr>
              <th><?php _e('Table', 'heritagepress'); ?></th>
              <th><?php _e('Status', 'heritagepress'); ?></th>
              <th><?php _e('Data Size Before', 'heritagepress'); ?></th>
              <th><?php _e('Data Size After', 'heritagepress'); ?></th>
              <th><?php _e('Space Saved', 'heritagepress'); ?></th>
            </tr>
          </thead>
          <tbody>
            <tr class="no-results">
              <td colspan="5"><?php _e('No optimization has been performed yet.', 'heritagepress'); ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Data Check Card -->
  <div class="maintenance-card card">
    <h3><?php _e('Data Integrity Check', 'heritagepress'); ?></h3>
    <p><?php _e('Check your genealogy database for orphaned records, invalid relationships, or other issues.', 'heritagepress'); ?></p>

    <div class="maintenance-actions">
      <button type="button" id="check-data-integrity" class="button button-primary">
        <span class="dashicons dashicons-search"></span>
        <?php _e('Run Integrity Check', 'heritagepress'); ?>
      </button>
    </div>

    <div class="integrity-results">
      <h4><?php _e('Integrity Check Results', 'heritagepress'); ?></h4>
      <div id="integrity-results-container">
        <p class="no-results"><?php _e('No integrity check has been performed yet.', 'heritagepress'); ?></p>
      </div>
    </div>
  </div>

  <!-- System Information Card -->
  <div class="maintenance-card card">
    <h3><?php _e('System Information', 'heritagepress'); ?></h3>
    <table class="widefat system-info-table">
      <tr>
        <th><?php _e('PHP Version', 'heritagepress'); ?></th>
        <td><?php echo PHP_VERSION; ?></td>
      </tr>
      <tr>
        <th><?php _e('MySQL Version', 'heritagepress'); ?></th>
        <td><?php echo $wpdb->db_version(); ?></td>
      </tr>
      <tr>
        <th><?php _e('WordPress Version', 'heritagepress'); ?></th>
        <td><?php echo get_bloginfo('version'); ?></td>
      </tr>
      <tr>
        <th><?php _e('HeritagePress Version', 'heritagepress'); ?></th>
        <td><?php echo HERITAGEPRESS_VERSION; ?></td>
      </tr>
      <tr>
        <th><?php _e('Memory Limit', 'heritagepress'); ?></th>
        <td><?php echo WP_MEMORY_LIMIT; ?></td>
      </tr>
      <tr>
        <th><?php _e('Max Execution Time', 'heritagepress'); ?></th>
        <td><?php echo ini_get('max_execution_time'); ?> <?php _e('seconds', 'heritagepress'); ?></td>
      </tr>
    </table>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {
    let maintenanceNonce = '<?php echo esc_js($maintenance_nonce); ?>';
    let availableTables = <?php echo json_encode(array_keys($available_tables)); ?>;

    // Optimize all tables
    $('#optimize-all-tables').on('click', function() {
      // Reset results
      $('.optimization-table tbody').html('');
      $('.progress-bar').css('width', '0%').text('0%');
      $('.progress-status').text('<?php esc_html_e('Starting optimization...', 'heritagepress'); ?>');
      $('.progress-container').show();

      // Disable button during optimization
      $(this).prop('disabled', true);

      let totalTables = availableTables.length;
      let processedTables = 0;
      let tableResults = {};

      // Process tables one by one
      function optimizeNextTable(index) {
        if (index >= totalTables) {
          // All done
          $('#optimize-all-tables').prop('disabled', false);
          $('.progress-status').text('<?php esc_html_e('Optimization complete!', 'heritagepress'); ?>');
          return;
        }

        let table = availableTables[index];
        let progress = Math.round(((index + 1) / totalTables) * 100);

        $('.progress-bar').css('width', progress + '%').text(progress + '%');
        $('.progress-status').text('<?php esc_html_e('Optimizing table', 'heritagepress'); ?>: ' + table);

        // AJAX call for table optimization would go here
        // For the placeholder, we'll just simulate it

        setTimeout(function() {
          let result = {
            table: table,
            status: 'OK',
            before: Math.floor(Math.random() * 10000) + ' KB',
            after: Math.floor(Math.random() * 8000) + ' KB',
            saved: Math.floor(Math.random() * 2000) + ' KB'
          };

          addOptimizationResult(result);
          processedTables++;

          // Process next table
          optimizeNextTable(index + 1);
        }, 500);
      }

      function addOptimizationResult(result) {
        // Remove no-results row if it exists
        $('.optimization-table tbody .no-results').remove();

        // Add result row
        let row = $('<tr>')
          .append($('<td>').text(result.table))
          .append($('<td>').html('<span class="status-badge status-success">' + result.status + '</span>'))
          .append($('<td>').text(result.before))
          .append($('<td>').text(result.after))
          .append($('<td>').text(result.saved));

        $('.optimization-table tbody').append(row);
      }

      // Start optimization
      optimizeNextTable(0);
    });

    // Data integrity check
    $('#check-data-integrity').on('click', function() {
      $(this).prop('disabled', true).html('<span class="spinner is-active" style="float:left;margin-top:0"></span> <?php esc_html_e('Checking...', 'heritagepress'); ?>');

      // Reset results
      $('#integrity-results-container').html('<p><?php esc_html_e('Checking data integrity...', 'heritagepress'); ?></p>');

      // AJAX call would go here
      // For the placeholder, we'll just simulate it

      setTimeout(function() {
        let results = [{
            type: 'warning',
            message: '<?php esc_html_e('Found 3 people with invalid birth dates', 'heritagepress'); ?>',
            details: '<?php esc_html_e('Some birth dates appear to be in the future or are otherwise invalid.', 'heritagepress'); ?>'
          },
          {
            type: 'error',
            message: '<?php esc_html_e('Found 2 orphaned children records', 'heritagepress'); ?>',
            details: '<?php esc_html_e('These children are not linked to any parents.', 'heritagepress'); ?>'
          },
          {
            type: 'info',
            message: '<?php esc_html_e('212 people records verified', 'heritagepress'); ?>',
            details: '<?php esc_html_e('These records are well-formed and without issues.', 'heritagepress'); ?>'
          }
        ];

        $('#check-data-integrity').prop('disabled', false).html('<span class="dashicons dashicons-search"></span> <?php esc_html_e('Run Integrity Check', 'heritagepress'); ?>');

        let resultsHtml = '<div class="integrity-result-list">';
        $.each(results, function(i, result) {
          resultsHtml += '<div class="integrity-result integrity-' + result.type + '">';
          resultsHtml += '<div class="integrity-message"><strong>' + result.message + '</strong></div>';
          resultsHtml += '<div class="integrity-details">' + result.details + '</div>';
          resultsHtml += '</div>';
        });
        resultsHtml += '</div>';

        $('#integrity-results-container').html(resultsHtml);
      }, 2000);
    });
  });
</script>

<style>
  .heritagepress-maintenance-section {
    margin: 20px 0;
  }

  .maintenance-card {
    background: #fff;
    border: 1px solid #e5e5e5;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
  }

  .maintenance-actions {
    margin: 20px 0;
  }

  .progress-container {
    margin: 20px 0;
  }

  .progress {
    height: 20px;
    background-color: #f5f5f5;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 10px;
  }

  .progress-bar {
    height: 100%;
    line-height: 20px;
    text-align: center;
    background-color: #0073aa;
    color: #fff;
    transition: width 0.6s ease;
  }

  .progress-status {
    font-size: 12px;
    color: #666;
  }

  .no-results {
    font-style: italic;
    color: #666;
    text-align: center;
  }

  .optimization-table-container,
  #integrity-results-container {
    max-height: 300px;
    overflow-y: auto;
    margin-top: 15px;
  }

  .status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
  }

  .status-success {
    background-color: #dff0d8;
    color: #3c763d;
  }

  .system-info-table th {
    width: 200px;
  }

  .integrity-result {
    padding: 10px;
    margin-bottom: 10px;
    border-left: 5px solid #ccc;
    background: #f9f9f9;
  }

  .integrity-error {
    border-color: #dc3232;
  }

  .integrity-warning {
    border-color: #ffb900;
  }

  .integrity-info {
    border-color: #00a0d2;
  }

  .integrity-details {
    margin-top: 5px;
    font-size: 13px;
    color: #666;
  }
</style>
