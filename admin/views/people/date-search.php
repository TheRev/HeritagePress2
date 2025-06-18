<?php

/**
 * Enhanced Date Search Feature
 * Advanced date-based searching using the dual storage system
 */

if (!defined('ABSPATH')) {
  exit;
}

// Include date utilities
require_once __DIR__ . '/../../helpers/class-hp-date-utils.php';

global $wpdb;

// Get search parameters
$search_params = array(
  'date_type' => isset($_GET['date_type']) ? sanitize_text_field($_GET['date_type']) : 'birth',
  'date_range' => isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '',
  'tree' => isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '',
  'output_format' => isset($_GET['output_format']) ? sanitize_text_field($_GET['output_format']) : 'table'
);

// Get available trees
$trees_table = $wpdb->prefix . 'hp_trees';
$trees_query = "SELECT gedcom, treename FROM $trees_table ORDER BY treename";
$trees_result = $wpdb->get_results($trees_query, 'ARRAY_A');

$search_results = array();
$total_found = 0;

// Perform search if parameters provided
if (!empty($search_params['date_range'])) {
  $people_table = $wpdb->prefix . 'hp_people';

  $date_range = HP_Date_Utils::parse_date_range_input($search_params['date_range']);

  if (!empty($date_range['start']) || !empty($date_range['end'])) {
    $search_results = HP_Date_Utils::get_people_by_date_range(
      $wpdb,
      $people_table,
      $search_params['date_type'],
      $date_range['start'],
      $date_range['end'],
      $search_params['tree'],
      200
    );
    $total_found = count($search_results);
  }
}
?>

