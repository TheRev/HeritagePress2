<?php
// Log any null or invalid path arguments passed to wp_is_stream or wp_normalize_path
add_filter('wp_is_stream', function ($result, $path) {
  if ($path === null) {
    error_log('[HeritagePress Debug] wp_is_stream called with null path. Backtrace: ' . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10), true));
  }
  return $result;
}, 10, 2);

add_filter('wp_normalize_path', function ($path) {
  if ($path === null) {
    error_log('[HeritagePress Debug] wp_normalize_path called with null path. Backtrace: ' . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10), true));
  }
  return $path;
});
