<?php

/**
 * DNA Test Controller
 * Handles admin and AJAX actions for DNA tests
 *
 * @package HeritagePress
 * @subpackage Controllers
 */
if (!defined('ABSPATH')) {
  exit;
}

require_once dirname(__FILE__) . '/../../includes/controllers/class-hp-base-controller.php';

class HP_DNA_Test_Controller extends HP_Base_Controller
{
  public function __construct()
  {
    parent::__construct('dnatests');
  }

  public function register_hooks()
  {
    parent::register_hooks();
    add_action('wp_ajax_hp_list_dna_tests', array($this, 'ajax_list_dna_tests'));
    add_action('wp_ajax_hp_add_dna_test', array($this, 'ajax_add_dna_test'));
    add_action('wp_ajax_hp_edit_dna_test', array($this, 'ajax_edit_dna_test'));
    add_action('wp_ajax_hp_delete_dna_test', array($this, 'ajax_delete_dna_test'));
    // Bulk delete selected DNA tests (AJAX endpoint)
    add_action('wp_ajax_hp_bulk_delete_dna_tests', array($this, 'ajax_bulk_delete_dna_tests'));
  }

  // List all DNA tests
  public function ajax_list_dna_tests()
  {
    // TODO: Implement logic
    wp_send_json_success(['tests' => []]);
  }

  // Add a new DNA test
  public function ajax_add_dna_test()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'DNA test added']);
  }

  // Edit an existing DNA test
  public function ajax_edit_dna_test()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'DNA test updated']);
  }

  // Delete a DNA test
  public function ajax_delete_dna_test()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'DNA test deleted']);
  }

  /**
   * Bulk delete selected DNA tests
   * Expects POST: test_ids[] (array of test IDs), _wpnonce
   */
  public function ajax_bulk_delete_dna_tests()
  {
    if (!current_user_can('delete_genealogy')) {
      wp_send_json_error(['message' => __('Insufficient permissions.', 'heritagepress')]);
    }
    if (!isset($_POST['test_ids'], $_POST['_wpnonce']) || !is_array($_POST['test_ids'])) {
      wp_send_json_error(['message' => __('Invalid request.', 'heritagepress')]);
    }
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_bulk_delete_dna_tests')) {
      wp_send_json_error(['message' => __('Security check failed.', 'heritagepress')]);
    }
    global $wpdb;
    $test_ids = array_map('sanitize_text_field', $_POST['test_ids']);
    $deleted = 0;
    foreach ($test_ids as $test_id) {
      // Delete links first (as in TNG)
      $wpdb->delete($wpdb->prefix . 'hp_dna_links', ['testID' => $test_id]);
      // Delete the DNA test
      $result = $wpdb->delete($wpdb->prefix . 'hp_dna_tests', ['testID' => $test_id]);
      if ($result) $deleted++;
    }
    wp_send_json_success(['deleted' => $deleted, 'message' => sprintf(_n('%d DNA test deleted.', '%d DNA tests deleted.', $deleted, 'heritagepress'), $deleted)]);
  }
}
