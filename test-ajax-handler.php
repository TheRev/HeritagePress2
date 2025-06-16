<?php

/**
 * Test script to verify AJAX handler is loaded
 */

// Mimic WordPress environment
define('ABSPATH', 'C:/MAMP/htdocs/HeritagePress2/');
require_once ABSPATH . 'wp-config.php';

// Trigger WordPress initialization
do_action('init');
do_action('admin_init');

// Check if the AJAX action is registered
global $wp_filter;

echo "=== AJAX Handler Test ===\n";
echo "Looking for hp_assign_person_to_tree action...\n";

if (isset($wp_filter['wp_ajax_hp_assign_person_to_tree'])) {
  echo "✓ AJAX handler 'hp_assign_person_to_tree' is registered\n";
  print_r($wp_filter['wp_ajax_hp_assign_person_to_tree']);
} else {
  echo "✗ AJAX handler 'hp_assign_person_to_tree' is NOT registered\n";
}

echo "\nHeritagePress AJAX actions:\n";
foreach ($wp_filter as $action => $callbacks) {
  if (strpos($action, 'wp_ajax_hp_') === 0) {
    echo "- $action\n";
  }
}
