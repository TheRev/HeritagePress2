<?php
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');
global $wpdb;

echo "All tables in database:\n";
$tables = $wpdb->get_results('SHOW TABLES');
foreach ($tables as $table) {
  $name = array_values((array)$table)[0];
  if (strpos($name, 'hp_') !== false || strpos($name, 'note') !== false) {
    echo $name . "\n";
  }
}

// Try to create hp_notes if it doesn't exist
$notes_table = $wpdb->prefix . 'hp_notes';
$exists = $wpdb->get_var("SHOW TABLES LIKE '$notes_table'");

if (!$exists) {
  echo "\nCreating hp_notes table...\n";
  $sql = "CREATE TABLE $notes_table (
        noteID varchar(255) NOT NULL,
        gedcom varchar(255) NOT NULL DEFAULT '',
        note longtext,
        nn varchar(255) DEFAULT NULL,
        PRIMARY KEY (noteID, gedcom),
        KEY gedcom (gedcom),
        KEY nn (nn)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

  $result = $wpdb->query($sql);
  if ($result !== false) {
    echo "✓ Created hp_notes table successfully\n";
  } else {
    echo "✗ Failed to create hp_notes table: " . $wpdb->last_error . "\n";
  }
} else {
  echo "\nhp_notes table already exists\n";
}
