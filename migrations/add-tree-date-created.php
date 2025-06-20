<?php

/**
 * Migration: Add date_created field to hp_trees table
 *
 * This migration adds a dedicated date_created field to track when trees were actually created,
 * rather than relying on lastimportdate which tracks when data was last imadapted.
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Migration_Add_Tree_Date_Created
{

  private $wpdb;
  private $table_name;

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->table_name = $wpdb->prefix . 'hp_trees';
  }

  /**
   * Run the migration
   */
  public function run()
  {
    try {
      // Check if the column already exists
      if ($this->column_exists()) {
        return array(
          'success' => true,
          'message' => 'Column date_created already exists in hp_trees table'
        );
      }

      // Add the new column
      $sql = "ALTER TABLE `{$this->table_name}`
                    ADD COLUMN `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    AFTER `importfilename`";

      $result = $this->wpdb->query($sql);

      if ($result === false) {
        throw new Exception('Failed to add date_created column: ' . $this->wpdb->last_error);
      }

      // Update existing records - set date_created to lastimportdate or current time
      $update_sql = "UPDATE `{$this->table_name}`
                          SET `date_created` = CASE
                              WHEN `lastimportdate` != '0000-00-00 00:00:00' AND `lastimportdate` IS NOT NULL
                              THEN `lastimportdate`
                              ELSE NOW()
                          END";

      $update_result = $this->wpdb->query($update_sql);

      if ($update_result === false) {
        throw new Exception('Failed to update existing records: ' . $this->wpdb->last_error);
      }

      // Add an index for better performance
      $index_sql = "ALTER TABLE `{$this->table_name}` ADD INDEX `idx_date_created` (`date_created`)";
      $this->wpdb->query($index_sql); // Non-critical, so don't throw on failure

      return array(
        'success' => true,
        'message' => "Successfully added date_created column to hp_trees table. Updated {$update_result} existing records."
      );
    } catch (Exception $e) {
      return array(
        'success' => false,
        'message' => 'Migration failed: ' . $e->getMessage()
      );
    }
  }

  /**
   * Rollback the migration
   */
  public function rollback()
  {
    try {
      // Check if the column exists
      if (!$this->column_exists()) {
        return array(
          'success' => true,
          'message' => 'Column date_created does not exist in hp_trees table'
        );
      }

      // Drop the index first
      $drop_index_sql = "ALTER TABLE `{$this->table_name}` DROP INDEX IF EXISTS `idx_date_created`";
      $this->wpdb->query($drop_index_sql);

      // Drop the column
      $sql = "ALTER TABLE `{$this->table_name}` DROP COLUMN `date_created`";
      $result = $this->wpdb->query($sql);

      if ($result === false) {
        throw new Exception('Failed to drop date_created column: ' . $this->wpdb->last_error);
      }

      return array(
        'success' => true,
        'message' => 'Successfully removed date_created column from hp_trees table'
      );
    } catch (Exception $e) {
      return array(
        'success' => false,
        'message' => 'Rollback failed: ' . $e->getMessage()
      );
    }
  }

  /**
   * Check if the date_created column exists
   */
  private function column_exists()
  {
    $columns = $this->wpdb->get_results("SHOW COLUMNS FROM `{$this->table_name}` LIKE 'date_created'");
    return !empty($columns);
  }

  /**
   * Get the current table structure
   */
  public function get_table_info()
  {
    $columns = $this->wpdb->get_results("SHOW COLUMNS FROM `{$this->table_name}`");
    $indexes = $this->wpdb->get_results("SHOW INDEX FROM `{$this->table_name}`");

    return array(
      'columns' => $columns,
      'indexes' => $indexes
    );
  }
}

// If running directly (for testing)
if (defined('WP_CLI') && WP_CLI) {
  $migration = new HP_Migration_Add_Tree_Date_Created();
  $result = $migration->run();
  WP_CLI::line($result['message']);
  if (!$result['success']) {
    WP_CLI::error('Migration failed');
  }
}
