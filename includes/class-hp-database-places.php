<?php

/**
 * HeritagePress Database Places Tables
 *
 * Handles creation of place-related tables: places, cemeteries, addresses, countries, states
 * Table structures for places and geography in HeritagePress genealogy system
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Database_Places
{
  private $wpdb;
  private $table_prefix;
  private $charset_collate;

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->table_prefix = $wpdb->prefix . 'hp_';
    $this->charset_collate = $wpdb->get_charset_collate();
  }

  /**
   * Create all places tables
   */
  public function create_tables()
  {
    $tables = $this->get_table_structures();
    $success_count = 0;
    $total_count = count($tables);

    foreach ($tables as $table_name => $structure) {
      if ($this->create_table($table_name, $structure)) {
        $success_count++;
      }
    }

    return $success_count === $total_count;
  }

  /**
   * Drop all places tables
   */
  public function drop_tables()
  {
    $tables = ['places', 'cemeteries', 'addresses', 'countries', 'states'];

    foreach ($tables as $table) {
      $table_name = $this->table_prefix . $table;
      $this->wpdb->query("DROP TABLE IF EXISTS `$table_name`");
    }
  }

  /**
   * Check if all places tables exist
   */
  public function tables_exist()
  {
    $tables = ['places', 'cemeteries', 'addresses', 'countries', 'states'];

    foreach ($tables as $table) {
      $table_name = $this->table_prefix . $table;
      if ($this->wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        return false;
      }
    }

    return true;
  }

  /**
   * Create a single table
   */
  private function create_table($table_name, $structure)
  {
    $table_full_name = $this->table_prefix . $table_name;

    // Drop existing table
    $this->wpdb->query("DROP TABLE IF EXISTS `$table_full_name`");

    // Create new table
    $sql = "CREATE TABLE `$table_full_name` ($structure) ENGINE=InnoDB {$this->charset_collate}";

    $result = $this->wpdb->query($sql);

    if ($result === false) {
      error_log("HeritagePress Places: Failed to create table $table_full_name: " . $this->wpdb->last_error);
      return false;
    }

    return true;
  }

  /**
   * Get places table structures
   * Exact structures from TNG SQL file, adapted with hp_ prefix
   */
  private function get_table_structures()
  {
    return [
      'places' => "
                `ID` int(11) NOT NULL AUTO_INCREMENT,
                `gedcom` varchar(20) NOT NULL,
                `place` varchar(248) NOT NULL,
                `longitude` varchar(22) DEFAULT NULL,
                `latitude` varchar(22) DEFAULT NULL,
                `zoom` tinyint(4) DEFAULT NULL,
                `placelevel` tinyint(4) DEFAULT NULL,
                `temple` tinyint(4) NOT NULL,
                `geoignore` tinyint(4) NOT NULL,
                `notes` text,
                `changedate` datetime NOT NULL,
                `changedby` varchar(100) NOT NULL,
                PRIMARY KEY (`ID`),
                UNIQUE KEY `place` (`gedcom`,`place`),
                KEY `temple` (`temple`,`gedcom`,`place`)
            ",

      'cemeteries' => "
                `cemeteryID` int(11) NOT NULL AUTO_INCREMENT,
                `cemname` varchar(64) NOT NULL,
                `maplink` varchar(255) NOT NULL,
                `city` varchar(64) DEFAULT NULL,
                `county` varchar(64) DEFAULT NULL,
                `state` varchar(64) DEFAULT NULL,
                `country` varchar(64) DEFAULT NULL,
                `longitude` varchar(22) DEFAULT NULL,
                `latitude` varchar(22) DEFAULT NULL,
                `zoom` tinyint(4) DEFAULT NULL,
                `notes` text,
                `place` varchar(248) NOT NULL,
                PRIMARY KEY (`cemeteryID`),
                KEY `cemname` (`cemname`),
                KEY `place` (`place`)
            ",

      'addresses' => "
                `addressID` int(11) NOT NULL AUTO_INCREMENT,
                `address1` varchar(64) NOT NULL,
                `address2` varchar(64) NOT NULL,
                `city` varchar(64) NOT NULL,
                `state` varchar(64) NOT NULL,
                `zip` varchar(10) NOT NULL,
                `country` varchar(64) NOT NULL,
                `www` varchar(100) NOT NULL,
                `email` varchar(100) NOT NULL,
                `phone` varchar(30) NOT NULL,
                `gedcom` varchar(20) NOT NULL,
                PRIMARY KEY (`addressID`),
                KEY `address` (`gedcom`,`country`,`state`,`city`)
            ",

      'countries' => "
                `country` varchar(64) NOT NULL,
                PRIMARY KEY (`country`)
            ",

      'states' => "
                `state` varchar(64) NOT NULL,
                PRIMARY KEY (`state`)
            "
    ];
  }
}
