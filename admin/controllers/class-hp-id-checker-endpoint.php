<?php

/**
 * ID Checker Endpoint
 *
 * Replicates HeritagePress admin_checkID.php as a standalone endpoint
 * Accessible via: /wp-admin/admin-ajax.php?action=hp_check_id_standalone
 *
 * @package HeritagePress
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Handle standalone ID checking endpoint
 * Replicates HeritagePress admin_checkID.php behavior exactly
 */
function hp_check_id_standalone_endpoint()
{
  // Allow both logged-in and non-logged in users for admin purposes
  // In production, you might want to restrict this

  $type = sanitize_text_field($_REQUEST['type'] ?? '');
  $check_id = sanitize_text_field($_REQUEST['checkID'] ?? '');
  $tree = sanitize_text_field($_REQUEST['tree'] ?? '');

  if (empty($type) || empty($check_id) || empty($tree)) {
    header("Content-type:text/html; charset=UTF-8");
    echo '<span class="msgerror">Missing required parameters: type, checkID, tree</span>';
    die();
  }

  // Use the ID checker controller
  $checker = new HP_ID_Checker_Controller();

  // Simulate POST data for the checker
  $_POST = array(
    'type' => $type,
    'checkID' => $check_id,
    'tree' => $tree,
    'format' => 'html',
    '_wpnonce' => wp_create_nonce('hp_check_entity_id')
  );

  // Call the checker - this will output HTML and die()
  $checker->check_entity_id();
}

// Register AJAX endpoints for both logged-in and non-logged-in users
add_action('wp_ajax_hp_check_id_standalone', 'hp_check_id_standalone_endpoint');
add_action('wp_ajax_nopriv_hp_check_id_standalone', 'hp_check_id_standalone_endpoint');

/**
 * Handle direct URL access to ID checker
 * Allows URL like: /wp-admin/admin-ajax.php?action=hp_check_id_standalone&type=person&checkID=P123&tree=tree1
 */
if (isset($_GET['action']) && $_GET['action'] === 'hp_check_id_standalone') {
  add_action('init', 'hp_check_id_standalone_endpoint');
}
