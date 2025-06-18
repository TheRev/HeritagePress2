<?php

/**
 * WordPress-based debug script for wp_hp_trees table
 * Access via: /wp-content/plugins/heritagepress/wp-debug-table.php
 */

// Load WordPress
require_once('../../../wp-config.php');

global $wpdb;

echo "<h2>wp_hp_trees Table Structure Debug</h2>";

// Check if table exists
$table_name = $wpdb->prefix . 'hp_trees';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

if ($table_exists) {
  echo "<h3>✅ Table $table_name exists</h3>";

  // Get table structure
  echo "<h4>Table Structure:</h4>";
  $columns = $wpdb->get_results("DESCRIBE $table_name");

  echo "<table border='1' cellpadding='5'>";
  echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

  foreach ($columns as $column) {
    echo "<tr>";
    echo "<td><strong>{$column->Field}</strong></td>";
    echo "<td>{$column->Type}</td>";
    echo "<td>{$column->Null}</td>";
    echo "<td>{$column->Key}</td>";
    echo "<td>{$column->Default}</td>";
    echo "<td>{$column->Extra}</td>";
    echo "</tr>";
  }
  echo "</table>";

  // Get sample data
  echo "<h4>Sample Data (first 5 rows):</h4>";
  $sample_data = $wpdb->get_results("SELECT * FROM $table_name LIMIT 5", ARRAY_A);

  if ($sample_data) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>";
    foreach (array_keys($sample_data[0]) as $header) {
      echo "<th>$header</th>";
    }
    echo "</tr>";

    foreach ($sample_data as $row) {
      echo "<tr>";
      foreach ($row as $value) {
        echo "<td>" . htmlspecialchars($value) . "</td>";
      }
      echo "</tr>";
    }
    echo "</table>";
  } else {
    echo "<p>No data found in table.</p>";
  }
  // Show the exact INSERT query that's failing
  echo "<h4>Old INSERT Query (that was failing):</h4>";
  echo "<pre>";
  echo "INSERT INTO `$table_name` (`gedcom`, `treename`, `description`, `owner`, `public`, `geddate`, `changedate`) VALUES (...)";
  echo "</pre>";

  // Show the corrected INSERT query
  echo "<h4>Corrected INSERT Query:</h4>";
  echo "<pre>";
  echo "INSERT INTO `$table_name` (`gedcom`, `treename`, `description`, `owner`, `secret`, `date_created`) VALUES (...)";
  echo "</pre>";

  // Show what columns actually exist
  echo "<h4>Available Columns:</h4>";
  $column_names = array_map(function ($col) {
    return $col->Field;
  }, $columns);
  echo "<ul>";
  foreach ($column_names as $col_name) {
    echo "<li>$col_name</li>";
  }
  echo "</ul>";
} else {
  echo "<h3>❌ Table $table_name does not exist!</h3>";

  // Show all HP tables
  echo "<h4>Available HP Tables:</h4>";
  $hp_tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}hp_%'");

  if ($hp_tables) {
    echo "<ul>";
    foreach ($hp_tables as $table) {
      $table_name = array_values((array)$table)[0];
      echo "<li>$table_name</li>";
    }
    echo "</ul>";
  } else {
    echo "<p>No HP tables found!</p>";
  }
}

echo "<hr>";
echo "<p><strong>WordPress DB Prefix:</strong> {$wpdb->prefix}</p>";
echo "<p><strong>Full Table Name:</strong> {$wpdb->prefix}hp_trees</p>";
