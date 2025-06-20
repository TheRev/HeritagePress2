<?php

/**
 * HeritagePress Database Core Tables
 *
 * Handles creation of core genealogy tables: people, families, children
 * Table structures extracted from genealogy SQL file and adapted for HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Database_Core
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
   * Create all core tables
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
   * Drop all core tables
   */
  public function drop_tables()
  {
    $tables = ['people', 'families', 'children'];

    foreach ($tables as $table) {
      $table_name = $this->table_prefix . $table;
      $this->wpdb->query("DROP TABLE IF EXISTS `$table_name`");
    }
  }

  /**
   * Check if all core tables exist
   */
  public function tables_exist()
  {
    $tables = ['people', 'families', 'children'];

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
      error_log("HeritagePress Core: Failed to create table $table_full_name: " . $this->wpdb->last_error);
      return false;
    }

    return true;
  }
  /**
   * Get core table structures
   * Exact structures from genealogy SQL file, adapted with hp_ prefix
   */
  private function get_table_structures()
  {
    return [
      'people' => "
                `ID` int(11) NOT NULL AUTO_INCREMENT,
                `gedcom` varchar(20) NOT NULL,
                `personID` varchar(22) NOT NULL,
                `lnprefix` varchar(25) NOT NULL,
                `lastname` varchar(127) NOT NULL,
                `firstname` varchar(127) NOT NULL,
                `birthdate` varchar(50) NOT NULL,
                `birthdatetr` date NOT NULL,
                `sex` varchar(25) NOT NULL,
                `birthplace` text NOT NULL,
                `deathdate` varchar(50) NOT NULL,
                `deathdatetr` date NOT NULL,
                `deathplace` text NOT NULL,
                `altbirthtype` varchar(5) NOT NULL,
                `altbirthdate` varchar(50) NOT NULL,
                `altbirthdatetr` date NOT NULL,
                `altbirthplace` text NOT NULL,
                `burialdate` varchar(50) NOT NULL,
                `burialdatetr` date NOT NULL,
                `burialplace` text NOT NULL,
                `burialtype` tinyint(4) NOT NULL,
                `baptdate` varchar(50) NOT NULL,
                `baptdatetr` date NOT NULL,
                `baptplace` text NOT NULL,
                `confdate` varchar(50) NOT NULL,
                `confdatetr` date NOT NULL,
                `confplace` text NOT NULL,
                `initdate` varchar(50) NOT NULL,
                `initdatetr` date NOT NULL,
                `initplace` text NOT NULL,
                `endldate` varchar(50) NOT NULL,
                `endldatetr` date NOT NULL,
                `endlplace` text NOT NULL,
                `changedate` datetime NOT NULL,
                `nickname` text NOT NULL,
                `title` tinytext NOT NULL,
                `prefix` tinytext NOT NULL,
                `suffix` tinytext NOT NULL,
                `nameorder` tinyint(4) NOT NULL,
                `famc` varchar(22) NOT NULL,
                `metaphone` varchar(15) NOT NULL,
                `living` tinyint(4) NOT NULL,
                `private` tinyint(4) NOT NULL,
                `branch` varchar(512) NOT NULL,
                `changedby` varchar(100) NOT NULL,
                `edituser` varchar(100) NOT NULL,
                `edittime` int(11) NOT NULL,
                PRIMARY KEY (`ID`),
                UNIQUE KEY `gedpers` (`gedcom`,`personID`),
                KEY `lastname` (`lastname`,`firstname`),
                KEY `firstname` (`firstname`),
                KEY `gedlast` (`gedcom`,`lastname`,`firstname`),
                KEY `gedfirst` (`gedcom`,`firstname`),
                KEY `birthplace` (`birthplace`(20)),
                KEY `altbirthplace` (`altbirthplace`(20)),
                KEY `deathplace` (`deathplace`(20)),
                KEY `burialplace` (`burialplace`(20)),
                KEY `baptplace` (`baptplace`(20)),
                KEY `confplace` (`confplace`(20)),
                KEY `initplace` (`initplace`(20)),
                KEY `endlplace` (`endlplace`(20)),
                KEY `changedate` (`changedate`)
            ",

      'families' => "
                `ID` int(11) NOT NULL AUTO_INCREMENT,
                `gedcom` varchar(20) NOT NULL,
                `familyID` varchar(22) NOT NULL,
                `husband` varchar(22) NOT NULL,
                `wife` varchar(22) NOT NULL,
                `marrdate` varchar(50) NOT NULL,
                `marrdatetr` date NOT NULL,
                `marrplace` text NOT NULL,
                `marrtype` varchar(90) NOT NULL,
                `divdate` varchar(50) NOT NULL,
                `divdatetr` date NOT NULL,
                `divplace` text NOT NULL,
                `status` varchar(20) NOT NULL,
                `sealdate` varchar(50) NOT NULL,
                `sealdatetr` date NOT NULL,
                `sealplace` text NOT NULL,
                `husborder` tinyint(4) NOT NULL,
                `wifeorder` tinyint(4) NOT NULL,
                `changedate` datetime NOT NULL,
                `living` tinyint(4) NOT NULL,
                `private` tinyint(4) NOT NULL,
                `branch` varchar(512) NOT NULL,
                `changedby` varchar(100) NOT NULL,
                `edituser` varchar(100) NOT NULL,
                `edittime` int(11) NOT NULL,
                PRIMARY KEY (`ID`),
                UNIQUE KEY `familyID` (`gedcom`,`familyID`),
                KEY `husband` (`gedcom`,`husband`),
                KEY `wife` (`gedcom`,`wife`),
                KEY `marrplace` (`marrplace`(20)),
                KEY `divplace` (`divplace`(20)),
                KEY `changedate` (`changedate`)
            ",

      'children' => "
                `ID` int(11) NOT NULL AUTO_INCREMENT,
                `gedcom` varchar(20) NOT NULL,
                `familyID` varchar(22) NOT NULL,
                `personID` varchar(22) NOT NULL,
                `frel` varchar(20) NOT NULL,
                `mrel` varchar(20) NOT NULL,
                `sealdate` varchar(50) NOT NULL,
                `sealdatetr` date NOT NULL,
                `sealplace` text NOT NULL,
                `haskids` tinyint(4) NOT NULL,
                `ordernum` smallint(6) NOT NULL,
                `parentorder` tinyint(4) NOT NULL,
                PRIMARY KEY (`ID`),
                UNIQUE KEY `familyID` (`gedcom`,`familyID`,`personID`),
                KEY `personID` (`gedcom`,`personID`)
            "
    ];
  }
}
