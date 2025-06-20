<?php

/**
 * Admin Dashboard Controller
 * Provides the HeritagePress admin dashboard with quick links and stats.
 */
if (!defined('ABSPATH')) exit;
class HP_Admin_Dashboard_Controller
{
  public function __construct()
  {
    add_action('admin_menu', array($this, 'register_page'));
  }
  public function register_page()
  {
    add_menu_page(
      __('HeritagePress Dashboard', 'heritagepress'),
      __('HeritagePress', 'heritagepress'),
      'manage_options',
      'hp_admin_dashboard',
      array($this, 'render_page'),
      'dashicons-admin-home',
      2
    );
  }
  public function render_page()
  {
    include dirname(__FILE__) . '/../views/admin-dashboard.php';
  }
}
new HP_Admin_Dashboard_Controller();