<div class="wrap">
  <h1 class="wp-heading-inline"><?php _e('Enhanced Date Search', 'heritagepress'); ?></h1>
  <hr class="wp-header-end">

  <!-- Search Form -->
  <div class="hp-search-form-container">
    <form method="get" action="" class="hp-date-search-form">
      <input type="hidden" name="page" value="heritagepress-people" />
      <input type="hidden" name="tab" value="date-search" />

      <div class="hp-search-fields">
        <div class="hp-field-group">
          <label for="date_type"><?php _e('Date Type:', 'heritagepress'); ?></label>
          <select id="date_type" name="date_type">
            <option value="birth" <?php selected($search_params['date_type'], 'birth'); ?>><?php _e('Birth Date', 'heritagepress'); ?></option>
            <option value="death" <?php selected($search_params['date_type'], 'death'); ?>><?php _e('Death Date', 'heritagepress'); ?></option>
            <option value="burial" <?php selected($search_params['date_type'], 'burial'); ?>><?php _e('Burial Date', 'heritagepress'); ?></option>
            <option value="bapt" <?php selected($search_params['date_type'], 'bapt'); ?>><?php _e('Baptism Date', 'heritagepress'); ?></option>
          </select>
        </div>

        <div class="hp-field-group">
          <label for="date_range"><?php _e('Date Range:', 'heritagepress'); ?></label>
          <input type="text" id="date_range" name="date_range" value="<?php echo esc_attr($search_params['date_range']); ?>"
            placeholder="<?php _e('e.g., 1850, 1800-1900, after 1920, before 1850', 'heritagepress'); ?>"
            class="regular-text" />
          <small class="description">
            <?php _e('Formats: YYYY, YYYY-YYYY, "before YYYY", "after YYYY", YYYY-MM-DD', 'heritagepress'); ?>
          </small>
        </div>

        <div class="hp-field-group">
          <label for="tree"><?php _e('Tree:', 'heritagepress'); ?></label>
          <select id="tree" name="tree">
            <option value=""><?php _e('All Trees', 'heritagepress'); ?></option>
            <?php foreach ($trees_result as $tree): ?>
              <option value="<?php echo esc_attr($tree['gedcom']); ?>" <?php selected($search_params['tree'], $tree['gedcom']); ?>>
                <?php echo esc_html($tree['treename']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="hp-field-group">
          <label for="output_format"><?php _e('Output:', 'heritagepress'); ?></label>
          <select id="output_format" name="output_format">
            <option value="table" <?php selected($search_params['output_format'], 'table'); ?>><?php _e('Table View', 'heritagepress'); ?></option>
            <option value="timeline" <?php selected($search_params['output_format'], 'timeline'); ?>><?php _e('Timeline View', 'heritagepress'); ?></option>
            <option value="chart" <?php selected($search_params['output_format'], 'chart'); ?>><?php _e('Chart View', 'heritagepress'); ?></option>
          </select>
        </div>
      </div>

      <div class="hp-search-actions">
        <input type="submit" class="button button-primary" value="<?php _e('Search', 'heritagepress'); ?>" />
        <a href="?page=heritagepress-people&tab=date-search" class="button"><?php _e('Clear', 'heritagepress'); ?></a>
      </div>
    </form>
  </div>

  <!-- Search Results -->
  <?php if (!empty($search_params['date_range'])): ?>
    <div class="hp-search-results">
      <h2><?php _e('Search Results', 'heritagepress'); ?></h2>

      <?php if ($total_found > 0): ?>
        <p class="hp-results-count">
          <?php printf(_n('Found %d person', 'Found %d people', $total_found, 'heritagepress'), $total_found); ?>
          <?php if (!empty($search_params['tree'])): ?>
            <?php $tree_name = ''; ?>
            <?php foreach ($trees_result as $tree): ?>
              <?php if ($tree['gedcom'] === $search_params['tree']): ?>
                <?php $tree_name = $tree['treename'];
                break; ?>
              <?php endif; ?>
            <?php endforeach; ?>
            <?php printf(__('in %s', 'heritagepress'), esc_html($tree_name)); ?>
          <?php endif; ?>
          <?php printf(
            __('with %s dates %s', 'heritagepress'),
            esc_html($search_params['date_type']),
            esc_html($search_params['date_range'])
          ); ?>
        </p>

        <?php if ($search_params['output_format'] === 'table'): ?>
          <!-- Table View -->
          <table class="wp-list-table widefat fixed striped">
            <thead>
              <tr>
                <th><?php _e('Person ID', 'heritagepress'); ?></th>
                <th><?php _e('Name', 'heritagepress'); ?></th>
                <th><?php printf(__('%s Date', 'heritagepress'), ucfirst($search_params['date_type'])); ?></th>
                <th><?php _e('Tree', 'heritagepress'); ?></th>
                <th><?php _e('Status', 'heritagepress'); ?></th>
                <th><?php _e('Actions', 'heritagepress'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($search_results as $person): ?>
                <tr>
                  <td><?php echo esc_html($person['personID']); ?></td>
                  <td>
                    <strong><?php echo esc_html($person['firstname'] . ' ' . $person['lastname']); ?></strong>
                  </td>
                  <td>
                    <?php
                    $display_date = HP_Date_Utils::format_display_date($person, $search_params['date_type']);
                    echo $display_date ?: '<em>' . __('No date', 'heritagepress') . '</em>';
                    ?>
                  </td>
                  <td><?php echo esc_html($person['gedcom']); ?></td>
                  <td>
                    <?php if ($person['living'] == 1): ?>
                      <span class="status-living"><?php _e('Living', 'heritagepress'); ?></span>
                    <?php endif; ?>
                    <?php if ($person['private'] == 1): ?>
                      <span class="status-private"><?php _e('Private', 'heritagepress'); ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="?page=heritagepress-people&tab=edit-person&id=<?php echo esc_attr($person['personID']); ?>&gedcom=<?php echo esc_attr($person['gedcom']); ?>" class="button button-small">
                      <?php _e('Edit', 'heritagepress'); ?>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

        <?php elseif ($search_params['output_format'] === 'timeline'): ?>
          <!-- Timeline View -->
          <div class="hp-timeline-view">
            <div class="timeline-container">
              <?php
              // Sort by sortable date for timeline
              $date_field = $search_params['date_type'] . 'datetr';
              usort($search_results, function ($a, $b) use ($date_field) {
                return strcmp($a[$date_field], $b[$date_field]);
              });
              ?>

              <?php foreach ($search_results as $person): ?>
                <?php $sortable_date = HP_Date_Utils::get_sortable_date($person, $search_params['date_type']); ?>
                <?php if (!empty($sortable_date)): ?>
                  <div class="timeline-item" data-date="<?php echo esc_attr($sortable_date); ?>">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                      <div class="timeline-date"><?php echo esc_html(HP_Date_Utils::extract_year($sortable_date)); ?></div>
                      <div class="timeline-person">
                        <strong><?php echo esc_html($person['firstname'] . ' ' . $person['lastname']); ?></strong>
                        <small>(<?php echo esc_html($person['personID']); ?>)</small>
                      </div>
                      <div class="timeline-event">
                        <?php echo HP_Date_Utils::format_display_date($person, $search_params['date_type']); ?>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </div>

        <?php elseif ($search_params['output_format'] === 'chart'): ?>
          <!-- Chart View -->
          <div class="hp-chart-view">
            <canvas id="dateChart" width="400" height="200"></canvas>
            <script>
              // Simple chart using Chart.js (would need to be included)
              document.addEventListener('DOMContentLoaded', function() {
                var chartData = <?php echo json_encode(array_map(function ($person) use ($search_params) {
                                  return [
                                    'year' => HP_Date_Utils::extract_year(HP_Date_Utils::get_sortable_date($person, $search_params['date_type'])),
                                    'name' => $person['firstname'] . ' ' . $person['lastname']
                                  ];
                                }, $search_results)); ?>;

                // Chart implementation would go here
                console.log('Chart data:', chartData);
              });
            </script>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <div class="notice notice-info">
          <p><?php _e('No people found matching your date search criteria.', 'heritagepress'); ?></p>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<style>
  .hp-search-form-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin: 20px 0;
  }

  .hp-search-fields {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
  }

  .hp-field-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
  }

  .hp-field-group select,
  .hp-field-group input[type="text"] {
    width: 100%;
  }

  .hp-timeline-view {
    margin-top: 20px;
  }

  .timeline-container {
    position: relative;
    padding-left: 30px;
  }

  .timeline-container::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #ddd;
  }

  .timeline-item {
    position: relative;
    margin-bottom: 30px;
    padding-left: 20px;
  }

  .timeline-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #0073aa;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #ddd;
  }

  .timeline-content {
    background: #fff;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 4px;
  }

  .timeline-date {
    font-size: 14px;
    font-weight: 600;
    color: #0073aa;
    margin-bottom: 5px;
  }

  .timeline-person {
    margin-bottom: 5px;
  }

  .timeline-event {
    color: #666;
    font-style: italic;
  }

  .status-living,
  .status-private {
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 3px;
    margin-left: 5px;
  }

  .status-living {
    background: #d4edda;
    color: #155724;
  }

  .status-private {
    background: #f8d7da;
    color: #721c24;
  }
</style>
?>
