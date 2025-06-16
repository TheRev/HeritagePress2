<?php

/**
 * HeritagePress Tree Assignment AJAX Handler
 *
 * Handles AJAX requests for assigning people to trees
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Handle tree assignment AJAX request
 */
function hp_assign_person_to_tree()
{    // Check nonce for security
  if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_assign_person_to_tree')) {
    wp_send_json_error(array('message' => 'Security check failed'));
    return;
  }

  // Check user capabilities
  if (!current_user_can('edit_genealogy')) {
    wp_send_json_error(array('message' => 'Insufficient permissions'));
    return;
  }
  // Get and validate input
  $person_id = sanitize_text_field($_POST['person_id']);
  $current_tree = sanitize_text_field($_POST['current_tree']);
  $new_tree = sanitize_text_field($_POST['new_tree']);

  if (empty($person_id)) {
    wp_send_json_error(array('message' => 'Person ID is required'));
    return;
  }

  // Allow empty new_tree for unassigning from tree
  if ($new_tree === '') {
    $new_tree = null;
  }
  global $wpdb;

  // Get table names
  $people_table = $wpdb->prefix . 'hp_people';
  $trees_table = $wpdb->prefix . 'hp_trees';

  try {
    // Verify person exists
    $person_exists = $wpdb->get_var($wpdb->prepare(
      "SELECT personID FROM $people_table WHERE personID = %s",
      $person_id
    ));

    if (!$person_exists) {
      wp_send_json_error(array('message' => 'Person not found'));
      return;
    }        // If new_tree is provided, verify tree exists
    $tree_name = null;
    if ($new_tree !== null) {
      $tree_data = $wpdb->get_row($wpdb->prepare(
        "SELECT gedcom, treename FROM $trees_table WHERE gedcom = %s",
        $new_tree
      ));

      if (!$tree_data) {
        wp_send_json_error(array('message' => 'Tree not found'));
        return;
      }
      $tree_name = $tree_data->treename;
    }

    // Update person's tree assignment
    $update_result = $wpdb->update(
      $people_table,
      array('gedcom' => $new_tree),
      array('personID' => $person_id),
      array('%s'),
      array('%s')
    );

    if ($update_result === false) {
      wp_send_json_error(array('message' => 'Database update failed'));
      return;
    }

    // Prepare response data
    $response_data = array(
      'person_id' => $person_id,
      'current_tree' => $current_tree,
      'new_tree' => $new_tree,
      'tree_name' => $tree_name,
      'message' => $new_tree ?
        "Person assigned to tree: $tree_name" :
        "Person unassigned from tree"
    );        // If tree was assigned, get updated tree population count
    if ($new_tree) {
      $tree_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $people_table WHERE gedcom = %s",
        $new_tree
      ));
      $response_data['tree_count'] = $tree_count;
    }

    wp_send_json_success($response_data);
  } catch (Exception $e) {
    error_log('HP Tree Assignment Error: ' . $e->getMessage());
    wp_send_json_error(array('message' => 'An error occurred while updating tree assignment'));
  }
}

// Register AJAX actions
add_action('wp_ajax_hp_assign_person_to_tree', 'hp_assign_person_to_tree');
add_action('wp_ajax_nopriv_hp_assign_person_to_tree', 'hp_assign_person_to_tree'); // If needed for non-logged-in users
