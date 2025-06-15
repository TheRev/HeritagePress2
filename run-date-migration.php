<?php

/**
 * HeritagePress Date Migration - Standalone Version
 *
 * Run this script to migrate existing date data to dual storage format
 * Usage: Access this file directly via browser or run via command line
 *
 * @package HeritagePress
 * @since 1.0.0
 */

// Database configuration - Update these with your settings
$db_config = [
  'host' => 'localhost',
  'database' => 'wordpress', // Your WordPress database
  'username' => 'root',      // Your database username
  'password' => 'root',      // Your database password
  'prefix' => 'wp_'          // Your WordPress table prefix
];

// Include the date parser
require_once __DIR__ . '/includes/class-hp-date-parser.php';

/**
 * Simple database wrapper
 */
class Simple_DB
{
  private $pdo;
  private $prefix;

  public function __construct($config)
  {
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
    $this->pdo = new PDO($dsn, $config['username'], $config['password']);
    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $this->prefix = $config['prefix'];
  }

  public function get_results($query)
  {
    $stmt = $this->pdo->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function get_var($query)
  {
    $stmt = $this->pdo->query($query);
    return $stmt->fetchColumn();
  }

  public function update($table, $data, $where)
  {
    $set_clause = [];
    $values = [];

    foreach ($data as $column => $value) {
      $set_clause[] = "`{$column}` = ?";
      $values[] = $value;
    }

    $where_clause = [];
    foreach ($where as $column => $value) {
      $where_clause[] = "`{$column}` = ?";
      $values[] = $value;
    }

    $sql = "UPDATE `{$table}` SET " . implode(', ', $set_clause) .
      " WHERE " . implode(' AND ', $where_clause);

    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute($values);
  }

  public function table_exists($table)
  {
    $query = "SHOW TABLES LIKE '{$table}'";
    return $this->get_var($query) !== false;
  }

  public function get_prefix()
  {
    return $this->prefix;
  }
}

/**
 * Simple migration class
 */
class HP_Simple_Date_Migration
{

  private $db;
  private $stats = [
    'total_records' => 0,
    'processed' => 0,
    'updated' => 0,
    'errors' => 0,
    'skipped' => 0
  ];

  private $date_fields = [
    'birthdate' => 'birthdatetr',
    'deathdate' => 'deathdatetr',
    'altbirthdate' => 'altbirthdatetr',
    'burialdate' => 'burialdatetr',
    'baptdate' => 'baptdatetr'
  ];

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function run($dry_run = true)
  {
    echo "<h2>HeritagePress Date Migration</h2>\n";

    if ($dry_run) {
      echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
      echo "<strong>DRY RUN MODE</strong> - No changes will be made to the database.";
      echo "</div>\n";
    }

    $start_time = microtime(true);

    // Migrate people table
    $this->migrate_people_table($dry_run);

    $end_time = microtime(true);
    $duration = round($end_time - $start_time, 2);

    $this->show_summary($duration, $dry_run);

    return $this->stats;
  }

  private function migrate_people_table($dry_run = false)
  {
    $table_name = $this->db->get_prefix() . 'hp_people';

    echo "<h3>Checking people table: {$table_name}</h3>\n";

    if (!$this->db->table_exists($table_name)) {
      echo "<p>❌ People table not found. Please check your table prefix and database.</p>\n";
      return;
    }

    // Get all records
    $records = $this->db->get_results("SELECT * FROM `{$table_name}`");
    $total = count($records);

    echo "<p>✅ Found {$total} people records to process</p>\n";
    $this->stats['total_records'] = $total;

    if ($total === 0) {
      echo "<p>No records to process.</p>\n";
      return;
    }

    echo "<div style='max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9; font-family: monospace; font-size: 12px;'>\n";

    foreach ($records as $record) {
      $this->process_person_record($table_name, $record, $dry_run);
    }

    echo "</div>\n";
  }

  private function process_person_record($table_name, $record, $dry_run = false)
  {
    $this->stats['processed']++;
    $updates = [];
    $person_id = $record['personID'] ?? 'unknown';
    $gedcom = $record['gedcom'] ?? '';

    $has_changes = false;

    foreach ($this->date_fields as $display_field => $sortable_field) {
      // Check if both fields exist
      if (!array_key_exists($display_field, $record) || !array_key_exists($sortable_field, $record)) {
        continue;
      }

      $display_date = trim($record[$display_field] ?? '');
      $current_sortable = $record[$sortable_field] ?? '';

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
          $has_changes = true;

          echo "📅 Person {$person_id}: {$display_field} '{$display_date}' → {$new_sortable}";
          if ($current_sortable && $current_sortable !== '0000-00-00') {
            echo " (was: {$current_sortable})";
          }
          echo "<br>\n";
        }
      } else {
        echo "⚠️ Person {$person_id}: Failed to parse {$display_field} '{$display_date}'<br>\n";
        $this->stats['errors']++;
      }
    }

