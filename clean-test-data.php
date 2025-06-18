<?php
define('ABSPATH', 'c:/MAMP/htdocs/HeritagePress2/');
require_once 'c:/MAMP/htdocs/HeritagePress2/wp-config.php';

global $wpdb;

$tables = ['hp_sources', 'hp_media', 'hp_repositories', 'hp_people', 'hp_families'];
foreach ($tables as $table) {
  $wpdb->delete($wpdb->prefix . $table, array('gedcom' => 'test-admin'));
  echo "Cleaned $table\n";
}

echo "All test-admin data cleaned\n";
