<?php

/**
 * HeritagePress Edit Tree Handler
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

/**
 * Handler class for editing trees
 */
class HP_Edit_Tree_Handler
{

  /**
   * Constructor
   */
  public function __construct()
  {
    add_action('admin_post_heritagepress_edit_tree', array($this, 'handle_edit_tree'));
  }

  /**
   * Handle edit tree form submission
   */
  public function handle_edit_tree()
  {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
    }

    check_admin_referer('heritagepress_edittree');

    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';
    $original_tree_id = isset($_POST['original_tree_id']) ? sanitize_text_field($_POST['original_tree_id']) : '';
    $tree_id = isset($_POST['tree_id']) ? sanitize_text_field($_POST['tree_id']) : '';
    $tree_name = isset($_POST['tree_name']) ? sanitize_text_field($_POST['tree_name']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    $owner = isset($_POST['owner']) ? sanitize_text_field($_POST['owner']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
    $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
    $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
    $zip = isset($_POST['zip']) ? sanitize_text_field($_POST['zip']) : '';
    $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $private = isset($_POST['private']) ? 1 : 0;
    $disallowgedcreate = isset($_POST['disallowgedcreate']) ? 1 : 0;
    $disallowpdf = isset($_POST['disallowpdf']) ? 1 : 0;

    if (empty($tree_id) || empty($tree_name)) {
      wp_redirect(admin_url('admin.php?page=heritagepress-edittree&tree=' . urlencode($original_tree_id) . '&message=' . urlencode(__('Tree ID and Tree Name are required.', 'heritagepress'))));
      exit;
    }

    $update_data = [
      'gedcom' => $tree_id,
      'treename' => $tree_name,
      'description' => $description,
      'owner' => $owner,
      'email' => $email,
      'address' => $address,
      'city' => $city,
      'state' => $state,
      'zip' => $zip,
      'country' => $country,
      'phone' => $phone,
      'secret' => $private,
      'disallowgedcreate' => $disallowgedcreate,
      'disallowpdf' => $disallowpdf
    ];

    $result = $wpdb->update($trees_table, $update_data, ['gedcom' => $original_tree_id]);

    if ($result === false) {
      $error = $wpdb->last_error;
      wp_die('Database error: ' . esc_html($error));
    }

    wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=browse&message=' . urlencode(__('Tree updated successfully.', 'heritagepress'))));
    exit;
  }
}

// Initialize the handler
new HP_Edit_Tree_Handler();
