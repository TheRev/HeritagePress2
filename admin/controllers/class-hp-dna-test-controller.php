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
}
