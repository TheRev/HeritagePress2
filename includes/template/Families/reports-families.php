<?php

/**
 * Family Reports - Comprehensive family readapting tools
 * Statistics, analysis, and export reports for families
 */

if (!defined('ABSPATH')) {
  exit;
}

global $wpdb;

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees = $wpdb->get_results("SELECT gedcom, treename FROM $trees_table ORDER BY treename", ARRAY_A);
?>

<div class="family-reports-container">

  <!-- Family Statistics -->
  <div class="report-section">
    <h3><?php _e('Family Statistics', 'heritagepress'); ?></h3>

    <div class="stats-grid">
      <?php
      $families_table = $wpdb->prefix . 'hp_families';
      $people_table = $wpdb->prefix . 'hp_people';
      $children_table = $wpdb->prefix . 'hp_children';

      // Total families
      $total_families = $wpdb->get_var("SELECT COUNT(*) FROM $families_table");

      // Families with marriages
      $married_families = $wpdb->get_var("SELECT COUNT(*) FROM $families_table WHERE marrdate != '' OR marrplace != ''");

      // Families with divorces
      $divorced_families = $wpdb->get_var("SELECT COUNT(*) FROM $families_table WHERE divdate != '' OR divplace != ''");

      // Living families
      $living_families = $wpdb->get_var("SELECT COUNT(*) FROM $families_table WHERE living = 1");

      // Families with children
      $families_with_children = $wpdb->get_var("SELECT COUNT(DISTINCT familyID) FROM $children_table");

      // Average children per family
      $avg_children = $wpdb->get_var("
        SELECT AVG(child_count) FROM (
          SELECT COUNT(*) as child_count
          FROM $children_table
          GROUP BY familyID, gedcom
        ) as counts
      ");
      $avg_children = $avg_children ? round($avg_children, 2) : 0;

      // Most children in one family
      $max_children = $wpdb->get_var("
        SELECT MAX(child_count) FROM (
          SELECT COUNT(*) as child_count
          FROM $children_table
          GROUP BY familyID, gedcom
        ) as counts
      ");
      $max_children = $max_children ?: 0;
      ?>

      <div class="stat-card">
        <h4><?php _e('Total Families', 'heritagepress'); ?></h4>
        <div class="stat-number"><?php echo number_format($total_families); ?></div>
      </div>

      <div class="stat-card">
        <h4><?php _e('Married Couples', 'heritagepress'); ?></h4>
        <div class="stat-number"><?php echo number_format($married_families); ?></div>
        <div class="stat-percentage">
          <?php echo $total_families > 0 ? round(($married_families / $total_families) * 100, 1) : 0; ?>%
        </div>
      </div>

      <div class="stat-card">
        <h4><?php _e('Divorced Couples', 'heritagepress'); ?></h4>
        <div class="stat-number"><?php echo number_format($divorced_families); ?></div>
        <div class="stat-percentage">
          <?php echo $total_families > 0 ? round(($divorced_families / $total_families) * 100, 1) : 0; ?>%
        </div>
      </div>

      <div class="stat-card">
        <h4><?php _e('Living Families', 'heritagepress'); ?></h4>
        <div class="stat-number"><?php echo number_format($living_families); ?></div>
        <div class="stat-percentage">
          <?php echo $total_families > 0 ? round(($living_families / $total_families) * 100, 1) : 0; ?>%
        </div>
      </div>

      <div class="stat-card">
        <h4><?php _e('Families with Children', 'heritagepress'); ?></h4>
        <div class="stat-number"><?php echo number_format($families_with_children); ?></div>
        <div class="stat-percentage">
          <?php echo $total_families > 0 ? round(($families_with_children / $total_families) * 100, 1) : 0; ?>%
        </div>
      </div>

      <div class="stat-card">
        <h4><?php _e('Average Children', 'heritagepress'); ?></h4>
        <div class="stat-number"><?php echo $avg_children; ?></div>
        <div class="stat-note"><?php _e('per family', 'heritagepress'); ?></div>
      </div>

      <div class="stat-card">
        <h4><?php _e('Most Children', 'heritagepress'); ?></h4>
        <div class="stat-number"><?php echo $max_children; ?></div>
        <div class="stat-note"><?php _e('in one family', 'heritagepress'); ?></div>
      </div>
    </div>
  </div>

  <!-- Family Reports Generator -->
  <div class="report-section">
    <h3><?php _e('Generate Family Reports', 'heritagepress'); ?></h3>

    <form method="post" action="" class="generate-report-form">
      <?php wp_nonce_field('heritagepress_generate_family_report', 'generate_family_report_nonce'); ?>
      <input type="hidden" name="action" value="generate_family_report">

      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="report_type"><?php _e('Report Type:', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="report_type" id="report_type" required onchange="updateReportOptions();">
              <option value=""><?php _e('Select report type...', 'heritagepress'); ?></option>
              <option value="family_list"><?php _e('Family List', 'heritagepress'); ?></option>
              <option value="marriage_list"><?php _e('Marriage List', 'heritagepress'); ?></option>
              <option value="anniversary_list"><?php _e('Anniversary List', 'heritagepress'); ?></option>
              <option value="childless_families"><?php _e('Childless Families', 'heritagepress'); ?></option>
              <option value="large_families"><?php _e('Large Families', 'heritagepress'); ?></option>
              <option value="family_statistics"><?php _e('Family Statistics by Tree', 'heritagepress'); ?></option>
              <option value="missing_spouses"><?php _e('Families with Missing Spouses', 'heritagepress'); ?></option>
              <option value="divorce_list"><?php _e('Divorce List', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="report_tree"><?php _e('Tree (optional):', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="report_tree" id="report_tree">
              <option value=""><?php _e('All trees', 'heritagepress'); ?></option>
              <?php foreach ($trees as $tree): ?>
                <option value="<?php echo esc_attr($tree['gedcom']); ?>">
                  <?php echo esc_html($tree['treename']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>

        <tr id="report_options" style="display:none;">
          <th scope="row"><?php _e('Options:', 'heritagepress'); ?></th>
          <td>
            <div id="options_content">
              <!-- Options will be populated based on report type -->
            </div>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="output_format"><?php _e('Output Format:', 'heritagepress'); ?></label>
          </th>
          <td>
            <select name="output_format" id="output_format" required>
              <option value="html"><?php _e('HTML (view in browser)', 'heritagepress'); ?></option>
              <option value="pdf"><?php _e('PDF (download)', 'heritagepress'); ?></option>
              <option value="csv"><?php _e('CSV (spreadsheet)', 'heritagepress'); ?></option>
              <option value="excel"><?php _e('Excel (download)', 'heritagepress'); ?></option>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row"><?php _e('Include:', 'heritagepress'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="include_living" value="1" checked>
              <?php _e('Living families', 'heritagepress'); ?>
            </label><br>
            <label>
              <input type="checkbox" name="include_private" value="1">
              <?php _e('Private families', 'heritagepress'); ?>
            </label><br>
            <label>
              <input type="checkbox" name="include_dates" value="1" checked>
              <?php _e('Marriage/divorce dates', 'heritagepress'); ?>
            </label><br>
            <label>
              <input type="checkbox" name="include_places" value="1" checked>
              <?php _e('Marriage/divorce places', 'heritagepress'); ?>
            </label><br>
            <label>
              <input type="checkbox" name="include_children" value="1" checked>
              <?php _e('Children information', 'heritagepress'); ?>
            </label>
          </td>
        </tr>
      </table>

      <p class="submit">
        <input type="submit" name="generate_report" class="button button-primary"
          value="<?php _e('Generate Report', 'heritagepress'); ?>">
      </p>
    </form>
  </div>

  <!-- Tree Comparison -->
  <div class="report-section">
    <h3><?php _e('Tree Comparison', 'heritagepress'); ?></h3>
    <p class="description">
      <?php _e('Compare family statistics across different trees.', 'heritagepress'); ?>
    </p>

    <?php if (count($trees) > 1): ?>
      <div class="tree-comparison">
        <table class="wp-list-table widefat striped">
          <thead>
            <tr>
              <th><?php _e('Tree', 'heritagepress'); ?></th>
              <th><?php _e('Total Families', 'heritagepress'); ?></th>
              <th><?php _e('Married', 'heritagepress'); ?></th>
              <th><?php _e('Divorced', 'heritagepress'); ?></th>
              <th><?php _e('With Children', 'heritagepress'); ?></th>
              <th><?php _e('Avg Children', 'heritagepress'); ?></th>
              <th><?php _e('Living', 'heritagepress'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($trees as $tree):
              $tree_families = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $families_table WHERE gedcom = %s", $tree['gedcom']));
              $tree_married = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $families_table WHERE gedcom = %s AND (marrdate != '' OR marrplace != '')", $tree['gedcom']));
              $tree_divorced = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $families_table WHERE gedcom = %s AND (divdate != '' OR divplace != '')", $tree['gedcom']));
              $tree_with_children = $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT familyID) FROM $children_table WHERE gedcom = %s", $tree['gedcom']));
              $tree_avg_children = $wpdb->get_var($wpdb->prepare("
              SELECT AVG(child_count) FROM (
                SELECT COUNT(*) as child_count
                FROM $children_table
                WHERE gedcom = %s
                GROUP BY familyID
              ) as counts
            ", $tree['gedcom']));
              $tree_living = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $families_table WHERE gedcom = %s AND living = 1", $tree['gedcom']));
            ?>
              <tr>
                <td><strong><?php echo esc_html($tree['treename']); ?></strong></td>
                <td><?php echo number_format($tree_families); ?></td>
                <td><?php echo number_format($tree_married); ?></td>
                <td><?php echo number_format($tree_divorced); ?></td>
                <td><?php echo number_format($tree_with_children); ?></td>
                <td><?php echo $tree_avg_children ? round($tree_avg_children, 1) : '0'; ?></td>
                <td><?php echo number_format($tree_living); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p><?php _e('You need at least two trees to compare statistics.', 'heritagepress'); ?></p>
    <?php endif; ?>
  </div>

  <!-- Marriage Date Analysis -->
  <div class="report-section">
    <h3><?php _e('Marriage Date Analysis', 'heritagepress'); ?></h3>

    <div class="date-analysis">
      <?php
      // Marriage dates by century
      $marriage_centuries = $wpdb->get_results("
        SELECT
          CASE
            WHEN marrdate REGEXP '[0-9]{4}' THEN
              CONCAT(FLOOR(SUBSTRING_INDEX(SUBSTRING_INDEX(marrdate, ' ', -1), ' ', 1) / 100) * 100, 's')
            ELSE 'Unknown'
          END as century,
          COUNT(*) as count
        FROM $families_table
        WHERE marrdate != ''
        GROUP BY century
        ORDER BY century
      ", ARRAY_A);

      // Marriage dates by month
      $marriage_months = $wpdb->get_results("
        SELECT
          CASE
            WHEN marrdate REGEXP 'JAN' THEN 'January'
            WHEN marrdate REGEXP 'FEB' THEN 'February'
            WHEN marrdate REGEXP 'MAR' THEN 'March'
            WHEN marrdate REGEXP 'APR' THEN 'April'
            WHEN marrdate REGEXP 'MAY' THEN 'May'
            WHEN marrdate REGEXP 'JUN' THEN 'June'
            WHEN marrdate REGEXP 'JUL' THEN 'July'
            WHEN marrdate REGEXP 'AUG' THEN 'August'
            WHEN marrdate REGEXP 'SEP' THEN 'September'
            WHEN marrdate REGEXP 'OCT' THEN 'October'
            WHEN marrdate REGEXP 'NOV' THEN 'November'
            WHEN marrdate REGEXP 'DEC' THEN 'December'
            ELSE 'Unknown'
          END as month,
          COUNT(*) as count
        FROM $families_table
        WHERE marrdate != ''
        GROUP BY month
        ORDER BY
          CASE month
            WHEN 'January' THEN 1
            WHEN 'February' THEN 2
            WHEN 'March' THEN 3
            WHEN 'April' THEN 4
            WHEN 'May' THEN 5
            WHEN 'June' THEN 6
            WHEN 'July' THEN 7
            WHEN 'August' THEN 8
            WHEN 'September' THEN 9
            WHEN 'October' THEN 10
            WHEN 'November' THEN 11
            WHEN 'December' THEN 12
            ELSE 13
          END
      ", ARRAY_A);
      ?>

      <div class="analysis-grid">
        <div class="analysis-card">
          <h4><?php _e('Marriages by Century', 'heritagepress'); ?></h4>
          <div class="chart-container">
            <?php if (!empty($marriage_centuries)): ?>
              <?php foreach ($marriage_centuries as $century): ?>
                <div class="chart-bar">
                  <span class="bar-label"><?php echo esc_html($century['century']); ?></span>
                  <div class="bar-graph">
                    <div class="bar-fill" style="width: <?php echo ($century['count'] / max(array_column($marriage_centuries, 'count'))) * 100; ?>%;"></div>
                  </div>
                  <span class="bar-value"><?php echo $century['count']; ?></span>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p><?php _e('No marriage dates available.', 'heritagepress'); ?></p>
            <?php endif; ?>
          </div>
        </div>

        <div class="analysis-card">
          <h4><?php _e('Marriages by Month', 'heritagepress'); ?></h4>
          <div class="chart-container">
            <?php if (!empty($marriage_months)): ?>
              <?php foreach ($marriage_months as $month): ?>
                <div class="chart-bar">
                  <span class="bar-label"><?php echo esc_html($month['month']); ?></span>
                  <div class="bar-graph">
                    <div class="bar-fill" style="width: <?php echo ($month['count'] / max(array_column($marriage_months, 'count'))) * 100; ?>%;"></div>
                  </div>
                  <span class="bar-value"><?php echo $month['count']; ?></span>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p><?php _e('No marriage dates available.', 'heritagepress'); ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Activity -->
  <div class="report-section">
    <h3><?php _e('Recent Family Activity', 'heritagepress'); ?></h3>

    <?php
    $recent_families = $wpdb->get_results("
      SELECT f.familyID, f.gedcom, f.changedate, f.changedby, t.treename,
             p1.firstname as h_firstname, p1.lastname as h_lastname,
             p2.firstname as w_firstname, p2.lastname as w_lastname
      FROM $families_table f
      LEFT JOIN $trees_table t ON f.gedcom = t.gedcom
      LEFT JOIN $people_table p1 ON f.husband = p1.personID AND f.gedcom = p1.gedcom
      LEFT JOIN $people_table p2 ON f.wife = p2.personID AND f.gedcom = p2.gedcom
      ORDER BY f.changedate DESC
      LIMIT 20
    ", ARRAY_A);
    ?>

    <table class="wp-list-table widefat striped">
      <thead>
        <tr>
          <th><?php _e('Family ID', 'heritagepress'); ?></th>
          <th><?php _e('Husband', 'heritagepress'); ?></th>
          <th><?php _e('Wife', 'heritagepress'); ?></th>
          <th><?php _e('Tree', 'heritagepress'); ?></th>
          <th><?php _e('Modified', 'heritagepress'); ?></th>
          <th><?php _e('By', 'heritagepress'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($recent_families)): ?>
          <?php foreach ($recent_families as $family): ?>
            <tr>
              <td>
                <a href="?page=heritagepress-families&tab=edit&familyID=<?php echo urlencode($family['familyID']); ?>&tree=<?php echo urlencode($family['gedcom']); ?>">
                  <?php echo esc_html($family['familyID']); ?>
                </a>
              </td>
              <td><?php echo esc_html($family['h_firstname'] . ' ' . $family['h_lastname']); ?></td>
              <td><?php echo esc_html($family['w_firstname'] . ' ' . $family['w_lastname']); ?></td>
              <td><?php echo esc_html($family['treename']); ?></td>
              <td><?php echo esc_html(date('M j, Y g:i A', strtotime($family['changedate']))); ?></td>
              <td><?php echo esc_html($family['changedby']); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6"><?php _e('No recent activity.', 'heritagepress'); ?></td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<script type="text/javascript">
  function updateReportOptions() {
    var reportType = document.getElementById('report_type').value;
    var optionsRow = document.getElementById('report_options');
    var optionsContent = document.getElementById('options_content');

    if (!reportType) {
      optionsRow.style.display = 'none';
      return;
    }

    var options = '';

    switch (reportType) {
      case 'anniversary_list':
        options = `
        <label>
          <input type="checkbox" name="current_year" value="1" checked>
          <?php _e('Current year only', 'heritagepress'); ?>
        </label><br>
        <label>
          <?php _e('Year:', 'heritagepress'); ?>
          <input type="number" name="anniversary_year" value="<?php echo date('Y'); ?>" min="1800" max="<?php echo date('Y') + 10; ?>">
        </label>
      `;
        break;

      case 'large_families':
        options = `
        <label>
          <?php _e('Minimum children:', 'heritagepress'); ?>
          <input type="number" name="min_children" value="5" min="1" max="20">
        </label>
      `;
        break;

      case 'family_list':
        options = `
        <label>
          <input type="checkbox" name="sort_by_name" value="1" checked>
          <?php _e('Sort by husband name', 'heritagepress'); ?>
        </label><br>
        <label>
          <input type="checkbox" name="include_ids" value="1" checked>
          <?php _e('Include person IDs', 'heritagepress'); ?>
        </label>
      `;
        break;
    }

    if (options) {
      optionsContent.innerHTML = options;
      optionsRow.style.display = 'table-row';
    } else {
      optionsRow.style.display = 'none';
    }
  }
</script>

<style>
  .family-reports-container {
    max-width: 1200px;
    margin: 20px 0;
  }

  .report-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    margin-bottom: 30px;
    padding: 20px;
  }

  .report-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #dcdcde;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
  }

  .stat-card {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    padding: 20px;
    text-align: center;
    border-radius: 4px;
  }

  .stat-card h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #646970;
    font-weight: 600;
  }

  .stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #1d2327;
    line-height: 1;
  }

  .stat-percentage {
    font-size: 14px;
    color: #646970;
    margin-top: 5px;
  }

  .stat-note {
    font-size: 12px;
    color: #646970;
    margin-top: 5px;
  }

  .tree-comparison {
    overflow-x: auto;
  }

  .analysis-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
  }

  .analysis-card {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    padding: 20px;
    border-radius: 4px;
  }

  .analysis-card h4 {
    margin-top: 0;
    margin-bottom: 15px;
  }

  .chart-container {
    max-height: 300px;
    overflow-y: auto;
  }

  .chart-bar {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    gap: 10px;
  }

  .bar-label {
    width: 80px;
    font-size: 12px;
    text-align: right;
    color: #646970;
  }

  .bar-graph {
    flex: 1;
    height: 20px;
    background: #e0e0e0;
    border-radius: 2px;
    overflow: hidden;
  }

  .bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #0073aa, #005177);
    border-radius: 2px;
    transition: width 0.3s ease;
  }

  .bar-value {
    width: 40px;
    font-size: 12px;
    font-weight: 600;
    text-align: left;
    color: #1d2327;
  }

  .form-table th {
    width: 150px;
    padding: 15px 10px 15px 0;
    vertical-align: top;
  }

  .form-table td {
    padding: 15px 0;
    vertical-align: top;
  }
</style>
