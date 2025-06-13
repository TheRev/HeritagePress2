<?php

/**
 * HeritagePress Database Management
 *
 * Coordinates database operations across specialized database classes
 * Delegates table creation and management to category-specific classes
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Database
{

  private $wpdb;
  private $charset_collate;
  private $core_db;
  private $sources_db;
  private $media_db;
  private $geography_db;
  private $system_db;

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->charset_collate = $wpdb->get_charset_collate();

    // Initialize specialized database managers
    $this->init_database_managers();
  }

  /**
   * Initialize all specialized database manager classes
   */
  private function init_database_managers()
  {
    require_once plugin_dir_path(__FILE__) . 'class-hp-database-core.php';
    require_once plugin_dir_path(__FILE__) . 'class-hp-database-sources.php';
    require_once plugin_dir_path(__FILE__) . 'class-hp-database-media.php';
    require_once plugin_dir_path(__FILE__) . 'class-hp-database-geography.php';
    require_once plugin_dir_path(__FILE__) . 'class-hp-database-system.php';

    $this->core_db = new HP_Database_Core();
    $this->sources_db = new HP_Database_Sources();
    $this->media_db = new HP_Database_Media();
    $this->geography_db = new HP_Database_Geography();
    $this->system_db = new HP_Database_System();
  }

  /**
   * Get table name with proper prefix
   */
  public function get_table_name($table)
  {
    return $this->wpdb->prefix . 'hp_' . $table;
  }

  /**
   * Create all genealogy tables using specialized database managers
   */
  public function create_tables()
  {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Create tables by category using specialized managers
    $this->core_db->create_tables();
    $this->sources_db->create_tables();
    $this->media_db->create_tables();
    $this->geography_db->create_tables();
    $this->system_db->create_tables();

    // Update database version
    update_option('heritagepress_db_version', HERITAGEPRESS_DB_VERSION);
  }

  /**
   * Drop all genealogy tables using specialized database managers
   */
  public function drop_tables()
  {
    $this->core_db->drop_tables();
    $this->sources_db->drop_tables();
    $this->media_db->drop_tables();
    $this->geography_db->drop_tables();
    $this->system_db->drop_tables();
  }

  /**
   * Get table statistics from all specialized managers
   */
  public function get_table_stats()
  {
    $stats = array();

    // Get stats from each specialized manager
    $core_stats = $this->core_db->get_table_stats();
    $sources_stats = $this->sources_db->get_table_stats();
    $media_stats = $this->media_db->get_table_stats();
    $geography_stats = $this->geography_db->get_table_stats();
    $system_stats = $this->system_db->get_table_stats();

    // Merge all stats
    $stats = array_merge($stats, $core_stats);
    $stats = array_merge($stats, $sources_stats);
    $stats = array_merge($stats, $media_stats);
    $stats = array_merge($stats, $geography_stats);
    $stats = array_merge($stats, $system_stats);

    return $stats;
  }

  /**
   * Get count of records in each table
   */
  public function get_table_counts($tree_id = 'main')
  {
    $counts = array();

    $tables = array('persons', 'families', 'events', 'sources', 'media', 'places', 'notes');

    foreach ($tables as $table) {
      $table_name = $this->get_table_name($table);
      $count = $this->wpdb->get_var(
        $this->wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE tree_id = %s",
          $tree_id
        )
      );
      $counts[$table] = (int)$count;
    }

    return $counts;
  }

  /**
   * Check if tables exist
   */
  public function tables_exist()
  {
    $table_name = $this->get_table_name('persons');
    $result = $this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    return ($result === $table_name);
  }

  /**
   * Get database version
   */
  public function get_db_version()
  {
    return get_option('heritagepress_db_version', '1.0.0');
  }

  /**
   * Check if database needs updating
   */
  public function needs_update()
  {
    return version_compare($this->get_db_version(), HERITAGEPRESS_DB_VERSION, '<');
  }

  /**
   * Update database structure
   */
  public function update_database()
  {
    if ($this->needs_update()) {
      $this->create_tables(); // This will update existing tables as needed
      return true;
    }
    return false;
  }

  /**
   * Get specialized database managers
   */
  public function get_core_db()
  {
    return $this->core_db;
  }

  public function get_sources_db()
  {
    return $this->sources_db;
  }

  public function get_media_db()
  {
    return $this->media_db;
  }

  public function get_geography_db()
  {
    return $this->geography_db;
  }

  public function get_system_db()
  {
    return $this->system_db;
  }
}
