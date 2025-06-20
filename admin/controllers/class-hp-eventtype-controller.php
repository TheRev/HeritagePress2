<?php

/**
 * Event Type Controller
 * Handles admin and AJAX actions for event types (custom event definitions)
 *
 * @package HeritagePress
 * @subpackage Controllers
 */
if (!defined('ABSPATH')) {
  exit;
}

require_once dirname(__FILE__) . '/../../includes/controllers/class-hp-base-controller.php';

class HP_EventType_Controller extends HP_Base_Controller
{
  public function __construct()
  {
    parent::__construct('eventtype');
  }

  // Add a new event type
  public function ajax_add_event_type()
  {
    // TODO: Implement logic for adding a new event type
    wp_send_json_success(['message' => 'Event type added']);
  }

  // Edit an event type
  public function ajax_edit_event_type()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'Event type updated']);
  }

  // Delete an event type
  public function ajax_delete_event_type()
  {
    // TODO: Implement logic
    wp_send_json_success(['message' => 'Event type deleted']);
  }

  // List all event types
  public function ajax_list_event_types()
  {
    // TODO: Implement logic
    wp_send_json_success(['event_types' => []]);
  }

  /**
   * Bulk actions for selected event types
   * Expects POST: action (string), eventtype_ids[] (array), _wpnonce
   * Actions: ignoreselected, acceptselected, collapseselected, expselected, deleteselected
   */
  public function ajax_bulk_eventtype_action()
  {
    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error(['message' => __('Insufficient permissions.', 'heritagepress')]);
    }
    if (!isset($_POST['action'], $_POST['eventtype_ids'], $_POST['_wpnonce']) || !is_array($_POST['eventtype_ids'])) {
      wp_send_json_error(['message' => __('Invalid request.', 'heritagepress')]);
    }
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_bulk_eventtype_action')) {
      wp_send_json_error(['message' => __('Security check failed.', 'heritagepress')]);
    }
    global $wpdb;
    $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';
    $ids = array_map('intval', $_POST['eventtype_ids']);
    $action = sanitize_text_field($_POST['action']);
    $count = 0;
    if (!$ids) {
      wp_send_json_error(['message' => __('No event types selected.', 'heritagepress')]);
    }
    switch ($action) {
      case 'ignoreselected':
        $count = $wpdb->query("UPDATE $eventtypes_table SET keep=0 WHERE eventtypeID IN (" . implode(',', $ids) . ")");
        break;
      case 'acceptselected':
        $count = $wpdb->query("UPDATE $eventtypes_table SET keep=1 WHERE eventtypeID IN (" . implode(',', $ids) . ")");
        break;
      case 'collapseselected':
        $count = $wpdb->query("UPDATE $eventtypes_table SET collapse=1 WHERE eventtypeID IN (" . implode(',', $ids) . ")");
        break;
      case 'expselected':
        $count = $wpdb->query("UPDATE $eventtypes_table SET collapse=0 WHERE eventtypeID IN (" . implode(',', $ids) . ")");
        break;
      case 'deleteselected':
        foreach ($ids as $id) {
          $wpdb->delete($eventtypes_table, ['eventtypeID' => $id]);
          $count++;
        }
        break;
      default:
        wp_send_json_error(['message' => __('Invalid action.', 'heritagepress')]);
    }
    wp_send_json_success(['count' => $count, 'message' => sprintf(__('Bulk action "%s" applied to %d event types.', 'heritagepress'), $action, $count)]);
  }

  public function register_hooks()
  {
    parent::register_hooks();
    add_action('wp_ajax_hp_add_event_type', array($this, 'ajax_add_event_type'));
    add_action('wp_ajax_hp_edit_event_type', array($this, 'ajax_edit_event_type'));
    add_action('wp_ajax_hp_delete_event_type', array($this, 'ajax_delete_event_type'));
    add_action('wp_ajax_hp_list_event_types', array($this, 'ajax_list_event_types'));
    add_action('wp_ajax_hp_bulk_eventtype_action', array($this, 'ajax_bulk_eventtype_action'));
  }
}
