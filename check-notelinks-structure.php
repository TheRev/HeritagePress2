<?php
require_once '../../../wp-config.php';
global $wpdb;

echo "Checking notelinks table:\n";
$notelinks_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}hp_notelinks'");
if ($notelinks_exists) {
  echo "✓ hp_notelinks table exists\n\n";
  echo "hp_notelinks table structure:\n";
  $columns = $wpdb->get_results("DESCRIBE {$wpdb->prefix}hp_notelinks");
  foreach ($columns as $column) {
    echo "{$column->Field} ({$column->Type}) - Default: {$column->Default}\n";
  }
} else {
  echo "✗ hp_notelinks table missing\n";
}
