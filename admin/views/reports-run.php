<?php

/**
 * Reports Run View
 * Displays report execution results
 */

if (!defined('ABSPATH')) {
  exit;
}

// Display messages
$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
if ($message) {
  switch ($message) {
    case 'error':
      echo '<div class="notice notice-error"><p>' . __('Error running report.', 'heritagepress') . '</p></div>';
      break;
  }
}

?>

<div class="wrap">
  <h1>
    <?php echo esc_html($report['reportname']); ?>
    <a href="<?php echo admin_url('admin.php?page=hp-reports'); ?>" class="page-title-action">
      <?php _e('Back to Reports', 'heritagepress'); ?>
    </a>
  </h1>

  <?php if (!empty($report['reportdesc'])): ?>
    <p class="description"><?php echo esc_html($report['reportdesc']); ?></p>
  <?php endif; ?>

  <div class="tablenav top">
    <div class="alignleft actions">
      <a href="<?php echo admin_url('admin.php?page=hp-reports-edit&reportID=' . $report['reportID']); ?>"
        class="button">
        <?php _e('Edit Report', 'heritagepress'); ?>
      </a>
    </div>

    <div class="alignright actions">
      <form method="get" style="display: inline;">
        <input type="hidden" name="page" value="hp-reports-run" />
        <input type="hidden" name="reportID" value="<?php echo esc_attr($report['reportID']); ?>" />
        <input type="hidden" name="export" value="csv" />
        <input type="submit" class="button" value="<?php _e('Export CSV', 'heritagepress'); ?>" />
      </form>
    </div>
  </div>

  <?php if (!empty($results)): ?>
    <div class="hp-report-results">
      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <?php foreach ($results[0] as $column => $value): ?>
              <th scope="col"><?php echo esc_html(ucfirst(str_replace('_', ' ', $column))); ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $row): ?>
            <tr>
              <?php foreach ($row as $column => $value): ?>
                <td>
                  <?php
                  // Handle special formatting for different field types
                  if (in_array($column, ['birthdate', 'deathdate', 'altbirthdate', 'altdeathdate'])) {
                    echo esc_html($value ? date('Y-m-d', strtotime($value)) : '');
                  } elseif ($column === 'personID') {
                    // Link to person view if available
                    echo '<a href="' . admin_url('admin.php?page=heritagepress&personID=' . $value) . '">' . esc_html($value) . '</a>';
                  } elseif ($column === 'familyID') {
                    // Link to family view if available
                    echo '<a href="' . admin_url('admin.php?page=hp-families&familyID=' . $value) . '">' . esc_html($value) . '</a>';
                  } else {
                    echo esc_html($value);
                  }
                  ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="tablenav bottom">
      <div class="tablenav-pages">
        <span class="displaying-num">
          <?php printf(__('%d results found', 'heritagepress'), count($results)); ?>
        </span>
      </div>
    </div>

  <?php else: ?>
    <div class="notice notice-info">
      <p><?php _e('No results found for this report.', 'heritagepress'); ?></p>
    </div>
  <?php endif; ?>

</div>

<style>
  .hp-report-results {
    margin: 20px 0;
  }

  .hp-report-results table {
    border-collapse: collapse;
    width: 100%;
  }

  .hp-report-results th,
  .hp-report-results td {
    padding: 8px 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
  }

  .hp-report-results th {
    background-color: #f9f9f9;
    font-weight: 600;
  }

  .hp-report-results tr:hover {
    background-color: #f5f5f5;
  }

  .hp-report-results a {
    color: #0073aa;
    text-decoration: none;
  }

  .hp-report-results a:hover {
    color: #005177;
    text-decoration: underline;
  }
</style>
