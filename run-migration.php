<?php

/**
 * Run Tree Date Created Migration
 *
 * Simple script to execute the tree date_created field migration
 */

// Include WordPress
require_once('../../../wp-config.php');

if (!defined('ABSPATH')) {
  die('WordPress not loaded');
}

// Include the migration class
require_once('migrations/add-tree-date-created.php');

echo "<h2>HeritagePress Tree Date Created Migration</h2>";

// Create migration instance
$migration = new HP_Migration_Add_Tree_Date_Created();

// Show current table info
echo "<h3>Current Table Structure:</h3>";
$table_info = $migration->get_table_info();
echo "<h4>Columns:</h4>";
echo "<ul>";
foreach ($table_info['columns'] as $column) {
  echo "<li><strong>{$column->Field}</strong> - {$column->Type} " .
    ($column->Null === 'NO' ? 'NOT NULL' : 'NULL') .
    ($column->Default ? " DEFAULT '{$column->Default}'" : '') . "</li>";
}
echo "</ul>";

// Run the migration
echo "<h3>Running Migration:</h3>";
$result = $migration->run();

if ($result['success']) {
  echo "<p style='color: green;'><strong>SUCCESS:</strong> " . htmlspecialchars($result['message']) . "</p>";
} else {
  echo "<p style='color: red;'><strong>ERROR:</strong> " . htmlspecialchars($result['message']) . "</p>";
}

// Show updated table info
echo "<h3>Updated Table Structure:</h3>";
$table_info = $migration->get_table_info();
echo "<h4>Columns:</h4>";
echo "<ul>";
foreach ($table_info['columns'] as $column) {
  $highlight = ($column->Field === 'date_created') ? 'style="background: yellow;"' : '';
  echo "<li {$highlight}><strong>{$column->Field}</strong> - {$column->Type} " .
    ($column->Null === 'NO' ? 'NOT NULL' : 'NULL') .
    ($column->Default ? " DEFAULT '{$column->Default}'" : '') . "</li>";
}
echo "</ul>";

echo "<h4>Indexes:</h4>";
echo "<ul>";
foreach ($table_info['indexes'] as $index) {
  $highlight = ($index->Key_name === 'idx_date_created') ? 'style="background: yellow;"' : '';
  echo "<li {$highlight}><strong>{$index->Key_name}</strong> on {$index->Column_name}</li>";
}
echo "</ul>";

echo "<p><a href='admin/'>Return to HeritagePress Admin</a></p>";
