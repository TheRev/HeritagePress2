<?php

/**
 * HeritagePress Database System Tables
 *
 * Handles creation of system/admin tables: users, trees, languages, branches, branchlinks, templates, reports
 * Table structures for system configuration in HeritagePress genealogy system
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Database_System
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
   * Create all system tables
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
   * Drop all system tables
   */
  public function drop_tables()
  {
    $tables = ['users', 'trees', 'languages', 'branches', 'branchlinks', 'templates', 'reports'];

    foreach ($tables as $table) {
      $table_name = $this->table_prefix . $table;
      $this->wpdb->query("DROP TABLE IF EXISTS `$table_name`");
    }
  }

  /**
   * Check if all system tables exist
   */
  public function tables_exist()
  {
    $tables = ['users', 'trees', 'languages', 'branches', 'branchlinks', 'templates', 'reports'];

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
      error_log("HeritagePress System: Failed to create table $table_full_name: " . $this->wpdb->last_error);
      return false;
    }

    return true;
  }

  /**
   * Get system table structures
   * Table structures for HeritagePress genealogy system, adapted with hp_ prefix
   */
  private function get_table_structures()
  {
    return [
      'users' => "
                `userID` int(11) NOT NULL AUTO_INCREMENT,
                `description` varchar(50) NOT NULL,
                `username` varchar(100) NOT NULL,
                `password` varchar(128) NOT NULL,
                `password_type` varchar(10) NOT NULL,
                `gedcom` varchar(128) DEFAULT NULL,
                `mygedcom` varchar(20) NOT NULL,
                `personID` varchar(22) NOT NULL,
                `role` varchar(15) NOT NULL,
                `allow_edit` tinyint(4) NOT NULL,
                `allow_add` tinyint(4) NOT NULL,
                `tentative_edit` tinyint(4) NOT NULL,
                `allow_delete` tinyint(4) NOT NULL,
                `allow_lds` tinyint(4) NOT NULL,
                `allow_ged` tinyint(4) NOT NULL,
                `allow_pdf` tinyint(4) NOT NULL,
                `allow_living` tinyint(4) NOT NULL,
                `allow_private` tinyint(4) NOT NULL,
                `allow_private_notes` tinyint(4) NOT NULL,
                `allow_private_media` tinyint(4) NOT NULL,
                `allow_profile` tinyint(4) NOT NULL,
                `branch` varchar(20) DEFAULT NULL,
                `realname` varchar(50) DEFAULT NULL,
                `phone` varchar(30) DEFAULT NULL,
                `email` varchar(50) DEFAULT NULL,
                `address` varchar(100) DEFAULT NULL,
                `city` varchar(64) DEFAULT NULL,
                `state` varchar(64) DEFAULT NULL,
                `zip` varchar(10) DEFAULT NULL,
                `country` varchar(64) DEFAULT NULL,
                `website` varchar(128) DEFAULT NULL,
                `languageID` smallint(6) NOT NULL,
                `lastlogin` datetime NOT NULL,
                `disabled` tinyint(4) NOT NULL,
                `dt_registered` datetime NOT NULL,
                `dt_activated` datetime NOT NULL,
                `dt_consented` datetime NOT NULL,
                `no_email` tinyint(4) DEFAULT NULL,
                `notes` text,
                `reset_pwd_code` varchar(30) DEFAULT NULL,
                PRIMARY KEY (`userID`),
                UNIQUE KEY `username` (`username`)
            ",
      'trees' => "
                `gedcom` varchar(20) NOT NULL,
                `treename` varchar(100) NOT NULL,
                `description` text NOT NULL,
                `owner` varchar(100) NOT NULL,
                `email` varchar(100) NOT NULL,
                `address` varchar(100) NOT NULL,
                `city` varchar(40) NOT NULL,
                `state` varchar(30) NOT NULL,
                `country` varchar(30) NOT NULL,
                `zip` varchar(20) NOT NULL,
                `phone` varchar(30) NOT NULL,
                `secret` tinyint(4) NOT NULL,
                `disallowgedcreate` tinyint(4) NOT NULL,
                `disallowpdf` tinyint(4) NOT NULL,
                `lastimportdate` datetime NOT NULL,
                `importfilename` varchar(100) NOT NULL,
                `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`gedcom`),
                KEY `idx_date_created` (`date_created`)
            ",

      'languages' => "
                `languageID` smallint(6) NOT NULL AUTO_INCREMENT,
                `display` varchar(100) NOT NULL,
                `folder` varchar(50) NOT NULL,
                `charset` varchar(30) NOT NULL,
                `norels` varchar(1) NOT NULL,
                PRIMARY KEY (`languageID`)
            ",

      'branches' => "
                `branch` varchar(20) NOT NULL,
                `gedcom` varchar(20) NOT NULL,
                `description` varchar(128) NOT NULL,
                `personID` varchar(22) NOT NULL,
                `agens` int(11) NOT NULL,
                `dgens` int(11) NOT NULL,
                `dagens` int(11) NOT NULL,
                `inclspouses` tinyint(4) NOT NULL,
                `action` tinyint(4) NOT NULL,
                PRIMARY KEY (`gedcom`,`branch`),
                KEY `description` (`gedcom`,`description`)
            ",

      'branchlinks' => "
                `ID` int(11) NOT NULL AUTO_INCREMENT,
                `branch` varchar(20) NOT NULL,
                `gedcom` varchar(20) NOT NULL,
                `persfamID` varchar(22) NOT NULL,
                PRIMARY KEY (`ID`),
                UNIQUE KEY `branch` (`gedcom`,`branch`,`persfamID`)
            ",
      'templates' => "
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `template` varchar(64) NOT NULL,
                `ordernum` int(11) NOT NULL,
                `keyname` varchar(64) NOT NULL,
                `language` varchar(64) NOT NULL,
                `value` text NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `template` (`template`,`keyname`,`language`),
                KEY `var_order` (`template`,`ordernum`)
            ",

      'reports' => "
                `reportID` int(11) NOT NULL AUTO_INCREMENT,
                `reportname` varchar(80) NOT NULL,
                `reportdesc` text NOT NULL,
                `ranking` int(11) NOT NULL,
                `display` text NOT NULL,
                `criteria` text NOT NULL,
                `orderby` text NOT NULL,
                `sqlselect` text NOT NULL,
                `active` tinyint(4) NOT NULL,
                PRIMARY KEY (`reportID`),
                KEY `reportname` (`reportname`),
                KEY `ranking` (`ranking`)
            "
    ];
  }
}
