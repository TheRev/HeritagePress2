<?php

/**
 * HeritagePress Date Migration Script
 *
 * Migrates existing date data to use the new dual storage system
 * Converts display dates to sortable dates for all date fields
 *
 * @package HeritagePress
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  // Allow CLI access for testing
  if (php_sapi_name() !== 'cli') {
    exit('Direct access not allowed');
  }

  // For CLI testing, define minimal WordPress-like functions
  function get_option($option, $default = false)
  {
    return $default;
  }
  function update_option($option, $value)
  {
    return true;
  }
  function wp_die($message)
  {
    die($message);
  }
  function current_user_can($capability)
  {
    return true;
  }
  function __($text, $domain = 'default')
  {
    return $text;
  }
  function _e($text, $domain = 'default')
  {
    echo $text;
  }
  function esc_html($text)
  {
    return htmlspecialchars($text);
  }
  function admin_url($path = '')
  {
    return '/wp-admin/' . $path;
  }
  function wp_create_nonce($action)
  {
    return 'test_nonce';
  }
  function check_ajax_referer($action, $query_arg = '_ajax_nonce')
  {
    return true;
  }
}

// Include the date parser
require_once __DIR__ . '/includes/class-hp-date-parser.php';

class HP_Date_Migration
{

  /**
   * Database connection
   */
  private $wpdb;

  /**
   * Migration statistics
   */
  private $stats = [
    'total_records' => 0,
    'processed' => 0,
    'updated' => 0,
    'errors' => 0,
    'skipped' => 0
  ];

  /**
   * Date fields to migrate
   */
  private $date_fields = [
    'birthdate' => 'birthdatetr',
    'deathdate' => 'deathdatetr',
    'altbirthdate' => 'altbirthdatetr',
    'burialdate' => 'burialdatetr',
    'baptdate' => 'baptdatetr',
    'confdate' => 'confdatetr'
  ];

  /**
   * Constructor
   */
  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
  }

  /**
   * Run the migration
   */
  public function run_migration($dry_run = false, $batch_size = 100)
  {
    echo "<h2>HeritagePress Date Migration</h2>\n";

    if ($dry_run) {
      echo "<p><strong>DRY RUN MODE</strong> - No changes will be made to the database.</p>\n";
    }

    $this->log("Starting date migration...");
    $start_time = microtime(true);

    // Migrate people table
    $this->migrate_people_table($dry_run, $batch_size);

    // Migrate families table if it exists
    $this->migrate_families_table($dry_run, $batch_size);

    // Migrate events table if it exists
    $this->migrate_events_table($dry_run, $batch_size);

    $end_time = microtime(true);
    $duration = round($end_time - $start_time, 2);

    $this->show_summary($duration, $dry_run);

    return $this->stats;
  }

  /**
   * Migrate people table dates
   */
  private function migrate_people_table($dry_run = false, $batch_size = 100)
  {
    $table_name = $this->wpdb->prefix . 'hp_people';

    $this->log("Migrating people table: {$table_name}");

    // Check if table exists
    $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
      "SHOW TABLES LIKE %s",
      $table_name
    ));

    if (!$table_exists) {
      $this->log("People table not found, skipping...");
      return;
    }

    // Get total count
    $total = $this->wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    $this->stats['total_records'] += $total;
    $this->log("Found {$total} people records to process");

    // Process in batches
    $offset = 0;
    while ($offset < $total) {
      $records = $this->wpdb->get_results(
        "SELECT * FROM {$table_name} LIMIT {$batch_size} OFFSET {$offset}",
        ARRAY_A
      );

      foreach ($records as $record) {
        $this->process_person_record($table_name, $record, $dry_run);
      }

      $offset += $batch_size;
      $this->log("Processed {$offset}/{$total} people records...");
    }
  }

  /**
   * Process a single person record
   */
  private function process_person_record($table_name, $record, $dry_run = false)
  {
    $this->stats['processed']++;
    $updates = [];
    $person_id = $record['personID'] ?? 'unknown';

    foreach ($this->date_fields as $display_field => $sortable_field) {
      if (!isset($record[$display_field]) || !isset($record[$sortable_field])) {
        continue; // Field doesn't exist in this table
      }

      $display_date = trim($record[$display_field]);
      $current_sortable = $record[$sortable_field];

      // Skip if display date is empty
      if (empty($display_date)) {
        continue;
      }

      // Parse the display date
      $parsed = HP_Date_Parser::parse_date($display_date);

      if ($parsed['is_valid'] && $parsed['sortable']) {
        $new_sortable = $parsed['sortable'];

        // Only update if different from current value
        if ($current_sortable !== $new_sortable) {
          $updates[$sortable_field] = $new_sortable;

          $this->log("Person {$person_id}: {$display_field} '{$display_date}' → {$new_sortable} (was: {$current_sortable})");
        }
      } else {
        $this->log("Person {$person_id}: Failed to parse {$display_field} '{$display_date}'", 'warning');
        $this->stats['errors']++;
      }
    }

    // Perform updates
    if (!empty($updates)) {
      if (!$dry_run) {
        $result = $this->wpdb->update(
          $table_name,
          $updates,
          ['personID' => $record['personID'], 'gedcom' => $record['gedcom']]
        );

        if ($result === false) {
          $this->log("Failed to update person {$person_id}", 'error');
          $this->stats['errors']++;
        } else {
          $this->stats['updated']++;
        }
      } else {
        $this->stats['updated']++; // Count what would be updated
      }
    } else {
      $this->stats['skipped']++;
    }
  }

  /**
   * Migrate families table dates
   */
  private function migrate_families_table($dry_run = false, $batch_size = 100)
  {
    $table_name = $this->wpdb->prefix . 'hp_families';

    // Check if table exists
    $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
      "SHOW TABLES LIKE %s",
      $table_name
    ));

    if (!$table_exists) {
      $this->log("Families table not found, skipping...");
      return;
    }

    $this->log("Migrating families table: {$table_name}");

    // Family date fields
    $family_date_fields = [
      'marr_date' => 'marr_datetr',
      'div_date' => 'div_datetr',
      'eng_date' => 'eng_datetr'
    ];

    // Get total count
    $total = $this->wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    $this->log("Found {$total} family records to process");

    // Process in batches
    $offset = 0;
    while ($offset < $total) {
      $records = $this->wpdb->get_results(
        "SELECT * FROM {$table_name} LIMIT {$batch_size} OFFSET {$offset}",
        ARRAY_A
      );

      foreach ($records as $record) {
        $this->process_family_record($table_name, $record, $family_date_fields, $dry_run);
      }

      $offset += $batch_size;
      $this->log("Processed {$offset}/{$total} family records...");
    }
  }

  /**
   * Process a single family record
   */
  private function process_family_record($table_name, $record, $date_fields, $dry_run = false)
  {
    $updates = [];
    $family_id = $record['familyID'] ?? 'unknown';

    foreach ($date_fields as $display_field => $sortable_field) {
      if (!isset($record[$display_field]) || !isset($record[$sortable_field])) {
        continue;
      }

      $display_date = trim($record[$display_field]);
      $current_sortable = $record[$sortable_field];

      if (empty($display_date)) {
        continue;
      }

      $parsed = HP_Date_Parser::parse_date($display_date);

      if ($parsed['is_valid'] && $parsed['sortable']) {
        $new_sortable = $parsed['sortable'];

        if ($current_sortable !== $new_sortable) {
          $updates[$sortable_field] = $new_sortable;
          $this->log("Family {$family_id}: {$display_field} '{$display_date}' → {$new_sortable}");
        }
      }
    }

    if (!empty($updates) && !$dry_run) {
      $result = $this->wpdb->update(
        $table_name,
        $updates,
        ['familyID' => $record['familyID'], 'gedcom' => $record['gedcom']]
      );

      if ($result !== false) {
        $this->stats['updated']++;
      }
    }
  }

  /**
   * Migrate events table dates
   */
  private function migrate_events_table($dry_run = false, $batch_size = 100)
  {
    $table_name = $this->wpdb->prefix . 'hp_events';

    // Check if table exists
    $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
      "SHOW TABLES LIKE %s",
      $table_name
    ));

    if (!$table_exists) {
      $this->log("Events table not found, skipping...");
      return;
    }

    $this->log("Migrating events table: {$table_name}");

    // Event date fields
    $event_date_fields = [
      'eventdate' => 'eventdatetr'
    ];

    // Get total count
    $total = $this->wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    $this->log("Found {$total} event records to process");

    // Process in batches
    $offset = 0;
    while ($offset < $total) {
      $records = $this->wpdb->get_results(
        "SELECT * FROM {$table_name} LIMIT {$batch_size} OFFSET {$offset}",
        ARRAY_A
      );

      foreach ($records as $record) {
        $this->process_event_record($table_name, $record, $event_date_fields, $dry_run);
      }

      $offset += $batch_size;
      $this->log("Processed {$offset}/{$total} event records...");
    }
  }

  /**
   * Process a single event record
   */
  private function process_event_record($table_name, $record, $date_fields, $dry_run = false)
  {
    $updates = [];
    $event_id = $record['eventID'] ?? 'unknown';

    foreach ($date_fields as $display_field => $sortable_field) {
      if (!isset($record[$display_field]) || !isset($record[$sortable_field])) {
        continue;
      }

      $display_date = trim($record[$display_field]);
      $current_sortable = $record[$sortable_field];

      if (empty($display_date)) {
        continue;
      }

      $parsed = HP_Date_Parser::parse_date($display_date);

      if ($parsed['is_valid'] && $parsed['sortable']) {
        $new_sortable = $parsed['sortable'];

        if ($current_sortable !== $new_sortable) {
          $updates[$sortable_field] = $new_sortable;
          $this->log("Event {$event_id}: {$display_field} '{$display_date}' → {$new_sortable}");
        }
      }
    }

    if (!empty($updates) && !$dry_run) {
      $result = $this->wpdb->update(
        $table_name,
        $updates,
        ['eventID' => $record['eventID']]
      );

      if ($result !== false) {
        $this->stats['updated']++;
      }
    }
  }

  /**
   * Log a message
   */
  private function log($message, $level = 'info')
  {
    $timestamp = date('Y-m-d H:i:s');
    $prefix = '';

    switch ($level) {
      case 'error':
        $prefix = '[ERROR] ';
        break;
      case 'warning':
        $prefix = '[WARNING] ';
        break;
      case 'info':
      default:
        $prefix = '[INFO] ';
        break;
    }

    echo "<p>{$timestamp} {$prefix}{$message}</p>\n";

    // Also log to error log in production
    if (function_exists('error_log')) {
      error_log("HP Date Migration: {$prefix}{$message}");
    }
  }

  /**
   * Show migration summary
   */
  private function show_summary($duration, $dry_run = false)
  {
    echo "<h3>Migration Summary</h3>\n";
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>Metric</th><th>Count</th></tr>\n";
    echo "<tr><td>Total Records Found</td><td>{$this->stats['total_records']}</td></tr>\n";
    echo "<tr><td>Records Processed</td><td>{$this->stats['processed']}</td></tr>\n";
    echo "<tr><td>Records " . ($dry_run ? "Would Be " : "") . "Updated</td><td>{$this->stats['updated']}</td></tr>\n";
    echo "<tr><td>Records Skipped</td><td>{$this->stats['skipped']}</td></tr>\n";
    echo "<tr><td>Errors</td><td>{$this->stats['errors']}</td></tr>\n";
    echo "<tr><td>Duration</td><td>{$duration} seconds</td></tr>\n";
    echo "</table>\n";

    if ($dry_run) {
      echo "<p><strong>This was a dry run.</strong> To perform the actual migration, run with dry_run=false.</p>\n";
    } else {
      echo "<p><strong>Migration completed successfully!</strong></p>\n";
    }

    if ($this->stats['errors'] > 0) {
      echo "<p class='error'><strong>Warning:</strong> {$this->stats['errors']} errors occurred during migration. Check the log above for details.</p>\n";
    }
  }

  /**
   * Backup tables before migration
   */
  public function backup_tables()
  {
    $tables = [
      $this->wpdb->prefix . 'hp_people',
      $this->wpdb->prefix . 'hp_families',
      $this->wpdb->prefix . 'hp_events'
    ];

    $backup_dir = __DIR__ . '/backups/';
    if (!file_exists($backup_dir)) {
      mkdir($backup_dir, 0755, true);
    }

    $backup_file = $backup_dir . 'date_migration_backup_' . date('Y-m-d_H-i-s') . '.sql';

    echo "<h3>Creating Backup</h3>\n";
    echo "<p>Backup location: {$backup_file}</p>\n";

    $backup_sql = "-- HeritagePress Date Migration Backup\n";
    $backup_sql .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
      $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
        "SHOW TABLES LIKE %s",
        $table
      ));

      if (!$table_exists) {
        continue;
      }

      // Get CREATE TABLE statement
      $create_table = $this->wpdb->get_row("SHOW CREATE TABLE {$table}", ARRAY_N);
      $backup_sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
      $backup_sql .= $create_table[1] . ";\n\n";

      // Get data
      $rows = $this->wpdb->get_results("SELECT * FROM {$table}", ARRAY_A);

      if (!empty($rows)) {
        $backup_sql .= "INSERT INTO `{$table}` VALUES\n";
        $value_strings = [];

        foreach ($rows as $row) {
          $values = [];
          foreach ($row as $value) {
            $values[] = $this->wpdb->prepare('%s', $value);
          }
          $value_strings[] = '(' . implode(',', $values) . ')';
        }

        $backup_sql .= implode(",\n", $value_strings) . ";\n\n";
      }
    }

    file_put_contents($backup_file, $backup_sql);
    echo "<p>Backup created successfully!</p>\n";

    return $backup_file;
  }
}

