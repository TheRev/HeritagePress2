<?php

/**
 * HeritagePress Database Utility Tables
 *
 * Handles creation of utility tables: saveimport, temp_events
 * Table structures extracted from TNG SQL file and adapted for HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Database_Utility
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
   * Create all utility tables
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
   * Drop all utility tables
   */
  public function drop_tables()
  {
    $tables = ['saveimport', 'temp_events'];

    foreach ($tables as $table) {
      $table_name = $this->table_prefix . $table;
      $this->wpdb->query("DROP TABLE IF EXISTS `$table_name`");
    }
  }

  /**
   * Check if all utility tables exist
   */
  public function tables_exist()
  {
    $tables = ['saveimport', 'temp_events'];

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
      error_log("HeritagePress Utility: Failed to create table $table_full_name: " . $this->wpdb->last_error);
      return false;
    }

    return true;
  }

  /**
   * Get utility table structures
   * Exact structures from TNG SQL file, adapted with hp_ prefix
   */
  private function get_table_structures()
  {
    return [
      'saveimport' => "
                `ID` int(11) NOT NULL AUTO_INCREMENT,
                `filename` varchar(255) DEFAULT NULL,
                `icount` int(11) DEFAULT NULL,
                `ioffset` int(11) DEFAULT NULL,
                `fcount` int(11) DEFAULT NULL,
                `foffset` int(11) DEFAULT NULL,
                `scount` int(11) DEFAULT NULL,
                `soffset` int(11) DEFAULT NULL,
                `fileoffset` int(11) DEFAULT NULL,
                `barwidth` int(11) NOT NULL,
                `delvar` varchar(10) DEFAULT NULL,
                `gedcom` varchar(20) DEFAULT NULL,
                `branch` varchar(20) DEFAULT NULL,
                `ncount` int(11) DEFAULT NULL,
                `noffset` int(11) DEFAULT NULL,
                `rcount` int(11) DEFAULT NULL,
                `roffset` int(11) DEFAULT NULL,
                `mcount` int(11) DEFAULT NULL,
                `pcount` int(11) DEFAULT NULL,
                `ucaselast` tinyint(4) DEFAULT NULL,
                `norecalc` tinyint(4) DEFAULT NULL,
                `media` tinyint(4) DEFAULT NULL,
                `neweronly` tinyint(4) DEFAULT NULL,
                `allevents` tinyint(4) DEFAULT NULL,
                `lasttype` tinyint(4) DEFAULT NULL,
                `lastid` varchar(255) DEFAULT NULL,
                PRIMARY KEY (`ID`)
            ",

      'temp_events' => "
                `tempID` int(11) NOT NULL AUTO_INCREMENT,
                `type` char(1) NOT NULL,
                `gedcom` varchar(20) NOT NULL,
                `personID` varchar(22) NOT NULL,
                `familyID` varchar(22) NOT NULL,
                `eventID` varchar(10) NOT NULL,
                `eventdate` varchar(50) NOT NULL,
                `eventplace` text NOT NULL,
                `info` text NOT NULL,
                `note` text NOT NULL,
                `user` varchar(20) NOT NULL,
                `postdate` datetime NOT NULL,
                PRIMARY KEY (`tempID`),
                KEY `gedtype` (`gedcom`,`type`),
                KEY `user` (`user`)
            "
    ];
  }
}
