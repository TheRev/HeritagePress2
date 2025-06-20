<?php

/**
 * Collection Controller
 * Handles admin and AJAX actions for media collections
 *
 * @package HeritagePress
 * @subpackage Controllers
 */
if (!defined('ABSPATH')) {
  exit;
}

require_once dirname(__FILE__) . '/../../includes/controllers/class-hp-base-controller.php';

class HP_Collection_Controller extends HP_Base_Controller
{
  public function __construct()
  {
    parent::__construct('collections');
  }

  public function register_hooks()
  {
    parent::register_hooks();
    add_action('wp_ajax_hp_list_collections', array($this, 'ajax_list_collections'));
    add_action('wp_ajax_hp_add_collection', array($this, 'ajax_add_collection'));
    add_action('wp_ajax_hp_edit_collection', array($this, 'ajax_edit_collection'));
    add_action('wp_ajax_hp_delete_collection', array($this, 'ajax_delete_collection'));
  }

  // List all collections
  public function ajax_list_collections()
  {
    // TODO: Implement logic
    wp_send_json_success(['collections' => []]);
  }

  // Add a new collection
  public function ajax_add_collection()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'Collection added']);
  }

  // Edit an existing collection
  public function ajax_edit_collection()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'Collection updated']);
  }

  // Delete a collection
  public function ajax_delete_collection()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'Collection deleted']);
  }
}
