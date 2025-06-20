<?php
// Debug script to trace form submission
error_log("=== HERITAGEPRESS DEBUG STARTED ===");

// Check if action handler is registered
$wp_filter = $GLOBALS['wp_filter'];
if (isset($wp_filter['admin_post_heritagepress_add_newtree'])) {
  error_log("✓ Action 'admin_post_heritagepress_add_newtree' is registered");
  foreach ($wp_filter['admin_post_heritagepress_add_newtree']->callbacks as $priority => $callbacks) {
    foreach ($callbacks as $callback) {
      error_log("  - Priority $priority: " . print_r($callback['function'], true));
    }
  }
} else {
  error_log("✗ Action 'admin_post_heritagepress_add_newtree' is NOT registered");
}

// Test database connection
global $wpdb;
$trees_table = $wpdb->prefix . 'hp_trees';
$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $trees_table));
if ($table_exists) {
  error_log("✓ Table $trees_table exists");
} else {
  error_log("✗ Table $trees_table does not exist");
}

// Check if function exists
if (function_exists('heritagepress_handle_add_newtree_tab')) {
  error_log("✓ Function 'heritagepress_handle_add_newtree_tab' exists");
} else {
  error_log("✗ Function 'heritagepress_handle_add_newtree_tab' does not exist");
}

// Test form submission directly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'heritagepress_add_newtree') {
  error_log("=== FORM SUBMITTED ===");
  error_log("POST data: " . print_r($_POST, true));

  // Call the handler directly for testing
  if (function_exists('heritagepress_handle_add_newtree_tab')) {
    error_log("Calling handler directly...");
    try {
      heritagepress_handle_add_newtree_tab();
    } catch (Exception $e) {
      error_log("Handler error: " . $e->getMessage());
    }
  }
}

error_log("=== HERITAGEPRESS DEBUG ENDED ===");
