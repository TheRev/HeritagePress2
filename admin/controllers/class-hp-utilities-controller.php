<?php

/**
 * HeritagePress Utilities Controller
 *
 * Manages utilities pages including backup, restore, structure, maintenance and tools
 *
 * @package    HeritagePress
 * @subpackage Admin\Controllers
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Utilities_Controller
{

  /**
   * Constructor
   */
  public function __construct()
  {
    // Load dependencies
    $this->load_dependencies();

    // Initialize handlers
    $this->init_handlers();
  }

  /**
   * Load dependencies
   */
  private function load_dependencies()
  {
    // Load backup controller
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/class-hp-backup-controller.php';

    // Load backup handler
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/handlers/class-hp-backup-handler.php';
  }

  /**
   * Initialize handlers
   */
  private function init_handlers()
  {
    // Initialize backup handler
    HP_Backup_Handler::init();
  }

  /**
   * Render utilities main page
   */
  public function render_utilities_main()
  {
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/utilities-main.php';
  }

  /**
   * Render backup page
   */
  public function render_backup_page()
  {
    // Create backup controller
    $backup_controller = new HP_Backup_Controller();

    // Get backup files
    $backup_files = $backup_controller->get_backup_files();

    // Get tables
    $tables = $backup_controller->get_tables();

    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/utilities-backup.php';
  }

  /**
   * Render structure page
   */
  public function render_structure_page()
  {
    // Create backup controller
    $backup_controller = new HP_Backup_Controller();

    // Get structure files
    $structure_files = $backup_controller->get_structure_files();

    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/utilities-structure.php';
  }

  /**
   * Render maintenance page
   */
  public function render_maintenance_page()
  {
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/utilities-maintenance.php';
  }

  /**
   * Render tools page
   */
  public function render_tools_page()
  {
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/utilities-tools.php';
  }
}
