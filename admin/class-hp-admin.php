<?php

/**
 * HeritagePress Admin Class
 *
 * Handles all admin-side functionality
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Admin
{  /**
   * Constructor
   */
  public function __construct()
  {
    error_log('HP_Admin constructor called - ' . microtime(true));
    $this->init_hooks();
  }
  /**
   * Initialize admin hooks
   */
  private function init_hooks()
  {
    add_action('admin_menu', array($this, 'admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
    add_action('admin_notices', array($this, 'admin_notices'));
  }
  /**
   * Add admin menu
   */  public function admin_menu()
  {
    add_submenu_page(
      'tools.php',
      'HeritagePress Dashboard',
      'HeritagePress',
      'manage_genealogy',
      'heritagepress',
      array($this, 'admin_page')
    );
  }

  /**
   * Enqueue admin scripts and styles
   */
  public function admin_scripts($hook)
  {
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

    wp_localize_script(
      'heritagepress-admin',
      'heritagepress_admin',
      array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('heritagepress_admin_nonce'),
        'strings' => array(
          'confirm_create' => __('Are you sure you want to create all database tables?', 'heritagepress'),
          'confirm_update' => __('Are you sure you want to update the database tables?', 'heritagepress'),
          'confirm_drop' => __('WARNING: This will permanently delete ALL genealogy data! Are you absolutely sure?', 'heritagepress'),
          'creating' => __('Creating Tables...', 'heritagepress'),
          'updating' => __('Updating Tables...', 'heritagepress'),
          'dropping' => __('Dropping Tables...', 'heritagepress'),
        )
      )
    );
  }

  /**
   * Admin notices
   */
  public function admin_notices()
  {
    $database = heritage_press()->database;

    if (!$database->tables_exist()) {
      echo '<div class="notice notice-warning is-dismissible">';
      echo '<p><strong>' . __('HeritagePress:', 'heritagepress') . '</strong> ';
      echo __('Database tables are not installed. Please deactivate and reactivate the plugin.', 'heritagepress');
      echo '</p></div>';
    }
  }

  /**
   * Main admin page
   */
  public function admin_page()
  {
    $database = heritage_press()->database;
    $table_counts = $database->get_table_counts();
    $stats = $database->get_table_stats();

    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/dashboard.php';
  }

  /**
   * Database tables page
   */
  public function tables_page()
  {
    $database = heritage_press()->database;

    // Handle table operations
    if (isset($_POST['action'])) {
      $this->handle_table_actions();
    }

    $tables_exist = $database->tables_exist();
    $stats = $database->get_table_stats();

    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/tables.php';
  }

  /**
   * People management page
   */
  public function people_page()
  {
    echo '<div class="wrap">';
    echo '<h1>' . __('Manage People', 'heritagepress') . '</h1>';
    echo '<p>' . __('People management functionality coming soon...', 'heritagepress') . '</p>';
    echo '</div>';
  }

  /**
   * GEDCOM import page
   */
  public function import_page()
  {
    echo '<div class="wrap">';
    echo '<h1>' . __('Import GEDCOM', 'heritagepress') . '</h1>';
    echo '<p>' . __('GEDCOM import functionality coming soon...', 'heritagepress') . '</p>';
    echo '</div>';
  }

  /**
   * Handle table actions
   */
  private function handle_table_actions()
  {
    if (!current_user_can('manage_genealogy')) {
      return;
    }

    if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_tables')) {
      return;
    }

    $database = heritage_press()->database;
    $action = sanitize_text_field($_POST['action']);

    switch ($action) {
      case 'create_tables':
        $database->create_tables();
        add_settings_error(
          'heritagepress_tables',
          'tables_created',
          __('Database tables created successfully!', 'heritagepress'),
          'success'
        );
        break;

      case 'drop_tables':
        $database->drop_tables();
        add_settings_error(
          'heritagepress_tables',
          'tables_dropped',
          __('Database tables dropped successfully!', 'heritagepress'),
          'success'
        );
        break;

      case 'update_tables':
        $database->update_database();
        add_settings_error(
          'heritagepress_tables',
          'tables_updated',
          __('Database tables updated successfully!', 'heritagepress'),
          'success'
        );
        break;
    }
  }
}
