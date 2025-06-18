<?php

/**
 * Quick test to see if the trees controller is working
 */

// Load WordPress
$wp_config_path = __DIR__ . '/../../../wp-config.php';
if (file_exists($wp_config_path)) {
  require_once $wp_config_path;
} else {
  die('WordPress not found. Please make sure this file is in the correct location.');
}

echo "<h2>Trees Controller Test</h2>";

// Check if HP_Trees_Controller class exists
if (class_exists('HP_Trees_Controller')) {
  echo "<p>✅ HP_Trees_Controller class is loaded</p>";

  // Check if the AJAX action is registered
  global $wp_filter;
  $ajax_action = 'wp_ajax_hp_add_tree';

  if (isset($wp_filter[$ajax_action])) {
    echo "<p>✅ AJAX action 'hp_add_tree' is registered</p>";
    echo "<pre>";
    print_r($wp_filter[$ajax_action]);
    echo "</pre>";
  } else {
    echo "<p>❌ AJAX action 'hp_add_tree' is NOT registered</p>";
  }
} else {
  echo "<p>❌ HP_Trees_Controller class is NOT loaded</p>";
}

// Test if we can create a trees controller instance
try {
  $trees_controller = new HP_Trees_Controller();
  echo "<p>✅ Trees controller can be instantiated</p>";
} catch (Exception $e) {
  echo "<p>❌ Error creating trees controller: " . $e->getMessage() . "</p>";
}

// Check AJAX hooks
echo "<h3>All AJAX hooks for 'hp_' actions:</h3>";
echo "<ul>";
foreach ($wp_filter as $hook => $callbacks) {
  if (strpos($hook, 'wp_ajax_hp_') === 0) {
    echo "<li><strong>$hook</strong> - " . count($callbacks->callbacks) . " callback(s)</li>";
  }
}
echo "</ul>";
