<?php

/**
 * HeritagePress Migration Manager Admin Page
 *
 * Provides an admin interface for running database migrations
 */

if (!defined('ABSPATH')) {
  exit;
}

// Handle migration actions
if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'hp_migration_action')) {

  $action = sanitize_text_field($_POST['action']);
  $migration_result = null;

  switch ($action) {
    case 'run_tree_date_created':
      require_once(HERITAGEPRESS_PLUGIN_DIR . 'migrations/add-tree-date-created.php');
      $migration = new HP_Migration_Add_Tree_Date_Created();
      $migration_result = $migration->run();
      break;

    case 'rollback_tree_date_created':
      require_once(HERITAGEPRESS_PLUGIN_DIR . 'migrations/add-tree-date-created.php');
      $migration = new HP_Migration_Add_Tree_Date_Created();
      $migration_result = $migration->rollback();
      break;
  }
}
?>

<div class="wrap">
  <h1><?php _e('HeritagePress Database Migrations', 'heritagepress'); ?></h1>

  <?php if (isset($migration_result)): ?>
    <div class="notice <?php echo $migration_result['success'] ? 'notice-success' : 'notice-error'; ?> is-dismissible">
      <p><?php echo esc_html($migration_result['message']); ?></p>
    </div>
  <?php endif; ?>

  <div class="card">
    <h2><?php _e('Available Migrations', 'heritagepress'); ?></h2>

    <div class="migration-item" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px;">
      <h3><?php _e('Tree Date Created Field', 'heritagepress'); ?></h3>
      <p><?php _e('Adds a dedicated date_created field to the hp_trees table to track when trees were actually created, separate from import dates.', 'heritagepress'); ?></p>

      <?php
      // Check if migration has been run
      require_once(HERITAGEPRESS_PLUGIN_DIR . 'migrations/add-tree-date-created.php');
      $migration = new HP_Migration_Add_Tree_Date_Created();
      $table_info = $migration->get_table_info();
      $has_date_created = false;
      foreach ($table_info['columns'] as $column) {
        if ($column->Field === 'date_created') {
          $has_date_created = true;
          break;
        }
      }
      ?>

      <p><strong><?php _e('Status:', 'heritagepress'); ?></strong>
        <?php if ($has_date_created): ?>
          <span style="color: green;"><?php _e('Applied ✓', 'heritagepress'); ?></span>
        <?php else: ?>
          <span style="color: orange;"><?php _e('Not Applied', 'heritagepress'); ?></span>
        <?php endif; ?>
      </p>

      <div class="migration-actions">
        <?php if (!$has_date_created): ?>
          <form method="post" style="display: inline;">
            <?php wp_nonce_field('hp_migration_action'); ?>
            <input type="hidden" name="action" value="run_tree_date_created">
            <input type="submit" class="button button-primary" value="<?php _e('Run Migration', 'heritagepress'); ?>" onclick="return confirm('<?php _e('Are you sure you want to run this migration?', 'heritagepress'); ?>')">
          </form>
        <?php else: ?>
          <form method="post" style="display: inline;">
            <?php wp_nonce_field('hp_migration_action'); ?>
            <input type="hidden" name="action" value="rollback_tree_date_created">
            <input type="submit" class="button button-secondary" value="<?php _e('Rollback Migration', 'heritagepress'); ?>" onclick="return confirm('<?php _e('Are you sure you want to rollback this migration? This will remove the date_created column!', 'heritagepress'); ?>')">
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <h2><?php _e('Current Tree Table Structure', 'heritagepress'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th><?php _e('Column Name', 'heritagepress'); ?></th>
          <th><?php _e('Type', 'heritagepress'); ?></th>
          <th><?php _e('Null', 'heritagepress'); ?></th>
          <th><?php _e('Default', 'heritagepress'); ?></th>
          <th><?php _e('Extra', 'heritagepress'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($table_info['columns'] as $column): ?>
          <tr <?php if ($column->Field === 'date_created') echo 'style="background-color: #fffbcc;"'; ?>>
            <td><strong><?php echo esc_html($column->Field); ?></strong></td>
            <td><?php echo esc_html($column->Type); ?></td>
            <td><?php echo esc_html($column->Null); ?></td>
            <td><?php echo esc_html($column->Default); ?></td>
            <td><?php echo esc_html($column->Extra); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h3><?php _e('Table Indexes', 'heritagepress'); ?></h3>
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th><?php _e('Key Name', 'heritagepress'); ?></th>
          <th><?php _e('Column', 'heritagepress'); ?></th>
          <th><?php _e('Unique', 'heritagepress'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($table_info['indexes'] as $index): ?>
          <tr <?php if ($index->Key_name === 'idx_date_created') echo 'style="background-color: #fffbcc;"'; ?>>
            <td><strong><?php echo esc_html($index->Key_name); ?></strong></td>
            <td><?php echo esc_html($index->Column_name); ?></td>
            <td><?php echo $index->Non_unique ? 'No' : 'Yes'; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
  .migration-item h3 {
    margin-top: 0;
  }

  .migration-actions {
    margin-top: 10px;
  }
</style>
