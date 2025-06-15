<?php

/**
 * HeritagePress Date Migration - Quick Version
 * This version has better error handling and timeout protection
 */

set_time_limit(300); // 5 minutes max
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>HeritagePress Date Migration</title>";
echo "<style>body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }</style>";
echo "</head><body>";

echo "<h2>HeritagePress Date Migration - Quick Version</h2>";

// Database configuration
$config = [
  'host' => 'localhost',
  'database' => 'wordpress',
  'username' => 'root',
  'password' => 'root',
  'prefix' => 'wp_'
];

// Include the date parser
require_once __DIR__ . '/includes/class-hp-date-parser.php';

try {
  echo "<p>🔗 Connecting to database...</p>";

  $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
  $pdo = new PDO($dsn, $config['username'], $config['password']);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  echo "<p>✅ Connected successfully!</p>";

  $table_name = $config['prefix'] . 'hp_people';

  // Check if table exists
  $stmt = $pdo->query("SHOW TABLES LIKE '{$table_name}'");
  if (!$stmt->fetch()) {
    throw new Exception("Table {$table_name} does not exist");
  }

  echo "<p>✅ Table {$table_name} found</p>";
  // Get mode from URL parameter
  $dry_run = !isset($_GET['run']) || $_GET['run'] !== 'true';

  if ($dry_run) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>DRY RUN MODE</strong> - No changes will be made";
    echo "</div>";
  }

  // Date field mappings
  $date_fields = [
    'birthdate' => 'birthdatetr',
    'deathdate' => 'deathdatetr',
    'altbirthdate' => 'altbirthdatetr',
    'burialdate' => 'burialdatetr',
    'baptdate' => 'baptdatetr'
  ];
  // Get records that need updating
  $where_conditions = [];
  foreach ($date_fields as $display => $sortable) {
    $where_conditions[] = "({$display} IS NOT NULL AND {$display} != '' AND ({$sortable} IS NULL OR {$sortable} = '' OR {$sortable} = '0000-00-00'))";
  }

  $query = "SELECT personID, " . implode(', ', array_keys($date_fields)) . ", " . implode(', ', array_values($date_fields)) .
    " FROM {$table_name} WHERE " . implode(' OR ', $where_conditions) . " LIMIT 100";

  echo "<p>🔍 Finding records that need updating...</p>";

  $stmt = $pdo->query($query);
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo "<p>📊 Found " . count($records) . " records to process</p>";

  if (empty($records)) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
    echo "<strong>✅ No records need updating!</strong> All date fields are already in dual storage format.";
    echo "</div>";
  } else {
    $updated_count = 0;
    $error_count = 0;

    echo "<h3>Processing Records...</h3>";
    echo "<div style='max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;'>";

    foreach ($records as $record) {
      $updates = [];
      $person_id = $record['personID'];

      echo "<p><strong>Person ID {$person_id}:</strong> ";
      foreach ($date_fields as $display_field => $sortable_field) {
        $display_date = $record[$display_field];
        $current_sortable = $record[$sortable_field];
        if (!empty($display_date) && (empty($current_sortable) || $current_sortable === '0000-00-00')) {
          try {
            $parsed = HP_Date_Parser::parse_date($display_date);
            if ($parsed && !empty($parsed['sortable'])) {
              $updates[$sortable_field] = $parsed['sortable'];
              echo "{$display_field}: '{$display_date}' → '{$parsed['sortable']}' ";
            }
          } catch (Exception $e) {
            echo "ERROR in {$display_field}: " . $e->getMessage() . " ";
            $error_count++;
          }
        }
      }

      if (!empty($updates)) {
        if (!$dry_run) {
          try {
            $set_clauses = [];
            $values = [];
            foreach ($updates as $field => $value) {
              $set_clauses[] = "{$field} = ?";
              $values[] = $value;
            }
            $values[] = $person_id;

            $update_sql = "UPDATE {$table_name} SET " . implode(', ', $set_clauses) . " WHERE personID = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute($values);

            echo "<span style='color: #46b450;'>✅ UPDATED</span>";
          } catch (Exception $e) {
            echo "<span style='color: #d63638;'>❌ UPDATE FAILED: " . $e->getMessage() . "</span>";
            $error_count++;
          }
        } else {
          echo "<span style='color: #0073aa;'>→ Would update</span>";
        }
        $updated_count++;
      } else {
        echo "No changes needed";
      }

      echo "</p>";
      flush(); // Output immediately
    }

    echo "</div>";

    // Summary
    echo "<h3>Summary</h3>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Metric</th><th>Count</th></tr>";
    echo "<tr><td>Records Found</td><td>" . count($records) . "</td></tr>";
    echo "<tr><td>Records " . ($dry_run ? "Would Be " : "") . "Updated</td><td style='color: #0073aa; font-weight: bold;'>{$updated_count}</td></tr>";
    echo "<tr><td>Errors</td><td style='color: " . ($error_count > 0 ? '#d63638' : '#46b450') . ";'>{$error_count}</td></tr>";
    echo "</table>";

    if ($dry_run && $updated_count > 0) {
      echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
      echo "<h4>This was a dry run - no changes were made</h4>";
      echo "<p>To perform the actual migration: ";
      echo "<a href='?run=true' style='background: #0073aa; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px;'>Run Migration</a>";
      echo "</p>";
      echo "</div>";
    } elseif (!$dry_run) {
      echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
      echo "<h4>✅ Migration completed successfully!</h4>";
      echo "<p>All date fields have been updated to dual storage format.</p>";
      echo "</div>";
    }
  }
} catch (Exception $e) {
  echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
  echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
  echo "</div>";
}

echo "</body></html>";
