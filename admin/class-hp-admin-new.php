<?php

/**
 * Admin Controller Loader
 *
 * IMPORTANT: This is the ONLY admin class file. All admin functionality
 * is handled through the controller system in admin/controllers/
 *
 * DO NOT create class-hp-admin.php or similar files!
 * Use the controller pattern instead:
 * - admin/controllers/class-hp-{feature}-controller.php
 * - admin/handlers/class-hp-{feature}-handler.php
 *
 * HeritagePress Admin Controller System
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Admin Controller Loader Class
 *
 * This class loads and coordinates all admin controllers.
 * It does NOT contain admin logic - that goes in controllers!
 */
class HP_Admin_New
{
  /**
   * Initialize the admin system
   */
  public function __construct()
  {
    $this->load_controllers();
    $this->init_hooks();
  }
  /**
   * Load all admin controllers
   *
   * Controllers handle specific admin functionality:
   * - ImportController: GEDCOM imports
   * - PeopleController: People management
   * - FamiliesController: Family management
   * - TreesController: Tree management
   * - SettingsController: Plugin settings
   */
  private function load_controllers()
  {
    // Load controller base class
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-base-controller.php';

    // Load specific controllers that exist
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-import-controller.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-people-controller.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-trees-controller.php';

    // Load handlers that exist
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/handlers/class-hp-import-handler.php';

    // Initialize controllers that exist
    new HP_Import_Controller();
    new HP_People_Controller();
    new HP_Trees_Controller();

    // Initialize handlers that exist
    new HP_Import_Handler();
  }

  /**
   * Initialize admin hooks
   */
  private function init_hooks()
  {
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
  }

  /**
   * Add admin menu
   */
  public function add_admin_menu()
  {
    add_menu_page(
      __('HeritagePress', 'heritagepress'),
      __('HeritagePress', 'heritagepress'),
      'manage_options',
      'heritagepress',
      array($this, 'admin_page'),
      'dashicons-groups',
      30
    );

    add_submenu_page(
      'heritagepress',
      __('Import', 'heritagepress'),
      __('Import', 'heritagepress'),
      'manage_options',
      'heritagepress-import',
      array($this, 'import_page')
    );

    add_submenu_page(
      'heritagepress',
      __('People', 'heritagepress'),
      __('People', 'heritagepress'),
      'manage_options',
      'heritagepress-people',
      array($this, 'people_page')
    );

    add_submenu_page(
      'heritagepress',
      __('Families', 'heritagepress'),
      __('Families', 'heritagepress'),
      'manage_options',
      'heritagepress-families',
      array($this, 'families_page')
    );

    add_submenu_page(
      'heritagepress',
      __('Trees', 'heritagepress'),
      __('Trees', 'heritagepress'),
      'manage_options',
      'heritagepress-trees',
      array($this, 'trees_page')
    );

    add_submenu_page(
      'heritagepress',
      __('Settings', 'heritagepress'),
      __('Settings', 'heritagepress'),
      'manage_options',
      'heritagepress-settings',
      array($this, 'settings_page')
    );
  }

  /**
   * Main admin page - delegates to controller
   */
  public function admin_page()
  {
    // Delegate to appropriate controller
    $controller = new HP_People_Controller();
    $controller->display_page();
  }

  /**
   * Import page - delegates to ImportController
   */
  public function import_page()
  {
    // Delegate to ImportController
    $controller = new HP_Import_Controller();
    $controller->display_page();
  }

  /**
   * People page - delegates to PeopleController
   */
  public function people_page()
  {
    // Delegate to PeopleController
    $controller = new HP_People_Controller();
    $controller->display_page();
  }
  /**
   * Families page - delegates to FamiliesController
   */
  public function families_page()
  {
    echo '<div class="wrap">';
    echo '<h1>' . __('Families', 'heritagepress') . '</h1>';
    echo '<p>' . __('Families controller not yet implemented.', 'heritagepress') . '</p>';
    echo '</div>';
  }

  /**
   * Trees page - delegates to TreesController
   */
  public function trees_page()
  {
    // Delegate to TreesController
    $controller = new HP_Trees_Controller();
    $controller->display_page();
  }

  /**
   * Settings page - delegates to SettingsController
   */
  public function settings_page()
  {
    echo '<div class="wrap">';
    echo '<h1>' . __('Settings', 'heritagepress') . '</h1>';
    echo '<p>' . __('Settings controller not yet implemented.', 'heritagepress') . '</p>';
    echo '</div>';
  }

  /**
   * Enqueue admin scripts and styles
   */
  public function enqueue_admin_scripts($hook)
  {
    // Only load on HeritagePress admin pages
    if (strpos($hook, 'heritagepress') === false) {
      return;
    }

    wp_enqueue_style(
      'heritagepress-admin',
      HERITAGEPRESS_PLUGIN_URL . 'admin/css/admin.css',
      array(),
      HERITAGEPRESS_VERSION
    );

    wp_enqueue_script(
      'heritagepress-admin',
      HERITAGEPRESS_PLUGIN_URL . 'admin/js/admin.js',
      array('jquery'),
      HERITAGEPRESS_VERSION,
      true
    );

    // Localize script for AJAX
    wp_localize_script('heritagepress-admin', 'hp_ajax', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('hp_ajax_nonce')
    ));
  }
}
