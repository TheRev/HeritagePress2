<?php

/**
 * HeritagePress Admin Class
 *
 * Professional genealogy admin interface with comprehensive genealogy management
 * Features tabbed navigation, multi-program GEDCOM import, and complete data management
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Admin
{
  /**
   * Current admin page
   */
  private $current_page = '';
  
  /**
   * Current tab
   */
  private $current_tab = '';
  
  /**
   * Admin pages configuration
   */
  private $admin_pages = array();
  
  /**
   * Constructor
   */
  public function __construct()
  {
    $this->setup_admin_pages();
    $this->init_hooks();
  }
  
  /**
   * Setup admin pages structure (professional genealogy interface)
   */
  private function setup_admin_pages() {
    $this->admin_pages = array(
      'dashboard' => array(
        'title' => 'Dashboard',
        'capability' => 'manage_genealogy',
        'icon' => 'dashicons-dashboard',
        'tabs' => array(
          'overview' => 'Overview',
          'statistics' => 'Statistics',
          'recent' => 'Recent Activity'
        )
      ),
      'people' => array(
        'title' => 'People',
        'capability' => 'edit_genealogy',
        'icon' => 'dashicons-groups',
        'tabs' => array(
          'browse' => 'Browse',
          'add' => 'Add New',
          'search' => 'Advanced Search',
          'reports' => 'Reports',
          'utilities' => 'Utilities'
        )
      ),
      'families' => array(
        'title' => 'Families',
        'capability' => 'edit_genealogy',
        'icon' => 'dashicons-networking',
        'tabs' => array(
          'browse' => 'Browse Families',
          'add' => 'Add Family',
          'trees' => 'Family Trees',
          'reports' => 'Family Reports',
          'relationships' => 'Relationship Tools'
        )
      ),
      'sources' => array(
        'title' => 'Sources',
        'capability' => 'edit_genealogy',
        'icon' => 'dashicons-book-alt',
        'tabs' => array(
          'browse' => 'Browse Sources',
          'add' => 'Add Source',
          'citations' => 'Citations',
          'repositories' => 'Repositories',
          'reports' => 'Source Reports'
        )
      ),
      'media' => array(
        'title' => 'Media',
        'capability' => 'edit_genealogy',
        'icon' => 'dashicons-format-gallery',
        'tabs' => array(
          'browse' => 'Browse Media',
          'upload' => 'Upload Media',
          'types' => 'Media Types',
          'albums' => 'Albums',
          'reports' => 'Media Reports'
        )
      ),
      'import-export' => array(
        'title' => 'Import/Export',
        'capability' => 'import_gedcom',
        'icon' => 'dashicons-database-import',
        'tabs' => array(
          'gedcom-import' => 'GEDCOM Import',
          'gedcom-export' => 'GEDCOM Export',
          'media-import' => 'Media Import',
          'history' => 'Import History',
          'backup' => 'Backup/Restore'
        )
      ),
      'places' => array(
        'title' => 'Places',
        'capability' => 'edit_genealogy',
        'icon' => 'dashicons-location-alt',
        'tabs' => array(
          'browse' => 'Browse Places',
          'add' => 'Add Places',
          'geography' => 'Countries/States',
          'cemeteries' => 'Cemeteries',
          'maps' => 'Maps Integration'
        )
      ),
      'dna' => array(
        'title' => 'DNA',
        'capability' => 'edit_genealogy',
        'icon' => 'dashicons-chart-line',
        'tabs' => array(
          'tests' => 'DNA Tests',
          'matches' => 'DNA Matches',
          'groups' => 'DNA Groups',
          'reports' => 'DNA Reports'
        )
      ),
      'settings' => array(
        'title' => 'Settings',
        'capability' => 'manage_genealogy',
        'icon' => 'dashicons-admin-settings',
        'tabs' => array(
          'general' => 'General Settings',
          'users' => 'User Management',
          'privacy' => 'Privacy Settings',
          'templates' => 'Template Management',
          'maintenance' => 'System Maintenance'
        )
      )
    );
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
   */
  public function admin_menu()
  {
    // Main menu page
    add_menu_page(
      'HeritagePress Dashboard',
      'HeritagePress',
      'manage_genealogy',
      'heritagepress',
      array($this, 'admin_page'),
      'dashicons-networking',
      30
    );

    // Dashboard submenu (same as main page)
    add_submenu_page(
      'heritagepress',
      'Dashboard',
      'Dashboard',
      'manage_genealogy',
      'heritagepress',
      array($this, 'admin_page')
    );

    // Import GEDCOM submenu
    add_submenu_page(
      'heritagepress',
      'Import GEDCOM',
      'Import GEDCOM',
      'import_gedcom',
      'heritagepress-import',
      array($this, 'import_page')
    );

    // People management submenu
    add_submenu_page(
      'heritagepress',
      'Manage People',
      'People',
      'edit_genealogy',
      'heritagepress-people',
      array($this, 'people_page')
    );

    // Database tables submenu
    add_submenu_page(
      'heritagepress',
      'Database Tables',
      'Database',
      'manage_genealogy',
      'heritagepress-tables',
      array($this, 'tables_page')
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
    // Handle import submission
    if (isset($_POST['action']) && $_POST['action'] === 'upload_gedcom') {
      $this->handle_gedcom_import();
    }

    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/import.php';
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
        // Check if we need a clean install due to conflicts
        if ($database->needs_clean_install()) {
          $database->clean_install();
          add_settings_error(
            'heritagepress_tables',
            'tables_recreated',
            __('Database tables recreated successfully! (Conflicts resolved)', 'heritagepress'),
            'success'
          );
        } else {
          $database->create_tables();
          add_settings_error(
            'heritagepress_tables',
            'tables_created',
            __('Database tables created successfully!', 'heritagepress'),
            'success'
          );
        }
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

      case 'clean_install':
        $database->clean_install();
        add_settings_error(
          'heritagepress_tables',
          'clean_install',
          __('Clean installation completed! All tables recreated.', 'heritagepress'),
          'success'
        );
        break;
    }
  }

  /**
   * Handle GEDCOM import submission
   */
  private function handle_gedcom_import()
  {
    if (!current_user_can('import_gedcom')) {
      add_settings_error(
        'heritagepress_import',
        'permission_denied',
        __('You do not have permission to import GEDCOM files.', 'heritagepress'),
        'error'
      );
      return;
    }

    if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_import')) {
      add_settings_error(
        'heritagepress_import',
        'invalid_nonce',
        __('Security check failed. Please try again.', 'heritagepress'),
        'error'
      );
      return;
    }

    // Check if file was uploaded
    if (!isset($_FILES['gedcom_file']) || $_FILES['gedcom_file']['error'] !== UPLOAD_ERR_OK) {
      add_settings_error(
        'heritagepress_import',
        'upload_error',
        __('File upload failed. Please try again.', 'heritagepress'),
        'error'
      );
      return;
    }

    $uploaded_file = $_FILES['gedcom_file'];
    $tree_id = sanitize_text_field($_POST['tree_id']);
    $encoding = sanitize_text_field($_POST['encoding']);

    // Validate file type
    $file_ext = strtolower(pathinfo($uploaded_file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, ['ged', 'gedcom'])) {
      add_settings_error(
        'heritagepress_import',
        'invalid_file_type',
        __('Invalid file type. Please upload a .ged or .gedcom file.', 'heritagepress'),
        'error'
      );
      return;
    }

    // Move uploaded file to secure location
    $upload_dir = wp_upload_dir();
    $heritagepress_dir = $upload_dir['basedir'] . '/heritagepress/';

    if (!file_exists($heritagepress_dir)) {
      wp_mkdir_p($heritagepress_dir);
    }

    $target_file = $heritagepress_dir . 'import_' . time() . '.' . $file_ext;

    if (!move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
      add_settings_error(
        'heritagepress_import',
        'move_file_error',
        __('Failed to save uploaded file.', 'heritagepress'),
        'error'
      );
      return;
    }

    // Prepare import options
    $options = [
      'import_living' => isset($_POST['import_living']),
      'import_private' => isset($_POST['import_private']),
      'import_sources' => isset($_POST['import_sources']),
      'import_media' => isset($_POST['import_media']),
      'encoding' => $encoding
    ];

    try {
      // Create GEDCOM importer instance
      $importer = new HP_GEDCOM_Importer($target_file, $tree_id, $options);

      // Start import process
      $result = $importer->import();

      if ($result['success']) {
        add_settings_error(
          'heritagepress_import',
          'import_success',
          sprintf(
            __('Import completed successfully! Imported %d people, %d families, %d events.', 'heritagepress'),
            $result['stats']['people_imported'] ?? 0,
            $result['stats']['families_imported'] ?? 0,
            $result['stats']['events'] ?? 0
          ),
          'success'
        );
      } else {
        add_settings_error(
          'heritagepress_import',
          'import_failed',
          __('Import failed: ', 'heritagepress') . implode(', ', $result['errors']),
          'error'
        );
      }
    } catch (Exception $e) {
      add_settings_error(
        'heritagepress_import',
        'import_exception',
        __('Import failed with error: ', 'heritagepress') . $e->getMessage(),
        'error'
      );
    } finally {
      // Clean up uploaded file
      if (file_exists($target_file)) {
        unlink($target_file);
      }
    }
  }
}
