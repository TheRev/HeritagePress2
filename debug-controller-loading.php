<?php

/**
 * Debug trees controller loading
 */

// Load WordPress
$wp_config_path = __DIR__ . '/../../../wp-config.php';
if (file_exists($wp_config_path)) {
  require_once $wp_config_path;
} else {
  die('WordPress not found. Please make sure this file is in the correct location.');
}

echo "<h2>Trees Controller Loading Debug</h2>";

// Check if base controller exists
$base_controller_path = __DIR__ . '/admin/controllers/class-hp-base-controller.php';
if (file_exists($base_controller_path)) {
  echo "<p>✅ Base controller file exists</p>";
  try {
    require_once $base_controller_path;
    echo "<p>✅ Base controller loaded successfully</p>";
  } catch (Exception $e) {
    echo "<p>❌ Error loading base controller: " . $e->getMessage() . "</p>";
  }
} else {
  echo "<p>❌ Base controller file not found at: $base_controller_path</p>";
}

// Check if trees controller exists
$trees_controller_path = __DIR__ . '/admin/controllers/class-hp-trees-controller.php';
if (file_exists($trees_controller_path)) {
  echo "<p>✅ Trees controller file exists</p>";
  try {
    require_once $trees_controller_path;
    echo "<p>✅ Trees controller loaded successfully</p>";
  } catch (Exception $e) {
    echo "<p>❌ Error loading trees controller: " . $e->getMessage() . "</p>";
    echo "<p>Error details: " . $e->getFile() . " line " . $e->getLine() . "</p>";
  }
} else {
  echo "<p>❌ Trees controller file not found at: $trees_controller_path</p>";
}

// Test if we can create a trees controller instance
if (class_exists('HP_Trees_Controller')) {
  try {
    $trees_controller = new HP_Trees_Controller();
    echo "<p>✅ Trees controller instantiated successfully</p>";
  } catch (Exception $e) {
    echo "<p>❌ Error creating trees controller: " . $e->getMessage() . "</p>";
  }
} else {
  echo "<p>❌ HP_Trees_Controller class not found</p>";
}

// List all available classes
echo "<h3>Available HP Classes:</h3>";
$classes = get_declared_classes();
foreach ($classes as $class) {
  if (strpos($class, 'HP_') === 0) {
    echo "<li>$class</li>";
  }
}
