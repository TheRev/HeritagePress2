<?php
require_once '../../../wp-config.php';
global $wpdb;
$table = $wpdb->prefix . 'hp_trees';
$result = $wpdb->get_var("SHOW TABLES LIKE '$table'");
if ($result) {
  echo "Table $table exists\n";
  $columns = $wpdb->get_results("DESCRIBE $table");
  foreach ($columns as $column) {
    echo "  - " . $column->Field . " (" . $column->Type . ")\n";
  }
} else {
  echo "Table $table does not exist\n";
  echo "Let's check what tables do exist:\n";
  $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");
  foreach ($tables as $table_obj) {
    $table_name = array_values((array)$table_obj)[0];
    echo "  - $table_name\n";
  }
}
