<?php

/**
 * HeritagePress Database Events Tables
 *
 * Handles creation of event-related tables: events, eventtypes, timelineevents
 * Table structures for events and genealogy dates in HeritagePress genealogy system
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Database_Events
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
   * Create all events tables
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
   * Drop all events tables
   */
  public function drop_tables()
  {
    $tables = ['events', 'eventtypes', 'timelineevents'];

    foreach ($tables as $table) {
      $table_name = $this->table_prefix . $table;
      $this->wpdb->query("DROP TABLE IF EXISTS `$table_name`");
    }
  }

  /**
   * Check if all events tables exist
   */
  public function tables_exist()
  {
    $tables = ['events', 'eventtypes', 'timelineevents'];

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
      error_log("HeritagePress Events: Failed to create table $table_full_name: " . $this->wpdb->last_error);
      return false;
    }

    return true;
  }

  /**
   * Get events table structures
   * Exact structures from TNG SQL file, adapted with hp_ prefix
   */
  private function get_table_structures()
  {
    return [
      'events' => "
                `eventID` int(11) NOT NULL AUTO_INCREMENT,
                `gedcom` varchar(20) NOT NULL,
                `persfamID` varchar(22) NOT NULL,
                `eventtypeID` int(11) NOT NULL,
                `eventdate` varchar(50) NOT NULL,
                `eventdatetr` date NOT NULL,
                `eventplace` text NOT NULL,
                `age` varchar(12) NOT NULL,
                `agency` varchar(120) NOT NULL,
                `cause` varchar(90) NOT NULL,
                `addressID` varchar(10) NOT NULL,
                `parenttag` varchar(10) NOT NULL,
                `info` text NOT NULL,
                PRIMARY KEY (`eventID`),
                KEY `persfamID` (`gedcom`,`persfamID`),
                KEY `eventplace` (`gedcom`,`eventplace`(20))
            ",

      'eventtypes' => "
                `eventtypeID` int(11) NOT NULL AUTO_INCREMENT,
                `tag` varchar(10) NOT NULL,
                `description` varchar(90) NOT NULL,
                `display` text NOT NULL,
                `keep` tinyint(4) NOT NULL,
                `collapse` tinyint(4) NOT NULL,
                `ordernum` smallint(6) NOT NULL,
                `ldsevent` tinyint(4) NOT NULL,
                `type` char(1) NOT NULL,
                PRIMARY KEY (`eventtypeID`),
                UNIQUE KEY `typetagdesc` (`type`,`tag`,`description`),
                KEY `ordernum` (`ordernum`)
            ",

      'timelineevents' => "
                `tleventID` int(11) NOT NULL AUTO_INCREMENT,
                `evday` tinyint(4) NOT NULL,
                `evmonth` tinyint(4) NOT NULL,
                `evyear` varchar(10) NOT NULL,
                `endday` tinyint(4) NOT NULL,
                `endmonth` tinyint(4) NOT NULL,
                `endyear` varchar(10) NOT NULL,
                `evtitle` varchar(128) NOT NULL,
                `evdetail` text NOT NULL,
                PRIMARY KEY (`tleventID`),
                KEY `evyear` (`evyear`,`evmonth`,`evday`,`evdetail`(100)),
                KEY `evdetail` (`evdetail`(100))
            "
    ];
  }
}
