<?php
require_once '../../../wp-config.php';
global $wpdb;
$table = $wpdb->prefix . 'hp_trees';
$result = $wpdb->get_var("SHOW TABLES LIKE '$table'");
if ($result) {
  echo "Table $table exists\n";
  $columns = $wpdb->get_results("DESCRIBE $table");
  foreach ($columns as $column) {
    $null = $column->Null == 'NO' ? ' (NOT NULL)' : '';
    $default = $column->Default ? " DEFAULT: {$column->Default}" : '';
    echo "  - " . $column->Field . " (" . $column->Type . ")" . $null . $default . "\n";
  }
} else {
  echo "Table $table does not exist\n";
}
