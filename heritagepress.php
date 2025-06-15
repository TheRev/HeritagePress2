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
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/class-hp-database-manager.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/class-hp-person-manager.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/class-hp-family-manager.php';

    // Load the modular GEDCOM importer controller first
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-gedcom-importer.php';

    // Load the simple adapter that provides the HP_GEDCOM_Importer class
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/class-hp-gedcom-adapter.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/hp-gedcom-settings.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/hp-gedcom-ajax.php';

    // Admin interface
    if (is_admin()) {
      require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/class-hp-admin.php';
    }    // Public interface
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
    $this->database = new HP_Database_Manager();

    // Initialize admin
    if (is_admin()) {
      new HP_Admin();
    }

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
