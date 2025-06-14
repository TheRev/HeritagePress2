<?php
// Test plugin activation manually
require_once '../../../wp-config.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

echo "Testing HeritagePress plugin activation...\n";

// Check if plugin is already active
if (is_plugin_active('heritagepress/heritagepress.php')) {
  echo "Plugin is currently ACTIVE\n";
} else {
  echo "Plugin is currently INACTIVE\n";

  // Activate the plugin
  echo "Activating plugin...\n";
  $result = activate_plugin('heritagepress/heritagepress.php');

  if (is_wp_error($result)) {
    echo "ERROR: " . $result->get_error_message() . "\n";
  } else {
    echo "Plugin activated successfully!\n";
  }
}

// Check tables after activation
echo "\nChecking tables after activation...\n";
global $wpdb;
$tables = $wpdb->get_results("SHOW TABLES LIKE 'wp_hp_%'");
echo "HeritagePress tables found: " . count($tables) . "\n";

echo "\nActivation test complete.\n";