/**
 * WordPress admin interface for the migration
 */
function hp_date_migration_admin_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }

  echo '<div class="wrap">';
  echo '<h1>HeritagePress Date Migration</h1>';

  if (isset($_POST['run_migration'])) {
    $dry_run = isset($_POST['dry_run']);
    $create_backup = isset($_POST['create_backup']);
    $batch_size = intval($_POST['batch_size']) ?: 100;

    echo '<div class="migration-results">';

    $migration = new HP_Date_Migration();

    if ($create_backup && !$dry_run) {
      $migration->backup_tables();
    }

    $migration->run_migration($dry_run, $batch_size);

    echo '</div>';
  }

  // Migration form
?>
  <form method="post" action="">
    <table class="form-table">
      <tr>
        <th scope="row">Migration Type</th>
        <td>
          <label>
            <input type="checkbox" name="dry_run" value="1" checked>
            Dry Run (preview changes without applying them)
          </label>
        </td>
      </tr>
      <tr>
        <th scope="row">Backup</th>
        <td>
          <label>
            <input type="checkbox" name="create_backup" value="1" checked>
            Create backup before migration
          </label>
        </td>
      </tr>
      <tr>
        <th scope="row">Batch Size</th>
        <td>
          <input type="number" name="batch_size" value="100" min="1" max="1000">
          <p class="description">Number of records to process at once</p>
        </td>
      </tr>
    </table>

    <?php submit_button('Run Date Migration', 'primary', 'run_migration'); ?>
  </form>

  <div class="migration-info">
    <h3>What This Migration Does</h3>
    <ul>
      <li>Scans all existing date fields in your HeritagePress database</li>
      <li>Converts display dates (like "2 OCT 1822") to sortable dates (like "1822-10-02")</li>
      <li>Updates the sortable date fields (*datetr) for proper sorting and searching</li>
      <li>Handles uncertain dates with qualifiers (ABT, BEF, AFT, etc.)</li>
      <li>Reports any dates that cannot be parsed</li>
    </ul>

    <h3>Safety Features</h3>
    <ul>
      <li><strong>Dry Run:</strong> Preview changes without modifying data</li>
      <li><strong>Backup:</strong> Automatic backup before migration</li>
      <li><strong>Batch Processing:</strong> Processes records in small batches</li>
      <li><strong>Error Handling:</strong> Continues processing even if some dates fail</li>
    </ul>
  </div>

  <style>
    .migration-results {
      background: #f9f9f9;
      border: 1px solid #ddd;
      padding: 20px;
      margin: 20px 0;
      border-radius: 5px;
      font-family: monospace;
      max-height: 500px;
      overflow-y: auto;
    }

    .migration-info {
      background: #fff;
      border: 1px solid #ddd;
      padding: 20px;
      margin: 20px 0;
      border-radius: 5px;
    }

    .migration-info ul {
      margin-left: 20px;
    }

    .error {
      color: #d63638;
      font-weight: bold;
    }
  </style>
<?php

  echo '</div>';
}

// If running as standalone script
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
  echo "<!DOCTYPE html><html><head><title>Date Migration</title></head><body>\n";

  // Simple CLI interface
  $dry_run = isset($_GET['dry_run']) ? (bool)$_GET['dry_run'] : true;
  $batch_size = isset($_GET['batch_size']) ? intval($_GET['batch_size']) : 100;

  $migration = new HP_Date_Migration();
  $migration->run_migration($dry_run, $batch_size);

  echo "</body></html>\n";
}
?>
