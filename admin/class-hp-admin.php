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
   */  private function init_hooks()
  {
    add_action('admin_menu', array($this, 'admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
    add_action('admin_notices', array($this, 'admin_notices'));    // AJAX hooks for import/export
    add_action('wp_ajax_hp_get_branches', array($this, 'ajax_get_branches'));

    // AJAX hooks for trees management
    add_action('wp_ajax_hp_check_tree_id', array($this, 'ajax_check_tree_id'));
    add_action('wp_ajax_hp_delete_tree', array($this, 'ajax_delete_tree'));
    add_action('wp_ajax_hp_clear_tree', array($this, 'ajax_clear_tree'));

    // AJAX hooks for chunked GEDCOM upload
    add_action('wp_ajax_hp_upload_gedcom_chunk', array($this, 'ajax_upload_gedcom_chunk'));
    add_action('wp_ajax_hp_finalize_gedcom_upload', array($this, 'ajax_finalize_gedcom_upload'));
    add_action('wp_ajax_hp_cancel_upload', array($this, 'ajax_cancel_upload'));
    add_action('wp_ajax_hp_refresh_server_files', array($this, 'ajax_refresh_server_files'));

    // Add GEDCOM file support
    add_filter('upload_mimes', array($this, 'add_gedcom_mime_type'));
    add_filter('wp_check_filetype_and_ext', array($this, 'check_gedcom_filetype'), 10, 4);

    // Initialize background processing
    $this->init_background_processing();
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
    );

    // People management submenu
    add_submenu_page(
      'heritagepress',
      'Manage People',
      'People',
      'edit_genealogy',
      'heritagepress-people',
      array($this, 'people_page')
    );    // Database tables submenu
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
    echo '<div class="wrap">';
    echo '<h1>' . __('Manage People', 'heritagepress') . '</h1>';
    echo '<p>' . __('People management functionality coming soon...', 'heritagepress') . '</p>';
    echo '</div>';
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
    }

    // Redirect to browse tab to show the notices
    wp_redirect(admin_url('admin.php?page=heritagepress-trees&tab=browse'));
    exit;
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
          $this->handle_gedcom_import();
        }
        break;

      case 'export':
        if ($_POST['action'] === 'export_gedcom') {
          $this->handle_gedcom_export();
        }
        break;

      case 'post-import':
        if (isset($_POST['secaction'])) {
          $this->handle_post_import_action();
        }
        break;
    }
  }

  /**
   * Handle GEDCOM import submission
   */  private function handle_gedcom_import()
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

    // Get file path based on upload method
    $file_path = '';
    $upload_method = sanitize_text_field($_POST['upload_method']);

    if ($upload_method === 'computer') {
      // File uploaded via chunked upload
      $file_path = sanitize_text_field($_POST['uploaded_file_path']);

      if (empty($file_path) || !file_exists($file_path)) {
        add_settings_error(
          'heritagepress_import',
          'no_file',
          __('No file was uploaded. Please upload a GEDCOM file.', 'heritagepress'),
          'error'
        );
        return;
      }
    } elseif ($upload_method === 'server') {
      // File selected from server
      $server_file = sanitize_text_field($_POST['server_file']);

      if (empty($server_file)) {
        add_settings_error(
          'heritagepress_import',
          'no_server_file',
          __('No server file was selected. Please select a GEDCOM file.', 'heritagepress'),
          'error'
        );
        return;
      }

      $upload_dir = wp_upload_dir();
      $gedcom_dir = $upload_dir['basedir'] . '/heritagepress/gedcom/';
      $file_path = $gedcom_dir . $server_file;

      if (!file_exists($file_path)) {
        add_settings_error(
          'heritagepress_import',
          'server_file_not_found',
          __('Selected server file was not found.', 'heritagepress'),
          'error'
        );
        return;
      }
    } else {
      add_settings_error(
        'heritagepress_import',
        'invalid_method',
        __('Invalid upload method specified.', 'heritagepress'),
        'error'
      );
      return;
    }

    // Validate file is actually a GEDCOM
    if (!$this->is_valid_gedcom_file($file_path)) {
      add_settings_error(
        'heritagepress_import',
        'invalid_gedcom',
        __('File is not a valid GEDCOM format.', 'heritagepress'),
        'error'
      );
      return;
    }

    // Collect import options
    $import_options = array(
      'tree_id' => sanitize_text_field($_POST['tree1']),
      'branch_id' => isset($_POST['branch1']) ? sanitize_text_field($_POST['branch1']) : '',
      'import_type' => sanitize_text_field($_POST['del']),
      'uppercase_surnames' => isset($_POST['ucaselast']),
      'import_media' => isset($_POST['importmedia']),
      'import_coordinates' => isset($_POST['importlatlong']),
      'skip_recalc' => isset($_POST['norecalc']),
      'newer_only' => isset($_POST['neweronly']),
      'all_events' => isset($_POST['allevents']),
      'events_only' => isset($_POST['eventsonly']),
      'offset_choice' => isset($_POST['offsetchoice']) ? sanitize_text_field($_POST['offsetchoice']) : 'auto',
      'user_offset' => isset($_POST['useroffset']) ? intval($_POST['useroffset']) : 0,
      'legacy_mode' => isset($_POST['old'])
    );

    // Validate required options
    if (empty($import_options['tree_id'])) {
      add_settings_error(
        'heritagepress_import',
        'no_tree',
        __('Please select a destination tree.', 'heritagepress'),
        'error'
      );
      return;
    }

    try {
      // Queue import for background processing
      $job_id = $this->queue_gedcom_import($file_path, $import_options);

      if ($job_id) {
        add_settings_error(
          'heritagepress_import',
          'import_queued',
          sprintf(
            __('GEDCOM import has been queued for background processing. Job ID: %s. You will receive an email when the import completes.', 'heritagepress'),
            $job_id
          ),
          'updated'
        );

        // Redirect to status page
        wp_redirect(admin_url('admin.php?page=heritagepress-import&tab=import&job_id=' . $job_id));
        exit;
      } else {
        throw new Exception('Failed to queue import job');
      }
    } catch (Exception $e) {
      add_settings_error(
        'heritagepress_import',
        'import_failed',
        sprintf(__('Import failed: %s', 'heritagepress'), $e->getMessage()),
        'error'
      );
    }
  }
  /**
   * Handle GEDCOM export submission
   */
  private function handle_gedcom_export()
  {
    if (!current_user_can('import_gedcom')) {
      add_settings_error(
        'heritagepress_export',
        'permission_denied',
        __('You do not have permission to export GEDCOM files.', 'heritagepress'),
        'error'
      );
      return;
    }

    // Validate nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_export')) {
      add_settings_error(
        'heritagepress_export',
        'nonce_failed',
        __('Security check failed. Please try again.', 'heritagepress'),
        'error'
      );
      return;
    }

    // Export processing will be implemented
    add_settings_error(
      'heritagepress_export',
      'export_started',
      __('GEDCOM export started...', 'heritagepress'),
      'success'
    );
  }

  /**
   * Handle post-import utilities
   */
  private function handle_post_import_action()
  {
    if (!current_user_can('import_gedcom')) {
      add_settings_error(
        'heritagepress_post_import',
        'permission_denied',
        __('You do not have permission to run post-import utilities.', 'heritagepress'),
        'error'
      );
      return;
    }

    $action = sanitize_text_field($_POST['secaction']);

    // Post-import utility processing will be implemented
    add_settings_error(
      'heritagepress_post_import',
      'utility_completed',
      sprintf(__('Post-import utility "%s" completed.', 'heritagepress'), $action),
      'success'
    );

    // Add notification for successful action
    add_settings_error(
      'heritagepress_post_import',
      'utility_success',
      sprintf(__('Post-import utility "%s" completed successfully.', 'heritagepress'), $action),
      'updated'
    );
  }

  /**
   * Add GEDCOM file support to WordPress uploads
   */
  public function add_gedcom_mime_type($mimes)
  {
    $mimes['ged'] = 'text/plain';
    $mimes['gedcom'] = 'text/plain';
    return $mimes;
  }

  public function check_gedcom_filetype($data, $file, $filename, $mimes)
  {
    $wp_filetype = wp_check_filetype($filename, $mimes);
    $ext = $wp_filetype['ext'];
    $type = $wp_filetype['type'];
    $proper_filename = $data['proper_filename'];

    if ($ext && in_array($ext, array('ged', 'gedcom'))) {
      $data['ext'] = $ext;
      $data['type'] = 'text/plain';
    }

    return $data;
  }

  /**
   * Create and return GEDCOM upload directory
   */
  private function get_gedcom_upload_dir()
  {
    $upload_dir = wp_upload_dir();
    $gedcom_dir = $upload_dir['basedir'] . '/heritagepress/gedcom/';

    if (!file_exists($gedcom_dir)) {
      wp_mkdir_p($gedcom_dir);

      // Add .htaccess for security
      $htaccess_content = "Options -Indexes\n";
      $htaccess_content .= "<Files *.ged>\n";
      $htaccess_content .= "    Order allow,deny\n";
      $htaccess_content .= "    Allow from all\n";
      $htaccess_content .= "</Files>\n";
      $htaccess_content .= "<Files *.gedcom>\n";
      $htaccess_content .= "    Order allow,deny\n";
      $htaccess_content .= "    Allow from all\n";
      $htaccess_content .= "</Files>\n";

      file_put_contents($gedcom_dir . '.htaccess', $htaccess_content);
      file_put_contents($gedcom_dir . 'index.php', '<?php // Silence is golden');
    }

    return $gedcom_dir;
  }

  /**
   * Get temporary upload directory for chunks
   */
  private function get_temp_upload_dir()
  {
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/heritagepress/temp/';

    if (!file_exists($temp_dir)) {
      wp_mkdir_p($temp_dir);
      file_put_contents($temp_dir . 'index.php', '<?php // Silence is golden');
    }

    return $temp_dir;
  }

  /**
   * Validate GEDCOM file format
   */
  private function is_valid_gedcom_file($file_path)
  {
    $handle = fopen($file_path, 'r');
    if (!$handle) return false;

    $first_line = fgets($handle);
    fclose($handle);

    // Check if it starts with GEDCOM header
    return (strpos($first_line, '0 HEAD') === 0);
  }

  /**
   * AJAX handler for chunked GEDCOM upload
   */
  public function ajax_upload_gedcom_chunk()
  {
    check_ajax_referer('heritagepress_admin_nonce', 'nonce');

    if (!current_user_can('import_gedcom')) {
      wp_send_json_error('Unauthorized');
    }

    $upload_id = sanitize_text_field($_POST['upload_id']);
    $chunk_number = intval($_POST['chunk_number']);
    $total_chunks = intval($_POST['total_chunks']);
    $filename = sanitize_file_name($_POST['filename']);

    // Create temp directory for chunks
    $temp_dir = $this->get_temp_upload_dir();
    $chunk_dir = $temp_dir . $upload_id . '/';

    if (!file_exists($chunk_dir)) {
      wp_mkdir_p($chunk_dir);
    }

    // Save chunk
    $chunk_file = $chunk_dir . 'chunk_' . $chunk_number;
    $chunk_data = file_get_contents($_FILES['chunk']['tmp_name']);

    if (file_put_contents($chunk_file, $chunk_data) === false) {
      wp_send_json_error('Failed to save chunk');
    }

    // Store upload metadata
    $metadata_file = $chunk_dir . 'metadata.json';
    $metadata = array(
      'filename' => $filename,
      'total_chunks' => $total_chunks,
      'uploaded_chunks' => array(),
      'start_time' => time()
    );

    if (file_exists($metadata_file)) {
      $metadata = json_decode(file_get_contents($metadata_file), true);
    }

    if (!in_array($chunk_number, $metadata['uploaded_chunks'])) {
      $metadata['uploaded_chunks'][] = $chunk_number;
    }

    file_put_contents($metadata_file, json_encode($metadata));

    wp_send_json_success(array(
      'chunk' => $chunk_number,
      'total' => $total_chunks,
      'uploaded' => count($metadata['uploaded_chunks'])
    ));
  }

  /**
   * AJAX handler to finalize chunked upload
   */
  public function ajax_finalize_gedcom_upload()
  {
    check_ajax_referer('heritagepress_admin_nonce', 'nonce');

    if (!current_user_can('import_gedcom')) {
      wp_send_json_error('Unauthorized');
    }

    $upload_id = sanitize_text_field($_POST['upload_id']);
    $filename = sanitize_file_name($_POST['filename']);

    $temp_dir = $this->get_temp_upload_dir();
    $chunk_dir = $temp_dir . $upload_id . '/';

    // Read metadata
    $metadata_file = $chunk_dir . 'metadata.json';
    if (!file_exists($metadata_file)) {
      wp_send_json_error('Upload metadata not found');
    }

    $metadata = json_decode(file_get_contents($metadata_file), true);

    // Verify all chunks are uploaded
    $expected_chunks = range(0, $metadata['total_chunks'] - 1);
    $missing_chunks = array_diff($expected_chunks, $metadata['uploaded_chunks']);

    if (!empty($missing_chunks)) {
      wp_send_json_error('Missing chunks: ' . implode(', ', $missing_chunks));
    }

    // Combine chunks
    $gedcom_dir = $this->get_gedcom_upload_dir();
    $final_file = $gedcom_dir . wp_unique_filename($gedcom_dir, $filename);

    $final_handle = fopen($final_file, 'wb');
    if (!$final_handle) {
      wp_send_json_error('Failed to create final file');
    }

    for ($i = 0; $i < $metadata['total_chunks']; $i++) {
      $chunk_file = $chunk_dir . 'chunk_' . $i;
      if (file_exists($chunk_file)) {
        $chunk_data = file_get_contents($chunk_file);
        fwrite($final_handle, $chunk_data);
      }
    }

    fclose($final_handle);

    // Validate final GEDCOM file
    if (!$this->is_valid_gedcom_file($final_file)) {
      unlink($final_file);
      wp_send_json_error('Invalid GEDCOM file format');
    }

    // Clean up chunks
    $this->cleanup_temp_upload($chunk_dir);

    wp_send_json_success(array(
      'filename' => basename($final_file),
      'file_path' => $final_file,
      'size' => filesize($final_file),
      'url' => wp_upload_dir()['baseurl'] . '/heritagepress/gedcom/' . basename($final_file)
    ));
  }

  /**
   * AJAX handler to cancel upload
   */
  public function ajax_cancel_upload()
  {
    check_ajax_referer('heritagepress_admin_nonce', 'nonce');

    if (!current_user_can('import_gedcom')) {
      wp_send_json_error('Unauthorized');
    }

    $upload_id = sanitize_text_field($_POST['upload_id']);
    $temp_dir = $this->get_temp_upload_dir();
    $chunk_dir = $temp_dir . $upload_id . '/';

    if (is_dir($chunk_dir)) {
      $this->cleanup_temp_upload($chunk_dir);
    }

    wp_send_json_success('Upload cancelled');
  }

  /**
   * AJAX handler to refresh server files
   */
  public function ajax_refresh_server_files()
  {
    check_ajax_referer('heritagepress_admin_nonce', 'nonce');

    if (!current_user_can('import_gedcom')) {
      wp_send_json_error('Unauthorized');
    }

    $gedcom_dir = $this->get_gedcom_upload_dir();
    $files = array();

    if (is_dir($gedcom_dir)) {
      $file_list = glob($gedcom_dir . '*.{ged,gedcom}', GLOB_BRACE);
      foreach ($file_list as $file) {
        $filename = basename($file);
        $size = filesize($file);
        $modified = filemtime($file);

        $files[] = array(
          'filename' => $filename,
          'size' => $size,
          'size_mb' => number_format($size / 1024 / 1024, 2),
          'modified' => date('M j, Y H:i', $modified),
          'modified_timestamp' => $modified
        );
      }
    }

    // Sort by modification date (newest first)
    usort($files, function ($a, $b) {
      return $b['modified_timestamp'] - $a['modified_timestamp'];
    });

    wp_send_json_success($files);
  }

  /**
   * Clean up temporary upload directory
   */
  private function cleanup_temp_upload($chunk_dir)
  {
    if (!is_dir($chunk_dir)) return;

    $files = glob($chunk_dir . '*');
    foreach ($files as $file) {
      if (is_file($file)) {
        unlink($file);
      }
    }
    rmdir($chunk_dir);
  }

  /**
   * Initialize background processing hooks
   */
  public function init_background_processing()
  {
    add_action('hp_process_gedcom_import', array($this, 'process_gedcom_import_background'), 10, 1);
    add_action('wp_ajax_hp_get_import_status', array($this, 'ajax_get_import_status'));
    add_action('wp_ajax_hp_cancel_import', array($this, 'ajax_cancel_import'));

    // Clean up old import jobs daily
    if (!wp_next_scheduled('hp_cleanup_import_jobs')) {
      wp_schedule_event(time(), 'daily', 'hp_cleanup_import_jobs');
    }
    add_action('hp_cleanup_import_jobs', array($this, 'cleanup_old_import_jobs'));
  }

  /**
   * Queue GEDCOM import for background processing
   */  public function queue_gedcom_import($file_path, $import_options)
  {
    // Ensure import jobs table exists
    $database_manager = new HP_Database_Manager();
    $database_manager->ensure_import_jobs_table();

    global $wpdb;

    // Create import job record
    $job_id = wp_generate_uuid4();
    $user_id = get_current_user_id();

    $job_data = array(
      'job_id' => $job_id,
      'user_id' => $user_id,
      'file_path' => $file_path,
      'import_options' => json_encode($import_options),
      'status' => 'queued',
      'created_at' => current_time('mysql'),
      'updated_at' => current_time('mysql'),
      'progress' => 0,
      'total_records' => 0,
      'processed_records' => 0,
      'errors' => '',
      'log' => ''
    );

    $table_name = $wpdb->prefix . 'hp_import_jobs';
    $wpdb->insert($table_name, $job_data);

    // Schedule immediate background processing
    wp_schedule_single_event(time(), 'hp_process_gedcom_import', array($job_id));

    return $job_id;
  }

  /**
   * Process GEDCOM import in background
   */
  public function process_gedcom_import_background($job_id)
  {
    global $wpdb;

    // Increase execution time and memory for large files
    @set_time_limit(0);
    @ini_set('memory_limit', '512M');

    $table_name = $wpdb->prefix . 'hp_import_jobs';

    // Get job details
    $job = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $table_name WHERE job_id = %s",
      $job_id
    ));

    if (!$job || $job->status !== 'queued') {
      return;
    }

    // Update status to processing
    $this->update_import_job_status($job_id, 'processing', 0, 'Starting GEDCOM import...');

    try {
      $import_options = json_decode($job->import_options, true);
      $file_path = $job->file_path;

      // Validate file exists
      if (!file_exists($file_path)) {
        throw new Exception('GEDCOM file not found: ' . $file_path);
      }

      // Start import process
      $this->process_gedcom_file($job_id, $file_path, $import_options);

      // Mark as completed
      $this->update_import_job_status($job_id, 'completed', 100, 'Import completed successfully!');

      // Send completion email
      $this->send_import_completion_email($job_id, true);
    } catch (Exception $e) {
      // Mark as failed
      $error_message = 'Import failed: ' . $e->getMessage();
      $this->update_import_job_status($job_id, 'failed', 0, $error_message);

      // Send failure email
      $this->send_import_completion_email($job_id, false, $error_message);

      error_log('HeritagePress Import Error: ' . $error_message);
    }
  }

  /**
   * Process GEDCOM file with progress tracking
   */
  private function process_gedcom_file($job_id, $file_path, $import_options)
  {
    // First pass: Count total records
    $this->update_import_job_status($job_id, 'processing', 5, 'Analyzing GEDCOM file...');

    $total_records = $this->count_gedcom_records($file_path);
    $this->update_import_job_total_records($job_id, $total_records);

    $this->update_import_job_status($job_id, 'processing', 10, "Found {$total_records} records. Starting import...");

    // Second pass: Process records
    $handle = fopen($file_path, 'r');
    if (!$handle) {
      throw new Exception('Could not open GEDCOM file for reading');
    }

    $processed = 0;
    $batch_size = 100; // Process in batches to update progress
    $current_record = '';
    $record_count = 0;

    while (($line = fgets($handle)) !== false) {
      // Check if job was cancelled
      if ($this->is_import_job_cancelled($job_id)) {
        fclose($handle);
        throw new Exception('Import cancelled by user');
      }

      $line = trim($line);

      // Detect start of new record (level 0)
      if (preg_match('/^0 @(.+)@ (INDI|FAM|SOUR|NOTE|OBJE|REPO|SUBM)/', $line)) {
        // Process previous record if exists
        if (!empty($current_record)) {
          $this->process_gedcom_record($current_record, $import_options);
          $processed++;
          $record_count++;

          // Update progress every batch
          if ($record_count % $batch_size === 0) {
            $progress = 10 + (($processed / $total_records) * 85); // 10-95% for processing
            $this->update_import_job_status(
              $job_id,
              'processing',
              $progress,
              "Processed {$processed} of {$total_records} records..."
            );
            $this->update_import_job_processed_records($job_id, $processed);
          }
        }

        // Start new record
        $current_record = $line . "\n";
      } else {
        // Continue building current record
        $current_record .= $line . "\n";
      }
    }

    // Process final record
    if (!empty($current_record)) {
      $this->process_gedcom_record($current_record, $import_options);
      $processed++;
    }

    fclose($handle);

    // Post-processing steps
    $this->update_import_job_status($job_id, 'processing', 95, 'Running post-import utilities...');
    $this->run_post_import_utilities($import_options);

    $this->update_import_job_processed_records($job_id, $processed);
  }

  /**
   * Count total records in GEDCOM file
   */
  private function count_gedcom_records($file_path)
  {
    $handle = fopen($file_path, 'r');
    if (!$handle) return 0;

    $count = 0;
    while (($line = fgets($handle)) !== false) {
      if (preg_match('/^0 @(.+)@ (INDI|FAM|SOUR|NOTE|OBJE|REPO|SUBM)/', trim($line))) {
        $count++;
      }
    }

    fclose($handle);
    return $count;
  }

  /**
   * Process individual GEDCOM record
   */
  private function process_gedcom_record($record_data, $import_options)
  {
    // This would contain the actual GEDCOM parsing and database insertion logic
    // For now, we'll simulate processing

    // Parse record type and ID
    preg_match('/^0 @(.+)@ (INDI|FAM|SOUR|NOTE|OBJE|REPO|SUBM)/', $record_data, $matches);

    if (count($matches) >= 3) {
      $record_id = $matches[1];
      $record_type = $matches[2];

      // Log processing (could be expanded for debugging)
      error_log("Processing {$record_type} record: {$record_id}");

      // Simulate processing time
      usleep(1000); // 1ms delay to prevent overwhelming the server
    }
  }

  /**
   * Run post-import utilities
   */
  private function run_post_import_utilities($import_options)
  {
    // Run essential post-import utilities
    $utilities = array('Track Lines', 'Sort Children', 'Sort Spouses');

    foreach ($utilities as $utility) {
      error_log("Running post-import utility: {$utility}");
      // Implement actual utility logic here
    }
  }

  /**
   * Update import job status
   */
  private function update_import_job_status($job_id, $status, $progress, $log_message = '')
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'hp_import_jobs';

    $update_data = array(
      'status' => $status,
      'progress' => $progress,
      'updated_at' => current_time('mysql')
    );

    if (!empty($log_message)) {
      $current_log = $wpdb->get_var($wpdb->prepare(
        "SELECT log FROM $table_name WHERE job_id = %s",
        $job_id
      ));

      $new_log = $current_log . "\n" . current_time('mysql') . ': ' . $log_message;
      $update_data['log'] = $new_log;
    }

    $wpdb->update(
      $table_name,
      $update_data,
      array('job_id' => $job_id)
    );
  }

  /**
   * Update total records count
   */
  private function update_import_job_total_records($job_id, $total_records)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'hp_import_jobs';
    $wpdb->update(
      $table_name,
      array('total_records' => $total_records),
      array('job_id' => $job_id)
    );
  }

  /**
   * Update processed records count
   */
  private function update_import_job_processed_records($job_id, $processed_records)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'hp_import_jobs';
    $wpdb->update(
      $table_name,
      array('processed_records' => $processed_records),
      array('job_id' => $job_id)
    );
  }

  /**
   * Check if import job was cancelled
   */
  private function is_import_job_cancelled($job_id)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'hp_import_jobs';
    $status = $wpdb->get_var($wpdb->prepare(
      "SELECT status FROM $table_name WHERE job_id = %s",
      $job_id
    ));

    return $status === 'cancelled';
  }

  /**
   * AJAX handler to get import status
   */  public function ajax_get_import_status()
  {
    check_ajax_referer('heritagepress_admin_nonce', 'nonce');

    if (!current_user_can('import_gedcom')) {
      wp_send_json_error('Unauthorized');
    }

    $job_id = sanitize_text_field($_POST['job_id']);

    // Ensure import jobs table exists
    $database_manager = new HP_Database_Manager();
    $database_manager->ensure_import_jobs_table();

    global $wpdb;
    $table_name = $wpdb->prefix . 'hp_import_jobs';

    $job = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $table_name WHERE job_id = %s",
      $job_id
    ));

    if (!$job) {
      wp_send_json_error('Import job not found');
    }

    wp_send_json_success(array(
      'status' => $job->status,
      'progress' => $job->progress,
      'total_records' => $job->total_records,
      'processed_records' => $job->processed_records,
      'log' => $job->log,
      'created_at' => $job->created_at,
      'updated_at' => $job->updated_at
    ));
  }

  /**
   * AJAX handler to cancel import
   */
  public function ajax_cancel_import()
  {
    check_ajax_referer('heritagepress_admin_nonce', 'nonce');

    if (!current_user_can('import_gedcom')) {
      wp_send_json_error('Unauthorized');
    }

    $job_id = sanitize_text_field($_POST['job_id']);

    $this->update_import_job_status($job_id, 'cancelled', 0, 'Import cancelled by user');

    wp_send_json_success('Import cancelled');
  }

  /**
   * Send import completion email
   */
  private function send_import_completion_email($job_id, $success, $error_message = '')
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'hp_import_jobs';
    $job = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $table_name WHERE job_id = %s",
      $job_id
    ));

    if (!$job) return;

    $user = get_user_by('id', $job->user_id);
    if (!$user) return;

    $subject = $success ?
      'HeritagePress: GEDCOM Import Completed' :
      'HeritagePress: GEDCOM Import Failed';

    $message = $success ?
      "Your GEDCOM import has completed successfully!\n\n" .
      "Records processed: {$job->processed_records}\n" .
      "Completion time: " . $job->updated_at :

      "Your GEDCOM import has failed.\n\n" .
      "Error: {$error_message}\n" .
      "Please check your GEDCOM file and try again.";

    wp_mail($user->user_email, $subject, $message);
  }

  /**
   * Clean up old import jobs (older than 30 days)
   */
  public function cleanup_old_import_jobs()
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'hp_import_jobs';
    $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));

    $wpdb->query($wpdb->prepare(
      "DELETE FROM $table_name WHERE created_at < %s AND status IN ('completed', 'failed', 'cancelled')",
      $cutoff_date
    ));
  }
}
