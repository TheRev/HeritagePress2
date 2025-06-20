<?php

/**
 * Custom Text Update Controller
 *
 * Handles custom text file management and updates for HeritagePress
 * Replicates functionality from TNG admin_cust_text_update.php
 *
 * @package HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Custom_Text_Controller
{
  private $languages_dir;
  private $key;
  private $insert;
  private $old_format;
  private $new_format;
  private $example_message;
  private $modified_count = 0;
  private $log_messages = array();
  public function __construct()
  {
    $this->languages_dir = HERITAGEPRESS_PLUGIN_DIR . 'languages/';

    // Standard comments and format strings - ensure they're not null
    $this->key = "//Put your own custom messages here, like this:";
    $this->insert = "//Mods should put their changes before this line, local changes should come after it.";
    $this->old_format = '//$text[messagename]';
    $this->new_format = '//$text[\'messagename\']';
    $this->example_message = ' = "This is the message"';

    // Initialize arrays
    $this->log_messages = array();
    $this->modified_count = 0;

    // Hook for AJAX actions
    add_action('wp_ajax_hp_update_custom_text', array($this, 'ajax_update_custom_text'));
  }

  /**
   * Display the custom text update utility page
   */
  public function display_page()
  {
    // Check permissions
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Handle form submission
    if (isset($_POST['update_custom_text']) && check_admin_referer('hp_update_custom_text')) {
      $this->process_update();
    }

    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/custom-text-update.php';
  }

  /**
   * Process custom text file updates
   */
  public function process_update()
  {
    $this->modified_count = 0;
    $this->log_messages = array();

    // Add initial log message
    $this->add_log_message($this->safe_text('Starting custom text file update process...'));

    // Scan language directories
    $this->scan_directory((string)$this->languages_dir);

    // Create missing language files if needed
    $this->create_missing_files();

    // Log completion
    if ($this->modified_count > 0) {
      $this->add_log_message($this->safe_sprintf('Modified %d files.', $this->modified_count));
    } else {
      $this->add_log_message($this->safe_text('No changes required.'));
    }

    $this->add_log_message($this->safe_text('All cust_text.php files are now up to current standard.'));
    $this->add_log_message($this->safe_text('The originals of files that have been changed are saved as cust_text.bak in the original directory.'));

    // Store results for display
    set_transient('hp_custom_text_update_results', array(
      'modified_count' => $this->modified_count,
      'log_messages' => $this->log_messages
    ), 60);

    // Redirect to prevent resubmission
    wp_redirect(add_query_arg(array(
      'page' => 'heritagepress-utilities-custom-text',
      'updated' => '1'
    ), admin_url('admin.php')));
    exit;
  }
  /**
   * Sanitize and validate a file path
   */
  private function sanitize_path($path)
  {
    if (!is_string($path)) {
      return '';
    }
    $path = trim((string)$path);
    return empty($path) ? '' : str_replace('\\', '/', $path);
  }

  /**
   * Join path segments safely
   */
  private function join_paths(...$segments)
  {
    $segments = array_filter(array_map([$this, 'sanitize_path'], $segments), 'strlen');
    return implode('/', $segments);
  }

  /**
   * Recursively scan directories for cust_text.php files
   */
  private function scan_directory($dir)
  {
    $dir = $this->sanitize_path($dir);
    if (empty($dir) || !is_dir($dir)) {
      return;
    }

    $files = scandir($dir);
    if ($files === false) {
      $this->add_log_message($this->safe_sprintf('Cannot read directory: %s', $dir));
      return;
    }

    $files = array_diff($files, array('.', '..'));
    foreach ($files as $file) {
      if (empty($file)) {
        continue;
      }

      $full_path = $this->join_paths($dir, $file);
      if (is_dir($full_path)) {
        $this->scan_directory($full_path . '/');
      } else {
        $this->modify_file($dir, $file);
      }
    }
  }

  /**
   * Modify a custom text file if it's a cust_text.php file
   */
  private function modify_file($dir, $file)
  {
    if ($file !== 'cust_text.php') {
      return;
    }

    // Sanitize paths
    $dir = $this->sanitize_path($dir);
    $file = $this->sanitize_path($file);
    if (empty($dir) || empty($file)) {
      return;
    }

    $file_path = $this->join_paths($dir, $file);
    if (!file_exists($file_path)) {
      return;
    }

    // Read and validate content
    $content = file_get_contents($file_path);
    if ($content === false || $content === null) {
      $this->add_log_message($this->safe_sprintf('Cannot read file: %s', $file_path));
      return;
    }
    $content = (string)$content;

    // Initialize changed flag
    $changed = false;

    // Ensure class properties are strings
    $insert = (string)($this->insert ?? '');
    $key = (string)($this->key ?? '');
    $old_format = (string)($this->old_format ?? '');
    $new_format = (string)($this->new_format ?? '');
    $example_message = (string)($this->example_message ?? '');

    // Determine line ending style
    $eol = strpos($content, "\r\n") !== false ? "\r\n" : "\n";

    // Process content
    if (!empty($insert) && strpos($content, $insert) === false) {
      if (!empty($key) && strpos($content, $key) === false) {
        $content = str_replace("<?php", "<?php{$eol}{$insert}{$eol}{$key}{$eol}{$new_format}{$example_message}", $content);
      } elseif (!empty($key)) {
        $content = str_replace($key, "{$insert}{$eol}{$key}", $content);
      }
      $changed = true;
    }

    if (!empty($old_format) && strpos($content, $old_format) !== false) {
      $content = str_replace($old_format, $new_format, $content);
      $changed = true;
    }

    // Save changes if any were made
    if ($changed) {
      // Create backup first
      $backup_path = $this->join_paths($dir, 'cust_text.bak');
      if (file_exists($backup_path)) {
        unlink($backup_path);
      }

      if (!rename($file_path, $backup_path)) {
        $this->add_log_message($this->safe_sprintf('Cannot make backup of %s', $file_path));
        return;
      }

      // Write updated content
      if (file_put_contents($file_path, $content) === false) {
        $this->add_log_message($this->safe_sprintf('Cannot create new %s', $file_path));
        // Try to restore backup
        rename($backup_path, $file_path);
        return;
      }

      $this->add_log_message($this->safe_sprintf('Updated: %s', $dir));
      $this->modified_count++;
    }
  }
  /**
   * Create missing cust_text.php files for languages that don't have them
   */
  private function create_missing_files()
  {
    $eol = "\r\n";

    // Ensure class properties are not null
    $insert = $this->insert ?? '';
    $key = $this->key ?? '';
    $new_format = $this->new_format ?? '';
    $example_message = $this->example_message ?? '';

    $template_content = "<?php{$eol}{$insert}{$eol}{$key}{$eol}{$new_format}{$example_message}{$eol}{$eol}?>";

    // Check each language directory
    $languages_dir = $this->languages_dir ?? '';
    if (empty($languages_dir) || !is_dir($languages_dir)) {
      return;
    }

    $language_dirs = scandir($languages_dir);
    if ($language_dirs === false) {
      return;
    }

    $language_dirs = array_diff($language_dirs, array('.', '..'));

    foreach ($language_dirs as $lang_dir) {
      if (empty($lang_dir)) {
        continue;
      }

      $full_dir_path = $languages_dir . $lang_dir . '/';
      $cust_text_path = $full_dir_path . 'cust_text.php';

      if (is_dir($full_dir_path) && !file_exists($cust_text_path)) {
        // Create directory if it doesn't exist
        if (!is_dir($full_dir_path)) {
          wp_mkdir_p($full_dir_path);
        }

        // Create the file
        if (file_put_contents($cust_text_path, $template_content) !== false) {
          $this->add_log_message(sprintf(__('Created: %s', 'heritagepress'), $full_dir_path));
          $this->modified_count++;
        } else {
          $this->add_log_message(sprintf(__('Failed to create: %s', 'heritagepress'), $cust_text_path));
        }
      }
    }
  }

  /**
   * Add a log message
   */
  private function add_log_message($message)
  {
    // Ensure message is a string before adding to log
    $message = is_string($message) ? $message : (string)$message;
    $this->log_messages[] = $message;
  }

  /**
   * Safe wrapper for WordPress text functions
   */
  private function safe_text($text, $domain = 'heritagepress')
  {
    // Ensure text is a string before passing to WordPress functions
    $text = is_string($text) ? $text : (string)$text;
    return __($text, $domain);
  }

  /**
   * Safe wrapper for sprintf with text domain
   */
  private function safe_sprintf($format, ...$args)
  {
    // Ensure format is a string
    $format = is_string($format) ? $format : (string)$format;
    // Ensure all string arguments are actually strings
    $args = array_map(function ($arg) {
      return is_string($arg) ? $arg : (string)$arg;
    }, $args);
    return sprintf($this->safe_text($format), ...$args);
  }

  /**
   * AJAX handler for custom text update
   */
  public function ajax_update_custom_text()
  {
    // Verify nonce
    if (!check_admin_referer('hp_custom_text_ajax', 'nonce')) {
      wp_die($this->safe_text('Security check failed'));
    }

    // Check permissions
    if (!current_user_can('manage_options')) {
      wp_die($this->safe_text('Insufficient permissions'));
    }

    // Process the update
    $this->process_update();

    // Return results
    wp_send_json_success(array(
      'modified_count' => (int)$this->modified_count,
      'log_messages' => array_map(function ($msg) {
        return is_string($msg) ? $msg : (string)$msg;
      }, $this->log_messages)
    ));
  }
  /**
   * Get current language directories
   */
  public function get_language_directories()
  {
    $languages_dir = $this->sanitize_path($this->languages_dir);
    if (empty($languages_dir) || !is_dir($languages_dir)) {
      return array();
    }

    $dirs = array();
    $items = scandir($languages_dir);
    if ($items === false) {
      return array();
    }

    $items = array_diff($items, array('.', '..'));
    foreach ($items as $item) {
      if (empty($item)) {
        continue;
      }

      $full_path = $this->join_paths($languages_dir, $item);
      if (is_dir($full_path)) {
        $cust_text_path = $this->join_paths($full_path, 'cust_text.php');
        $language_path = $this->join_paths($full_path, 'language.php');

        $dirs[] = array(
          'name' => (string)$item,
          'path' => $full_path,
          'has_cust_text' => file_exists($cust_text_path),
          'has_language_file' => file_exists($language_path)
        );
      }
    }

    return $dirs;
  }

  /**
   * Check if a cust_text.php file needs updating
   */
  public function check_file_needs_update($file_path)
  {
    $file_path = $this->sanitize_path($file_path);
    if (empty($file_path) || !file_exists($file_path)) {
      return true; // Needs to be created
    }

    $content = file_get_contents($file_path);
    if ($content === false || $content === null) {
      return false; // Can't read file
    }

    // Ensure content and properties are strings
    $content = (string)$content;
    $insert = (string)($this->insert ?? '');
    $old_format = (string)($this->old_format ?? '');

    // Check if modern format is present
    return (empty($insert) || strpos($content, $insert) === false ||
      (!empty($old_format) && strpos($content, $old_format) !== false));
  }

  /**
   * Get update status for all language directories
   */
  public function get_update_status()
  {
    $languages = $this->get_language_directories();
    $status = array(
      'total_languages' => count($languages),
      'need_update' => 0,
      'missing_files' => 0,
      'up_to_date' => 0,
      'details' => array()
    );

    foreach ($languages as $lang) {
      $cust_text_path = $lang['path'] . '/cust_text.php';
      $needs_update = $this->check_file_needs_update($cust_text_path);

      if (!$lang['has_cust_text']) {
        $status['missing_files']++;
        $file_status = 'missing';
      } elseif ($needs_update) {
        $status['need_update']++;
        $file_status = 'needs_update';
      } else {
        $status['up_to_date']++;
        $file_status = 'up_to_date';
      }

      $status['details'][$lang['name']] = array(
        'status' => $file_status,
        'has_language_file' => $lang['has_language_file'],
        'path' => $lang['path']
      );
    }

    return $status;
  }
}
