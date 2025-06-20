<?php

/**
 * Reports Main View
 * Lists and manages custom reports
 */

if (!defined('ABSPATH')) {
  exit;
}

// Display messages
$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
if ($message) {
  switch ($message) {
    case 'created':
      echo '<div class="notice notice-success"><p>' . __('Report created successfully.', 'heritagepress') . '</p></div>';
      break;
    case 'updated':
      echo '<div class="notice notice-success"><p>' . __('Report updated successfully.', 'heritagepress') . '</p></div>';
      break;
    case 'deleted':
      echo '<div class="notice notice-success"><p>' . __('Report deleted successfully.', 'heritagepress') . '</p></div>';
      break;
  }
}

settings_errors('heritagepress_reports');
?>

<div class="wrap">
  <h1 class="wp-heading-inline"><?php _e('Custom Reports', 'heritagepress'); ?></h1>
  <a href="<?php echo admin_url('admin.php?page=heritagepress-reports&action=add'); ?>" class="page-title-action">
    <?php _e('Add New Report', 'heritagepress'); ?>
  </a>

  <hr class="wp-header-end">

  <!-- Search and Filter -->
  <div class="heritagepress-reports-filters">
    <form method="get" class="search-form">
      <input type="hidden" name="page" value="heritagepress-reports">

      <div class="tablenav top">
        <div class="alignleft actions">
          <input type="search" name="search" value="<?php echo esc_attr($search); ?>"
            placeholder="<?php _e('Search reports...', 'heritagepress'); ?>" />

          <label class="screen-reader-text" for="active_only"><?php _e('Active Only', 'heritagepress'); ?></label>
          <select name="active_only" id="active_only">
            <option value="0" <?php selected($active_only, 0); ?>><?php _e('All Reports', 'heritagepress'); ?></option>
            <option value="1" <?php selected($active_only, 1); ?>><?php _e('Active Only', 'heritagepress'); ?></option>
          </select>

          <input type="submit" class="button" value="<?php _e('Filter', 'heritagepress'); ?>" />
        </div>
      </div>
    </form>
  </div>

  <!-- Reports Table -->
  <div class="heritagepress-reports-table">
    <?php if (!empty($reports)): ?>
      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th scope="col" class="manage-column"><?php _e('Report Name', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column"><?php _e('Description', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column"><?php _e('Ranking', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column"><?php _e('Status', 'heritagepress'); ?></th>
            <th scope="col" class="manage-column"><?php _e('Actions', 'heritagepress'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reports as $report): ?>
            <tr>
              <td class="report-name">
                <strong>
                  <a href="<?php echo admin_url('admin.php?page=heritagepress-reports&action=edit&reportID=' . $report['reportID']); ?>">
                    <?php echo esc_html($report['reportname']); ?>
                  </a>
                </strong>
              </td>
              <td class="report-description">
                <?php echo esc_html(wp_trim_words($report['reportdesc'], 20)); ?>
              </td>
              <td class="report-ranking">
                <?php echo esc_html($report['ranking']); ?>
              </td>
              <td class="report-status">
                <?php if ($report['active']): ?>
                  <span class="status-active"><?php _e('Active', 'heritagepress'); ?></span>
                <?php else: ?>
                  <span class="status-inactive"><?php _e('Inactive', 'heritagepress'); ?></span>
                <?php endif; ?>
              </td>
              <td class="report-actions">
                <?php if ($report['active']): ?>
                  <a href="<?php echo admin_url('admin.php?page=heritagepress-reports&action=run&reportID=' . $report['reportID']); ?>"
                    class="button button-small">
                    <?php _e('Run', 'heritagepress'); ?>
                  </a>
                <?php endif; ?>

                <a href="<?php echo admin_url('admin.php?page=heritagepress-reports&action=edit&reportID=' . $report['reportID']); ?>"
                  class="button button-small">
                  <?php _e('Edit', 'heritagepress'); ?>
                </a>

                <form method="post" style="display: inline;"
                  onsubmit="return confirm('<?php _e('Are you sure you want to delete this report?', 'heritagepress'); ?>')">
                  <?php wp_nonce_field('heritagepress_report_delete'); ?>
                  <input type="hidden" name="reportID" value="<?php echo esc_attr($report['reportID']); ?>">
                  <input type="submit" name="delete_report" class="button button-small button-link-delete"
                    value="<?php _e('Delete', 'heritagepress'); ?>">
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="heritagepress-no-reports">
        <h3><?php _e('No Custom Reports Found', 'heritagepress'); ?></h3>
        <p><?php _e('Create your first custom report to analyze your genealogy data with flexible queries and criteria.', 'heritagepress'); ?></p>
        <a href="<?php echo admin_url('admin.php?page=heritagepress-reports&action=add'); ?>" class="button button-primary">
          <?php _e('Create Your First Report', 'heritagepress'); ?>
        </a>
      </div>
    <?php endif; ?>
  </div>

  <!-- Help Section -->
  <div class="heritagepress-reports-help">
    <h3><?php _e('About Custom Reports', 'heritagepress'); ?></h3>
    <p><?php _e('Custom reports allow you to create flexible queries to analyze your genealogy data. You can:', 'heritagepress'); ?></p>
    <ul>
      <li><?php _e('Select which fields to display in your report', 'heritagepress'); ?></li>
      <li><?php _e('Build complex criteria to filter your data', 'heritagepress'); ?></li>
      <li><?php _e('Define custom sort orders', 'heritagepress'); ?></li>
      <li><?php _e('Write advanced SQL queries for complex analysis', 'heritagepress'); ?></li>
    </ul>

    <h4><?php _e('Quick Actions', 'heritagepress'); ?></h4>
    <p>
      <a href="<?php echo admin_url('admin.php?page=heritagepress-people&tab=reports'); ?>" class="button button-secondary">
        <?php _e('People Reports', 'heritagepress'); ?>
      </a>
      <a href="<?php echo admin_url('admin.php?page=heritagepress-families&tab=reports'); ?>" class="button button-secondary">
        <?php _e('Family Reports', 'heritagepress'); ?>
      </a>
    </p>
  </div>
</div>

<style>
  .heritagepress-reports-filters {
    background: #fff;
    border: 1px solid #c3c4c7;
    margin: 20px 0;
    padding: 15px;
  }

  .heritagepress-reports-filters .search-form {
    margin: 0;
  }

  .heritagepress-reports-table {
    background: #fff;
  }

  .heritagepress-no-reports {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 40px;
    text-align: center;
  }

  .heritagepress-no-reports h3 {
    margin-top: 0;
  }

  .heritagepress-reports-help {
    background: #fff;
    border: 1px solid #c3c4c7;
    margin: 20px 0;
    padding: 20px;
  }

  .heritagepress-reports-help h3 {
    margin-top: 0;
  }

  .heritagepress-reports-help ul {
    padding-left: 20px;
  }

  .status-active {
    color: #00a32a;
    font-weight: 600;
  }

  .status-inactive {
    color: #646970;
    font-weight: 600;
  }

  .report-actions form {
    margin: 0;
  }

  .report-actions .button {
    margin-right: 5px;
  }

  .tablenav .alignleft.actions {
    float: left;
  }

  .tablenav .alignleft.actions>* {
    margin-right: 10px;
  }

  .tablenav .alignleft.actions input[type="search"] {
    width: 200px;
  }
</style>
