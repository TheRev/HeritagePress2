<?php

/**
 * People Reports Tab
 * Various genealogy reports and statistics
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, ARRAY_A);

// Get selected tree and report type
$selected_tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
$report_type = isset($_GET['report']) ? sanitize_text_field($_GET['report']) : 'statistics';

// Available report types
$report_types = array(
  'statistics' => __('Database Statistics', 'heritagepress'),
  'missing_dates' => __('Missing Birth/Death Dates', 'heritagepress'),
  'living_people' => __('Living People', 'heritagepress'),
  'recent_changes' => __('Recent Changes', 'heritagepress'),
  'duplicate_names' => __('Possible Duplicates', 'heritagepress'),
  'age_statistics' => __('Age Statistics', 'heritagepress'),
  'surname_list' => __('Surname List', 'heritagepress'),
  'orphans' => __('Orphaned Records', 'heritagepress')
);

$report_data = array();

// Generate report data based on type
if (!empty($selected_tree) || $report_type === 'statistics') {
  $people_table = $wpdb->prefix . 'hp_people';

  switch ($report_type) {
    case 'statistics':
      $report_data = generate_statistics_report($wpdb, $people_table, $trees_result, $selected_tree);
      break;

    case 'missing_dates':
      $report_data = generate_missing_dates_report($wpdb, $people_table, $selected_tree);
      break;

    case 'living_people':
      $report_data = generate_living_people_report($wpdb, $people_table, $selected_tree);
      break;

    case 'recent_changes':
      $report_data = generate_recent_changes_report($wpdb, $people_table, $selected_tree);
      break;

    case 'duplicate_names':
      $report_data = generate_duplicate_names_report($wpdb, $people_table, $selected_tree);
      break;

    case 'age_statistics':
      $report_data = generate_age_statistics_report($wpdb, $people_table, $selected_tree);
      break;

    case 'surname_list':
      $report_data = generate_surname_list_report($wpdb, $people_table, $selected_tree);
      break;

    case 'orphans':
      $report_data = generate_orphans_report($wpdb, $people_table, $selected_tree);
      break;
  }
}

// Report generation functions
function generate_statistics_report($wpdb, $people_table, $trees_result, $selected_tree)
{
  $stats = array();

  if ($selected_tree) {
    $trees_to_check = array($selected_tree);
  } else {
    $trees_to_check = array_column($trees_result, 'gedcom');
  }

  foreach ($trees_to_check as $tree_code) {
    $tree_name = '';
    foreach ($trees_result as $tree) {
      if ($tree['gedcom'] === $tree_code) {
        $tree_name = $tree['treename'];
        break;
      }
    }

    $tree_stats = array(
      'tree_name' => $tree_name,
      'tree_code' => $tree_code,
      'total_people' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $people_table WHERE gedcom = %s", $tree_code)),
      'males' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $people_table WHERE gedcom = %s AND sex = 'M'", $tree_code)),
      'females' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $people_table WHERE gedcom = %s AND sex = 'F'", $tree_code)),
      'unknown_sex' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $people_table WHERE gedcom = %s AND (sex = '' OR sex IS NULL)", $tree_code)),
      'living' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $people_table WHERE gedcom = %s AND living = 1", $tree_code)),
      'deceased' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $people_table WHERE gedcom = %s AND living = 0", $tree_code)),
      'private' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $people_table WHERE gedcom = %s AND private = 1", $tree_code)),
      'with_birth_dates' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $people_table WHERE gedcom = %s AND birthdate != ''", $tree_code)),
      'with_death_dates' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $people_table WHERE gedcom = %s AND deathdate != ''", $tree_code)),
      'with_birth_places' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $people_table WHERE gedcom = %s AND birthplace != ''", $tree_code)),
      'with_death_places' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $people_table WHERE gedcom = %s AND deathplace != ''", $tree_code))
    );

    $stats[] = $tree_stats;
  }

  return $stats;
}

function generate_missing_dates_report($wpdb, $people_table, $selected_tree)
{
  $where_clause = $selected_tree ? $wpdb->prepare("WHERE gedcom = %s", $selected_tree) : "";

  $query = "SELECT personID, firstname, lastname, birthdate, deathdate, living
            FROM $people_table
            $where_clause
            AND (birthdate = '' OR birthdate IS NULL OR deathdate = '' OR deathdate IS NULL)
            AND living = 0
            ORDER BY lastname, firstname
            LIMIT 100";

  return $wpdb->get_results($query, ARRAY_A);
}

function generate_living_people_report($wpdb, $people_table, $selected_tree)
{
  $where_clause = $selected_tree ? $wpdb->prepare("WHERE gedcom = %s AND", $selected_tree) : "WHERE";

  $query = "SELECT personID, firstname, lastname, birthdate, birthplace, private
            FROM $people_table
            $where_clause living = 1
            ORDER BY lastname, firstname
            LIMIT 100";

  return $wpdb->get_results($query, ARRAY_A);
}

function generate_recent_changes_report($wpdb, $people_table, $selected_tree)
{
  $where_clause = $selected_tree ? $wpdb->prepare("WHERE gedcom = %s", $selected_tree) : "";

  $query = "SELECT personID, firstname, lastname, changedate, changedby
            FROM $people_table
            $where_clause
            ORDER BY changedate DESC
            LIMIT 50";

  return $wpdb->get_results($query, ARRAY_A);
}

function generate_duplicate_names_report($wpdb, $people_table, $selected_tree)
{
  $where_clause = $selected_tree ? $wpdb->prepare("WHERE gedcom = %s", $selected_tree) : "";

  $query = "SELECT firstname, lastname, COUNT(*) as count,
            GROUP_CONCAT(personID) as person_ids
            FROM $people_table
            $where_clause
            GROUP BY LOWER(firstname), LOWER(lastname)
            HAVING count > 1
            ORDER BY count DESC, lastname, firstname
            LIMIT 50";

  return $wpdb->get_results($query, ARRAY_A);
}

function generate_age_statistics_report($wpdb, $people_table, $selected_tree)
{
  $where_clause = $selected_tree ? $wpdb->prepare("WHERE gedcom = %s", $selected_tree) : "";

  // This is a simplified version - full implementation would need better date parsing
  $query = "SELECT
            AVG(YEAR(STR_TO_DATE(deathdate, '%d %M %Y')) - YEAR(STR_TO_DATE(birthdate, '%d %M %Y'))) as avg_age,
            MIN(YEAR(STR_TO_DATE(deathdate, '%d %M %Y')) - YEAR(STR_TO_DATE(birthdate, '%d %M %Y'))) as min_age,
            MAX(YEAR(STR_TO_DATE(deathdate, '%d %M %Y')) - YEAR(STR_TO_DATE(birthdate, '%d %M %Y'))) as max_age,
            COUNT(*) as total_with_both_dates
            FROM $people_table
            $where_clause
            AND birthdate != '' AND deathdate != ''
            AND birthdate IS NOT NULL AND deathdate IS NOT NULL";

  return $wpdb->get_results($query, ARRAY_A);
}

function generate_surname_list_report($wpdb, $people_table, $selected_tree)
{
  $where_clause = $selected_tree ? $wpdb->prepare("WHERE gedcom = %s", $selected_tree) : "";

  $query = "SELECT lastname, COUNT(*) as count
            FROM $people_table
            $where_clause
            AND lastname != '' AND lastname IS NOT NULL
            GROUP BY lastname
            ORDER BY count DESC, lastname
            LIMIT 100";

  return $wpdb->get_results($query, ARRAY_A);
}

function generate_orphans_report($wpdb, $people_table, $selected_tree)
{
  // This would need joins to families and children tables for full implementation
  $where_clause = $selected_tree ? $wpdb->prepare("WHERE gedcom = %s", $selected_tree) : "";

  $query = "SELECT personID, firstname, lastname, birthdate
            FROM $people_table
            $where_clause
            ORDER BY lastname, firstname
            LIMIT 50";

  return $wpdb->get_results($query, ARRAY_A);
}
?>

<div class="reports-people-section">
  <!-- Report Selection -->
  <div class="report-controls-card">
    <form method="get" id="report-form" class="report-form">
      <input type="hidden" name="page" value="heritagepress-people">
      <input type="hidden" name="tab" value="reports">

      <div class="form-header">
        <h3><?php _e('People Reports', 'heritagepress'); ?></h3>
        <p class="description"><?php _e('Generate various reports and statistics about your genealogy data.', 'heritagepress'); ?></p>
      </div>

      <div class="report-controls">
        <div class="control-row">
          <div class="control-field">
            <label for="report"><?php _e('Report Type:', 'heritagepress'); ?></label>
            <select id="report" name="report" onchange="this.form.submit()">
              <?php foreach ($report_types as $key => $label): ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($report_type, $key); ?>>
                  <?php echo esc_html($label); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="control-field">
            <label for="tree"><?php _e('Tree:', 'heritagepress'); ?></label>
            <select id="tree" name="tree" onchange="this.form.submit()">
              <?php if ($report_type !== 'statistics'): ?>
                <option value=""><?php _e('Select a tree...', 'heritagepress'); ?></option>
              <?php else: ?>
                <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
              <?php endif; ?>
              <?php foreach ($trees_result as $tree): ?>
                <option value="<?php echo esc_attr($tree['gedcom']); ?>" <?php selected($selected_tree, $tree['gedcom']); ?>>
                  <?php echo esc_html($tree['treename']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="report-actions">
          <button type="button" id="export-report" class="button"><?php _e('Export Report', 'heritagepress'); ?></button>
          <button type="button" id="print-report" class="button"><?php _e('Print Report', 'heritagepress'); ?></button>
        </div>
      </div>
    </form>
  </div>

  <!-- Report Content -->
  <?php if (!empty($report_data) || $report_type === 'statistics'): ?>
    <div class="report-content-section">
      <div class="report-header">
        <h3><?php echo esc_html($report_types[$report_type]); ?></h3>
        <?php if ($selected_tree): ?>
          <?php
          $tree_name = '';
          foreach ($trees_result as $tree) {
            if ($tree['gedcom'] === $selected_tree) {
              $tree_name = $tree['treename'];
              break;
            }
          }
          ?>
          <p class="tree-info"><?php printf(__('Tree: %s', 'heritagepress'), esc_html($tree_name)); ?></p>
        <?php endif; ?>
        <p class="generated-date"><?php printf(__('Generated on: %s', 'heritagepress'), date('F j, Y g:i A')); ?></p>
      </div>

      <div class="report-data">
        <?php
        switch ($report_type) {
          case 'statistics':
            include __DIR__ . '/reports/statistics-report.php';
            break;

          case 'missing_dates':
            include __DIR__ . '/reports/missing-dates-report.php';
            break;

          case 'living_people':
            include __DIR__ . '/reports/living-people-report.php';
            break;

          case 'recent_changes':
            include __DIR__ . '/reports/recent-changes-report.php';
            break;

          case 'duplicate_names':
            include __DIR__ . '/reports/duplicate-names-report.php';
            break;

          case 'age_statistics':
            include __DIR__ . '/reports/age-statistics-report.php';
            break;

          case 'surname_list':
            include __DIR__ . '/reports/surname-list-report.php';
            break;

          case 'orphans':
            include __DIR__ . '/reports/orphans-report.php';
            break;

          default:
            echo '<p>' . __('Report type not implemented yet.', 'heritagepress') . '</p>';
            break;
        }
        ?>
      </div>
    </div>
  <?php else: ?>
    <div class="no-report-data">
      <p><?php _e('Please select a tree to generate reports.', 'heritagepress'); ?></p>
    </div>
  <?php endif; ?>

  <!-- Report Quick Stats -->
  <div class="quick-stats-panel">
    <h4><?php _e('Quick Statistics', 'heritagepress'); ?></h4>
    <div class="stats-grid">
      <?php
      if (!empty($report_data) && $report_type === 'statistics') {
        $total_people = 0;
        $total_males = 0;
        $total_females = 0;
        $total_living = 0;

        foreach ($report_data as $tree_stats) {
          $total_people += $tree_stats['total_people'];
          $total_males += $tree_stats['males'];
          $total_females += $tree_stats['females'];
          $total_living += $tree_stats['living'];
        }
      ?>
        <div class="stat-item">
          <span class="stat-number"><?php echo number_format($total_people); ?></span>
          <span class="stat-label"><?php _e('Total People', 'heritagepress'); ?></span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?php echo number_format($total_males); ?></span>
          <span class="stat-label"><?php _e('Males', 'heritagepress'); ?></span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?php echo number_format($total_females); ?></span>
          <span class="stat-label"><?php _e('Females', 'heritagepress'); ?></span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?php echo number_format($total_living); ?></span>
          <span class="stat-label"><?php _e('Living', 'heritagepress'); ?></span>
        </div>
      <?php
      } else {
        echo '<p><em>' . __('Select a report to view statistics.', 'heritagepress') . '</em></p>';
      }
      ?>
    </div>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {
    // Export report functionality
    $('#export-report').on('click', function() {
      var reportType = $('#report').val();
      var tree = $('#tree').val();

      // Create download link
      var exportUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
      exportUrl += '?action=hp_export_people_report';
      exportUrl += '&report=' + reportType;
      if (tree) {
        exportUrl += '&tree=' + tree;
      }
      exportUrl += '&_wpnonce=<?php echo wp_create_nonce('hp_export_report'); ?>';

      // Open download
      window.open(exportUrl, '_blank');
    });

    // Print report functionality
    $('#print-report').on('click', function() {
      var printContent = $('.report-content-section').html();
      var printWindow = window.open('', 'print', 'width=800,height=600');

      printWindow.document.write('<html><head><title><?php _e('People Report', 'heritagepress'); ?></title>');
      printWindow.document.write('<style>');
      printWindow.document.write('body { font-family: Arial, sans-serif; margin: 20px; }');
      printWindow.document.write('table { border-collapse: collapse; width: 100%; }');
      printWindow.document.write('th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }');
      printWindow.document.write('th { background-color: #f2f2f2; }');
      printWindow.document.write('.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }');
      printWindow.document.write('.stat-item { border: 1px solid #ddd; padding: 15px; text-align: center; }');
      printWindow.document.write('</style>');
      printWindow.document.write('</head><body>');
      printWindow.document.write(printContent);
      printWindow.document.write('</body></html>');

      printWindow.document.close();
      printWindow.print();
    });

    // Auto-refresh report when selections change
    $('#report, #tree').on('change', function() {
      // Form already submits on change
    });
  });
</script>
