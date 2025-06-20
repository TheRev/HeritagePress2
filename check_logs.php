<?php
// Check WordPress error logs
$log_file = ini_get('error_log');
if (!$log_file) {
  $log_file = WP_CONTENT_DIR . '/debug.log';
}

echo "Checking WordPress error log: $log_file\n\n";

if (file_exists($log_file)) {
  echo "=== RECENT LOG ENTRIES ===\n";
  $lines = file($log_file);
  $recent_lines = array_slice($lines, -50); // Last 50 lines

  foreach ($recent_lines as $line) {
    if (strpos($line, 'HeritagePress') !== false || strpos($line, 'heritagepress') !== false) {
      echo $line;
    }
  }
} else {
  echo "Error log file not found\n";
}

// Also check if WP_DEBUG is enabled
echo "\n=== DEBUG SETTINGS ===\n";
echo "WP_DEBUG: " . (defined('WP_DEBUG') && WP_DEBUG ? 'enabled' : 'disabled') . "\n";
echo "WP_DEBUG_LOG: " . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'enabled' : 'disabled') . "\n";
echo "WP_DEBUG_DISPLAY: " . (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'enabled' : 'disabled') . "\n";
