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
   * Admin notices array
   */
  private $admin_notices = array();
  /**
   * Constructor
   */
  public function __construct()
  {
    $this->load_date_system();
    $this->setup_admin_pages();
    $this->init_hooks();
  }
  /**
   * Load the date parsing and validation system
   */
  private function load_date_system()
  {
    require_once plugin_dir_path(__FILE__) . '../includes/helpers/class-hp-date-parser.php';
    require_once plugin_dir_path(__FILE__) . '../includes/helpers/class-hp-date-validator.php';
    require_once plugin_dir_path(__FILE__) . '../includes/helpers/class-hp-date-config.php';

    // Initialize date validation and configuration
    HP_Date_Validator::init();
    HP_Date_Config::init();
  }

  /**
   * Setup admin pages structure (professional genealogy interface)
   */
  private function setup_admin_pages()
  {
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
          'reports' => 'Reports',
          'utilities' => 'Utilities'
        )
      ),      'families' => array(
        'title' => 'Families',
        'capability' => 'edit_genealogy',
        'icon' => 'dashicons-networking',
        'tabs' => array(
          'browse' => 'Browse',
          'add' => 'Add New',
          'edit' => 'Edit Family',
          'utilities' => 'Utilities',
          'reports' => 'Reports'
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
      'trees' => array(
        'title' => 'Trees',
        'capability' => 'edit_genealogy',
        'icon' => 'dashicons-palmtree',
        'tabs' => array(
          'browse' => 'Browse Trees',
          'add' => 'Add Tree',
          'edit' => 'Edit Tree'
        )
      ),
      'import-export' => array(
        'title' => 'Import / Export',
        'capability' => 'import_gedcom',
        'icon' => 'dashicons-database-import',
        'tabs' => array(
          'import' => 'Import',
          'export' => 'Export',
          'post-import' => 'Post-Import',
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
    // Handle early output issues
    add_action('admin_init', array($this, 'handle_admin_init'), 1);

    add_action('admin_menu', array($this, 'admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
    add_action('admin_notices', array($this, 'admin_notices'));

    // AJAX hooks for import/export
    add_action('wp_ajax_hp_get_branches', array($this, 'ajax_get_branches'));

    // AJAX hooks for trees management
    add_action('wp_ajax_hp_check_tree_id', array($this, 'ajax_check_tree_id'));
    add_action('wp_ajax_hp_delete_tree', array($this, 'ajax_delete_tree'));
    add_action('wp_ajax_hp_clear_tree', array($this, 'ajax_clear_tree'));

    // AJAX hooks for chunked GEDCOM upload
    add_action('wp_ajax_hp_upload_gedcom_chunk', array($this, 'ajax_upload_gedcom_chunk'));
    add_action('wp_ajax_hp_finalize_gedcom_upload', array($this, 'ajax_finalize_gedcom_upload'));
    add_action('wp_ajax_hp_cancel_upload', array($this, 'ajax_cancel_upload'));
    add_action('wp_ajax_hp_refresh_server_files', array($this, 'ajax_refresh_server_files'));    // Load People AJAX handlers
    $this->load_people_ajax_handlers();

    // Load Families AJAX handlers
    $this->load_families_ajax_handlers();

    // Add GEDCOM file support
    add_filter('upload_mimes', array($this, 'add_gedcom_mime_type'));
    add_filter('wp_check_filetype_and_ext', array($this, 'check_gedcom_filetype'), 10, 4);

    // TODO: Initialize background processing when needed
    // $this->init_background_processing();
  }
  /**
   * Load People AJAX handlers
   */
  private function load_people_ajax_handlers()
  {
    $ajax_dir = plugin_dir_path(__FILE__) . '../includes/template/People/ajax/';

    // Include AJAX handler files
    if (file_exists($ajax_dir . 'person-id-handler.php')) {
      require_once $ajax_dir . 'person-id-handler.php';
    }

    if (file_exists($ajax_dir . 'reports-handler.php')) {
      require_once $ajax_dir . 'reports-handler.php';
    }

    if (file_exists($ajax_dir . 'utilities-handler.php')) {
      require_once $ajax_dir . 'utilities-handler.php';
    }    if (file_exists($ajax_dir . 'tree-assignment-handler.php')) {
      require_once $ajax_dir . 'tree-assignment-handler.php';
    }
  }

  /**
   * Load Families AJAX handlers
   */
  private function load_families_ajax_handlers()
  {
    $ajax_dir = plugin_dir_path(__FILE__) . '../includes/template/Families/ajax/';

    // Include AJAX handler files
    if (file_exists($ajax_dir . 'family-id-handler.php')) {
      require_once $ajax_dir . 'family-id-handler.php';
    }

    if (file_exists($ajax_dir . 'family-finder-handler.php')) {
      require_once $ajax_dir . 'family-finder-handler.php';
    }

    if (file_exists($ajax_dir . 'person-finder-handler.php')) {
      require_once $ajax_dir . 'person-finder-handler.php';
    }

    if (file_exists($ajax_dir . 'reports-handler.php')) {
      require_once $ajax_dir . 'reports-handler.php';
    }

    if (file_exists($ajax_dir . 'utilities-handler.php')) {
      require_once $ajax_dir . 'utilities-handler.php';
    }
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

    // Trees submenu
    add_submenu_page(
      'heritagepress',
      'Family Trees',
      'Trees',
      'edit_genealogy',
      'heritagepress-trees',
      array($this, 'trees_page')
    );

    // Import/Export submenu
    add_submenu_page(
      'heritagepress',
      'Import / Export',
      'Import / Export',
      'import_gedcom',
      'heritagepress-import',
      array($this, 'import_export_page')
    );    // People management submenu
    add_submenu_page(
      'heritagepress',
      'Manage People',
      'People',
      'edit_genealogy',
      'heritagepress-people',
      array($this, 'people_page')
    );

    // Families management submenu
    add_submenu_page(
      'heritagepress',
      'Manage Families',
      'Families',
      'edit_genealogy',
      'heritagepress-families',
      array($this, 'families_page')
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

    // Database migrations submenu
    add_submenu_page(
      'heritagepress',
      'Database Migrations',
      'Migrations',
      'manage_genealogy',
      'heritagepress-migrations',
      array($this, 'migrations_page')
    );

    // Test Data submenu
    add_submenu_page(
      'heritagepress',
      'Insert Test Data',
      'Test Data',
      'manage_options',
      'heritagepress-test-data',
      array($this, 'test_data_page')
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
      HERITAGEPRESS_VERSION . '.' . time()
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

    // Import/Export specific assets
    if (isset($_GET['page']) && $_GET['page'] === 'heritagepress-import') {
      wp_enqueue_style(
        'heritagepress-import-export',
        HERITAGEPRESS_PLUGIN_URL . 'admin/css/import-export.css',
        array('heritagepress-admin'),
        HERITAGEPRESS_VERSION
      );
      wp_enqueue_script(
        'heritagepress-import-export',
        HERITAGEPRESS_PLUGIN_URL . 'admin/js/import-export.js',
        array('jquery', 'heritagepress-admin'),
        HERITAGEPRESS_VERSION,
        true
      );

      wp_localize_script(
        'heritagepress-import-export',
        'hp_admin',
        array(
          'ajax_url' => admin_url('admin-ajax.php'),
          'nonce' => wp_create_nonce('heritagepress_admin_nonce'),
          'strings' => array(
            'select_file' => __('Please select an import file.', 'heritagepress'),
            'select_tree' => __('Please select a destination tree.', 'heritagepress'),
            'confirm_export' => __('Start GEDCOM export?', 'heritagepress'),
            'confirm_utility' => __('Run this post-import utility?', 'heritagepress'),
          )
        )
      );
    }    // Trees specific assets
    if (isset($_GET['page']) && $_GET['page'] === 'heritagepress-trees') {
      wp_enqueue_style(
        'heritagepress-trees',
        HERITAGEPRESS_PLUGIN_URL . 'includes/template/Trees/trees.css',
        array('heritagepress-admin'),
        HERITAGEPRESS_VERSION . '.' . time() . '.v7'
      );
    }

    // GEDCOM specific assets (will move to new importer soon)
    if (isset($_GET['page']) && $_GET['page'] === 'heritagepress-import') {
      wp_enqueue_style(
        'heritagepress-gedcom',
        HERITAGEPRESS_PLUGIN_URL . 'admin/css/gedcom.css',
        array('heritagepress-admin'),
        HERITAGEPRESS_VERSION
      );
    }

    // People specific assets
    if (isset($_GET['page']) && $_GET['page'] === 'heritagepress-people') {
      wp_enqueue_style(
        'heritagepress-people',
        HERITAGEPRESS_PLUGIN_URL . 'includes/template/People/people.css',
        array('heritagepress-admin'),
        HERITAGEPRESS_VERSION . '.' . time()
      );
      wp_enqueue_script(
        'heritagepress-people',
        HERITAGEPRESS_PLUGIN_URL . 'includes/template/People/people.js',
        array('jquery', 'heritagepress-admin'),
        HERITAGEPRESS_VERSION,
        true
      );
      wp_localize_script(
        'heritagepress-people',
        'hp_people',
        array(
          'ajax_url' => admin_url('admin-ajax.php'),
          'nonce' => wp_create_nonce('heritagepress_people_action'),
          'strings' => array(
            'confirm_delete' => __('Are you sure you want to delete this person?', 'heritagepress'),
            'confirm_bulk_delete' => __('Are you sure you want to delete the selected people?', 'heritagepress'),
            'select_people' => __('Please select at least one person.', 'heritagepress'),
            'select_action' => __('Please select an action.', 'heritagepress'),
            'loading' => __('Loading...', 'heritagepress'),
            'person_saved' => __('Person saved successfully.', 'heritagepress'),
            'error_occurred' => __('An error occurred. Please try again.', 'heritagepress'),          )
        )
      );
    }

    // Families specific assets
    if (isset($_GET['page']) && $_GET['page'] === 'heritagepress-families') {
      wp_enqueue_style(
        'heritagepress-families',
        HERITAGEPRESS_PLUGIN_URL . 'includes/template/Families/families.css',
        array('heritagepress-admin'),
        HERITAGEPRESS_VERSION . '.' . time()
      );
      wp_enqueue_script(
        'heritagepress-families',
        HERITAGEPRESS_PLUGIN_URL . 'includes/template/Families/families.js',
        array('jquery', 'heritagepress-admin'),
        HERITAGEPRESS_VERSION,
        true
      );
      wp_localize_script(
        'heritagepress-families',
        'hp_families',
        array(
          'ajax_url' => admin_url('admin-ajax.php'),
          'nonce' => wp_create_nonce('heritagepress_families_action'),
          'strings' => array(
            'confirm_delete' => __('Are you sure you want to delete this family?', 'heritagepress'),
            'confirm_bulk_delete' => __('Are you sure you want to delete the selected families?', 'heritagepress'),
            'select_families' => __('Please select at least one family.', 'heritagepress'),
            'select_action' => __('Please select an action.', 'heritagepress'),
            'loading' => __('Loading...', 'heritagepress'),
            'family_saved' => __('Family saved successfully.', 'heritagepress'),
            'error_occurred' => __('An error occurred. Please try again.', 'heritagepress'),
          )
        )
      );
    }
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

    // Display transient admin notices
    $this->display_admin_notices();
  }

  /**
   * Display transient admin notices
   */
  private function display_admin_notices()
  {
    $notices = get_transient('heritagepress_admin_notices');
    if (!$notices) {
      return;
    }

    foreach ($notices as $notice) {
      $type = isset($notice['type']) ? $notice['type'] : 'info';
      $message = isset($notice['message']) ? $notice['message'] : '';
      $dismissible = isset($notice['dismissible']) ? $notice['dismissible'] : true;

      if (!empty($message)) {
        echo '<div class="notice notice-' . esc_attr($type) . ($dismissible ? ' is-dismissible' : '') . '">';
        echo '<p>' . wp_kses_post($message) . '</p>';
        echo '</div>';
      }
    }

    // Clear the notices after displaying
    delete_transient('heritagepress_admin_notices');
  }

  /**
   * Add admin notice
   */
  private function add_admin_notice($message, $type = 'success', $dismissible = true)
  {
    $notices = get_transient('heritagepress_admin_notices');
    if (!$notices) {
      $notices = array();
    }

    $notices[] = array(
      'message' => $message,
      'type' => $type,
      'dismissible' => $dismissible
    );

    set_transient('heritagepress_admin_notices', $notices, 30);
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
   */  public function tables_page()
  {
    $database = heritage_press()->database;

    // Handle table operations
    if (isset($_POST['action'])) {
      // TODO: Implement handle_table_actions() method
      // $this->handle_table_actions();
    }

    $tables_exist = $database->tables_exist();
    $stats = $database->get_table_stats();

    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/tables.php';
  }

  /**
   * Database migrations page
   */
  public function migrations_page()
  {
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/migrations.php';
  }
  /**
   * People management page
   */
  public function people_page()
  {
    // Get current tab
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';

    // Get person ID for edit tab
    $person_id = isset($_GET['personID']) ? sanitize_text_field($_GET['personID']) : '';
    $tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';

    // Handle form submissions
    $this->handle_people_actions($current_tab);    include HERITAGEPRESS_PLUGIN_DIR . 'includes/template/People/people-main.php';
  }

  /**
   * Families page with tabs
   */
  public function families_page()
  {
    // Get current tab
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';

    // Get family ID for edit tab
    $family_id = isset($_GET['familyID']) ? sanitize_text_field($_GET['familyID']) : '';
    $tree = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';

    // Handle form submissions
    $this->handle_families_actions($current_tab);

    include HERITAGEPRESS_PLUGIN_DIR . 'includes/template/Families/families-main.php';
  }

  /**
   * Import/Export page with tabs
   */
  public function import_export_page()
  {
    // Get current tab
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'import';

    // Handle form submissions
    $this->handle_import_export_actions($current_tab);

    include HERITAGEPRESS_PLUGIN_DIR . 'includes/template/Import/import-export-split.php';
  }

  /**
   * Trees page with tabs
   */
  public function trees_page()
  {
    // Get current tab
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'browse';

    // Get tree ID for edit tab
    $tree_id = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';

    // Handle form submissions
    $this->handle_trees_actions($current_tab);

    include HERITAGEPRESS_PLUGIN_DIR . 'includes/template/Trees/trees-main.php';
  }
  /**
   * Handle trees page actions
   */
  private function handle_trees_actions($current_tab)
  {
    if (!current_user_can('edit_genealogy')) {
      return;
    }

    // Handle bulk actions from browse tab
    if (isset($_POST['action']) && $_POST['action'] !== '-1' && !empty($_POST['tree_ids'])) {
      $this->handle_bulk_tree_actions();
      return;
    }

    // Handle individual tree operations
    if (isset($_POST['action'])) {
      $action = sanitize_text_field($_POST['action']);

      switch ($action) {
        case 'add_tree':
          $this->handle_add_tree();
          break;
        case 'update_tree':
          $this->handle_update_tree();
          break;
        case 'delete_tree':
          $this->handle_delete_tree();
          break;
        case 'clear_tree':
          $this->handle_clear_tree();
          break;
      }
    }
  }

  /**
   * Handle bulk tree actions
   */
  private function handle_bulk_tree_actions()
  {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_bulk_trees')) {
      wp_die('Security check failed');
    }

    $tree_ids = array_map('sanitize_text_field', $_POST['tree_ids']);
    $action = sanitize_text_field($_POST['action']);

    // Handle action2 from bottom bulk selector
    if ($action === '-1' && isset($_POST['action2']) && $_POST['action2'] !== '-1') {
      $action = sanitize_text_field($_POST['action2']);
    }

    $success_count = 0;
    $error_count = 0;

    switch ($action) {
      case 'delete':
        foreach ($tree_ids as $tree_id) {
          if ($this->delete_tree_data($tree_id, false)) {
            $success_count++;
          } else {
            $error_count++;
          }
        }

        if ($success_count > 0) {
          $this->add_admin_notice(
            sprintf(_n('%d tree deleted successfully.', '%d trees deleted successfully.', $success_count, 'heritagepress'), $success_count),
            'success'
          );
        }

        if ($error_count > 0) {
          $this->add_admin_notice(
            sprintf(_n('Failed to delete %d tree.', 'Failed to delete %d trees.', $error_count, 'heritagepress'), $error_count),
            'error'
          );
        }
        break;

      case 'clear_data':
        foreach ($tree_ids as $tree_id) {
          if ($this->delete_tree_data($tree_id, true)) {
            $success_count++;
          } else {
            $error_count++;
          }
        }

        if ($success_count > 0) {
          $this->add_admin_notice(
            sprintf(_n('Data cleared from %d tree successfully.', 'Data cleared from %d trees successfully.', $success_count, 'heritagepress'), $success_count),
            'success'
          );
        }

        if ($error_count > 0) {
          $this->add_admin_notice(
            sprintf(_n('Failed to clear data from %d tree.', 'Failed to clear data from %d trees.', $error_count, 'heritagepress'), $error_count),
            'error'
          );
        }
        break;
    }    // Use proper redirect handling to avoid header issues
    if (!headers_sent()) {
      wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=browse'));
      exit;
    } else {
      echo '<script type="text/javascript">window.location.href = "' . admin_url('admin.php?page=heritagepress-trees&tab=browse') . '";</script>';
      echo '<noscript><meta http-equiv="refresh" content="0;url=' . admin_url('admin.php?page=heritagepress-trees&tab=browse') . '"></noscript>';
    }
    return;
  }

  /**
   * Handle add tree action
   */
  private function handle_add_tree()
  {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_add_tree')) {
      wp_die('Security check failed');
    }

    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';

    $gedcom = sanitize_text_field($_POST['gedcom']);
    $treename = sanitize_text_field($_POST['treename']);
    $description = sanitize_textarea_field($_POST['description']);
    $owner = sanitize_text_field($_POST['owner']);
    $email = sanitize_email($_POST['email']);

    $result = $wpdb->insert(
      $trees_table,
      array(
        'gedcom' => $gedcom,
        'treename' => $treename,
        'description' => $description,
        'owner' => $owner,
        'email' => $email,
        'secret' => isset($_POST['private']) ? 1 : 0,
        'disallowgedcreate' => isset($_POST['disallowgedcreate']) ? 1 : 0,
        'disallowpdf' => isset($_POST['disallowpdf']) ? 1 : 0
      ),
      array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d')
    );
    if ($result) {
      $this->add_admin_notice('Tree added successfully!', 'success');
      // Redirect to browse tab to show the notice
      wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=browse'));
      exit;
    } else {
      $this->add_admin_notice('Error adding tree.', 'error');
      // Redirect back to add tab to show the error
      wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=add'));
      exit;
    }
  }

  /**
   * Handle update tree action
   */
  private function handle_update_tree()
  {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_update_tree')) {
      wp_die('Security check failed');
    }

    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';

    $tree_id = sanitize_text_field($_POST['tree_id']);
    $treename = sanitize_text_field($_POST['treename']);
    $description = sanitize_textarea_field($_POST['description']);
    $owner = sanitize_text_field($_POST['owner']);
    $email = sanitize_email($_POST['email']);
    $address = sanitize_textarea_field($_POST['address']);
    $city = sanitize_text_field($_POST['city']);
    $state = sanitize_text_field($_POST['state']);
    $country = sanitize_text_field($_POST['country']);
    $zip = sanitize_text_field($_POST['zip']);
    $phone = sanitize_text_field($_POST['phone']);

    $result = $wpdb->update(
      $trees_table,
      array(
        'treename' => $treename,
        'description' => $description,
        'owner' => $owner,
        'email' => $email,
        'address' => $address,
        'city' => $city,
        'state' => $state,
        'country' => $country,
        'zip' => $zip,
        'phone' => $phone,
        'secret' => isset($_POST['private']) ? 1 : 0,
        'disallowgedcreate' => isset($_POST['disallowgedcreate']) ? 1 : 0,
        'disallowpdf' => isset($_POST['disallowpdf']) ? 1 : 0
      ),
      array('gedcom' => $tree_id),
      array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d'),
      array('%s')
    );
    if ($result !== false) {
      $this->add_admin_notice('Tree updated successfully!', 'success');
      // Redirect to browse tab to show the notice
      wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=browse'));
      exit;
    } else {
      $this->add_admin_notice('Error updating tree.', 'error');
      // Redirect back to edit tab to show the error
      wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=edit&tree=' . urlencode($tree_id)));
      exit;
    }
  }

  /**
   * Handle delete tree action
   */
  private function handle_delete_tree()
  {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_delete_tree')) {
      wp_die('Security check failed');
    }

    $tree_id = sanitize_text_field($_POST['tree_id']);
    $data_only = isset($_POST['data_only']) && $_POST['data_only'] == '1';
    $this->delete_tree_data($tree_id, $data_only);

    if ($data_only) {
      $this->add_admin_notice('Tree data cleared successfully!', 'success');
    } else {
      $this->add_admin_notice('Tree deleted successfully!', 'success');
    }

    // Redirect to browse tab to show the notice
    wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=browse'));
    exit;
  }

  /**
   * Handle clear tree action
   */
  private function handle_clear_tree()
  {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_clear_tree')) {
      wp_die('Security check failed');
    }

    $tree_id = sanitize_text_field($_POST['tree_id']);
    $this->delete_tree_data($tree_id, true);

    $this->add_admin_notice('Tree data cleared successfully!', 'success');

    // Redirect to browse tab to show the notice
    wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=browse'));
    exit;
  }
  /**
   * Delete tree data (and optionally tree configuration)
   */
  private function delete_tree_data($tree_id, $data_only = true)
  {
    global $wpdb;

    try {
      // List of tables that contain tree data
      $data_tables = array(
        'hp_people',
        'hp_families',
        'hp_children',
        'hp_events',
        'hp_sources',
        'hp_citations',
        'hp_repositories',
        'hp_media',
        'hp_medialinks',
        'hp_places',
        'hp_addresses',
        'hp_xnotes',
        'hp_notelinks',
        'hp_associations',
        'hp_branches',
        'hp_branchlinks',
        'hp_albumlinks',
        'hp_albumplinks',
        'hp_dna_tests',
        'hp_dna_links'
      );

      // Delete data from all tables
      foreach ($data_tables as $table) {
        $table_name = $wpdb->prefix . $table;
        $wpdb->delete($table_name, array('gedcom' => $tree_id), array('%s'));
      }

      // If not data only, delete the tree configuration too
      if (!$data_only) {
        $trees_table = $wpdb->prefix . 'hp_trees';
        $result = $wpdb->delete($trees_table, array('gedcom' => $tree_id), array('%s'));
        return $result !== false;
      }

      return true;
    } catch (Exception $e) {
      return false;
    }
  }

  /**
   * AJAX handler for getting branches
   */
  public function ajax_get_branches()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'heritagepress_admin_nonce')) {
      wp_die('Security check failed');
    }

    // Check permissions
    if (!current_user_can('import_gedcom')) {
      wp_die('Insufficient permissions');
    }

    $tree = sanitize_text_field($_POST['tree']);

    global $wpdb;
    $branches_table = $wpdb->prefix . 'hp_branches';

    $branches = $wpdb->get_results($wpdb->prepare(
      "SELECT branch, gedcom, description FROM $branches_table WHERE gedcom = %s ORDER BY description",
      $tree
    ), ARRAY_A);

    wp_send_json_success($branches);
  }

  /**
   * AJAX handler for checking tree ID availability
   */
  public function ajax_check_tree_id()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'heritagepress_check_tree')) {
      wp_die('Security check failed');
    }

    if (!current_user_can('edit_genealogy')) {
      wp_die('Insufficient permissions');
    }

    $tree_id = sanitize_text_field($_POST['tree_id']);

    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';
    $exists = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $trees_table WHERE gedcom = %s",
      $tree_id
    ));

    if ($exists > 0) {
      wp_send_json_error('Tree ID already exists');
    } else {
      wp_send_json_success('Tree ID available');
    }
  }

  /**
   * AJAX handler for deleting trees
   */
  public function ajax_delete_tree()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'heritagepress_delete_tree')) {
      wp_die('Security check failed');
    }

    if (!current_user_can('edit_genealogy')) {
      wp_die('Insufficient permissions');
    }

    $tree_id = sanitize_text_field($_POST['tree_id']);
    $data_only = isset($_POST['data_only']) && $_POST['data_only'] == '1';

    try {
      $this->delete_tree_data($tree_id, !$data_only);

      if ($data_only) {
        wp_send_json_success('Tree data cleared successfully');
      } else {
        wp_send_json_success('Tree deleted successfully');
      }
    } catch (Exception $e) {
      wp_send_json_error('Error: ' . $e->getMessage());
    }
  }

  /**
   * AJAX handler for clearing tree data
   */
  public function ajax_clear_tree()
  {
    if (!wp_verify_nonce($_POST['nonce'], 'heritagepress_clear_tree')) {
      wp_die('Security check failed');
    }

    if (!current_user_can('edit_genealogy')) {
      wp_die('Insufficient permissions');
    }

    $tree_id = sanitize_text_field($_POST['tree_id']);

    try {
      $this->delete_tree_data($tree_id, true);
      wp_send_json_success('Tree data cleared successfully');
    } catch (Exception $e) {
      wp_send_json_error('Error: ' . $e->getMessage());
    }
  }

  /**
   * AJAX handler for generating person IDs
   */
  public function ajax_generate_person_id()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_generate_person_id')) {
      wp_send_json_error('Security check failed');
      return;
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
      return;
    }

    $gedcom = sanitize_text_field($_POST['gedcom']);
    if (empty($gedcom)) {
      wp_send_json_error('Tree not specified');
      return;
    }

    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    // Generate a new person ID
    $prefix = get_option('heritagepress_person_id_prefix', 'I');

    // Find the highest existing ID
    $query = $wpdb->prepare(
      "SELECT personID FROM $people_table WHERE gedcom = %s AND personID LIKE %s ORDER BY CAST(SUBSTRING(personID, 2) AS UNSIGNED) DESC LIMIT 1",
      $gedcom,
      $prefix . '%'
    );

    $last_id = $wpdb->get_var($query);

    if ($last_id) {
      $number = intval(substr($last_id, 1)) + 1;
    } else {
      $number = 1;
    }

    $new_id = $prefix . $number;

    wp_send_json_success(array('personID' => $new_id));
  }

  /**
   * AJAX handler for checking person ID availability
   */
  public function ajax_check_person_id()
  {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'hp_check_person_id')) {
      wp_send_json_error('Security check failed');
      return;
    }

    if (!current_user_can('edit_genealogy')) {
      wp_send_json_error('Permission denied');
      return;
    }

    $person_id = sanitize_text_field($_POST['personID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    if (empty($person_id) || empty($gedcom)) {
      wp_send_json_error('Missing parameters');
      return;
    }

    global $wpdb;
    $people_table = $wpdb->prefix . 'hp_people';

    $exists = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $people_table WHERE personID = %s AND gedcom = %s",
      $person_id,
      $gedcom
    ));

    wp_send_json_success(array('available' => ($exists == 0)));
  }

  /**
   * Handle people management form submissions
   */
  private function handle_people_actions($tab)
  {
    if (!isset($_POST['action'])) {
      return;
    }

    // Verify nonce for security
    if (
      !wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_people_action') &&
      !wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_bulk_people') &&
      !wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_delete_person')
    ) {
      add_settings_error(
        'heritagepress_people',
        'invalid_nonce',
        __('Security check failed. Please try again.', 'heritagepress'),
        'error'
      );
      return;
    }

    switch ($_POST['action']) {
      case 'add_person':
        $this->handle_add_person();
        break;

      case 'update_person':
        $this->handle_update_person();
        break;

      case 'delete_person':
        $this->handle_delete_person();
        break;

      case 'delete':
      case 'make_private':
      case 'make_public':
        $this->handle_bulk_people_actions();
        break;
    }    // Handle action2 for bottom bulk actions
    if (isset($_POST['action2']) && $_POST['action2'] !== '-1') {
      $_POST['action'] = $_POST['action2'];
      $this->handle_bulk_people_actions();
    }
  }

  /**
   * Handle families management form submissions
   */
  private function handle_families_actions($tab)
  {
    if (!isset($_POST['action'])) {
      return;
    }

    // Verify nonce for security
    if (
      !wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_families_action') &&
      !wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_bulk_families') &&
      !wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_delete_family')
    ) {
      add_settings_error(
        'heritagepress_families',
        'invalid_nonce',
        __('Security check failed. Please try again.', 'heritagepress'),
        'error'
      );
      return;
    }

    switch ($_POST['action']) {
      case 'add_family':
        $this->handle_add_family();
        break;

      case 'update_family':
        $this->handle_update_family();
        break;

      case 'delete_family':
        $this->handle_delete_family();
        break;

      case 'delete':
      case 'make_private':
      case 'make_public':
        $this->handle_bulk_families_actions();
        break;
    }

    // Handle action2 for bottom bulk actions
    if (isset($_POST['action2']) && $_POST['action2'] !== '-1') {
      $_POST['action'] = $_POST['action2'];
      $this->handle_bulk_families_actions();
    }
  }

  /**
   * Handle adding a new person - Enhanced TNG-style
   */
  private function handle_add_person()
  {
    global $wpdb;

    if (!current_user_can('edit_genealogy')) {
      add_settings_error(
        'heritagepress_people',
        'permission_denied',
        __('You do not have permission to add people.', 'heritagepress'),
        'error'
      );
      return;
    }

    $people_table = $wpdb->prefix . 'hp_people';
    $branches_table = $wpdb->prefix . 'hp_branchlinks';

    // Handle Person ID generation if empty
    $person_id = sanitize_text_field($_POST['personID']);
    if (empty($person_id)) {
      $gedcom = sanitize_text_field($_POST['gedcom']);
      $prefix = get_option('heritagepress_person_id_prefix', 'I');

      // Find the highest existing ID
      $query = $wpdb->prepare(
        "SELECT personID FROM $people_table WHERE gedcom = %s AND personID LIKE %s ORDER BY CAST(SUBSTRING(personID, 2) AS UNSIGNED) DESC LIMIT 1",
        $gedcom,
        $prefix . '%'
      );

      $last_id = $wpdb->get_var($query);

      if ($last_id) {
        $number = intval(substr($last_id, 1)) + 1;
      } else {
        $number = 1;
      }

      $person_id = $prefix . $number;
    }    // Handle gender selection including "other" option
    $sex = sanitize_text_field($_POST['sex']);
    if (empty($sex) && !empty($_POST['other_gender'])) {
      $sex = sanitize_text_field($_POST['other_gender']);
    }    // Sanitize and prepare person data - Full TNG schema
    $person_data = array(
      'personID' => $person_id,
      'gedcom' => sanitize_text_field($_POST['tree1']), // TNG uses 'tree1'
      'firstname' => sanitize_text_field($_POST['firstname']),
      'lastname' => sanitize_text_field($_POST['lastname']),
      'lnprefix' => sanitize_text_field($_POST['lnprefix'] ?? ''), // TNG field
      'prefix' => sanitize_text_field($_POST['prefix']),
      'suffix' => sanitize_text_field($_POST['suffix']),
      'nickname' => sanitize_text_field($_POST['nickname']),
      'title' => sanitize_text_field($_POST['title'] ?? ''), // Now available in full schema
      'nameorder' => sanitize_text_field($_POST['pnameorder']), // Form uses 'pnameorder', DB uses 'nameorder'
      'sex' => $sex,

      // Birth events - all TNG fields now available
      'birthdate' => sanitize_text_field($_POST['birthdate'] ?? ''),
      'birthdatetr' => '0000-00-00', // Will be calculated
      'birthplace' => sanitize_text_field($_POST['birthplace'] ?? ''),
      'altbirthtype' => sanitize_text_field($_POST['altbirthtype'] ?? ''), // TNG field
      'altbirthdate' => sanitize_text_field($_POST['altbirthdate'] ?? ''), // TNG field
      'altbirthdatetr' => '0000-00-00', // Will be calculated
      'altbirthplace' => sanitize_text_field($_POST['altbirthplace'] ?? ''), // TNG field

      // Death events - all TNG fields now available
      'deathdate' => sanitize_text_field($_POST['deathdate'] ?? ''),
      'deathdatetr' => '0000-00-00', // Will be calculated
      'deathplace' => sanitize_text_field($_POST['deathplace'] ?? ''),
      'burialdate' => sanitize_text_field($_POST['burialdate'] ?? ''), // Now available
      'burialdatetr' => '0000-00-00', // Will be calculated
      'burialplace' => sanitize_text_field($_POST['burialplace'] ?? ''), // Now available
      'burialtype' => isset($_POST['burialtype']) ? '1' : '0', // TNG field

      // LDS events - all TNG fields now available
      'baptdate' => sanitize_text_field($_POST['baptdate'] ?? ''),
      'baptdatetr' => '0000-00-00', // Will be calculated
      'baptplace' => sanitize_text_field($_POST['baptplace'] ?? ''),
      'confdate' => sanitize_text_field($_POST['confdate'] ?? ''),
      'confdatetr' => '0000-00-00', // Will be calculated
      'confplace' => sanitize_text_field($_POST['confplace'] ?? ''),
      'initdate' => sanitize_text_field($_POST['initdate'] ?? ''),
      'initdatetr' => '0000-00-00', // Will be calculated
      'initplace' => sanitize_text_field($_POST['initplace'] ?? ''),
      'endldate' => sanitize_text_field($_POST['endldate'] ?? ''),
      'endldatetr' => '0000-00-00', // Will be calculated
      'endlplace' => sanitize_text_field($_POST['endlplace'] ?? ''),

      // Status fields
      'living' => isset($_POST['living']) ? '1' : '0',
      'private' => isset($_POST['private']) ? '1' : '0',

      // TNG additional fields now available
      'famc' => sanitize_text_field($_POST['famc'] ?? ''),
      'metaphone' => '', // Will be calculated
      'branch' => '', // Will be set separately
      'changedate' => current_time('mysql'),
      'changedby' => wp_get_current_user()->user_login,
      'edituser' => '',
      'edittime' => 0
    );    // Handle all TNG date fields with both display and sortable versions
    $date_fields = array(
      'birthdate',
      'altbirthdate',
      'deathdate',
      'burialdate',
      'baptdate',
      'confdate',
      'initdate',
      'endldate'
    );

    foreach ($date_fields as $field) {
      if (isset($_POST[$field])) {
        $display_date = sanitize_text_field($_POST[$field]);
        $sortable_date = HP_Date_Parser::to_sortable($display_date);

        $person_data[$field] = $display_date;
        $person_data[$field . 'tr'] = $sortable_date ?: '0000-00-00';
      }
    }

    // Check if Person ID already exists (indicating it was locked/reserved)
    $existing_person = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $people_table WHERE personID = %s AND gedcom = %s",
      $person_id,
      $person_data['gedcom']
    ));

    $is_locked_record = false;
    if ($existing_person) {
      // Check if this is a locked/reserved record
      if ($existing_person->firstname === '** LOCKED **' && $existing_person->lastname === '** RESERVED **') {
        $is_locked_record = true;
      } else {
        // Person ID already exists with real data
        add_settings_error(
          'heritagepress_people',
          'duplicate_id',
          __('Person ID already exists. Please use a different ID.', 'heritagepress'),
          'error'
        );
        return;
      }
    }

    // Start transaction for person and branch insertion
    $wpdb->query('START TRANSACTION');

    try {
      if ($is_locked_record) {
        // Update the existing locked record
        $result = $wpdb->update(
          $people_table,
          $person_data,
          array(
            'personID' => $person_id,
            'gedcom' => $person_data['gedcom']
          )
        );
        $person_wp_id = $existing_person->ID; // Use existing WordPress ID
      } else {
        // Insert new person
        $result = $wpdb->insert($people_table, $person_data);
        $person_wp_id = $wpdb->insert_id;
      }

      if ($result === false) {
        throw new Exception($is_locked_record ? 'Failed to update locked person' : 'Failed to insert person');
      } // Handle branch assignment
      if (isset($_POST['branch']) && !empty($_POST['branch'])) {
        $branch = sanitize_text_field($_POST['branch']);
        $branch_data = array(
          'personID' => $person_id,
          'gedcom' => $person_data['gedcom'],
          'branch' => $branch
        );
        $wpdb->insert($branches_table, $branch_data);
      }

      $wpdb->query('COMMIT');
      add_settings_error(
        'heritagepress_people',
        'person_added',
        $is_locked_record ?
          __('Person information completed successfully! The locked ID has been updated.', 'heritagepress') :
          __('Person added successfully.', 'heritagepress'),
        'success'
      );

      // Check if "add and continue" was requested
      if (isset($_POST['add_continue'])) {
        // Redirect back to add form
        wp_redirect(admin_url('admin.php?page=heritagepress-people&tab=add&added=1'));
        exit;
      }
    } catch (Exception $e) {
      $wpdb->query('ROLLBACK');

      add_settings_error(
        'heritagepress_people',
        'add_failed',
        __('Failed to add person. Please try again.', 'heritagepress') . ' Error: ' . $e->getMessage(),
        'error'
      );
    }
  }
  /**
   * Handle updating an existing person
   */
  private function handle_update_person()
  {
    global $wpdb;

    if (!current_user_can('edit_genealogy')) {
      add_settings_error(
        'heritagepress_people',
        'permission_denied',
        __('You do not have permission to edit people.', 'heritagepress'),
        'error'
      );
      return;
    }

    $people_table = $wpdb->prefix . 'hp_people';
    $person_id = sanitize_text_field($_POST['personID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);    // Sanitize and prepare person data
    $person_data = array(
      'firstname' => sanitize_text_field($_POST['firstname']),
      'lastname' => sanitize_text_field($_POST['lastname']),
      'lnprefix' => '', // Field removed from form but kept in database for compatibility
      'prefix' => sanitize_text_field($_POST['prefix']),
      'suffix' => sanitize_text_field($_POST['suffix']),
      'nickname' => sanitize_text_field($_POST['nickname']),
      'nameorder' => sanitize_text_field($_POST['nameorder']),
      'sex' => sanitize_text_field($_POST['sex']),
      'birthplace' => sanitize_text_field($_POST['birthplace']),
      'deathplace' => sanitize_text_field($_POST['deathplace']),
      'altbirthtype' => sanitize_text_field($_POST['altbirthtype'] ?? ''),
      'altbirthdate' => sanitize_text_field($_POST['altbirthdate'] ?? ''),
      'altbirthplace' => sanitize_text_field($_POST['altbirthplace'] ?? ''),
      'burialdate' => sanitize_text_field($_POST['burialdate'] ?? ''),
      'burialplace' => sanitize_text_field($_POST['burialplace'] ?? ''),
      'living' => isset($_POST['living']) ? 1 : 0,
      'private' => isset($_POST['private']) ? 1 : 0,
      'changedate' => current_time('mysql'),
      'changedby' => wp_get_current_user()->user_login
    );

    // Process dates with dual storage using the date validator
    $date_fields = ['birthdate', 'deathdate', 'altbirthdate', 'burialdate'];

    foreach ($date_fields as $field) {
      if (isset($_POST[$field])) {
        $display_date = sanitize_text_field($_POST[$field]);
        $sortable_date = HP_Date_Parser::to_sortable($display_date);

        $person_data[$field] = $display_date;
        $person_data[$field . 'tr'] = $sortable_date ?: '0000-00-00';
      }
    }

    $result = $wpdb->update(
      $people_table,
      $person_data,
      array('personID' => $person_id, 'gedcom' => $gedcom)
    );

    if ($result === false) {
      add_settings_error(
        'heritagepress_people',
        'update_failed',
        __('Failed to update person. Please try again.', 'heritagepress'),
        'error'
      );
    } else {
      add_settings_error(
        'heritagepress_people',
        'person_updated',
        __('Person updated successfully.', 'heritagepress'),
        'success'
      );
    }
  }

  /**
   * Handle deleting a person
   */
  private function handle_delete_person()
  {
    global $wpdb;

    if (!current_user_can('delete_genealogy')) {
      add_settings_error(
        'heritagepress_people',
        'permission_denied',
        __('You do not have permission to delete people.', 'heritagepress'),
        'error'
      );
      return;
    }

    $people_table = $wpdb->prefix . 'hp_people';
    $person_id = sanitize_text_field($_POST['personID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    $result = $wpdb->delete(
      $people_table,
      array('personID' => $person_id, 'gedcom' => $gedcom)
    );

    if ($result === false) {
      add_settings_error(
        'heritagepress_people',
        'delete_failed',
        __('Failed to delete person. Please try again.', 'heritagepress'),
        'error'
      );
    } else {
      add_settings_error(
        'heritagepress_people',
        'person_deleted',
        __('Person deleted successfully.', 'heritagepress'),
        'success'
      );
    }
  }

  /**
   * Handle bulk actions on people
   */
  private function handle_bulk_people_actions()
  {
    global $wpdb;

    if (!current_user_can('edit_genealogy')) {
      add_settings_error(
        'heritagepress_people',
        'permission_denied',
        __('You do not have permission to perform bulk actions.', 'heritagepress'),
        'error'
      );
      return;
    }

    $action = sanitize_text_field($_POST['action']);
    if ($action === '-1' && isset($_POST['action2'])) {
      $action = sanitize_text_field($_POST['action2']);
    }

    if (!isset($_POST['selected_people']) || empty($_POST['selected_people'])) {
      add_settings_error(
        'heritagepress_people',
        'no_selection',
        __('No people selected for bulk action.', 'heritagepress'),
        'error'
      );
      return;
    }

    $selected_people = array_map('intval', $_POST['selected_people']);
    $people_table = $wpdb->prefix . 'hp_people';

    switch ($action) {
      case 'delete':
        if (!current_user_can('delete_genealogy')) {
          add_settings_error(
            'heritagepress_people',
            'permission_denied',
            __('You do not have permission to delete people.', 'heritagepress'),
            'error'
          );
          return;
        }

        $deleted = 0;
        foreach ($selected_people as $person_id) {
          $result = $wpdb->delete($people_table, array('ID' => $person_id));
          if ($result !== false) {
            $deleted++;
          }
        }

        add_settings_error(
          'heritagepress_people',
          'bulk_delete',
          sprintf(_n('%d person deleted.', '%d people deleted.', $deleted, 'heritagepress'), $deleted),
          'success'
        );
        break;

      case 'make_private':
        $updated = 0;
        foreach ($selected_people as $person_id) {
          $result = $wpdb->update(
            $people_table,
            array('private' => 1, 'changedate' => current_time('mysql'), 'changedby' => wp_get_current_user()->user_login),
            array('ID' => $person_id)
          );
          if ($result !== false) {
            $updated++;
          }
        }
        $this->add_admin_notice(
          sprintf(_n('%d person marked as private.', '%d people marked as private.', $updated, 'heritagepress'), $updated),
          'success'
        );
        break;

      case 'make_public':
        $updated = 0;
        foreach ($selected_people as $person_id) {
          $result = $wpdb->update(
            $people_table,
            array('private' => 0, 'changedate' => current_time('mysql'), 'changedby' => wp_get_current_user()->user_login),
            array('ID' => $person_id)
          );
          if ($result !== false) {
            $updated++;
          }
        }
        $this->add_admin_notice(
          sprintf(_n('%d person marked as public.', '%d people marked as public.', $updated, 'heritagepress'), $updated),
          'success'        );
        break;
    }
  }

  /**
   * Handle adding a new family
   */
  private function handle_add_family()
  {
    global $wpdb;

    if (!current_user_can('edit_genealogy')) {
      add_settings_error(
        'heritagepress_families',
        'permission_denied',
        __('You do not have permission to add families.', 'heritagepress'),
        'error'
      );
      return;
    }

    $families_table = $wpdb->prefix . 'hp_families';

    // Extract and sanitize form data
    $family_data = array(
      'familyID' => sanitize_text_field($_POST['familyID']),
      'husband' => sanitize_text_field($_POST['husband']),
      'wife' => sanitize_text_field($_POST['wife']),
      'gedcom' => sanitize_text_field($_POST['gedcom']),
      'marrdate' => sanitize_text_field($_POST['marrdate']),
      'marrplace' => sanitize_text_field($_POST['marrplace']),
      'divdate' => sanitize_text_field($_POST['divdate']),
      'divplace' => sanitize_text_field($_POST['divplace']),
      'living' => isset($_POST['living']) ? 1 : 0,
      'private' => isset($_POST['private']) ? 1 : 0,
      'notes' => wp_kses_post($_POST['notes']),
      'changedate' => current_time('mysql'),
      'changedby' => wp_get_current_user()->user_login
    );

    // Validate required fields
    if (empty($family_data['familyID']) || empty($family_data['gedcom'])) {
      add_settings_error(
        'heritagepress_families',
        'missing_required',
        __('Family ID and tree selection are required.', 'heritagepress'),
        'error'
      );
      return;
    }

    // Check if family ID already exists
    $existing = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$families_table} WHERE familyID = %s AND gedcom = %s",
      $family_data['familyID'],
      $family_data['gedcom']
    ));

    if ($existing > 0) {
      add_settings_error(
        'heritagepress_families',
        'duplicate_id',
        __('A family with this ID already exists in the selected tree.', 'heritagepress'),
        'error'
      );
      return;
    }

    // Insert the family
    $result = $wpdb->insert($families_table, $family_data);

    if ($result === false) {
      add_settings_error(
        'heritagepress_families',
        'insert_failed',
        __('Failed to add family. Please try again.', 'heritagepress'),
        'error'
      );
    } else {
      add_settings_error(
        'heritagepress_families',
        'family_added',
        sprintf(__('Family %s added successfully.', 'heritagepress'), $family_data['familyID']),
        'success'
      );
    }
  }

  /**
   * Handle updating an existing family
   */
  private function handle_update_family()
  {
    global $wpdb;

    if (!current_user_can('edit_genealogy')) {
      add_settings_error(
        'heritagepress_families',
        'permission_denied',
        __('You do not have permission to update families.', 'heritagepress'),
        'error'
      );
      return;
    }

    $families_table = $wpdb->prefix . 'hp_families';
    $original_id = sanitize_text_field($_POST['original_familyID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    // Extract and sanitize form data
    $family_data = array(
      'familyID' => sanitize_text_field($_POST['familyID']),
      'husband' => sanitize_text_field($_POST['husband']),
      'wife' => sanitize_text_field($_POST['wife']),
      'marrdate' => sanitize_text_field($_POST['marrdate']),
      'marrplace' => sanitize_text_field($_POST['marrplace']),
      'divdate' => sanitize_text_field($_POST['divdate']),
      'divplace' => sanitize_text_field($_POST['divplace']),
      'living' => isset($_POST['living']) ? 1 : 0,
      'private' => isset($_POST['private']) ? 1 : 0,
      'notes' => wp_kses_post($_POST['notes']),
      'changedate' => current_time('mysql'),
      'changedby' => wp_get_current_user()->user_login
    );

    // If family ID changed, check for conflicts
    if ($family_data['familyID'] !== $original_id) {
      $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$families_table} WHERE familyID = %s AND gedcom = %s",
        $family_data['familyID'],
        $gedcom
      ));

      if ($existing > 0) {
        add_settings_error(
          'heritagepress_families',
          'duplicate_id',
          __('A family with this ID already exists in the selected tree.', 'heritagepress'),
          'error'
        );
        return;
      }

      // Update references in people table if ID changed
      $people_table = $wpdb->prefix . 'hp_people';
      $wpdb->update(
        $people_table,
        array('famc' => $family_data['familyID']),
        array('famc' => $original_id, 'gedcom' => $gedcom)
      );
      $wpdb->update(
        $people_table,
        array('fams' => $family_data['familyID']),
        array('fams' => $original_id, 'gedcom' => $gedcom)
      );
    }

    // Update the family
    $result = $wpdb->update(
      $families_table,
      $family_data,
      array('familyID' => $original_id, 'gedcom' => $gedcom)
    );

    if ($result === false) {
      add_settings_error(
        'heritagepress_families',
        'update_failed',
        __('Failed to update family. Please try again.', 'heritagepress'),
        'error'
      );
    } else {
      add_settings_error(
        'heritagepress_families',
        'family_updated',
        sprintf(__('Family %s updated successfully.', 'heritagepress'), $family_data['familyID']),
        'success'
      );
    }
  }

  /**
   * Handle deleting a family
   */
  private function handle_delete_family()
  {
    global $wpdb;

    if (!current_user_can('edit_genealogy')) {
      add_settings_error(
        'heritagepress_families',
        'permission_denied',
        __('You do not have permission to delete families.', 'heritagepress'),
        'error'
      );
      return;
    }

    $family_id = sanitize_text_field($_POST['familyID']);
    $gedcom = sanitize_text_field($_POST['gedcom']);

    $families_table = $wpdb->prefix . 'hp_families';
    $people_table = $wpdb->prefix . 'hp_people';

    // Clear family references from people
    $wpdb->update(
      $people_table,
      array('famc' => ''),
      array('famc' => $family_id, 'gedcom' => $gedcom)
    );

    $wpdb->update(
      $people_table,
      array('fams' => ''),
      array('fams' => $family_id, 'gedcom' => $gedcom)
    );

    // Delete the family
    $result = $wpdb->delete(
      $families_table,
      array('familyID' => $family_id, 'gedcom' => $gedcom)
    );

    if ($result === false) {
      add_settings_error(
        'heritagepress_families',
        'delete_failed',
        __('Failed to delete family. Please try again.', 'heritagepress'),
        'error'
      );
    } else {
      add_settings_error(
        'heritagepress_families',
        'family_deleted',
        sprintf(__('Family %s deleted successfully.', 'heritagepress'), $family_id),
        'success'
      );
    }
  }

  /**
   * Handle bulk actions on families
   */
  private function handle_bulk_families_actions()
  {
    global $wpdb;

    if (!current_user_can('edit_genealogy')) {
      add_settings_error(
        'heritagepress_families',
        'permission_denied',
        __('You do not have permission to perform bulk actions.', 'heritagepress'),
        'error'
      );
      return;
    }

    $action = sanitize_text_field($_POST['action']);
    $selected_families = isset($_POST['selected_families']) ? array_map('sanitize_text_field', $_POST['selected_families']) : array();

    if (empty($selected_families)) {
      add_settings_error(
        'heritagepress_families',
        'no_selection',
        __('Please select families to perform bulk actions.', 'heritagepress'),
        'error'
      );
      return;
    }

    $families_table = $wpdb->prefix . 'hp_families';
    $people_table = $wpdb->prefix . 'hp_people';

    switch ($action) {
      case 'delete':
        $deleted = 0;
        foreach ($selected_families as $family_id) {
          // Clear references first
          $wpdb->update(
            $people_table,
            array('famc' => ''),
            array('famc' => $family_id)
          );
          $wpdb->update(
            $people_table,
            array('fams' => ''),
            array('fams' => $family_id)
          );

          // Delete family
          $result = $wpdb->delete(
            $families_table,
            array('ID' => $family_id)
          );
          if ($result !== false) {
            $deleted++;
          }
        }
        add_settings_error(
          'heritagepress_families',
          'bulk_deleted',
          sprintf(_n('%d family deleted.', '%d families deleted.', $deleted, 'heritagepress'), $deleted),
          'success'
        );
        break;

      case 'make_private':
        $updated = 0;
        foreach ($selected_families as $family_id) {
          $result = $wpdb->update(
            $families_table,
            array('private' => 1, 'changedate' => current_time('mysql'), 'changedby' => wp_get_current_user()->user_login),
            array('ID' => $family_id)
          );
          if ($result !== false) {
            $updated++;
          }
        }
        add_settings_error(
          'heritagepress_families',
          'bulk_private',
          sprintf(_n('%d family marked as private.', '%d families marked as private.', $updated, 'heritagepress'), $updated),
          'success'
        );
        break;

      case 'make_public':
        $updated = 0;
        foreach ($selected_families as $family_id) {
          $result = $wpdb->update(
            $families_table,
            array('private' => 0, 'changedate' => current_time('mysql'), 'changedby' => wp_get_current_user()->user_login),
            array('ID' => $family_id)
          );
          if ($result !== false) {
            $updated++;
          }
        }
        add_settings_error(
          'heritagepress_families',
          'bulk_public',
          sprintf(_n('%d family marked as public.', '%d families marked as public.', $updated, 'heritagepress'), $updated),
          'success'
        );
        break;
    }
  }

  /**
   * Handle import/export form submissions
   */
  private function handle_import_export_actions($tab)
  {
    if (!isset($_POST['action'])) {
      return;
    }
    switch ($tab) {
      case 'import':
        if ($_POST['action'] === 'import_gedcom') {
          // TODO: Implement handle_gedcom_import() method
          // $this->handle_gedcom_import();
        }
        break;

      case 'export':
        if ($_POST['action'] === 'export_gedcom') {
          // TODO: Implement handle_gedcom_export() method
          // $this->handle_gedcom_export();
        }
        break;

      case 'post-import':
        if (isset($_POST['secaction'])) {
          // TODO: Implement handle_post_import_action() method
          // $this->handle_post_import_action();
        }
        break;
    }
  }
  /**
   * Test data page
   */
  public function test_data_page()
  {
    global $wpdb;

    // Handle form submission with proper output buffering
    if (isset($_POST['insert_test_data']) && wp_verify_nonce($_POST['_wpnonce'], 'insert_test_data')) {
      $this->handle_early_output();
      $this->insert_test_data();
      $this->clean_output_buffer();

      // Use JavaScript redirect to avoid header issues
      if (!headers_sent()) {
        wp_redirect(admin_url('admin.php?page=heritagepress-test-data&success=1'));
        exit;
      } else {
        echo '<script type="text/javascript">window.location.href = "' . admin_url('admin.php?page=heritagepress-test-data&success=1') . '";</script>';
        return;
      }
    }

    $people_table = $wpdb->prefix . 'hp_people';
    $current_count = 0;

    if ($wpdb->get_var("SHOW TABLES LIKE '$people_table'") == $people_table) {
      $current_count = $wpdb->get_var("SELECT COUNT(*) FROM $people_table WHERE gedcom = 'test_tree'");
    }

?>
    <div class="wrap">
      <h1>HeritagePress Test Data</h1>

      <?php settings_errors('hp_test_data'); ?>

      <?php if (isset($_GET['success'])): ?>
        <div class="notice notice-success is-dismissible">
          <p><strong>Test data inserted successfully!</strong> You can now test the People section.</p>
          <p><a href="<?php echo admin_url('admin.php?page=heritagepress-people'); ?>" class="button button-primary">Go to People Section</a></p>
        </div>
      <?php endif; ?>

      <div class="card">
        <h2>Current Status</h2>
        <p><strong>Test Tree People Count:</strong> <?php echo $current_count; ?></p>
        <p><strong>People Table:</strong> <?php echo ($wpdb->get_var("SHOW TABLES LIKE '$people_table'") == $people_table) ? '✅ Exists' : '❌ Missing'; ?></p>
      </div>

      <div class="card">
        <h2>Insert Test Data</h2>
        <p>This will insert 5 sample people into the 'test_tree' for testing the People section.</p>

        <form method="post" action="">
          <?php wp_nonce_field('insert_test_data'); ?>
          <p>
            <input type="submit" name="insert_test_data" class="button button-primary" value="Insert Test Data"
              onclick="return confirm('This will replace any existing test_tree data. Continue?')">
          </p>
        </form>
      </div>

      <?php if ($current_count > 0): ?>
        <div class="card">
          <h2>Test Data Preview</h2>
          <?php
          $samples = $wpdb->get_results("SELECT personID, firstname, lastname, sex, birthdate, living FROM $people_table WHERE gedcom = 'test_tree' ORDER BY personID LIMIT 10");
          if ($samples): ?>
            <table class="wp-list-table widefat fixed striped">
              <thead>
                <tr>
                  <th>Person ID</th>
                  <th>Name</th>
                  <th>Sex</th>
                  <th>Birth Date</th>
                  <th>Living</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($samples as $person): ?> <tr>
                    <td><?php echo esc_html($person->personID); ?></td>
                    <td><?php echo esc_html($person->firstname . ' ' . $person->lastname); ?></td>
                    <td><?php echo esc_html($person->sex); ?></td>
                    <td><?php echo esc_html($person->birthdate); ?></td>
                    <td><?php echo $person->living ? 'Yes' : 'No'; ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="card">
        <h2>Next Steps</h2>
        <p>After inserting test data, you can:</p>
        <ul>
          <li><a href="<?php echo admin_url('admin.php?page=heritagepress-people'); ?>">Browse people in the People section</a></li>
          <li>Test searching and filtering</li>
          <li>Try adding/editing people</li>
          <li>Test reports and utilities</li>
        </ul>
      </div>
    </div>
<?php
  }
  /**
   * Insert test data
   */
  private function insert_test_data()
  {
    global $wpdb;

    $people_table = $wpdb->prefix . 'hp_people';
    $trees_table = $wpdb->prefix . 'hp_trees';    // Check if tables exist, create if needed
    if ($wpdb->get_var("SHOW TABLES LIKE '$people_table'") != $people_table) {
      $sql = "CREATE TABLE $people_table (
        ID int(11) NOT NULL AUTO_INCREMENT,
        personID varchar(20) NOT NULL,
        gedcom varchar(50) NOT NULL DEFAULT 'main',
        firstname varchar(100) DEFAULT NULL,
        lastname varchar(100) DEFAULT NULL,
        lnprefix varchar(50) DEFAULT NULL,
        prefix varchar(20) DEFAULT NULL,
        suffix varchar(20) DEFAULT NULL,
        nickname varchar(50) DEFAULT NULL,
        nameorder varchar(20) DEFAULT 'western',
        sex varchar(1) DEFAULT NULL,
        birthdate varchar(50) DEFAULT NULL,
        birthplace varchar(255) DEFAULT NULL,
        deathdate varchar(50) DEFAULT NULL,
        deathplace varchar(255) DEFAULT NULL,
        living tinyint(1) DEFAULT 0,
        private tinyint(1) DEFAULT 0,
        changedate datetime DEFAULT CURRENT_TIMESTAMP,
        changedby varchar(50) DEFAULT NULL,
        PRIMARY KEY (ID),
        UNIQUE KEY person_tree (personID, gedcom),
        KEY name_index (lastname, firstname)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
    }

    // Check if trees table exists, create if needed
    if ($wpdb->get_var("SHOW TABLES LIKE '$trees_table'") != $trees_table) {
      $trees_sql = "CREATE TABLE $trees_table (
        ID int(11) NOT NULL AUTO_INCREMENT,
        gedcom varchar(50) NOT NULL,
        treename varchar(255) NOT NULL,
        description text,
        owner varchar(100) DEFAULT NULL,
        email varchar(100) DEFAULT NULL,
        rootpersonID varchar(20) DEFAULT NULL,
        living_prefix varchar(10) DEFAULT '',
        allow_living varchar(10) DEFAULT 'yes',
        people_count int(11) DEFAULT 0,
        family_count int(11) DEFAULT 0,
        changedate datetime DEFAULT CURRENT_TIMESTAMP,
        changedby varchar(50) DEFAULT NULL,
        PRIMARY KEY (ID),
        UNIQUE KEY gedcom (gedcom)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($trees_sql);
    }

    // Create/update the test tree record first
    $tree_data = array(
      'gedcom' => 'test_tree',
      'treename' => 'Test Family Tree',
      'description' => 'Sample tree for testing HeritagePress People section',
      'owner' => 'test_admin',
      'email' => 'test@example.com',
      'rootpersonID' => 'I1',
      'living_prefix' => '',
      'allow_living' => 'yes',
      'people_count' => 5,
      'family_count' => 0,
      'changedate' => current_time('mysql'),
      'changedby' => 'test_import'
    );    // Check if test tree already exists
    $existing_tree = $wpdb->get_var($wpdb->prepare(
      "SELECT gedcom FROM $trees_table WHERE gedcom = %s",
      'test_tree'
    ));

    if ($existing_tree) {
      // Update existing tree
      $tree_result = $wpdb->update($trees_table, $tree_data, array('gedcom' => 'test_tree'));
    } else {
      // Insert new tree
      $tree_result = $wpdb->insert($trees_table, $tree_data);
    }

    // Check if tree operation was successful
    if ($tree_result === false) {
      add_settings_error('hp_test_data', 'error', 'Failed to create test tree: ' . $wpdb->last_error, 'error');
      return;
    }

    // Clear existing test people data
    $wpdb->delete($people_table, array('gedcom' => 'test_tree'));

    // Sample people data
    $sample_people = array(
      array(
        'personID' => 'I1',
        'gedcom' => 'test_tree',
        'firstname' => 'Robert Eugene',
        'lastname' => 'Williams',
        'sex' => 'M',
        'birthdate' => '2 OCT 1822',
        'birthplace' => 'Weston, Madison, Connecticut',
        'deathdate' => '14 APR 1905',
        'deathplace' => 'Stamford, Fairfield, CT',
        'living' => 0,
        'private' => 0,
        'changedate' => current_time('mysql'),
        'changedby' => 'test_import'
      ),
      array(
        'personID' => 'I2',
        'gedcom' => 'test_tree',
        'firstname' => 'Mary Ann',
        'lastname' => 'Wilson',
        'sex' => 'F',
        'birthdate' => 'BEF 1828',
        'birthplace' => 'Connecticut',
        'living' => 0,
        'private' => 0,
        'changedate' => current_time('mysql'),
        'changedby' => 'test_import'
      ),
      array(
        'personID' => 'I3',
        'gedcom' => 'test_tree',
        'firstname' => 'Joe',
        'lastname' => 'Williams',
        'sex' => 'M',
        'birthdate' => '11 JUN 1861',
        'birthplace' => 'Idaho Falls, Bonneville, Idaho',
        'living' => 0,
        'private' => 0,
        'changedate' => current_time('mysql'),
        'changedby' => 'test_import'
      ),
      array(
        'personID' => 'I4',
        'gedcom' => 'test_tree',
        'firstname' => 'John',
        'lastname' => 'Smith',
        'sex' => 'M',
        'birthdate' => '15 JUL 1850',
        'birthplace' => 'New York, New York',
        'deathdate' => '3 MAR 1920',
        'deathplace' => 'Brooklyn, New York',
        'living' => 0,
        'private' => 0,
        'changedate' => current_time('mysql'),
        'changedby' => 'test_import'
      ),
      array(
        'personID' => 'I5',
        'gedcom' => 'test_tree',
        'firstname' => 'Emily Rose',
        'lastname' => 'Davis',
        'sex' => 'F',
        'birthdate' => '10 MAY 1990',
        'birthplace' => 'Seattle, Washington',
        'living' => 1,
        'private' => 1,
        'changedate' => current_time('mysql'),
        'changedby' => 'test_import'
      )
    );
    $inserted = 0;
    foreach ($sample_people as $person) {
      if ($wpdb->insert($people_table, $person)) {
        $inserted++;
      }
    }

    if ($inserted > 0) {
      add_settings_error('hp_test_data', 'success', "Successfully created 'Test Family Tree' and inserted $inserted test people", 'success');
    } else {
      add_settings_error('hp_test_data', 'error', 'Failed to insert test data', 'error');
    }
  }

  /**
   * Handle early output and prevent header warnings
   */
  private function handle_early_output()
  {
    // Check if output has already started
    if (headers_sent()) {
      return false;
    }

    // Start output buffering to catch any early output
    if (!ob_get_length()) {
      ob_start();
    }

    return true;
  }

  /**
   * Clean output buffer safely
   */
  private function clean_output_buffer()
  {
    $output = '';
    if (ob_get_length()) {
      $output = ob_get_contents();
      ob_end_clean();
    }
    return $output;
  }

  /**
   * Handle admin initialization
   */
  public function handle_admin_init()
  {
    // Handle any early output to prevent header warnings
    if (isset($_POST['action']) && in_array($_POST['action'], array('insert_test_data', 'add_tree', 'update_tree', 'delete_tree', 'clear_tree'))) {
      $this->handle_early_output();
    }
  }
}
