<?php

/**
 * HeritagePress Core Database Management
 *
 * Handles core genealogy tables: people, families, children, events
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Database_Core
{
  private $wpdb;
  private $charset_collate;

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->charset_collate = $wpdb->get_charset_collate();
  }

  /**
   * Get table name with proper prefix
   */
  public function get_table_name($table)
  {
    return $this->wpdb->prefix . 'hp_' . $table;
  }

  /**
   * Create all core genealogy tables
   */
  public function create_tables()
  {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $this->create_persons_table();
    $this->create_families_table();
    $this->create_children_table();
    $this->create_events_table();
    $this->create_eventtypes_table();
    $this->create_temp_events_table();
    $this->create_timeline_events_table();
  }

  /**
   * Main persons table - core genealogy records
   */
  private function create_persons_table()
  {
    $table_name = $this->get_table_name('persons');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            gedcom_id varchar(50) NOT NULL,
            tree_id varchar(50) DEFAULT 'main',
            first_name varchar(255) DEFAULT NULL,
            middle_name varchar(255) DEFAULT NULL,
            last_name varchar(255) DEFAULT NULL,
            maiden_name varchar(255) DEFAULT NULL,
            nickname varchar(255) DEFAULT NULL,
            prefix varchar(50) DEFAULT NULL,
            suffix varchar(50) DEFAULT NULL,
            gender enum('M','F','U') DEFAULT 'U',
            birth_date varchar(100) DEFAULT NULL,
            birth_place varchar(500) DEFAULT NULL,
            birth_date_estimated tinyint(1) DEFAULT 0,
            death_date varchar(100) DEFAULT NULL,
            death_place varchar(500) DEFAULT NULL,
            death_date_estimated tinyint(1) DEFAULT 0,
            burial_date varchar(100) DEFAULT NULL,
            burial_place varchar(500) DEFAULT NULL,
            occupation varchar(255) DEFAULT NULL,
            education varchar(255) DEFAULT NULL,
            religion varchar(255) DEFAULT NULL,
            notes text,
            private tinyint(1) DEFAULT 0,
            living tinyint(1) DEFAULT 0,
            father_id int(11) DEFAULT NULL,
            mother_id int(11) DEFAULT NULL,
            primary_photo_id int(11) DEFAULT NULL,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_gedcom_tree (gedcom_id, tree_id),
            KEY tree_id (tree_id),
            KEY father_id (father_id),
            KEY mother_id (mother_id),
            KEY last_name (last_name),
            KEY birth_date (birth_date),
            KEY death_date (death_date),
            KEY living (living),
            KEY private (private),
            FULLTEXT KEY search_names (first_name, middle_name, last_name, maiden_name, nickname)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Families table - marriage/relationship units
   */
  private function create_families_table()
  {
    $table_name = $this->get_table_name('families');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            gedcom_id varchar(50) NOT NULL,
            tree_id varchar(50) DEFAULT 'main',
            husband_id int(11) DEFAULT NULL,
            wife_id int(11) DEFAULT NULL,
            marriage_date varchar(100) DEFAULT NULL,
            marriage_place varchar(500) DEFAULT NULL,
            marriage_date_estimated tinyint(1) DEFAULT 0,
            divorce_date varchar(100) DEFAULT NULL,
            divorce_place varchar(500) DEFAULT NULL,
            engagement_date varchar(100) DEFAULT NULL,
            engagement_place varchar(500) DEFAULT NULL,
            notes text,
            private tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_gedcom_tree (gedcom_id, tree_id),
            KEY tree_id (tree_id),
            KEY husband_id (husband_id),
            KEY wife_id (wife_id),
            KEY marriage_date (marriage_date)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Children table - parent-child relationships
   */
  private function create_children_table()
  {
    $table_name = $this->get_table_name('children');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            family_id int(11) NOT NULL,
            person_id int(11) NOT NULL,
            child_order int(11) DEFAULT 0,
            relationship_type varchar(50) DEFAULT 'biological',
            notes text,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_family_child (family_id, person_id),
            KEY tree_id (tree_id),
            KEY family_id (family_id),
            KEY person_id (person_id),
            KEY child_order (child_order)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Events table - all life events
   */
  private function create_events_table()
  {
    $table_name = $this->get_table_name('events');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            gedcom_id varchar(50) DEFAULT NULL,
            tree_id varchar(50) DEFAULT 'main',
            person_id int(11) DEFAULT NULL,
            family_id int(11) DEFAULT NULL,
            event_type varchar(100) NOT NULL,
            event_date varchar(100) DEFAULT NULL,
            event_place varchar(500) DEFAULT NULL,
            event_date_estimated tinyint(1) DEFAULT 0,
            description text,
            notes text,
            private tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY person_id (person_id),
            KEY family_id (family_id),
            KEY event_type (event_type),
            KEY event_date (event_date)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Event types table - genealogy event type definitions
   */
  private function create_eventtypes_table()
  {
    $table_name = $this->get_table_name('eventtypes');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            event_type varchar(100) NOT NULL,
            display_name varchar(100) NOT NULL,
            category varchar(50) DEFAULT 'personal',
            description text,
            gedcom_tag varchar(10) DEFAULT NULL,
            is_vital tinyint(1) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY event_type (event_type),
            KEY category (category),
            KEY sort_order (sort_order),
            KEY active (active)
        ) {$this->charset_collate};";

    dbDelta($sql);

    // Insert default event types
    $this->insert_default_eventtypes();
  }

  /**
   * Temporary events table - for import processing
   */
  private function create_temp_events_table()
  {
    $table_name = $this->get_table_name('temp_events');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            import_session_id varchar(50) NOT NULL,
            gedcom_id varchar(50) DEFAULT NULL,
            tree_id varchar(50) DEFAULT 'main',
            person_id varchar(50) DEFAULT NULL,
            family_id varchar(50) DEFAULT NULL,
            event_type varchar(100) NOT NULL,
            event_date varchar(100) DEFAULT NULL,
            event_place varchar(500) DEFAULT NULL,
            description text,
            notes text,
            raw_data text,
            processed tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY import_session_id (import_session_id),
            KEY tree_id (tree_id),
            KEY person_id (person_id),
            KEY family_id (family_id),
            KEY processed (processed)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Timeline events table - for timeline visualization
   */
  private function create_timeline_events_table()
  {
    $table_name = $this->get_table_name('timeline_events');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            event_id int(11) DEFAULT NULL,
            person_id int(11) DEFAULT NULL,
            family_id int(11) DEFAULT NULL,
            event_title varchar(255) NOT NULL,
            event_date varchar(100) DEFAULT NULL,
            event_year int(11) DEFAULT NULL,
            event_description text,
            event_category varchar(50) DEFAULT 'personal',
            display_order int(11) DEFAULT 0,
            is_public tinyint(1) DEFAULT 1,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY event_id (event_id),
            KEY person_id (person_id),
            KEY family_id (family_id),
            KEY event_year (event_year),
            KEY event_category (event_category),
            KEY display_order (display_order)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Insert default event types
   */
  private function insert_default_eventtypes()
  {
    $table_name = $this->get_table_name('eventtypes');
    
    $default_types = array(
      array('BIRT', 'Birth', 'vital', 'Birth of person', 'BIRT', 1, 1),
      array('DEAT', 'Death', 'vital', 'Death of person', 'DEAT', 1, 2),
      array('BURI', 'Burial', 'vital', 'Burial of person', 'BURI', 1, 3),
      array('MARR', 'Marriage', 'family', 'Marriage event', 'MARR', 1, 4),
      array('DIV', 'Divorce', 'family', 'Divorce event', 'DIV', 0, 5),
      array('BAPM', 'Baptism', 'religious', 'Baptism or christening', 'BAPM', 0, 6),
      array('CONF', 'Confirmation', 'religious', 'Religious confirmation', 'CONF', 0, 7),
      array('EDUC', 'Education', 'personal', 'Education or schooling', 'EDUC', 0, 8),
      array('OCCU', 'Occupation', 'personal', 'Occupation or career', 'OCCU', 0, 9),
      array('RESI', 'Residence', 'personal', 'Place of residence', 'RESI', 0, 10),
      array('IMMI', 'Immigration', 'personal', 'Immigration to new country', 'IMMI', 0, 11),
      array('EMIG', 'Emigration', 'personal', 'Emigration from country', 'EMIG', 0, 12),
      array('NATU', 'Naturalization', 'personal', 'Naturalization as citizen', 'NATU', 0, 13),
      array('MILI', 'Military', 'personal', 'Military service', 'MILI', 0, 14),
      array('PROB', 'Probate', 'legal', 'Probate of will', 'PROB', 0, 15)
    );

    foreach ($default_types as $type) {
      $this->wpdb->replace(
        $table_name,
        array(
          'event_type' => $type[0],
          'display_name' => $type[1],
          'category' => $type[2],
          'description' => $type[3],
          'gedcom_tag' => $type[4],
          'is_vital' => $type[5],
          'sort_order' => $type[6],
          'active' => 1
        )
      );
    }
  }

  /**
   * Drop core tables
   */
  public function drop_tables()
  {
    $tables = array(
      'persons', 'families', 'children', 'events', 
      'eventtypes', 'temp_events', 'timeline_events'
    );

    foreach ($tables as $table) {
      $table_name = $this->get_table_name($table);
      $this->wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
  }

  /**
   * Get table statistics for core tables
   */
  public function get_table_stats()
  {
    $stats = array();
    $tables = array(
      'persons', 'families', 'children', 'events', 
      'eventtypes', 'temp_events', 'timeline_events'
    );

    foreach ($tables as $table) {
      $table_name = $this->get_table_name($table);
      $count = $this->wpdb->get_var("SELECT COUNT(*) FROM $table_name");
      $stats[$table] = (int)$count;
    }

    return $stats;
  }
}
