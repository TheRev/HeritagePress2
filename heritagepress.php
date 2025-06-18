<?php

/**
 * Plugin Name: HeritagePress
 * Plugin URI: https://github.com/TheRev/HeritagePress2
 * Description: Complete genealogy management system for WordPress. Import GEDCOM files, manage family trees, and create beautiful genealogy websites.
 * Version: 1.0.0
 * Author: TheRev
 * License: MIT
 * Text Domain: heritagepress
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 *
 * DEVELOPMENT NOTE:
 * =================
 * This plugin uses a CONTROLLER-based architecture.
 *
 * DO NOT create class-hp-admin.php or similar monolithic admin files!
 *
 * Instead, use the existing controller pattern:
 * - admin/controllers/class-hp-{feature}-controller.php (handles logic)
 * - admin/handlers/class-hp-{feature}-handler.php (handles forms/AJAX)
 * - admin/views/{feature}/ (contains templates)
 *
 * Example: For GEDCOM import functionality, use:
 * - admin/controllers/class-hp-import-controller.php
 * - admin/handlers/class-hp-import-handler.php
 * - admin/views/import/
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Define plugin constants
define('HERITAGEPRESS_VERSION', '1.0.0');
define('HERITAGEPRESS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HERITAGEPRESS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HERITAGEPRESS_PLUGIN_FILE', __FILE__);
define('HERITAGEPRESS_DB_VERSION', '1.0.0');

/**
 * Main HeritagePress Plugin Class
 */
class HeritagePress
{

  /**
   * Single instance of the class
   */
  private static $instance = null;

  /**
   * Database manager
   */
  public $database;

  /**
   * Get main instance
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
    $this->includes();
    // $this->init(); // Removed to prevent double instantiation
  }

  /**
   * Hook into actions and filters
   */
  private function init_hooks()
  {
    register_activation_hook(__FILE__, array($this, 'activate'));
    register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    add_action('init', array($this, 'init'), 0);
    add_action('plugins_loaded', array($this, 'load_textdomain'));
  }
  /**
   * Include required files
   */  private function includes()
  {    // Core database and model classes
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/database/class-hp-database-manager.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/core/class-hp-person-manager.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/core/class-hp-family-manager.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/core/class-hp-association-manager.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/core/class-hp-branch-manager.php';

    // Load the modular GEDCOM importer controller first
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-importer.php';

    // Load the simple adapter that provides the HP_GEDCOM_Importer class
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/class-hp-gedcom-adapter.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/hp-gedcom-settings.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/hp-gedcom-ajax.php';    // Admin interface - using controller system
    if (is_admin()) {
      // Load controller base class
      require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-base-controller.php';
      require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/interface-hp-controller.php';

      // Load controllers
      require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-import-controller.php';
      require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-people-controller.php';
      require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-trees-controller.php';

      // Add admin menu
      add_action('admin_menu', array($this, 'add_admin_menu'));
    } // Public interface
    if (!is_admin() || wp_doing_ajax()) {
      require_once HERITAGEPRESS_PLUGIN_DIR . 'public/class-hp-public.php';
    }    // Old reference to adapter removed since we load it earlier
  }
  /**
   * Initialize plugin
   */
  public function init()
  {
    // Suppress specific PHP notices that don't affect functionality
    add_action('admin_init', array($this, 'suppress_minor_warnings'), 1);

    // Initialize database manager
    $this->database = new HP_Database_Manager();    // Initialize admin (already initialized in load_dependencies)
    // Controllers are now managed by HP_Admin_New class

    // Initialize public
    if (!is_admin() || wp_doing_ajax()) {
      new HP_Public();
    }

    do_action('heritagepress_loaded');
  }

  /**
   * Load plugin text domain
   */
  public function load_textdomain()
  {
    load_plugin_textdomain(
      'heritagepress',
      false,
      dirname(plugin_basename(__FILE__)) . '/languages/'
    );
  }
  /**
   * Plugin activation
   */
  public function activate()
  {
    // Create database tables using unified database manager
    $database = new HP_Database_Manager();
    $database->create_tables();

    // Add capabilities
    $this->add_capabilities();

    // Set version
    update_option('heritagepress_version', HERITAGEPRESS_VERSION);
    update_option('heritagepress_db_version', HERITAGEPRESS_DB_VERSION);

    // Flush rewrite rules
    flush_rewrite_rules();
  }

  /**
   * Plugin deactivation
   */
  public function deactivate()
  {
    flush_rewrite_rules();
  }

  /**
   * Add user capabilities
   */
  private function add_capabilities()
  {
    $admin = get_role('administrator');
    if ($admin) {
      $admin->add_cap('manage_genealogy');
      $admin->add_cap('edit_genealogy');
      $admin->add_cap('import_gedcom');
    }
  }

  /**
   * Suppress minor PHP warnings that don't affect functionality
   */
  public function suppress_minor_warnings()
  {
    // Custom error handler for zlib and output buffering notices
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
      $original_handler = set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$original_handler) {
        // Suppress specific zlib output compression notices
        if (strpos($errstr, 'ob_end_flush(): Failed to send buffer of zlib output compression') !== false) {
          return true; // Suppress this error
        }

        // Call the original error handler for other errors
        if ($original_handler) {
          return call_user_func($original_handler, $errno, $errstr, $errfile, $errline);
        }

        return false; // Let PHP handle other errors normally
      });
    }
  }
  /**
   * Add admin menu using controller system
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
      __('Trees', 'heritagepress'),
      __('Trees', 'heritagepress'),
      'manage_options',
      'heritagepress-trees',
      array($this, 'trees_page')
    );
  }

  /**
   * Main admin page
   */
  public function admin_page()
  {
    $controller = new HP_People_Controller();
    $controller->display_page();
  }

  /**
   * Import page
   */
  public function import_page()
  {
    $controller = new HP_Import_Controller();
    $controller->display_page();
  }

  /**
   * People page
   */
  public function people_page()
  {
    $controller = new HP_People_Controller();
    $controller->display_page();
  }

  /**
   * Trees page
   */
  public function trees_page()
  {
    $controller = new HP_Trees_Controller();
    $controller->display_page();
  }
}

/**
 * Main instance of HeritagePress
 */
function heritage_press()
{
  return HeritagePress::instance();
}

// Initialize the plugin
heritage_press();
