<?php

/**
 * HeritagePress Database Research Tables
 *
 * Handles creation of research-related tables: sources, citations, repositories, mostwanted, xnotes, notelinks, associations
 * Table structures extracted from TNG SQL file and adapted for HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Database_Research
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
   * Create all research tables
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
   * Drop all research tables
   */
  public function drop_tables()
  {
    $tables = ['sources', 'citations', 'repositories', 'mostwanted', 'xnotes', 'notelinks', 'associations'];

    foreach ($tables as $table) {
      $table_name = $this->table_prefix . $table;
      $this->wpdb->query("DROP TABLE IF EXISTS `$table_name`");
    }
  }

  /**
   * Check if all research tables exist
   */
  public function tables_exist()
  {
    $tables = ['sources', 'citations', 'repositories', 'mostwanted', 'xnotes', 'notelinks', 'associations'];

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
      error_log("HeritagePress Research: Failed to create table $table_full_name: " . $this->wpdb->last_error);
      return false;
    }

    return true;
  }

  /**
   * Get research table structures
   * Exact structures from TNG SQL file, adapted with hp_ prefix
   */
  private function get_table_structures()
  {
    return [
      'sources' => "
                `ID` int(11) NOT NULL AUTO_INCREMENT,
                `gedcom` varchar(20) NOT NULL,
                `sourceID` varchar(22) NOT NULL,
                `callnum` varchar(120) NOT NULL,
                `type` varchar(20) DEFAULT NULL,
                `title` text NOT NULL,
                `author` text NOT NULL,
                `publisher` text NOT NULL,
                `other` text NOT NULL,
                `shorttitle` text NOT NULL,
                `comments` text,
                `actualtext` text NOT NULL,
                `repoID` varchar(22) NOT NULL,
                `changedate` datetime NOT NULL,
                `changedby` varchar(100) NOT NULL,
                PRIMARY KEY (`ID`),
                UNIQUE KEY `sourceID` (`gedcom`,`sourceID`),
                KEY `changedate` (`changedate`),
                FULLTEXT KEY `sourcetext` (`actualtext`)
            ",

      'citations' => "
                `citationID` int(11) NOT NULL AUTO_INCREMENT,
                `gedcom` varchar(20) NOT NULL,
                `persfamID` varchar(22) NOT NULL,
                `eventID` varchar(10) NOT NULL,
                `sourceID` varchar(22) NOT NULL,
                `ordernum` float NOT NULL,
                `description` text NOT NULL,
                `citedate` varchar(50) NOT NULL,
                `citedatetr` date NOT NULL,
                `citetext` text NOT NULL,
                `page` text NOT NULL,
                `quay` varchar(2) NOT NULL,
                `note` text NOT NULL,
                PRIMARY KEY (`citationID`),
                KEY `citation` (`gedcom`,`persfamID`,`eventID`,`sourceID`,`description`(20))
            ",

      'repositories' => "
                `ID` int(11) NOT NULL AUTO_INCREMENT,
                `repoID` varchar(22) NOT NULL,
                `reponame` varchar(90) NOT NULL,
                `gedcom` varchar(20) NOT NULL,
                `addressID` int(11) NOT NULL,
                `changedate` datetime NOT NULL,
                `changedby` varchar(100) NOT NULL,
                PRIMARY KEY (`ID`),
                UNIQUE KEY `repoID` (`gedcom`,`repoID`),
                KEY `reponame` (`reponame`)
            ",

      'mostwanted' => "
                `ID` int(11) NOT NULL AUTO_INCREMENT,
                `ordernum` float NOT NULL,
                `gedcom` varchar(20) NOT NULL,
                `mwtype` varchar(10) NOT NULL,
                `title` varchar(128) NOT NULL,
                `description` text NOT NULL,
                `personID` varchar(22) NOT NULL,
                `mediaID` int(11) NOT NULL,
                PRIMARY KEY (`ID`),
                KEY `mwtype` (`mwtype`,`ordernum`,`title`)
            ",

      'xnotes' => "
                `ID` int(11) NOT NULL AUTO_INCREMENT,
                `noteID` varchar(22) NOT NULL,
                `gedcom` varchar(20) NOT NULL,
                `note` text NOT NULL,
                PRIMARY KEY (`ID`),
                KEY `noteID` (`gedcom`,`noteID`),
                FULLTEXT KEY `note` (`note`)
            ",

      'notelinks' => "
                `ID` int(11) NOT NULL AUTO_INCREMENT,
                `persfamID` varchar(22) NOT NULL,
                `gedcom` varchar(20) NOT NULL,
                `xnoteID` int(11) NOT NULL,
                `eventID` varchar(10) NOT NULL,
                `ordernum` float NOT NULL,
                `secret` tinyint(4) NOT NULL,
                PRIMARY KEY (`ID`),
                KEY `notelinks` (`gedcom`,`persfamID`,`eventID`)
            ",

      'associations' => "
                `assocID` int(11) NOT NULL AUTO_INCREMENT,
                `gedcom` varchar(20) NOT NULL,
                `personID` varchar(22) NOT NULL,
                `passocID` varchar(22) NOT NULL,
                `reltype` varchar(1) NOT NULL,
                `relationship` varchar(75) NOT NULL,
                PRIMARY KEY (`assocID`),
                KEY `assoc` (`gedcom`,`personID`)
            "
    ];
  }
}
