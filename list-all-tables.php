<?php
define('ABSPATH', 'c:/MAMP/htdocs/HeritagePress2/');
require_once 'c:/MAMP/htdocs/HeritagePress2/wp-config.php';

global $wpdb;

echo "=== All Database Tables ===\n";
$tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
foreach ($tables as $table) {
  if (strpos($table[0], 'heritagepress') !== false) {
    echo $table[0] . "\n";
  }
}
