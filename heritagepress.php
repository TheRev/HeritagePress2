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
   */
  private function includes()
  {
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/class-hp-database.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/class-hp-database-tng-compatible.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/class-hp-tng-mapper.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/class-hp-tng-importer.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/class-hp-person.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/class-hp-family.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/class-hp-gedcom-importer.php';
    if (is_admin()) {
      require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/class-hp-admin.php';
      require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/class-hp-tng-admin.php';
    }

    if (!is_admin() || wp_doing_ajax()) {
      require_once HERITAGEPRESS_PLUGIN_DIR . 'public/class-hp-public.php';
    }
  }

  /**
   * Initialize plugin
   */
  public function init()
  {
    // Initialize database
    $this->database = new HP_Database();

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
    // Check if user wants TNG compatibility (can be set via option later)
    $use_tng_compatibility = get_option('heritagepress_tng_compatibility', false);

    if ($use_tng_compatibility) {
      // Create TNG-compatible database tables
      $database = new HP_Database_TNG_Compatible();
      $database->create_tables();
      update_option('heritagepress_db_type', 'tng_compatible');
    } else {
      // Create standard HeritagePress database tables
      $database = new HP_Database();
      $database->create_tables();
      update_option('heritagepress_db_type', 'standard');
    }

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
