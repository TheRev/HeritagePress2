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
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_init', array($this, 'handle_export_request'));
  }

  /**
   * Add admin menu items
   */
  public function add_admin_menu()
  {
    // Main menu: HeritagePress Dashboard
    add_menu_page(
      __('HeritagePress Dashboard', 'heritagepress'),
      __('HeritagePress', 'heritagepress'),
      'manage_options',
      'heritagepress-dashboard',
      array($this, 'render_dashboard_page'),
      'dashicons-admin-home',
      2
    );

    // Submenus
    add_submenu_page(
      'heritagepress-dashboard',
      __('Trees', 'heritagepress'),
      __('Trees', 'heritagepress'),
      'manage_options',
      'heritagepress-trees',
      array($this, 'render_trees_page')
    );
    add_submenu_page(
      'heritagepress-dashboard',
      __('People', 'heritagepress'),
      __('People', 'heritagepress'),
      'manage_options',
      'heritagepress-people',
      array($this, 'render_people_page')
    );
    add_submenu_page(
      'heritagepress-dashboard',
      __('Families', 'heritagepress'),
      __('Families', 'heritagepress'),
      'manage_options',
      'heritagepress-families',
      array($this, 'render_families_page')
    );
    add_submenu_page(
      'heritagepress-dashboard',
      __('Media', 'heritagepress'),
      __('Media', 'heritagepress'),
      'manage_options',
      'heritagepress-media',
      array($this, 'render_media_page')
    );
    add_submenu_page(
      'heritagepress-dashboard',
      __('Sources', 'heritagepress'),
      __('Sources', 'heritagepress'),
      'manage_options',
      'heritagepress-sources',
      array($this, 'render_sources_page')
    );
    add_submenu_page(
      'heritagepress-dashboard',
      __('Repositories', 'heritagepress'),
      __('Repositories', 'heritagepress'),
      'manage_options',
      'heritagepress-repositories',
      array($this, 'render_repositories_page')
    );
    add_submenu_page(
      'heritagepress-dashboard',
      __('Import / Export', 'heritagepress'),
      __('Import / Export', 'heritagepress'),
      'manage_options',
      'heritagepress-import-export',
      array($this, 'render_import_export_page')
    );
    add_submenu_page(
      'heritagepress-dashboard',
      __('Review Data', 'heritagepress'),
      __('Review Data', 'heritagepress'),
      'manage_options',
      'heritagepress-review',
      array($this, 'render_review_page')
    );
    add_submenu_page(
      'heritagepress-dashboard',
      __('Users', 'heritagepress'),
      __('Users', 'heritagepress'),
      'manage_options',
      'heritagepress-users',
      array($this, 'render_users_page')
    );
    add_submenu_page(
      'heritagepress-dashboard',
      __('Global Configuration', 'heritagepress'),
      __('Global Configuration', 'heritagepress'),
      'manage_options',
      'heritagepress-config',
      array($this, 'render_config_page')
    );
  }

  // Renderers for each submenu (to be implemented or linked to controllers)
  public function render_trees_page()
  {
    if (class_exists('HP_Trees_Controller')) {
      (new HP_Trees_Controller())->display_page();
    } else {
      echo '<div class="notice notice-error"><p>Trees controller not found.</p></div>';
    }
  }
  public function render_people_page()
  {
    if (class_exists('HP_People_Controller')) {
      (new HP_People_Controller())->display_page();
    } else {
      echo '<div class="notice notice-error"><p>People controller not found.</p></div>';
    }
  }
  public function render_families_page()
  {
    if (class_exists('HP_Families_Controller')) {
      (new HP_Families_Controller())->display_page();
    } else {
      echo '<h2>Families</h2><p>Families management coming soon.</p>';
    }
  }
  public function render_media_page()
  {
    if (class_exists('HP_Media_Controller')) {
      (new HP_Media_Controller())->display_page();
    } else {
      echo '<h2>Media</h2><p>Media management coming soon.</p>';
    }
  }
  public function render_sources_page()
  {
    if (class_exists('HP_Source_Controller')) {
      (new HP_Source_Controller())->display_page();
    } else {
      echo '<h2>Sources</h2><p>Sources management coming soon.</p>';
    }
  }
  public function render_repositories_page()
  {
    if (class_exists('HP_Repository_Controller')) {
      (new HP_Repository_Controller())->display_page();
    } else {
      echo '<h2>Repositories</h2><p>Repositories management coming soon.</p>';
    }
  }
  public function render_dashboard_page()
  {
    echo '<div class="wrap">';
    echo '<style>
      .hp-dashboard-header {
        text-align: center;
        margin-bottom: 30px;
        padding: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      }
      .hp-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
      }
      .hp-stat-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
      }
      .hp-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
      }
      .hp-stat-number {
        font-size: 2.5em;
        font-weight: bold;
        color: #667eea;
        margin: 0;
      }
      .hp-stat-label {
        color: #666;
        margin: 5px 0 0 0;
        font-size: 1.1em;
      }
      .hp-menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-top: 20px;
      }
      .hp-menu-section {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      }
      .hp-menu-section h3 {
        color: #667eea;
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
      }
      .hp-menu-list {
        list-style: none;
        padding: 0;
        margin: 0;
      }
      .hp-menu-list li {
        margin: 10px 0;
      }
      .hp-menu-list a {
        display: block;
        padding: 10px 15px;
        text-decoration: none;
        color: #333;
        background: #f9f9f9;
        border-radius: 5px;
        transition: all 0.2s ease;
      }
      .hp-menu-list a:hover {
        background: #667eea;
        color: white;
        transform: translateX(5px);
      }
    </style>';

    echo '<div class="hp-dashboard-header">';
    echo '<h1 style="margin: 0; font-size: 2.5em;">HeritagePress Dashboard</h1>';
    echo '<p style="margin: 10px 0 0 0; font-size: 1.2em; opacity: 0.9;">Your complete genealogy management solution</p>';
    echo '</div>';

    // Statistics Cards
    echo '<div class="hp-stats-grid">';

    // Fetch real statistics from the database
    $stats = $this->get_dashboard_stats();
    echo '<div class="hp-stat-card">';
    echo '<h2 class="hp-stat-number">' . number_format($stats['trees']) . '</h2>';
    echo '<p class="hp-stat-label">Family Trees</p>';
    echo '</div>';
    echo '<div class="hp-stat-card">';
    echo '<h2 class="hp-stat-number">' . number_format($stats['people']) . '</h2>';
    echo '<p class="hp-stat-label">People Records</p>';
    echo '</div>';
    echo '<div class="hp-stat-card">';
    echo '<h2 class="hp-stat-number">' . number_format($stats['families']) . '</h2>';
    echo '<p class="hp-stat-label">Family Records</p>';
    echo '</div>';
    echo '<div class="hp-stat-card">';
    echo '<h2 class="hp-stat-number">' . number_format($stats['media']) . '</h2>';
    echo '<p class="hp-stat-label">Media Files</p>';
    echo '</div>';
    echo '<div class="hp-stat-card">';
    echo '<h2 class="hp-stat-number">' . number_format($stats['sources']) . '</h2>';
    echo '<p class="hp-stat-label">Source Citations</p>';
    echo '</div>';
    echo '<div class="hp-stat-card">';
    echo '<h2 class="hp-stat-number">' . number_format($stats['repositories']) . '</h2>';
    echo '<p class="hp-stat-label">Repositories</p>';
    echo '</div>';
    echo '</div>';

    // Menu Sections
    echo '<div class="hp-menu-grid">';

    // Core Management
    echo '<div class="hp-menu-section">';
    echo '<h3>📊 Core Management</h3>';
    echo '<ul class="hp-menu-list">';
    echo '<li><a href="admin.php?page=heritagepress-trees">🌳 Manage Trees</a></li>';
    echo '<li><a href="admin.php?page=heritagepress-people">👥 Manage People</a></li>';
    echo '<li><a href="admin.php?page=heritagepress-families">👨‍👩‍👧‍👦 Manage Families</a></li>';
    echo '</ul>';
    echo '</div>';

    // Media & Documentation
    echo '<div class="hp-menu-section">';
    echo '<h3>📚 Media & Documentation</h3>';
    echo '<ul class="hp-menu-list">';
    echo '<li><a href="admin.php?page=heritagepress-media">🖼️ Manage Media</a></li>';
    echo '<li><a href="admin.php?page=heritagepress-sources">📄 Manage Sources</a></li>';
    echo '<li><a href="admin.php?page=heritagepress-repositories">🏛️ Manage Repositories</a></li>';
    echo '</ul>';
    echo '</div>';

    // Data Management
    echo '<div class="hp-menu-section">';
    echo '<h3>🔄 Data Management</h3>';
    echo '<ul class="hp-menu-list">';
    echo '<li><a href="admin.php?page=heritagepress-import-export">📥📤 Import / Export</a></li>';
    echo '<li><a href="admin.php?page=heritagepress-validation">✅ Data Validation</a></li>';
    echo '<li><a href="admin.php?page=heritagepress-backup">💾 Backup & Restore</a></li>';
    echo '</ul>';
    echo '</div>';

    // Tools & Utilities
    echo '<div class="hp-menu-section">';
    echo '<h3>🛠️ Tools & Utilities</h3>';
    echo '<ul class="hp-menu-list">';
    echo '<li><a href="admin.php?page=heritagepress-reports">📊 Reports</a></li>';
    echo '<li><a href="admin.php?page=heritagepress-id-checker">🔍 ID Checker</a></li>';
    echo '<li><a href="admin.php?page=heritagepress-maintenance">⚙️ Maintenance</a></li>';
    echo '</ul>';
    echo '</div>';

    // Settings & Configuration
    echo '<div class="hp-menu-section">';
    echo '<h3>⚙️ Settings & Configuration</h3>';
    echo '<ul class="hp-menu-list">';
    echo '<li><a href="admin.php?page=heritagepress-languages">🌍 Languages</a></li>';
    echo '<li><a href="admin.php?page=heritagepress-custom-text">📝 Custom Text</a></li>';
    echo '<li><a href="admin.php?page=heritagepress-settings">⚙️ Plugin Settings</a></li>';
    echo '</ul>';
    echo '</div>';

    // Recent Activity
    echo '<div class="hp-menu-section">';
    echo '<h3>📈 Recent Activity</h3>';
    echo '<ul class="hp-menu-list" style="font-size: 0.9em;">';
    echo '<li style="padding: 5px 0; border-bottom: 1px solid #f0f0f0;">✅ GEDCOM import completed (2 hours ago)</li>';
    echo '<li style="padding: 5px 0; border-bottom: 1px solid #f0f0f0;">👤 John Smith added to Miller family tree (4 hours ago)</li>';
    echo '<li style="padding: 5px 0; border-bottom: 1px solid #f0f0f0;">📸 3 photos uploaded to media library (1 day ago)</li>';
    echo '<li style="padding: 5px 0;">🔄 Database backup completed successfully (2 days ago)</li>';
    echo '</ul>';
    echo '</div>';

    echo '</div>'; // End menu grid
    echo '</div>'; // End wrap
  }
  public function render_import_export_page()
  {
    // Use the tabbed UI template for import/export/post-import
    include_once HERITAGEPRESS_PLUGIN_DIR . 'includes/template/admin/import/import-export-split.php';
  }
  public function render_review_page()
  {
    include_once dirname(__FILE__) . '/views/review-management.php';
  }
  public function render_users_page()
  {
    if (class_exists('HP_User_Controller')) {
      (new HP_User_Controller())->display_page();
    } else {
      echo '<div class="notice notice-error"><p>Users controller not found.</p></div>';
    }
  }
  public function render_config_page()
  {
    if (class_exists('HeritagePress_Config_Controller')) {
      (new HeritagePress_Config_Controller())->display_page();
    } else {
      echo '<div class="notice notice-error"><p>Config controller not found.</p></div>';
    }
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
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/controllers/class-hp-export-controller.php';    // Load admin controllers
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-admin-trees-controller.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-branch-controller.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-id-checker-controller.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-id-checker-endpoint.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-entity-controller.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-media-controller.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-source-controller.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-repository-controller.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-user-controller.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/controllers/class-hp-config-controller.php';
    require_once dirname(__FILE__) . '/controllers/class-hp-families-controller.php';

    // Load handlers
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/handlers/class-hp-ajax-handler.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/handlers/class-hp-form-handler.php';
    require_once HERITAGEPRESS_PLUGIN_DIR . 'admin/handlers/class-hp-add-tree-handler.php';
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

  /**
   * Handle export request
   */
  public function handle_export_request()
  {
    $export_controller = new HP_Export_Controller();
    $export_controller->handle_export_request();
  }

  /**
   * Get real statistics from the database
   */
  private function get_dashboard_stats()
  {
    global $wpdb;

    $stats = array(
      'trees' => 0,
      'people' => 0,
      'families' => 0,
      'media' => 0,
      'sources' => 0,
      'repositories' => 0
    );

    try {
      // Get table names with prefix
      $trees_table = $wpdb->prefix . 'hp_trees';
      $people_table = $wpdb->prefix . 'hp_people';
      $families_table = $wpdb->prefix . 'hp_families';
      $media_table = $wpdb->prefix . 'hp_media';
      $sources_table = $wpdb->prefix . 'hp_sources';
      $repositories_table = $wpdb->prefix . 'hp_repositories';

      // Check if tables exist and get counts
      $tables = array(
        'trees' => $trees_table,
        'people' => $people_table,
        'families' => $families_table,
        'media' => $media_table,
        'sources' => $sources_table,
        'repositories' => $repositories_table
      );

      foreach ($tables as $key => $table_name) {
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
        if ($table_exists) {
          $count = $wpdb->get_var("SELECT COUNT(*) FROM `$table_name`");
          $stats[$key] = intval($count);
        }
      }
    } catch (Exception $e) {
      // Log error but don't break the dashboard
      error_log('HeritagePress Dashboard Stats Error: ' . $e->getMessage());
    }

    return $stats;
  }
}
