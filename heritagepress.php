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

if (!defined('WPINC')) {
  die;
}

// If this file is called directly, abort
if (!defined('ABSPATH')) {
  exit;
}

// Debug: Log deprecation warnings for strpos/str_replace null arguments
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
  if (
    strpos($errstr, 'strpos(): Passing null to parameter') !== false ||
    strpos($errstr, 'str_replace(): Passing null to parameter') !== false
  ) {
    error_log('[HeritagePress Debug] Deprecated warning: ' . $errstr . ' in ' . $errfile . ' on line ' . $errline . "\nBacktrace: " . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10), true));
  }
  // Let normal error handler run
  return false;
});

// Define plugin constants
define('HERITAGEPRESS_VERSION', '1.0.0');
define('HERITAGEPRESS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HERITAGEPRESS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HERITAGEPRESS_PLUGIN_FILE', __FILE__);
define('HERITAGEPRESS_DB_VERSION', '1.0.0');

// Include required files
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/functions/citation-functions.php';

// Global function to get plugin instance
function HeritagePress()
{
  return HeritagePress::instance();
}

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
   */
  private function includes()
  {
    // Core database and model classes
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/database/class-hp-database-manager.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/core/class-hp-person-manager.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/core/class-hp-family-manager.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/core/class-hp-association-manager.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/core/class-hp-branch-manager.php';

    // Handlers
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/handlers/interface-hp-handler.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/handlers/class-hp-ajax-handler.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/handlers/class-hp-form-handler.php';

    // Controllers
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/interface-hp-controller.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/class-hp-base-controller.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/class-hp-people-controller.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/class-hp-trees-controller.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/class-hp-import-controller.php';

    // GEDCOM functionality
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-importer.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-adapter.php';    // Load admin functionality if in admin area
    if (is_admin()) {
      // Only use the new admin system
      require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/class-hp-admin.php';
      // require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/class-hp-admin-new.php'; // Disabled legacy admin
      require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-entity-transfer-controller.php';
      require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/helpers/entity-transfer-integration.php';
      require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/handlers/class-hp-clear-tree-handler.php';
      require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/helpers/citation-modal-helpers.php';
      require_once plugin_dir_path(__FILE__) . 'admin/controllers/class-hp-whatsnew-controller.php';
      HeritagePress_Admin::instance(); // Only instantiate the new admin
      // new HP_Admin_New(); // Disabled legacy admin
    }

    // Public facing functionality
    require_once HERITAGEPRESS_PLUGIN_DIR . 'public/class-hp-public.php';
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
