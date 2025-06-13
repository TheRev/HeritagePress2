<?php

/**
 * Admin Tables Management View
 */

if (!defined('ABSPATH')) {
  exit;
}

// Display settings errors/success messages
settings_errors('heritagepress_tables');
?>

<div class="wrap">
  <h1><?php _e('Database Tables', 'heritagepress'); ?></h1>

  <div class="heritagepress-tables">

    <!-- Table Status -->
    <div class="postbox">
      <div class="postbox-header">
        <h2 class="hndle"><?php _e('Table Status', 'heritagepress'); ?></h2>
      </div>
      <div class="inside">
        <?php if ($tables_exist): ?>
          <p class="status-good">
            <span class="dashicons dashicons-yes-alt"></span>
            <?php _e('All database tables are installed.', 'heritagepress'); ?>
          </p>

          <h4><?php _e('Available Actions:', 'heritagepress'); ?></h4>
          <form method="post" style="display: inline;">
            <?php wp_nonce_field('heritagepress_tables'); ?>
            <input type="hidden" name="action" value="update_tables">
            <input type="submit" class="button button-secondary"
              value="<?php _e('Update Tables', 'heritagepress'); ?>"
              onclick="return confirm('<?php _e('Are you sure you want to update the database tables?', 'heritagepress'); ?>');">
          </form>

          <form method="post" style="display: inline; margin-left: 10px;">
            <?php wp_nonce_field('heritagepress_tables'); ?>
            <input type="hidden" name="action" value="drop_tables">
            <input type="submit" class="button button-secondary"
              value="<?php _e('Drop Tables', 'heritagepress'); ?>"
              onclick="return confirm('<?php _e('WARNING: This will delete ALL genealogy data! Are you absolutely sure?', 'heritagepress'); ?>');">
          </form>

        <?php else: ?>
          <p class="status-error">
            <span class="dashicons dashicons-warning"></span>
            <?php _e('Database tables are not installed.', 'heritagepress'); ?>
          </p>

          <form method="post">
            <?php wp_nonce_field('heritagepress_tables'); ?>
            <input type="hidden" name="action" value="create_tables">
            <input type="submit" class="button button-primary"
              value="<?php _e('Create Tables', 'heritagepress'); ?>">
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Table Statistics -->
    <?php if ($tables_exist && !empty($stats)): ?>
      <div class="postbox">
        <div class="postbox-header">
          <h2 class="hndle"><?php _e('Table Statistics', 'heritagepress'); ?></h2>
        </div>
        <div class="inside">
          <table class="widefat striped">
            <thead>
              <tr>
                <th><?php _e('Category', 'heritagepress'); ?></th>
                <th><?php _e('Table', 'heritagepress'); ?></th>
                <th><?php _e('Records', 'heritagepress'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php
              $categories = array(
                'Core Genealogy' => array('persons', 'families', 'children', 'events', 'eventtypes', 'temp_events', 'timeline_events'),
                'Sources & Research' => array('sources', 'citations', 'repositories', 'notes', 'xnotes', 'notelinks', 'mostwanted', 'associations', 'reports', 'templates'),
                'Media & Albums' => array('media', 'medialinks', 'mediatypes', 'albums', 'albumlinks', 'album2entities', 'image_tags'),
                'Geography' => array('places', 'addresses', 'countries', 'states', 'cemeteries'),
                'System' => array('trees', 'user_permissions', 'import_logs', 'saveimport', 'branches', 'branchlinks', 'languages', 'dna_tests', 'dna_links', 'dna_groups', 'users')
              );

              foreach ($categories as $category => $tables):
                $first_in_category = true;
                foreach ($tables as $table):
                  $count = isset($stats[$table]) ? $stats[$table] : 0;
              ?>
                  <tr>
                    <td><?php echo $first_in_category ? esc_html($category) : ''; ?></td>
                    <td><?php echo esc_html($table); ?></td>
                    <td><?php echo number_format($count); ?></td>
                  </tr>
              <?php
                  $first_in_category = false;
                endforeach;
              endforeach;
              ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <!-- Technical Information -->
    <div class="postbox">
      <div class="postbox-header">
        <h2 class="hndle"><?php _e('Technical Information', 'heritagepress'); ?></h2>
      </div>
      <div class="inside">
        <h4><?php _e('Database Architecture', 'heritagepress'); ?></h4>
        <p><?php _e('HeritagePress uses a modular database architecture with 5 specialized categories:', 'heritagepress'); ?></p>
        <ul>
          <li><strong><?php _e('Core Genealogy:', 'heritagepress'); ?></strong> <?php _e('Essential genealogy data (people, families, events)', 'heritagepress'); ?></li>
          <li><strong><?php _e('Sources & Research:', 'heritagepress'); ?></strong> <?php _e('Documentation and research management', 'heritagepress'); ?></li>
          <li><strong><?php _e('Media & Albums:', 'heritagepress'); ?></strong> <?php _e('Photo and document management', 'heritagepress'); ?></li>
          <li><strong><?php _e('Geography:', 'heritagepress'); ?></strong> <?php _e('Places, addresses, and locations', 'heritagepress'); ?></li>
          <li><strong><?php _e('System:', 'heritagepress'); ?></strong> <?php _e('Configuration, permissions, and advanced features', 'heritagepress'); ?></li>
        </ul>

        <h4><?php _e('TNG Compatibility', 'heritagepress'); ?></h4>
        <p><?php _e('All 37 TNG database tables are implemented for 100% feature parity.', 'heritagepress'); ?></p>

        <h4><?php _e('Table Prefix', 'heritagepress'); ?></h4>
        <p><?php echo sprintf(__('All tables use the prefix: %s', 'heritagepress'), '<code>' . $database->get_table_name('') . '</code>'); ?></p>
      </div>
    </div>

  </div>
</div>

<style>
  .heritagepress-tables .postbox {
    margin-bottom: 20px;
  }

  .status-good {
    color: #46b450;
  }

  .status-error {
    color: #dc3232;
  }

  .status-good .dashicons,
  .status-error .dashicons {
    margin-right: 5px;
  }
</style>
