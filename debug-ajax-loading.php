<?php

/**
 * Debug the AJAX handler loading
 */

// Mimic WordPress environment
define('ABSPATH', 'C:/MAMP/htdocs/HeritagePress2/');
require_once ABSPATH . 'wp-config.php';

echo "=== AJAX Handler Loading Debug ===\n";

// Check if file exists
$ajax_dir = 'C:/MAMP/htdocs/HeritagePress2/wp-content/plugins/heritagepress/includes/template/People/ajax/';
$handler_file = $ajax_dir . 'tree-assignment-handler.php';

echo "Handler file path: $handler_file\n";
echo "File exists: " . (file_exists($handler_file) ? 'YES' : 'NO') . "\n";

if (file_exists($handler_file)) {
  echo "File size: " . filesize($handler_file) . " bytes\n";
  echo "File readable: " . (is_readable($handler_file) ? 'YES' : 'NO') . "\n";

  // Try to include the file
  echo "Attempting to include file...\n";
  try {
    require_once $handler_file;
    echo "✓ File included successfully\n";
  } catch (Exception $e) {
    echo "✗ Error including file: " . $e->getMessage() . "\n";
  }
}

// Check if the function exists
echo "Function hp_assign_person_to_tree exists: " . (function_exists('hp_assign_person_to_tree') ? 'YES' : 'NO') . "\n";

// Trigger init to see if AJAX gets registered
echo "Triggering WordPress init...\n";
do_action('init');

// Check registration again
global $wp_filter;
if (isset($wp_filter['wp_ajax_hp_assign_person_to_tree'])) {
  echo "✓ AJAX handler is now registered\n";
} else {
  echo "✗ AJAX handler still not registered\n";
}
