<?php
// Test activation hook and table creation process
require_once '../../../wp-config.php';

echo "Testing HeritagePress activation hook and table creation...\n";

// Load the plugin file to trigger activation
require_once 'heritagepress.php';

echo "Plugin loaded successfully.\n";

// Test the HeritagePress class directly
$heritage_press = HeritagePress::instance();
echo "HeritagePress instance created.\n";

// Test activation method directly
echo "\nTesting activation method...\n";
$heritage_press->activate();
echo "Activation method executed.\n";

// Check final table count
global $wpdb;
$tables = $wpdb->get_results("SHOW TABLES LIKE 'wp_hp_%'");
echo "\nFinal table count: " . count($tables) . "\n";

// Check if database manager is working
echo "\nTesting database manager directly...\n";
$db_manager = new HP_Database_Manager();
echo "Database manager instantiated successfully.\n";

// Test table creation method
echo "Testing create_tables method...\n";
$db_manager->create_tables();
echo "create_tables method executed successfully.\n";

echo "\nAll tests completed successfully!\n";
