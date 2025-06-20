<?php

/**
 * Review (Temp Event) Controller for HeritagePress
 * Handles review/temp event deletion and management
 */

if (!defined('ABSPATH')) exit;

class HP_Review_Controller
{
  private $table;
  private $wpdb;

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->table = $wpdb->prefix . 'hp_temp_events';

    // Register AJAX handlers
    add_action('wp_ajax_hp_delete_review', [$this, 'ajax_delete_review']);
    add_action('wp_ajax_hp_approve_review', [$this, 'ajax_approve_review']);
  }

  /**
   * Delete a review/temp event by ID
   */
  public function delete_review($temp_id)
  {
    $row = $this->wpdb->get_row($this->wpdb->prepare(
      "SELECT type FROM {$this->table} WHERE tempID = %d",
      $temp_id
    ));
    if (!$row) return false;

    $deleted = $this->wpdb->delete($this->table, ['tempID' => $temp_id], ['%d']);
    if ($deleted) {
      $this->log_admin_action('Deleted review: ' . $temp_id);
      return $row->type;
    }
    return false;
  }

  /**
   * Approve a review/temp event by ID
   */
  public function approve_review($temp_id)
  {
    $row = $this->wpdb->get_row($this->wpdb->prepare(
      "SELECT * FROM {$this->table} WHERE tempID = %d",
      $temp_id
    ));
    if (!$row) return false;
    $data = maybe_unserialize($row->eventstr);
    if ($row->type === 'I') {
      // Approve person: insert or update in hp_people
      $people_table = $this->wpdb->prefix . 'hp_people';
      $exists = $this->wpdb->get_var($this->wpdb->prepare(
        "SELECT COUNT(*) FROM $people_table WHERE personID = %s AND gedcom = %s",
        $row->personID,
        $row->gedcom
      ));
      if ($row->action === 'add' && !$exists) {
        $this->wpdb->insert($people_table, $data);
      } elseif ($row->action === 'update' && $exists) {
        $this->wpdb->update($people_table, $data, ['personID' => $row->personID, 'gedcom' => $row->gedcom]);
      }
    } elseif ($row->type === 'F') {
      // Approve family: insert or update in hp_families
      $families_table = $this->wpdb->prefix . 'hp_families';
      $exists = $this->wpdb->get_var($this->wpdb->prepare(
        "SELECT COUNT(*) FROM $families_table WHERE familyID = %s AND gedcom = %s",
        $row->familyID,
        $row->gedcom
      ));
      if ($row->action === 'add' && !$exists) {
        $this->wpdb->insert($families_table, $data);
      } elseif ($row->action === 'update' && $exists) {
        $this->wpdb->update($families_table, $data, ['familyID' => $row->familyID, 'gedcom' => $row->gedcom]);
      }
    }
    $this->wpdb->delete($this->table, ['tempID' => $temp_id], ['%d']);
    $this->log_admin_action('Approved review: ' . $temp_id);
    return true;
  }

  /**
   * AJAX handler for deleting a review
   */
  public function ajax_delete_review()
  {
    check_ajax_referer('hp_review_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Insufficient permissions.', 'heritagepress')]);
    }
    $temp_id = isset($_POST['tempID']) ? intval($_POST['tempID']) : 0;
    if (!$temp_id) {
      wp_send_json_error(['message' => __('No review specified.', 'heritagepress')]);
    }
    $type = $this->delete_review($temp_id);
    if ($type !== false) {
      wp_send_json_success(['type' => $type, 'message' => __('Review deleted.', 'heritagepress')]);
    } else {
      wp_send_json_error(['message' => __('Failed to delete review.', 'heritagepress')]);
    }
  }

  /**
   * AJAX handler for approving a review
   */
  public function ajax_approve_review()
  {
    check_ajax_referer('hp_review_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Insufficient permissions.', 'heritagepress')]);
    }
    $temp_id = isset($_POST['tempID']) ? intval($_POST['tempID']) : 0;
    if (!$temp_id) {
      wp_send_json_error(['message' => __('No review specified.', 'heritagepress')]);
    }
    $ok = $this->approve_review($temp_id);
    if ($ok) {
      wp_send_json_success(['message' => __('Review approved and published.', 'heritagepress')]);
    } else {
      wp_send_json_error(['message' => __('Failed to approve review.', 'heritagepress')]);
    }
  }

  /**
   * Log admin action
   */
  private function log_admin_action($message)
  {
    // Simple logging; replace with a more robust system if needed
    error_log('HeritagePress Review Action: ' . $message);
  }
}

// Initialize controller
new HP_Review_Controller();
