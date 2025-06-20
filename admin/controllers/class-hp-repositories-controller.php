<?php

/**
 * Repositories Controller
 * Handles admin functionality for managing repositories in HeritagePress.
 */
if (!defined('ABSPATH')) exit;
require_once dirname(__FILE__) . '/../../includes/controllers/class-hp-base-controller.php';
class HP_Repositories_Controller extends HP_Base_Controller
{
  public function __construct()
  {
    parent::__construct('repositories');
    $this->capabilities = array(
      'manage_repositories' => 'manage_genealogy',
      'edit_repositories' => 'edit_genealogy',
      'delete_repositories' => 'delete_genealogy'
    );
  }
  /**
   * Generate a unique repository ID
   */
  public function generate_repository_id($gedcom, $base_name = '')
  {
    global $wpdb;
    $repositories_table = $wpdb->prefix . 'hp_repositories';
    if (empty($base_name)) $base_name = 'R';
    $pattern = $base_name . '%';
    $existing_ids = $wpdb->get_col($wpdb->prepare(
      "SELECT repoID FROM $repositories_table WHERE gedcom = %s AND repoID LIKE %s ORDER BY repoID",
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
   * AJAX: Generate repository ID
   */
  public function ajax_generate_repository_id()
  {
    if (!$this->verify_nonce($_POST['nonce'])) {
      wp_send_json_error('Security check failed');
    }
    $gedcom = sanitize_text_field($_POST['gedcom']);
    $base_name = sanitize_text_field($_POST['base_name'] ?? '');
    $repo_id = $this->generate_repository_id($gedcom, $base_name);
    wp_send_json_success(array('repo_id' => $repo_id));
  }
  public static function register_ajax_handlers()
  {
    add_action('wp_ajax_hp_generate_repository_id', array('HP_Repositories_Controller', 'ajax_generate_repository_id_static'));
  }
  public static function ajax_generate_repository_id_static()
  {
    $controller = new self();
    $controller->ajax_generate_repository_id();
  }
}
// Register AJAX handler for generating repository ID
if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && $_POST['action'] === 'hp_generate_repository_id') {
  HP_Repositories_Controller::register_ajax_handlers();
}