    // Perform updates
    if (!empty($updates)) {
      if (!$dry_run) {
        try {
          $where_conditions = ['personID' => $person_id];
          if (!empty($gedcom)) {
            $where_conditions['gedcom'] = $gedcom;
          }

          $result = $this->db->update($table_name, $updates, $where_conditions);

          if ($result) {
            $this->stats['updated']++;
            echo "✅ Updated person {$person_id}<br>\n";
          } else {
            echo "❌ Failed to update person {$person_id}<br>\n";
            $this->stats['errors']++;
          }
        } catch (Exception $e) {
          echo "❌ Error updating person {$person_id}: " . $e->getMessage() . "<br>\n";
          $this->stats['errors']++;
        }
      } else {
        $this->stats['updated']++; // Count what would be updated
      }
    } else if (!$has_changes) {
      $this->stats['skipped']++;
    }
  }

  private function show_summary($duration, $dry_run = false)
  {
    echo "<h3>Migration Summary</h3>\n";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>\n";
    echo "<tr style='background: #f0f0f0;'><th>Metric</th><th>Count</th></tr>\n";
    echo "<tr><td>Total Records Found</td><td>{$this->stats['total_records']}</td></tr>\n";
    echo "<tr><td>Records Processed</td><td>{$this->stats['processed']}</td></tr>\n";
    echo "<tr><td>Records " . ($dry_run ? "Would Be " : "") . "Updated</td><td style='font-weight: bold; color: #0073aa;'>{$this->stats['updated']}</td></tr>\n";
    echo "<tr><td>Records Skipped (No Changes)</td><td>{$this->stats['skipped']}</td></tr>\n";
    echo "<tr><td>Errors</td><td style='color: " . ($this->stats['errors'] > 0 ? '#d63638' : '#46b450') . ";'>{$this->stats['errors']}</td></tr>\n";
    echo "<tr><td>Duration</td><td>{$duration} seconds</td></tr>\n";
    echo "</table>\n";

    if ($dry_run && $this->stats['updated'] > 0) {
      echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
      echo "<strong>This was a dry run.</strong> To perform the actual migration, add <code>?run=true</code> to the URL.";
      echo "</div>\n";
    } else if (!$dry_run) {
      echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
      echo "<strong>✅ Migration completed successfully!</strong>";
      echo "</div>\n";
    }

    if ($this->stats['errors'] > 0) {
      echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
      echo "<strong>⚠️ Warning:</strong> {$this->stats['errors']} errors occurred during migration. Check the details above.";
      echo "</div>\n";
    }
  }
}

// Main execution
try {
  echo "<!DOCTYPE html><html><head><title>HeritagePress Date Migration</title>";
  echo "<meta charset='UTF-8'>";
  echo "<style>body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }</style>";
  echo "</head><body>\n";

  // Create database connection
  $db = new Simple_DB($db_config);

  // Check if we should run the actual migration
  $dry_run = !isset($_GET['run']) || $_GET['run'] !== 'true';

  // Create and run migration
  $migration = new HP_Simple_Date_Migration($db);
  $migration->run($dry_run);

  if ($dry_run) {
    echo "<hr>";
    echo "<h3>Next Steps</h3>";
    echo "<p>1. Review the changes above</p>";
    echo "<p>2. If everything looks correct, run the actual migration: ";
    echo "<a href='?run=true' style='background: #0073aa; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px;'>Run Migration</a>";
    echo "</p>";
    echo "<p><strong>Backup Recommendation:</strong> Before running the migration, backup your database:</p>";
    echo "<code style='background: #f0f0f0; padding: 10px; display: block; margin: 10px 0;'>";
    echo "mysqldump -u {$db_config['username']} -p {$db_config['database']} > heritagepress_backup_" . date('Y-m-d') . ".sql";
    echo "</code>";
  }
} catch (Exception $e) {
  echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
  echo "<strong>❌ Database Connection Error:</strong><br>";
  echo htmlspecialchars($e->getMessage());
  echo "<br><br>Please check your database configuration at the top of this file.";
  echo "</div>";
}

echo "</body></html>\n";
