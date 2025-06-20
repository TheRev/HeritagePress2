<?php

/**
 * Family Controller
 * Handles admin and AJAX actions for families (spousal/parental relationships)
 *
 * @package HeritagePress
 * @subpackage Controllers
 */
if (!defined('ABSPATH')) {
  exit;
}

require_once dirname(__FILE__) . '/../../includes/controllers/class-hp-base-controller.php';

class HP_Family_Controller extends HP_Base_Controller
{
  public function __construct()
  {
    parent::__construct('family');
  }

  // Add a new family (linking people as spouses/parents)
  public function ajax_add_family()
  {
    // TODO: Implement logic for adding a family (parents, marriage, children, etc.)
    wp_send_json_success(['message' => 'Family added']);
  }

  // Edit an existing family
  public function ajax_edit_family()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'Family updated']);
  }

  // Delete a family
  public function ajax_delete_family()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'Family deleted']);
  }

  // Get a single family by ID
  public function ajax_get_family()
  {
    // TODO: Implement logic
    wp_send_json_success(['family' => null]);
  }

  // List all families
  public function ajax_list_families()
  {
    // TODO: Implement logic
    wp_send_json_success(['families' => []]);
  }

  public function register_hooks()
  {
    parent::register_hooks();
    add_action('wp_ajax_hp_add_family', array($this, 'ajax_add_family'));
    add_action('wp_ajax_hp_edit_family', array($this, 'ajax_edit_family'));
    add_action('wp_ajax_hp_delete_family', array($this, 'ajax_delete_family'));
    add_action('wp_ajax_hp_get_family', array($this, 'ajax_get_family'));
    add_action('wp_ajax_hp_list_families', array($this, 'ajax_list_families'));
  }
}
