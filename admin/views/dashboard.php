<?php

/**
 * Admin Dashboard View
 */

if (!defined('ABSPATH')) {
  exit;
}
?>

<div class="wrap">
  <h1><?php _e('HeritagePress Dashboard', 'heritagepress'); ?></h1>

  <div class="heritagepress-dashboard">
    <div class="dashboard-widgets-wrap">
      <div class="dashboard-widgets">

        <!-- Database Status Widget -->
        <div class="postbox">
          <div class="postbox-header">
            <h2 class="hndle"><?php _e('Database Status', 'heritagepress'); ?></h2>
          </div>
          <div class="inside">
            <?php if ($database->tables_exist()): ?>
              <p class="status-good">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Database tables are installed and ready.', 'heritagepress'); ?>
              </p>

              <h4><?php _e('Database Version', 'heritagepress'); ?></h4>
              <p><?php echo esc_html($database->get_db_version()); ?></p>

              <?php if ($database->needs_update()): ?>
                <p class="status-warning">
                  <span class="dashicons dashicons-warning"></span>
                  <?php _e('Database needs updating.', 'heritagepress'); ?>
                  <a href="<?php echo admin_url('admin.php?page=heritagepress-tables'); ?>" class="button button-secondary">
                    <?php _e('Update Now', 'heritagepress'); ?>
                  </a>
                </p>
              <?php endif; ?>
            <?php else: ?>
              <p class="status-error">
                <span class="dashicons dashicons-warning"></span>
                <?php _e('Database tables are not installed.', 'heritagepress'); ?>
                <a href="<?php echo admin_url('admin.php?page=heritagepress-tables'); ?>" class="button button-primary">
                  <?php _e('Install Tables', 'heritagepress'); ?>
                </a>
              </p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Statistics Widget -->
        <div class="postbox">
          <div class="postbox-header">
            <h2 class="hndle"><?php _e('Statistics', 'heritagepress'); ?></h2>
          </div>
          <div class="inside">
            <?php if (!empty($table_counts)): ?>
              <table class="widefat">
                <thead>
                  <tr>
                    <th><?php _e('Table', 'heritagepress'); ?></th>
                    <th><?php _e('Records', 'heritagepress'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($table_counts as $table => $count): ?>
                    <tr>
                      <td><?php echo esc_html(ucfirst($table)); ?></td>
                      <td><?php echo number_format($count); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else: ?>
              <p><?php _e('No data available. Import some genealogy data to see statistics.', 'heritagepress'); ?></p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Quick Actions Widget -->
        <div class="postbox">
          <div class="postbox-header">
            <h2 class="hndle"><?php _e('Quick Actions', 'heritagepress'); ?></h2>
          </div>
          <div class="inside">
            <p>
              <a href="<?php echo admin_url('admin.php?page=heritagepress-people'); ?>" class="button button-secondary">
                <span class="dashicons dashicons-groups"></span>
                <?php _e('Manage People', 'heritagepress'); ?>
              </a>
            </p>
            <p>
              <a href="<?php echo admin_url('admin.php?page=heritagepress-import'); ?>" class="button button-secondary">
                <span class="dashicons dashicons-upload"></span>
                <?php _e('Import GEDCOM', 'heritagepress'); ?>
              </a>
            </p>
            <p>
              <a href="<?php echo admin_url('admin.php?page=heritagepress-tables'); ?>" class="button button-secondary">
                <span class="dashicons dashicons-database"></span>
                <?php _e('Database Tables', 'heritagepress'); ?>
              </a>
            </p>
          </div>
        </div>

        <!-- System Info Widget -->
        <div class="postbox">
          <div class="postbox-header">
            <h2 class="hndle"><?php _e('System Information', 'heritagepress'); ?></h2>
          </div>
          <div class="inside">
            <table class="widefat">
              <tbody>
                <tr>
                  <td><strong><?php _e('Plugin Version', 'heritagepress'); ?></strong></td>
                  <td><?php echo HERITAGEPRESS_VERSION; ?></td>
                </tr>
                <tr>
                  <td><strong><?php _e('WordPress Version', 'heritagepress'); ?></strong></td>
                  <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                  <td><strong><?php _e('PHP Version', 'heritagepress'); ?></strong></td>
                  <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                  <td><strong><?php _e('Database Type', 'heritagepress'); ?></strong></td>
                  <td><?php echo $GLOBALS['wpdb']->db_version(); ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<style>
  .heritagepress-dashboard .postbox {
    margin-bottom: 20px;
  }

  .status-good {
    color: #46b450;
  }

  .status-warning {
    color: #ffb900;
  }

  .status-error {
    color: #dc3232;
  }

  .status-good .dashicons,
  .status-warning .dashicons,
  .status-error .dashicons {
    margin-right: 5px;
  }

  .dashboard-widgets {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }

  @media (max-width: 782px) {
    .dashboard-widgets {
      grid-template-columns: 1fr;
    }
  }
</style>
