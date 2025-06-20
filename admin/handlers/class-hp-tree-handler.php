<?php

/**
 * HeritagePress Tree Handler Class
 * Handles adding, editing, and other tree-related form submissions
 */
class HP_Tree_Handler
{
  /**
   * Single instance of class
   */
  private static $instance = null;

  /**
   * Get class instance
   */
  public static function instance()
  {
    if (is_null(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Constructor
   */
  private function __construct()
  {
    $this->init_hooks();
  }

  /**
   * Initialize hooks
   */
  private function init_hooks()
  {
    add_action('admin_post_heritagepress_add_newtree', array($this, 'handle_add_tree'));
    add_action('admin_post_heritagepress_edit_tree', array($this, 'handle_edit_tree'));
  }

  /**
   * Handle add tree form submission
   */
  public function handle_add_tree()
  {
    error_log("=== HeritagePress Add Tree Handler Called ===");
    error_log("POST data: " . print_r($_POST, true));

    if (!current_user_can('manage_options')) {
      error_log("Permission check failed");
      wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
    }

    check_admin_referer('heritagepress_newtree');
    error_log("Nonce check passed");

    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';
    error_log("Using table: $trees_table");

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

    error_log("Parsed data - tree_id: $tree_id, tree_name: $tree_name");

    if (empty($tree_id) || empty($tree_name)) {
      error_log("Validation failed - empty tree_id or tree_name");
      wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=add&message=' . urlencode(__('Tree ID and Tree Name are required.', 'heritagepress'))));
      exit;
    }    // Get current MySQL timestamp
    $now = $wpdb->get_var("SELECT NOW()");
    error_log("Setting created timestamp to: $now");

    // Insert new tree
    $insert_data = [
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
      'disallowpdf' => $disallowpdf,
      'lastimportdate' => '1970-01-01 00:00:00',
      'importfilename' => '',
      'created' => $now, // Explicitly set created timestamp
    ];

    error_log("Insert data: " . print_r($insert_data, true));

    $result = $wpdb->insert($trees_table, $insert_data);
    error_log("Insert result: " . ($result === false ? 'FALSE' : $result));

    if ($result === false) {
      $error = $wpdb->last_error;
      error_log('HeritagePress Add Tree DB Error: ' . $error);
      error_log('Last query: ' . $wpdb->last_query);
      wp_die('Database error: ' . esc_html($error));
    }    // Also make sure to run a direct update to set the created timestamp as a failsafe
    $original_sql_mode = $wpdb->get_var("SELECT @@sql_mode");
    $wpdb->query("SET sql_mode = ''");

    $update_result = $wpdb->query($wpdb->prepare(
      "UPDATE $trees_table SET created = NOW() WHERE gedcom = %s",
      $tree_id
    ));

    error_log("Additional direct update result: " . ($update_result === false ? 'FALSE' : $update_result));

    // Restore original SQL mode
    $wpdb->query("SET sql_mode = '$original_sql_mode'");
    error_log("Insert successful, redirecting...");
    // Redirect without refresh parameter to avoid infinite loop
    wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=browse&message=' . urlencode(__('Tree added successfully.', 'heritagepress'))));
    exit;
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
