<?php

/**
 * Tree Controller
 * Handles admin and AJAX actions for genealogy trees
 *
 * @package HeritagePress
 * @subpackage Controllers
 */
if (!defined('ABSPATH')) {
  exit;
}

require_once dirname(__FILE__) . '/../../includes/controllers/class-hp-base-controller.php';

class HP_Tree_Controller extends HP_Base_Controller
{
  public function __construct()
  {
    parent::__construct('tree');
  }

  // Add a new tree
  public function ajax_add_tree()
  {
    // TODO: Implement logic for adding a new tree
    wp_send_json_success(['message' => 'Tree added']);
  }

  // List all trees
  public function ajax_list_trees()
  {
    // TODO: Implement logic
    wp_send_json_success(['trees' => []]);
  }

  public function register_hooks()
  {
    parent::register_hooks();
    add_action('wp_ajax_hp_add_tree', array($this, 'ajax_add_tree'));
    add_action('wp_ajax_hp_list_trees', array($this, 'ajax_list_trees'));
  }
}
