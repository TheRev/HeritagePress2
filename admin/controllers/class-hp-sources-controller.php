<?php

/**
 * Sources Controller
 * Handles admin functionality for managing sources in HeritagePress.
 */
if (!defined('ABSPATH')) exit;
require_once dirname(__FILE__) . '/../../includes/controllers/class-hp-base-controller.php';
class HP_Sources_Controller extends HP_Base_Controller
{
  public function __construct()
  {
    parent::__construct('sources');
    $this->capabilities = array(
      'manage_sources' => 'manage_genealogy',
      'edit_sources' => 'edit_genealogy',
      'delete_sources' => 'delete_genealogy'
    );
  }
  /**
   * Generate a unique source ID
   */
  public function generate_source_id($gedcom, $base_name = '')
  {
    global $wpdb;
    $sources_table = $wpdb->prefix . 'hp_sources';
    if (empty($base_name)) $base_name = 'S';
    $pattern = $base_name . '%';
    $existing_ids = $wpdb->get_col($wpdb->prepare(
      "SELECT sourceID FROM $sources_table WHERE gedcom = %s AND sourceID LIKE %s ORDER BY sourceID",
      $gedcom,
      $pattern
    ));
    $highest_num = 0;
    foreach ($existing_ids as $id) {
      if (preg_match('/(\d+)$/', $id, $matches)) {
        $num = intval($matches[1]);
        if ($num > $highest_num) $highest_num = $num;
      }
    }
    return $base_name . ($highest_num + 1);
  }
  /**
   * AJAX: Generate source ID
   */
  public function ajax_generate_source_id()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }
    $gedcom = sanitize_text_field($_POST['gedcom']);
    $base_name = sanitize_text_field($_POST['base_name'] ?? '');
    $source_id = $this->generate_source_id($gedcom, $base_name);
    wp_send_json_success(array('source_id' => $source_id));
  }
  public static function register_ajax_handlers()
  {
    add_action('wp_ajax_hp_generate_source_id', array('HP_Sources_Controller', 'ajax_generate_source_id_static'));
  }
  public static function ajax_generate_source_id_static()
  {
    $controller = new self();
    $controller->ajax_generate_source_id();
  }
}
// Register AJAX handler for generating source ID
if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && $_POST['action'] === 'hp_generate_source_id') {
  HP_Sources_Controller::register_ajax_handlers();
}
