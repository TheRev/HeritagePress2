<?php

/**
 * Timeline Event Controller
 * Handles admin and AJAX actions for timeline events
 *
 * @package HeritagePress
 * @subpackage Controllers
 */
if (!defined('ABSPATH')) {
  exit;
}

require_once dirname(__FILE__) . '/../../includes/controllers/class-hp-base-controller.php';

class HP_TimelineEvent_Controller extends HP_Base_Controller
{
  public function __construct()
  {
    parent::__construct('timelineevent');
  }

  // Add a new timeline event
  public function ajax_add_timeline_event()
  {
    // TODO: Implement logic for adding a new timeline event
    wp_send_json_success(['message' => 'Timeline event added']);
  }

  // List all timeline events
  public function ajax_list_timeline_events()
  {
    // TODO: Implement logic
    wp_send_json_success(['timeline_events' => []]);
  }

  public function register_hooks()
  {
    parent::register_hooks();
    add_action('wp_ajax_hp_add_timeline_event', array($this, 'ajax_add_timeline_event'));
    add_action('wp_ajax_hp_list_timeline_events', array($this, 'ajax_list_timeline_events'));
  }
}
