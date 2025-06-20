<?php

/**
 * HP_ID_Checker_Controller
 *
 * Handles ID validation for all entity types (person, family, source, repository)
 * Replicates TNG admin_checkID.php functionality with WordPress security and standards
 *
 * @package HeritagePress
 * @subpackage Admin/Controllers
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_ID_Checker_Controller
{
  /**
   * Initialize the controller
   */
  public function __construct()
  {
    add_action('wp_ajax_hp_check_entity_id', array($this, 'check_entity_id'));
    add_action('wp_ajax_hp_check_person_id', array($this, 'check_person_id')); // Maintain compatibility
    add_action('wp_ajax_hp_check_family_id', array($this, 'check_family_id'));
    add_action('wp_ajax_hp_check_source_id', array($this, 'check_source_id'));
    add_action('wp_ajax_hp_check_repository_id', array($this, 'check_repository_id'));
  }

  /**
   * Universal ID checker for all entity types
   * Replicates TNG admin_checkID.php functionality
   */
  public function check_entity_id()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_check_entity_id')) {
      wp_send_json_error('Security check failed');
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
    }

    $entity_type = sanitize_text_field($_POST['type']);
    $check_id = sanitize_text_field($_POST['checkID']);
    $tree = sanitize_text_field($_POST['tree']);

    if (empty($entity_type) || empty($check_id)) {
      wp_send_json_error('Type and ID required');
    }

    if (empty($tree)) {
      wp_send_json_error('Tree selection required');
    }

    // Validate entity type
    if (!in_array($entity_type, array('person', 'family', 'source', 'repo'))) {
      wp_send_json_error('Invalid entity type');
    }

    global $wpdb;

    // Get table and column info
    $table_info = $this->get_table_info($entity_type);
    if (!$table_info) {
      wp_send_json_error('Invalid entity type');
    }

    $table_name = $wpdb->prefix . 'hp_' . $table_info['table'];
    $id_column = $table_info['id_column'];

    // Check if ID exists
    $exists = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$table_name} WHERE {$id_column} = %s AND gedcom = %s",
      $check_id,
      $tree
    ));

    // Get prefix/suffix configuration
    $validation_result = $this->validate_id_format($check_id, $entity_type);

    if ($exists > 0) {
      $message = sprintf(__('ID %s is already in use', 'heritagepress'), $check_id);
      $css_class = 'msgerror';
      $available = false;
    } elseif (!$validation_result['valid']) {
      $message = $validation_result['message'];
      $css_class = 'msgerror';
      $available = false;
    } else {
      $message = sprintf(__('ID %s is available', 'heritagepress'), $check_id);
      $css_class = 'msgapproved';
      $available = true;
    }

    // Return both JSON response and HTML for TNG-style compatibility
    if (isset($_POST['format']) && $_POST['format'] === 'html') {
      // Return HTML response like TNG
      header("Content-type:text/html; charset=UTF-8");
      echo "<span class=\"{$css_class}\">{$message}</span>";
      die();
    } else {
      // Return JSON response for AJAX
      wp_send_json_success(array(
        'available' => $available,
        'checkID' => $check_id,
        'message' => $message,
        'css_class' => $css_class
      ));
    }
  }

  /**
   * Check Person ID (maintain compatibility with existing implementation)
   */
  public function check_person_id()
  {
    $_POST['type'] = 'person';
    $_POST['checkID'] = $_POST['personID'];
    $_POST['_wpnonce'] = $_POST['_wpnonce'] ?? wp_create_nonce('hp_check_entity_id');
    $this->check_entity_id();
  }

  /**
   * Check Family ID
   */
  public function check_family_id()
  {
    $_POST['type'] = 'family';
    $_POST['checkID'] = $_POST['familyID'];
    $_POST['_wpnonce'] = $_POST['_wpnonce'] ?? wp_create_nonce('hp_check_entity_id');
    $this->check_entity_id();
  }

  /**
   * Check Source ID
   */
  public function check_source_id()
  {
    $_POST['type'] = 'source';
    $_POST['checkID'] = $_POST['sourceID'];
    $_POST['_wpnonce'] = $_POST['_wpnonce'] ?? wp_create_nonce('hp_check_entity_id');
    $this->check_entity_id();
  }

  /**
   * Check Repository ID
   */
  public function check_repository_id()
  {
    $_POST['type'] = 'repo';
    $_POST['checkID'] = $_POST['repoID'];
    $_POST['_wpnonce'] = $_POST['_wpnonce'] ?? wp_create_nonce('hp_check_entity_id');
    $this->check_entity_id();
  }

  /**
   * Get table information for entity type
   */
  private function get_table_info($entity_type)
  {
    switch ($entity_type) {
      case 'person':
        return array('table' => 'people', 'id_column' => 'personID');
      case 'family':
        return array('table' => 'families', 'id_column' => 'familyID');
      case 'source':
        return array('table' => 'sources', 'id_column' => 'sourceID');
      case 'repo':
        return array('table' => 'repositories', 'id_column' => 'repoID');
      default:
        return false;
    }
  }

  /**
   * Validate ID format with prefix/suffix rules
   * Replicates TNG prefix/suffix validation logic
   */
  private function validate_id_format($check_id, $entity_type)
  {
    // Get prefix/suffix configuration
    $config = $this->get_id_configuration($entity_type);

    $prefix = $config['prefix'];
    $suffix = $config['suffix'];

    // Basic format validation - alphanumeric, hyphens, underscores only
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $check_id)) {
      return array(
        'valid' => false,
        'message' => sprintf(__('ID %s contains invalid characters. Use only letters, numbers, hyphens, and underscores.', 'heritagepress'), $check_id)
      );
    }

    // Check prefix/suffix requirements if configured
    if (!empty($prefix) || !empty($suffix)) {
      $prefix_len = strlen($prefix);
      $suffix_len = strlen($suffix);

      // Check prefix
      if (!empty($prefix)) {
        if (substr($check_id, 0, $prefix_len) !== $prefix) {
          return array(
            'valid' => false,
            'message' => sprintf(__('ID %s must start with prefix: %s', 'heritagepress'), $check_id, $prefix)
          );
        }

        // Check if remaining part after prefix is numeric (if suffix is empty)
        if (empty($suffix)) {
          $numeric_part = substr($check_id, $prefix_len);
          if (!is_numeric($numeric_part)) {
            return array(
              'valid' => false,
              'message' => sprintf(__('ID %s must have numeric part after prefix %s', 'heritagepress'), $check_id, $prefix)
            );
          }
        }
      }

      // Check suffix
      if (!empty($suffix)) {
        $suffix_start = -$suffix_len;
        if (substr($check_id, $suffix_start) !== $suffix) {
          return array(
            'valid' => false,
            'message' => sprintf(__('ID %s must end with suffix: %s', 'heritagepress'), $check_id, $suffix)
          );
        }

        // Check if remaining part before suffix is numeric (if prefix is empty)
        if (empty($prefix)) {
          $numeric_part = substr($check_id, 0, $suffix_start);
          if (!is_numeric($numeric_part)) {
            return array(
              'valid' => false,
              'message' => sprintf(__('ID %s must have numeric part before suffix %s', 'heritagepress'), $check_id, $suffix)
            );
          }
        }
      }

      // Check middle numeric part if both prefix and suffix exist
      if (!empty($prefix) && !empty($suffix)) {
        $numeric_part = substr($check_id, $prefix_len, -$suffix_len);
        if (!is_numeric($numeric_part)) {
          return array(
            'valid' => false,
            'message' => sprintf(__('ID %s must have numeric part between prefix %s and suffix %s', 'heritagepress'), $check_id, $prefix, $suffix)
          );
        }
      }
    }

    return array('valid' => true, 'message' => '');
  }

  /**
   * Get ID configuration (prefix/suffix) for entity type
   * Can be extended to use WordPress options for configuration
   */
  private function get_id_configuration($entity_type)
  {
    // Default configuration - can be made configurable via WordPress options
    $default_config = array(
      'person' => array('prefix' => '', 'suffix' => ''),
      'family' => array('prefix' => 'F', 'suffix' => ''),
      'source' => array('prefix' => 'S', 'suffix' => ''),
      'repo' => array('prefix' => 'R', 'suffix' => '')
    );

    // Allow configuration via WordPress options
    $option_name = 'heritagepress_id_config_' . $entity_type;
    $saved_config = get_option($option_name, $default_config[$entity_type]);

    return $saved_config;
  }

  /**
   * Generate next available ID for entity type
   * Replicates TNG auto-ID generation functionality
   */
  public function generate_next_id($entity_type, $tree)
  {
    global $wpdb;

    $table_info = $this->get_table_info($entity_type);
    if (!$table_info) {
      return false;
    }

    $table_name = $wpdb->prefix . 'hp_' . $table_info['table'];
    $id_column = $table_info['id_column'];
    $config = $this->get_id_configuration($entity_type);

    $prefix = $config['prefix'];
    $suffix = $config['suffix'];

    // Find highest existing numeric ID with same prefix/suffix
    $like_pattern = $prefix . '%' . $suffix;

    $query = $wpdb->prepare(
      "SELECT {$id_column} FROM {$table_name} WHERE gedcom = %s AND {$id_column} LIKE %s ORDER BY CAST(SUBSTRING({$id_column}, %d, LENGTH({$id_column}) - %d) AS UNSIGNED) DESC LIMIT 1",
      $tree,
      $like_pattern,
      strlen($prefix) + 1,
      strlen($suffix)
    );

    $highest_id = $wpdb->get_var($query);

    if ($highest_id) {
      // Extract numeric part
      $prefix_len = strlen($prefix);
      $suffix_len = strlen($suffix);
      $numeric_part = substr($highest_id, $prefix_len, $suffix_len > 0 ? -$suffix_len : null);
      $next_number = intval($numeric_part) + 1;
    } else {
      $next_number = 1;
    }

    return $prefix . $next_number . $suffix;
  }
}

// Initialize the controller
new HP_ID_Checker_Controller();
