<?php

/**
 * HeritagePress Database DNA Tables
 *
 * Handles creation of DNA-related tables: dna_tests, dna_links, dna_groups
 * Table structures extracted from TNG SQL file and adapted for HeritagePress
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Database_DNA
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
   * Create all DNA tables
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
   * Drop all DNA tables
   */
  public function drop_tables()
  {
    $tables = ['dna_tests', 'dna_links', 'dna_groups'];

    foreach ($tables as $table) {
      $table_name = $this->table_prefix . $table;
      $this->wpdb->query("DROP TABLE IF EXISTS `$table_name`");
    }
  }

  /**
   * Check if all DNA tables exist
   */
  public function tables_exist()
  {
    $tables = ['dna_tests', 'dna_links', 'dna_groups'];

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
      error_log("HeritagePress DNA: Failed to create table $table_full_name: " . $this->wpdb->last_error);
      return false;
    }

    return true;
  }

  /**
   * Get DNA table structures
   * Exact structures from TNG SQL file, adapted with hp_ prefix
   */
  private function get_table_structures()
  {
    return [
      'dna_tests' => "
                `testID` int(11) NOT NULL AUTO_INCREMENT,
                `test_type` varchar(40) NOT NULL,
                `test_number` varchar(50) NOT NULL,
                `notes` text NOT NULL,
                `vendor` varchar(100) NOT NULL,
                `test_date` date NOT NULL,
                `match_date` date NOT NULL,
                `personID` varchar(22) NOT NULL,
                `gedcom` varchar(20) NOT NULL,
                `person_name` varchar(100) NOT NULL,
                `urls` text NOT NULL,
                `mtdna_haplogroup` varchar(40) NOT NULL,
                `ydna_haplogroup` varchar(30) NOT NULL,
                `significant_snp` varchar(255) NOT NULL,
                `terminal_snp` varchar(80) NOT NULL,
                `markers` varchar(40) NOT NULL,
                `y_results` varchar(512) NOT NULL,
                `hvr1_results` varchar(100) NOT NULL,
                `hvr2_results` varchar(100) NOT NULL,
                `mtdna_confirmed` varchar(2) NOT NULL,
                `ydna_confirmed` varchar(2) NOT NULL,
                `markeropt` varchar(2) NOT NULL,
                `notesopt` varchar(2) NOT NULL,
                `linksopt` varchar(2) NOT NULL,
                `surnamesopt` tinyint(4) NOT NULL,
                `private_dna` varchar(2) NOT NULL,
                `private_test` varchar(2) NOT NULL,
                `dna_group` varchar(128) NOT NULL,
                `dna_group_desc` varchar(128) NOT NULL,
                `surnames` text NOT NULL,
                `MD_ancestorID` varchar(20) NOT NULL,
                `MRC_ancestorID` varchar(20) NOT NULL,
                `admin_notes` text NOT NULL,
                `medialinks` text NOT NULL,
                `ref_seq` text NOT NULL,
                `xtra_mut` text NOT NULL,
                `coding_reg` text NOT NULL,
                `GEDmatchID` varchar(30) NOT NULL,
                `relationship_range` varchar(80) NOT NULL,
                `suggested_relationship` varchar(80) NOT NULL,
                `actual_relationship` varchar(40) NOT NULL,
                `related_side` varchar(120) NOT NULL,
                `shared_cMs` varchar(10) NOT NULL,
                `shared_segments` varchar(10) NOT NULL,
                `chromosome` varchar(4) NOT NULL,
                `segment_start` varchar(40) NOT NULL,
                `segment_end` varchar(40) NOT NULL,
                `centiMorgans` varchar(40) NOT NULL,
                `matching_SNPs` varchar(10) NOT NULL,
                `x_match` varchar(2) NOT NULL,
                PRIMARY KEY (`testID`),
                KEY `test_date` (`test_date`)
            ",

      'dna_links' => "
                `ID` int(11) NOT NULL AUTO_INCREMENT,
                `testID` int(11) NOT NULL,
                `personID` varchar(22) NOT NULL,
                `gedcom` varchar(20) NOT NULL,
                `dna_group` varchar(128) NOT NULL,
                PRIMARY KEY (`ID`)
            ",

      'dna_groups' => "
                `dna_group` varchar(20) NOT NULL,
                `test_type` varchar(40) NOT NULL,
                `gedcom` varchar(20) NOT NULL,
                `description` varchar(128) NOT NULL,
                `action` tinyint(4) NOT NULL,
                PRIMARY KEY (`dna_group`)
            "
    ];
  }
}
