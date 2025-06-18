<?php

/**
 * HeritagePress Admin Class
 */
class HeritagePress_Admin
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
    add_action('admin_init', array($this, 'init_admin'));
  }

  /**
   * Initialize admin
   */
  public function init_admin()
  {
    // Load admin dependencies
    $this->load_files();

    // Initialize controllers
    $this->init_controllers();
  }

  /**
   * Load required files
   */
  private function load_files()
  {
    // Load controllers
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/class-hp-people-controller.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/class-hp-trees-controller.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/class-hp-import-controller.php';

    // Load handlers
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/handlers/class-hp-ajax-handler.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/handlers/class-hp-form-handler.php';
  }

  /**
   * Initialize controllers
   */
  private function init_controllers()
  {
    new HP_People_Controller();
    new HP_Trees_Controller();
    new HP_Import_Controller();
  }
}
