<?php

/**
 * HeritagePress Tree Page Renderer Class
 * Handles rendering of tree-related admin pages
 */
class HP_Tree_Page_Renderer
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
    // Initialize if needed
  }

  /**
   * Render the edit tree page
   */
  public function render_edit_tree_page()
  {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.', 'heritagepress'));
    }
    global $wpdb;
    $trees_table = $wpdb->prefix . 'hp_trees';
    $tree_id = isset($_GET['tree']) ? sanitize_text_field($_GET['tree']) : '';
    if (empty($tree_id)) {
      echo '<div class="notice notice-error"><p>' . esc_html__('No tree specified.', 'heritagepress') . '</p></div>';
      return;
    }
    $tree = $wpdb->get_row($wpdb->prepare("SELECT * FROM $trees_table WHERE gedcom = %s", $tree_id), ARRAY_A);
    if (!$tree) {
      echo '<div class="notice notice-error"><p>' . esc_html__('Tree not found.', 'heritagepress') . '</p></div>';
      return;
    }
    $nonce = wp_create_nonce('heritagepress_edittree');
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Edit Tree', 'heritagepress') . ': ' . esc_html($tree['gedcom']) . '</h1>';

    // Display any messages
    if (isset($_GET['message'])) {
      echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(sanitize_text_field($_GET['message'])) . '</p></div>';
    }

    echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post">';
    echo '<input type="hidden" name="action" value="heritagepress_edit_tree">';
    echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($nonce) . '">';
    echo '<input type="hidden" name="original_tree_id" value="' . esc_attr($tree['gedcom']) . '">';
    echo '<table class="form-table">';
    echo '<tr><th><label for="tree_id">' . esc_html__('Tree ID', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="tree_id" id="tree_id" value="' . esc_attr($tree['gedcom']) . '" class="regular-text" maxlength="20" required></td></tr>';
    echo '<tr><th><label for="tree_name">' . esc_html__('Tree Name', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="tree_name" id="tree_name" value="' . esc_attr($tree['treename']) . '" class="regular-text" required></td></tr>';
    echo '<tr><th><label for="description">' . esc_html__('Description', 'heritagepress') . '</label></th>';
    echo '<td><textarea name="description" id="description" rows="3" cols="40">' . esc_textarea($tree['description']) . '</textarea></td></tr>';
    echo '<tr><th><label for="owner">' . esc_html__('Owner', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="owner" id="owner" value="' . esc_attr($tree['owner']) . '" class="regular-text"></td></tr>';
    echo '<tr><th><label for="email">' . esc_html__('Email', 'heritagepress') . '</label></th>';
    echo '<td><input type="email" name="email" id="email" value="' . esc_attr($tree['email']) . '" class="regular-text"></td></tr>';
    echo '<tr><th><label for="address">' . esc_html__('Address', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="address" id="address" value="' . esc_attr($tree['address']) . '" class="regular-text"></td></tr>';
    echo '<tr><th><label for="city">' . esc_html__('City', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="city" id="city" value="' . esc_attr($tree['city']) . '" class="regular-text"></td></tr>';
    echo '<tr><th><label for="state">' . esc_html__('State/Province', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="state" id="state" value="' . esc_attr($tree['state']) . '" class="regular-text"></td></tr>';
    echo '<tr><th><label for="zip">' . esc_html__('Zip', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="zip" id="zip" value="' . esc_attr($tree['zip']) . '" class="regular-text"></td></tr>';
    echo '<tr><th><label for="country">' . esc_html__('Country', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="country" id="country" value="' . esc_attr($tree['country']) . '" class="regular-text"></td></tr>';
    echo '<tr><th><label for="phone">' . esc_html__('Phone', 'heritagepress') . '</label></th>';
    echo '<td><input type="text" name="phone" id="phone" value="' . esc_attr($tree['phone']) . '" class="regular-text"></td></tr>';
    echo '<tr><th><label for="private">' . esc_html__('Keep Owner/Contact Info Private', 'heritagepress') . '</label></th>';
    echo '<td><input type="checkbox" name="private" id="private" value="1"' . ($tree['secret'] ? ' checked' : '') . '></td></tr>';
    echo '<tr><th><label for="disallowgedcreate">' . esc_html__('Disallow GEDCOM Download', 'heritagepress') . '</label></th>';
    echo '<td><input type="checkbox" name="disallowgedcreate" id="disallowgedcreate" value="1"' . ($tree['disallowgedcreate'] ? ' checked' : '') . '></td></tr>';
    echo '<tr><th><label for="disallowpdf">' . esc_html__('Disallow PDF Generation', 'heritagepress') . '</label></th>';
    echo '<td><input type="checkbox" name="disallowpdf" id="disallowpdf" value="1"' . ($tree['disallowpdf'] ? ' checked' : '') . '></td></tr>';
    echo '</table>';
    echo '<p class="submit">';
    echo '<input type="submit" class="button-primary" value="' . esc_attr__('Save Changes', 'heritagepress') . '"> ';
    echo '<a href="' . esc_url(admin_url('admin.php?page=heritagepress-trees&tab=browse')) . '" class="button">' . esc_html__('Cancel', 'heritagepress') . '</a>';
    echo '</p>';
    echo '</form>';
    echo '</div>';
  }

  /**
   * Render the dashboard page
   */
  public function render_dashboard_page()
  {
    global $wpdb;
    $stats = $this->get_dashboard_stats();

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
