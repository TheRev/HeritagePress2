<?php
require_once '../../../wp-config.php';
global $wpdb;
$table = $wpdb->prefix . 'hp_trees';
$columns = $wpdb->get_results("DESCRIBE $table");
foreach ($columns as $column) {
  echo $column->Field . " (" . $column->Type . ") - " . $column->Null . " - Default: " . $column->Default . "\n";
}
