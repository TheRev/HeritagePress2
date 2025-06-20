<?php

/**
 * User Controller
 * Handles user management for HeritagePress (admin UI, CRUD, permissions)
 * @package HeritagePress
 */
if (!defined('ABSPATH')) exit;

if (!function_exists('plugin_dir_path')) {
  require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}
if (!function_exists('current_user_can')) {
  require_once(ABSPATH . 'wp-includes/pluggable.php');
}
if (!function_exists('sanitize_text_field')) {
  require_once(ABSPATH . 'wp-includes/formatting.php');
}

require_once plugin_dir_path(__FILE__) . '../../includes/controllers/class-hp-base-controller.php';

class HP_User_Controller extends HP_Base_Controller
{
  public function __construct()
  {
    parent::__construct('users');
    $this->capabilities = array(
      'manage_users' => 'manage_options',
      'edit_users' => 'edit_users',
      'delete_users' => 'delete_users',
    );
  }

  public function handle_form_submission()
  {
    // Handle add user form submission
    if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_add_user')) {
      $user_login = sanitize_user($_POST['user_login']);
      $user_email = sanitize_email($_POST['user_email']);
      $user_pass = $_POST['user_pass'];
      $genealogy_permissions = sanitize_text_field($_POST['hp_genealogy_permissions']);
      $errors = array();
      if (empty($user_login) || username_exists($user_login)) {
        $errors[] = __('Username is required and must be unique.', 'heritagepress');
      }
      if (empty($user_email) || !is_email($user_email)) {
        $errors[] = __('A valid email is required.', 'heritagepress');
      }
      if (empty($user_pass)) {
        $errors[] = __('Password is required.', 'heritagepress');
      }
      if (empty($errors)) {
        $user_id = wp_create_user($user_login, $user_pass, $user_email);
        if (!is_wp_error($user_id)) {
          update_user_meta($user_id, 'hp_genealogy_permissions', $genealogy_permissions);
          $this->add_notice(__('User added successfully!', 'heritagepress'), 'success');
          wp_redirect(admin_url('admin.php?page=heritagepress-users'));
          exit;
        } else {
          $this->add_notice($user_id->get_error_message(), 'error');
        }
      } else {
        foreach ($errors as $error) {
          $this->add_notice($error, 'error');
        }
      }
    }
    // Handle edit user form submission
    if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_edit_user')) {
      $user_id = intval($_POST['user_id']);
      $user_email = sanitize_email($_POST['user_email']);
      $genealogy_permissions = sanitize_text_field($_POST['hp_genealogy_permissions']);
      $userdata = array(
        'ID' => $user_id,
        'user_email' => $user_email,
      );
      $result = wp_update_user($userdata);
      if (!is_wp_error($result)) {
        update_user_meta($user_id, 'hp_genealogy_permissions', $genealogy_permissions);
        $this->add_notice(__('User updated successfully!', 'heritagepress'), 'success');
        wp_redirect(admin_url('admin.php?page=heritagepress-users'));
        exit;
      } else {
        $this->add_notice($result->get_error_message(), 'error');
      }
    }
  }

  public function display_page()
  {
    $this->handle_form_submission();
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $is_editing = $current_tab === 'edit' && $user_id;
    $is_adding = $current_tab === 'add';
    $this->display_notices();
    if ($is_editing) {
      include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/users/edit-user.php';
    } elseif ($is_adding) {
      include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/users/add-user.php';
    } else {
      include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/users/users-main.php';
    }
  }
}
