<?php
require_once '../../../wp-config.php';
global $wpdb;

echo "hp_xnotes table structure:\n";
$columns = $wpdb->get_results("DESCRIBE {$wpdb->prefix}hp_xnotes");
foreach ($columns as $column) {
  echo "{$column->Field} ({$column->Type}) - Default: {$column->Default}\n";
}
