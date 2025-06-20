<?php

/**
 * Entity Tables Migration
 * Creates states and countries tables for geographic entity management
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Migration_Add_Entity_Tables
{
  /**
   * Run the migration
   */
  public function run()
  {
    global $wpdb;

    $states_table = $wpdb->prefix . 'hp_states';
    $countries_table = $wpdb->prefix . 'hp_countries';

    // Get charset
    $charset_collate = $wpdb->get_charset_collate();

    // Create states table
    $states_sql = "CREATE TABLE $states_table (
      id int(11) NOT NULL AUTO_INCREMENT,
      state_name varchar(100) NOT NULL,
      created_at datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY state_name (state_name),
      KEY idx_state_name (state_name)
    ) $charset_collate;";

    // Create countries table
    $countries_sql = "CREATE TABLE $countries_table (
      id int(11) NOT NULL AUTO_INCREMENT,
      country_name varchar(100) NOT NULL,
      created_at datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY country_name (country_name),
      KEY idx_country_name (country_name)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $result1 = dbDelta($states_sql);
    $result2 = dbDelta($countries_sql);

    // Check if tables were created successfully
    $states_exists = $wpdb->get_var("SHOW TABLES LIKE '$states_table'") === $states_table;
    $countries_exists = $wpdb->get_var("SHOW TABLES LIKE '$countries_table'") === $countries_table;

    if ($states_exists && $countries_exists) {
      return array(
        'success' => true,
        'message' => 'Entity tables created successfully.',
        'tables' => array($states_table, $countries_table)
      );
    }

    return array(
      'success' => false,
      'message' => 'Failed to create entity tables.',
      'details' => array(
        'states_table' => $states_exists,
        'countries_table' => $countries_exists
      )
    );
  }

  /**
   * Rollback the migration
   */
  public function rollback()
  {
    global $wpdb;

    $states_table = $wpdb->prefix . 'hp_states';
    $countries_table = $wpdb->prefix . 'hp_countries';

    $result1 = $wpdb->query("DROP TABLE IF EXISTS $states_table");
    $result2 = $wpdb->query("DROP TABLE IF EXISTS $countries_table");

    if ($result1 !== false && $result2 !== false) {
      return array(
        'success' => true,
        'message' => 'Entity tables removed successfully.'
      );
    }

    return array(
      'success' => false,
      'message' => 'Failed to remove entity tables.'
    );
  }

  /**
   * Check if migration is needed
   */
  public function is_needed()
  {
    global $wpdb;

    $states_table = $wpdb->prefix . 'hp_states';
    $countries_table = $wpdb->prefix . 'hp_countries';

    $states_exists = $wpdb->get_var("SHOW TABLES LIKE '$states_table'") === $states_table;
    $countries_exists = $wpdb->get_var("SHOW TABLES LIKE '$countries_table'") === $countries_table;

    return !($states_exists && $countries_exists);
  }

  /**
   * Get migration description
   */
  public function get_description()
  {
    return 'Creates database tables for geographic entity management (states and countries)';
  }
}
