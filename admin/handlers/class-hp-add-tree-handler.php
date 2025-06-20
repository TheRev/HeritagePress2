<?php

/**
 * HeritagePress Add Tree Handler
 *
 * Handles the form submission for adding new trees.
 * Replicates functionality from TNG admin_addtree.php
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Add_Tree_Handler
{
  /**
   * Initialize the handler
   */
  public static function init()
  {
    add_action('admin_post_heritagepress_add_tree', array(__CLASS__, 'handle_add_tree'));
  }

  /**
   * Handle add tree form submission
   */
  public static function handle_add_tree()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_add_tree')) {
      wp_die(__('Security check failed.', 'heritagepress'));
    }

    // Check user permissions
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to add trees.', 'heritagepress'));
    }

    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';

    // Sanitize form data - exact field mapping from TNG
    $gedcom = sanitize_text_field($_POST['gedcom']);
    $gedcom = preg_replace("/\s*/", "", $gedcom); // Remove whitespace like TNG
    $treename = sanitize_text_field($_POST['treename']);
    $description = sanitize_textarea_field($_POST['description']);
    $owner = sanitize_text_field($_POST['owner']);
    $email = sanitize_email($_POST['email']);
    $address = sanitize_textarea_field($_POST['address']);
    $city = sanitize_text_field($_POST['city']);
    $state = sanitize_text_field($_POST['state']);
    $country = sanitize_text_field($_POST['country']);
    $zip = sanitize_text_field($_POST['zip']);
    $phone = sanitize_text_field($_POST['phone']);

    // Handle checkboxes (default to 0 if not set, like TNG)
    $private = isset($_POST['private']) ? 1 : 0;
    $disallowgedcreate = isset($_POST['disallowgedcreate']) ? 1 : 0;
    $disallowpdf = isset($_POST['disallowpdf']) ? 1 : 0;

    // Check for import context
    $before_import = isset($_POST['beforeimport']) ? sanitize_text_field($_POST['beforeimport']) : '';
    $submit_type = isset($_POST['submitx']) ? 'return_to_trees' : 'edit_tree';

    // Validate required fields
    if (empty($gedcom)) {
      self::redirect_with_error(__('Tree ID is required.', 'heritagepress'), $_POST, $before_import);
      return;
    }

    if (empty($treename)) {
      self::redirect_with_error(__('Tree Name is required.', 'heritagepress'), $_POST, $before_import);
      return;
    }

    // Validate tree ID format (same as TNG)
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $gedcom)) {
      self::redirect_with_error(__('Tree ID must contain only letters, numbers, underscores, and hyphens.', 'heritagepress'), $_POST, $before_import);
      return;
    }

    // Check if tree ID already exists
    $existing_tree = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $trees_table WHERE gedcom = %s",
      $gedcom
    ));

    if ($existing_tree > 0) {
      self::redirect_with_error(__('Tree ID already exists. Please choose a different ID.', 'heritagepress'), $_POST, $before_import);
      return;
    }

    // Prepare data for insertion (matching TNG table structure)
    $tree_data = array(
      'gedcom' => $gedcom,
      'treename' => $treename,
      'description' => $description,
      'owner' => $owner,
      'email' => $email,
      'address' => $address,
      'city' => $city,
      'state' => $state,
      'country' => $country,
      'zip' => $zip,
      'phone' => $phone,
      'secret' => $private,
      'disallowgedcreate' => $disallowgedcreate,
      'disallowpdf' => $disallowpdf,
      'date_created' => current_time('mysql')
    );

    $format = array(
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
      '%d',
      '%d',
      '%d',
      '%s'
    );

    // Insert tree record
    $result = $wpdb->insert($trees_table, $tree_data, $format);

    if ($result === 1) {
      // Log the action (like TNG adminwritelog)
      $current_user = wp_get_current_user();
      $log_message = sprintf(
        __('Tree added: %s/%s by %s', 'heritagepress'),
        $gedcom,
        $treename,
        $current_user->user_login
      );
      error_log("HeritagePress Tree Addition: $log_message");

      // Handle different redirect scenarios (matching TNG logic)
      if ($before_import === "yes") {
        // AJAX response for import context
        echo "1";
        exit;
      } else {
        if ($submit_type === 'return_to_trees') {
          // Redirect back to trees with success message
          $message = sprintf(
            __('Tree "%s" was successfully added.', 'heritagepress'),
            stripslashes($treename)
          );
          $redirect_url = add_query_arg(
            array(
              'page' => 'heritagepress-trees',
              'message' => urlencode($message)
            ),
            admin_url('admin.php')
          );
        } else {
          // Redirect to edit the new tree
          $redirect_url = add_query_arg(
            array(
              'page' => 'heritagepress-trees',
              'tab' => 'edit',
              'tree' => $gedcom,
              'action' => 'edit'
            ),
            admin_url('admin.php')
          );
        }

        wp_redirect($redirect_url);
        exit;
      }
    } else {
      // Handle insertion failure
      $error_message = __('Failed to create tree. Please try again.', 'heritagepress');

      if ($before_import === "yes") {
        echo $error_message;
        exit;
      } else {
        self::redirect_with_error($error_message, $_POST, $before_import);
      }
    }
  }

  /**
   * Redirect with error message and preserve form data
   */
  private static function redirect_with_error($message, $form_data, $before_import = '')
  {
    if ($before_import === "yes") {
      echo $message;
      exit;
    }

    // Build redirect URL with error message and form data
    $redirect_args = array(
      'page' => 'heritagepress-trees',
      'tab' => 'add',
      'message' => urlencode($message)
    );

    // Preserve form data in URL (like TNG does)
    $preserve_fields = array('treename', 'description', 'owner', 'email', 'address', 'city', 'state', 'country', 'zip', 'phone', 'private', 'disallowgedcreate', 'disallowpdf');

    foreach ($preserve_fields as $field) {
      if (!empty($form_data[$field])) {
        $redirect_args[$field] = urlencode($form_data[$field]);
      }
    }

    $redirect_url = add_query_arg($redirect_args, admin_url('admin.php'));

    wp_redirect($redirect_url);
    exit;
  }
}

// Initialize the handler
HP_Add_Tree_Handler::init();
