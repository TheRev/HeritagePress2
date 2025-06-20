<?php

/**
 * Most Wanted Controller
 * Handles admin and AJAX actions for "Most Wanted" (featured people/media)
 *
 * @package HeritagePress
 * @subpackage Controllers
 */
if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . '../../includes/controllers/class-hp-base-controller.php';

class HP_MostWanted_Controller extends HP_Base_Controller
{
  public function __construct()
  {
    parent::__construct('mostwanted');
  }

  public function register_hooks()
  {
    parent::register_hooks();
    add_action('wp_ajax_hp_list_mostwanted', array($this, 'ajax_list_mostwanted'));
    add_action('wp_ajax_hp_add_mostwanted', array($this, 'ajax_add_mostwanted'));
    add_action('wp_ajax_hp_edit_mostwanted', array($this, 'ajax_edit_mostwanted'));
    add_action('wp_ajax_hp_delete_mostwanted', array($this, 'ajax_delete_mostwanted'));
    add_action('wp_ajax_hp_order_mostwanted', array($this, 'ajax_order_mostwanted'));
  }

  // List all Most Wanted entries
  public function ajax_list_mostwanted()
  {
    // TODO: Implement logic
    wp_send_json_success(['entries' => []]);
  }

  // Add a new Most Wanted entry
  public function ajax_add_mostwanted()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'Most Wanted entry added']);
  }

  // Edit an existing Most Wanted entry
  public function ajax_edit_mostwanted()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'Most Wanted entry updated']);
  }

  // Delete a Most Wanted entry
  public function ajax_delete_mostwanted()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'Most Wanted entry deleted']);
  }

  // Reorder Most Wanted entries
  public function ajax_order_mostwanted()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'Order updated']);
  }
}
