<?php

/**
 * HeritagePress System & Admin Database
 *
 * Handles system tables: trees, users, permissions, imports, DNA, branches
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Database_System
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
   * Create all system and admin tables
   */
  public function create_tables()
  {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $this->create_trees_table();
    $this->create_user_permissions_table();
    $this->create_import_logs_table();
    $this->create_saveimport_table();
    $this->create_branches_table();
    $this->create_branchlinks_table();
    $this->create_languages_table();
    $this->create_dna_tests_table();
    $this->create_dna_links_table();
    $this->create_dna_groups_table();
    $this->create_users_table();
  }

  /**
   * Trees table - multiple family trees
   */
  private function create_trees_table()
  {
    $table_name = $this->get_table_name('trees');

    $sql = "CREATE TABLE $table_name (
            id varchar(50) NOT NULL,
            name varchar(255) NOT NULL,
            description text,
            owner_id int(11) NOT NULL,
            privacy_level enum('public','private','restricted') DEFAULT 'private',
            allow_registrations tinyint(1) DEFAULT 0,
            require_approval tinyint(1) DEFAULT 1,
            home_person_id int(11) DEFAULT NULL,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY owner_id (owner_id),
            KEY privacy_level (privacy_level),
            KEY home_person_id (home_person_id)
        ) {$this->charset_collate};";

    dbDelta($sql);

    // Insert default tree
    $this->wpdb->replace(
      $table_name,
      array(
        'id' => 'main',
        'name' => 'Main Family Tree',
        'description' => 'Default family tree',
        'owner_id' => 1,
        'privacy_level' => 'private'
      )
    );
  }

  /**
   * User permissions table - tree-specific user access
   */
  private function create_user_permissions_table()
  {
    $table_name = $this->get_table_name('user_permissions');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            tree_id varchar(50) NOT NULL,
            permission_level enum('none','view','edit','admin') DEFAULT 'none',
            granted_by int(11) DEFAULT NULL,
            granted_date datetime DEFAULT CURRENT_TIMESTAMP,
            notes text,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_tree (user_id, tree_id),
            KEY tree_id (tree_id),
            KEY permission_level (permission_level),
            KEY granted_by (granted_by)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Import logs table - track GEDCOM imports
   */
  private function create_import_logs_table()
  {
    $table_name = $this->get_table_name('import_logs');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) NOT NULL,
            file_name varchar(500) NOT NULL,
            file_size int(11) DEFAULT NULL,
            imported_by int(11) NOT NULL,
            import_started datetime DEFAULT CURRENT_TIMESTAMP,
            import_completed datetime DEFAULT NULL,
            status enum('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
            records_processed int(11) DEFAULT 0,
            records_imported int(11) DEFAULT 0,
            records_updated int(11) DEFAULT 0,
            records_skipped int(11) DEFAULT 0,
            error_count int(11) DEFAULT 0,
            progress_percent decimal(5,2) DEFAULT 0.00,
            log_messages text,
            error_messages text,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY imported_by (imported_by),
            KEY status (status),
            KEY import_started (import_started)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Save import table - save import sessions
   */
  private function create_saveimport_table()
  {
    $table_name = $this->get_table_name('saveimport');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            tree_id varchar(50) NOT NULL,
            user_id int(11) NOT NULL,
            import_type varchar(50) DEFAULT 'gedcom',
            file_name varchar(500) NOT NULL,
            file_path varchar(1000) NOT NULL,
            import_data longtext,
            options text,
            status varchar(50) DEFAULT 'saved',
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY tree_id (tree_id),
            KEY user_id (user_id),
            KEY status (status)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Branches table - family tree branches
   */
  private function create_branches_table()
  {
    $table_name = $this->get_table_name('branches');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            branch_name varchar(255) NOT NULL,
            description text,
            root_person_id int(11) DEFAULT NULL,
            branch_color varchar(7) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY root_person_id (root_person_id),
            KEY is_active (is_active),
            KEY sort_order (sort_order)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Branch links table - connect people to branches
   */
  private function create_branchlinks_table()
  {
    $table_name = $this->get_table_name('branchlinks');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            branch_id int(11) NOT NULL,
            person_id int(11) NOT NULL,
            relationship_type varchar(100) DEFAULT 'descendant',
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_branch_person (branch_id, person_id),
            KEY tree_id (tree_id),
            KEY branch_id (branch_id),
            KEY person_id (person_id),
            KEY relationship_type (relationship_type)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Languages table - multi-language support
   */
  private function create_languages_table()
  {
    $table_name = $this->get_table_name('languages');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            language_code varchar(10) NOT NULL,
            language_name varchar(100) NOT NULL,
            english_name varchar(100) NOT NULL,
            is_default tinyint(1) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY language_code (language_code),
            KEY is_default (is_default),
            KEY is_active (is_active),
            KEY sort_order (sort_order)
        ) {$this->charset_collate};";

    dbDelta($sql);

    // Insert default languages
    $this->insert_default_languages();
  }

  /**
   * DNA tests table - DNA test management
   */
  private function create_dna_tests_table()
  {
    $table_name = $this->get_table_name('dna_tests');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            person_id int(11) NOT NULL,
            test_type varchar(100) NOT NULL,
            testing_company varchar(255) DEFAULT NULL,
            test_date date DEFAULT NULL,
            kit_number varchar(100) DEFAULT NULL,
            results_file varchar(500) DEFAULT NULL,
            notes text,
            private tinyint(1) DEFAULT 1,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY person_id (person_id),
            KEY test_type (test_type),
            KEY testing_company (testing_company),
            KEY kit_number (kit_number)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * DNA links table - DNA result connections
   */
  private function create_dna_links_table()
  {
    $table_name = $this->get_table_name('dna_links');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            test1_id int(11) NOT NULL,
            test2_id int(11) NOT NULL,
            relationship_type varchar(100) DEFAULT NULL,
            shared_cm decimal(8,2) DEFAULT NULL,
            confidence_level varchar(50) DEFAULT NULL,
            notes text,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY test1_id (test1_id),
            KEY test2_id (test2_id),
            KEY relationship_type (relationship_type),
            KEY shared_cm (shared_cm)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * DNA groups table - DNA testing groups
   */
  private function create_dna_groups_table()
  {
    $table_name = $this->get_table_name('dna_groups');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            group_name varchar(255) NOT NULL,
            description text,
            ancestor_person_id int(11) DEFAULT NULL,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY ancestor_person_id (ancestor_person_id),
            KEY group_name (group_name)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Users table for TNG compatibility (separate from WordPress users)
   */
  private function create_users_table()
  {
    $table_name = $this->get_table_name('users');

    $sql = "CREATE TABLE $table_name (
            user_id int(11) NOT NULL AUTO_INCREMENT,
            username varchar(255) NOT NULL,
            password varchar(255) NOT NULL,
            firstname varchar(255) DEFAULT NULL,
            lastname varchar(255) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            admin tinyint(1) DEFAULT 0,
            allow_edit tinyint(1) DEFAULT 0,
            allow_add tinyint(1) DEFAULT 0,
            allow_delete tinyint(1) DEFAULT 0,
            allow_ged tinyint(1) DEFAULT 0,
            mygedcom varchar(255) DEFAULT NULL,
            gedcom varchar(255) DEFAULT NULL,
            personid varchar(255) DEFAULT NULL,
            description text,
            role varchar(50) DEFAULT 'member',
            last_login datetime DEFAULT NULL,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id),
            UNIQUE KEY username (username),
            KEY email (email),
            KEY role (role),
            KEY last_login (last_login)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Insert default languages
   */
  private function insert_default_languages()
  {
    $table_name = $this->get_table_name('languages');
    
    $languages = array(
      array('en', 'English', 'English', 1, 1, 1),
      array('es', 'Español', 'Spanish', 0, 1, 2),
      array('fr', 'Français', 'French', 0, 1, 3),
      array('de', 'Deutsch', 'German', 0, 1, 4),
      array('it', 'Italiano', 'Italian', 0, 1, 5),
      array('pt', 'Português', 'Portuguese', 0, 1, 6),
      array('nl', 'Nederlands', 'Dutch', 0, 1, 7),
      array('sv', 'Svenska', 'Swedish', 0, 1, 8),
      array('da', 'Dansk', 'Danish', 0, 1, 9),
      array('no', 'Norsk', 'Norwegian', 0, 1, 10)
    );

    foreach ($languages as $lang) {
      $this->wpdb->replace(
        $table_name,
        array(
          'language_code' => $lang[0],
          'language_name' => $lang[1],
          'english_name' => $lang[2],
          'is_default' => $lang[3],
          'is_active' => $lang[4],
          'sort_order' => $lang[5]
        )
      );
    }
  }

  /**
   * Drop system tables
   */
  public function drop_tables()
  {
    $tables = array(
      'trees', 'user_permissions', 'import_logs', 'saveimport',
      'branches', 'branchlinks', 'languages', 'dna_tests', 
      'dna_links', 'dna_groups', 'users'
    );

    foreach ($tables as $table) {
      $table_name = $this->get_table_name($table);
      $this->wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
  }

  /**
   * Get table statistics for system tables
   */
  public function get_table_stats()
  {
    $stats = array();
    $tables = array(
      'trees', 'user_permissions', 'import_logs', 'saveimport', 
      'branches', 'branchlinks', 'languages', 'dna_tests', 
      'dna_links', 'dna_groups', 'users'
    );

    foreach ($tables as $table) {
      $table_name = $this->get_table_name($table);
      $count = $this->wpdb->get_var("SELECT COUNT(*) FROM $table_name");
      $stats[$table] = (int)$count;
    }

    return $stats;
  }

}
