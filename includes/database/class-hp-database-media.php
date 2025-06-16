<?php

/**
 * HeritagePress Database Media Tables
 *
 * Handles creation of media-related tables: media, medialinks, mediatypes, albums, albumlinks, albumplinks, image_tags
 * Table structures for media and multimedia in HeritagePress genealogy system
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Database_Media
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
   * Create all media tables
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
   * Drop all media tables
   */
  public function drop_tables()
  {
    $tables = ['media', 'medialinks', 'mediatypes', 'albums', 'albumlinks', 'albumplinks', 'image_tags'];

    foreach ($tables as $table) {
      $table_name = $this->table_prefix . $table;
      $this->wpdb->query("DROP TABLE IF EXISTS `$table_name`");
    }
  }

  /**
   * Check if all media tables exist
   */
  public function tables_exist()
  {
    $tables = ['media', 'medialinks', 'mediatypes', 'albums', 'albumlinks', 'albumplinks', 'image_tags'];

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
      error_log("HeritagePress Media: Failed to create table $table_full_name: " . $this->wpdb->last_error);
      return false;
    }

    return true;
  }
  /**
   * Get media table structures
   * Database structures for HeritagePress media tables
   */
  private function get_table_structures()
  {
    return [
      'media' => "
                `mediaID` int(11) NOT NULL AUTO_INCREMENT,
                `mediatypeID` varchar(20) NOT NULL,
                `mediakey` varchar(255) NOT NULL,
                `gedcom` varchar(20) NOT NULL,
                `form` varchar(10) NOT NULL,
                `path` varchar(255) DEFAULT NULL,
                `description` text,
                `datetaken` varchar(50) DEFAULT NULL,
                `placetaken` text,
                `notes` text,
                `owner` text,
                `thumbpath` varchar(255) DEFAULT NULL,
                `alwayson` tinyint(4) DEFAULT NULL,
                `map` text,
                `abspath` tinyint(4) DEFAULT NULL,
                `status` varchar(40) DEFAULT NULL,
                `showmap` smallint(6) DEFAULT NULL,
                `cemeteryID` int(11) DEFAULT NULL,
                `plot` text,
                `linktocem` tinyint(4) DEFAULT NULL,
                `longitude` varchar(22) DEFAULT NULL,
                `latitude` varchar(22) DEFAULT NULL,
                `zoom` tinyint(4) DEFAULT NULL,
                `width` smallint(6) DEFAULT NULL,
                `height` smallint(6) DEFAULT NULL,
                `left_value` smallint(6) NOT NULL,
                `top_value` smallint(6) NOT NULL,
                `bodytext` text,
                `usenl` tinyint(4) DEFAULT NULL,
                `newwindow` tinyint(4) DEFAULT NULL,
                `usecollfolder` tinyint(4) DEFAULT NULL,
                `private` tinyint(4) NOT NULL,
                `changedate` datetime NOT NULL,
                `changedby` varchar(100) NOT NULL,
                PRIMARY KEY (`mediaID`),
                UNIQUE KEY `mediakey` (`gedcom`,`mediakey`),
                KEY `mediatypeID` (`mediatypeID`),
                KEY `changedate` (`changedate`),
                KEY `description` (`description`(20)),
                KEY `headstones` (`cemeteryID`,`description`(20))
            ",

      'medialinks' => "
                `medialinkID` int(11) NOT NULL AUTO_INCREMENT,
                `gedcom` varchar(20) NOT NULL,
                `linktype` char(1) NOT NULL,
                `personID` varchar(248) NOT NULL,
                `eventID` varchar(10) NOT NULL,
                `mediaID` int(11) NOT NULL,
                `altdescription` text NOT NULL,
                `altnotes` text NOT NULL,
                `ordernum` float NOT NULL,
                `dontshow` tinyint(4) NOT NULL,
                `defphoto` varchar(1) NOT NULL,
                PRIMARY KEY (`medialinkID`),
                UNIQUE KEY `mediaID` (`gedcom`,`personID`(22),`mediaID`,`eventID`),
                KEY `personID` (`gedcom`,`personID`(22),`ordernum`)
            ",

      'mediatypes' => "
                `mediatypeID` varchar(20) NOT NULL,
                `display` varchar(40) NOT NULL,
                `path` varchar(127) NOT NULL,
                `liketype` varchar(20) NOT NULL,
                `icon` varchar(50) NOT NULL,
                `thumb` varchar(50) NOT NULL,
                `exportas` varchar(20) NOT NULL,
                `disabled` tinyint(4) NOT NULL,
                `ordernum` tinyint(4) NOT NULL,
                `localpath` varchar(250) NOT NULL,
                PRIMARY KEY (`mediatypeID`),
                KEY `ordernum` (`ordernum`,`display`)
            ",

      'albums' => "
                `albumID` int(11) NOT NULL AUTO_INCREMENT,
                `albumname` varchar(100) NOT NULL,
                `description` text,
                `alwayson` tinyint(4) DEFAULT NULL,
                `keywords` text,
                `active` tinyint(4) NOT NULL,
                PRIMARY KEY (`albumID`),
                KEY `albumname` (`albumname`)
            ",

      'albumlinks' => "
                `albumlinkID` int(11) NOT NULL AUTO_INCREMENT,
                `albumID` int(11) NOT NULL,
                `mediaID` int(11) NOT NULL,
                `ordernum` int(11) DEFAULT NULL,
                `defphoto` varchar(1) NOT NULL,
                PRIMARY KEY (`albumlinkID`),
                KEY `albumID` (`albumID`,`ordernum`)
            ",

      'albumplinks' => "
                `alinkID` int(11) NOT NULL AUTO_INCREMENT,
                `gedcom` varchar(20) NOT NULL,
                `linktype` char(1) NOT NULL,
                `entityID` varchar(100) NOT NULL,
                `eventID` varchar(10) NOT NULL,
                `albumID` int(11) NOT NULL,
                `ordernum` float NOT NULL,
                PRIMARY KEY (`alinkID`),
                UNIQUE KEY `alinkID` (`gedcom`,`entityID`,`albumID`),
                KEY `entityID` (`gedcom`,`entityID`,`ordernum`)
            ",

      'image_tags' => "
                `ID` int(11) NOT NULL AUTO_INCREMENT,
                `mediaID` int(11) NOT NULL,
                `rtop` int(11) NOT NULL,
                `rleft` int(11) NOT NULL,
                `rheight` int(11) NOT NULL,
                `rwidth` int(11) NOT NULL,
                `gedcom` varchar(20) NOT NULL,
                `linktype` char(1) NOT NULL,
                `persfamID` varchar(100) NOT NULL,
                `label` varchar(64) NOT NULL,
                PRIMARY KEY (`ID`),
                UNIQUE KEY `mediaID` (`mediaID`,`gedcom`,`persfamID`)
            "
    ];
  }
}
