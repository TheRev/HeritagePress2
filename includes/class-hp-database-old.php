<?php

/**
 * HeritagePress Database Management
 *
 * Coordinates database operations across specialized database classes
 * Delegates table creation and management to category-specific classes
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Database
{

  private $wpdb;
  private $charset_collate;
  private $core_db;
  private $sources_db;
  private $media_db;
  private $geography_db;
  private $system_db;

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->charset_collate = $wpdb->get_charset_collate();
    
    // Initialize specialized database managers
    $this->init_database_managers();
  }

  /**
   * Initialize all specialized database manager classes
   */
  private function init_database_managers()
  {
    require_once plugin_dir_path(__FILE__) . 'class-hp-database-core.php';
    require_once plugin_dir_path(__FILE__) . 'class-hp-database-sources.php';
    require_once plugin_dir_path(__FILE__) . 'class-hp-database-media.php';
    require_once plugin_dir_path(__FILE__) . 'class-hp-database-geography.php';
    require_once plugin_dir_path(__FILE__) . 'class-hp-database-system.php';
    
    $this->core_db = new HP_Database_Core();
    $this->sources_db = new HP_Database_Sources();
    $this->media_db = new HP_Database_Media();
    $this->geography_db = new HP_Database_Geography();
    $this->system_db = new HP_Database_System();
  }

  /**
   * Get table name with proper prefix
   */
  public function get_table_name($table)
  {
    return $this->wpdb->prefix . 'hp_' . $table;
  }
  /**
   * Create all genealogy tables using specialized database managers
   */
  public function create_tables()
  {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Create tables by category using specialized managers
    $this->core_db->create_tables();
    $this->sources_db->create_tables();
    $this->media_db->create_tables();
    $this->geography_db->create_tables();
    $this->system_db->create_tables();

    // Update database version
    update_option('heritagepress_db_version', HERITAGEPRESS_DB_VERSION);
  }

  /**
   * Drop all genealogy tables using specialized database managers
   */
  public function drop_tables()
  {
    $this->core_db->drop_tables();
    $this->sources_db->drop_tables();
    $this->media_db->drop_tables();
    $this->geography_db->drop_tables();
    $this->system_db->drop_tables();
  }

  /**
   * Get table statistics from all specialized managers
   */
  public function get_table_stats()
  {
    $stats = array();
    
    $stats = array_merge($stats, $this->core_db->get_table_stats());
    $stats = array_merge($stats, $this->sources_db->get_table_stats());
    $stats = array_merge($stats, $this->media_db->get_table_stats());
    $stats = array_merge($stats, $this->geography_db->get_table_stats());
    $stats = array_merge($stats, $this->system_db->get_table_stats());
    
    return $stats;
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
   * Sources table - genealogy sources
   */
  private function create_sources_table()
  {
    $table_name = $this->get_table_name('sources');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            gedcom_id varchar(50) NOT NULL,
            tree_id varchar(50) DEFAULT 'main',
            title text NOT NULL,
            author varchar(500) DEFAULT NULL,
            publisher varchar(500) DEFAULT NULL,
            publication_date varchar(100) DEFAULT NULL,
            publication_place varchar(500) DEFAULT NULL,
            call_number varchar(255) DEFAULT NULL,
            media_type varchar(100) DEFAULT NULL,
            repository_id int(11) DEFAULT NULL,
            notes text,
            private tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_gedcom_tree (gedcom_id, tree_id),
            KEY tree_id (tree_id),
            KEY repository_id (repository_id),
            KEY media_type (media_type),
            FULLTEXT KEY search_source (title, author, publisher)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Citations table - source citations
   */
  private function create_citations_table()
  {
    $table_name = $this->get_table_name('citations');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            source_id int(11) NOT NULL,
            person_id int(11) DEFAULT NULL,
            family_id int(11) DEFAULT NULL,
            event_id int(11) DEFAULT NULL,
            page_reference text,
            citation_text text,
            quality_rating enum('0','1','2','3') DEFAULT '0',
            notes text,
            private tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY source_id (source_id),
            KEY person_id (person_id),
            KEY family_id (family_id),
            KEY event_id (event_id),
            KEY quality_rating (quality_rating)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Places table - geographic locations
   */
  private function create_places_table()
  {
    $table_name = $this->get_table_name('places');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            place_name varchar(500) NOT NULL,
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            place_type varchar(100) DEFAULT NULL,
            parent_place_id int(11) DEFAULT NULL,
            notes text,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY place_name (place_name),
            KEY parent_place_id (parent_place_id),
            KEY coordinates (latitude, longitude),
            FULLTEXT KEY search_place (place_name)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Media table - photos, documents, multimedia
   */
  private function create_media_table()
  {
    $table_name = $this->get_table_name('media');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            gedcom_id varchar(50) DEFAULT NULL,
            tree_id varchar(50) DEFAULT 'main',
            title varchar(500) DEFAULT NULL,
            description text,
            file_name varchar(500) DEFAULT NULL,
            file_path varchar(1000) DEFAULT NULL,
            file_size int(11) DEFAULT NULL,
            mime_type varchar(100) DEFAULT NULL,
            media_type varchar(100) DEFAULT NULL,
            thumbnail_path varchar(1000) DEFAULT NULL,
            notes text,
            private tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY media_type (media_type),
            KEY mime_type (mime_type),
            FULLTEXT KEY search_media (title, description)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Media links table - connect media to people/families/events
   */
  private function create_medialinks_table()
  {
    $table_name = $this->get_table_name('medialinks');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            media_id int(11) NOT NULL,
            person_id int(11) DEFAULT NULL,
            family_id int(11) DEFAULT NULL,
            event_id int(11) DEFAULT NULL,
            source_id int(11) DEFAULT NULL,
            link_type varchar(100) DEFAULT 'primary',
            sort_order int(11) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY media_id (media_id),
            KEY person_id (person_id),
            KEY family_id (family_id),
            KEY event_id (event_id),
            KEY source_id (source_id),
            KEY sort_order (sort_order)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Repositories table - archives, libraries, etc.
   */
  private function create_repositories_table()
  {
    $table_name = $this->get_table_name('repositories');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            gedcom_id varchar(50) NOT NULL,
            tree_id varchar(50) DEFAULT 'main',
            name varchar(500) NOT NULL,
            address_line1 varchar(255) DEFAULT NULL,
            address_line2 varchar(255) DEFAULT NULL,
            city varchar(255) DEFAULT NULL,
            state varchar(255) DEFAULT NULL,
            postal_code varchar(50) DEFAULT NULL,
            country varchar(255) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            website varchar(500) DEFAULT NULL,
            notes text,
            private tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_gedcom_tree (gedcom_id, tree_id),
            KEY tree_id (tree_id),
            FULLTEXT KEY search_repository (name, city, state, country)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Notes table - research notes and comments
   */
  private function create_notes_table()
  {
    $table_name = $this->get_table_name('notes');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            gedcom_id varchar(50) DEFAULT NULL,
            tree_id varchar(50) DEFAULT 'main',
            person_id int(11) DEFAULT NULL,
            family_id int(11) DEFAULT NULL,
            source_id int(11) DEFAULT NULL,
            note_type varchar(100) DEFAULT 'general',
            subject varchar(500) DEFAULT NULL,
            note_text text NOT NULL,
            private tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY person_id (person_id),
            KEY family_id (family_id),
            KEY source_id (source_id),
            KEY note_type (note_type),
            FULLTEXT KEY search_notes (subject, note_text)
        ) {$this->charset_collate};";

    dbDelta($sql);
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
   * Drop all tables (for uninstallation)
   */
  public function drop_tables()
  {    $tables = array(
      'persons',
      'families',
      'children',
      'events',
      'eventtypes',
      'sources',
      'citations',
      'places',
      'media',
      'medialinks',
      'repositories',
      'notes',
      'trees',
      'user_permissions',
      'import_logs',
      'associations',
      'addresses',
      'cemeteries',
      'albums',
      'albumlinks',      'xnotes',
      'notelinks',
      'mostwanted',
      'mediatypes',
      'states',
      'album2entities',
      'branches',
      'branchlinks',
      'countries',
      'dna_groups',
      'dna_links',
      'dna_tests'
    );

    foreach ($tables as $table) {
      $table_name = $this->get_table_name($table);
      $this->wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
  }

  /**
   * Get database statistics
   */
  public function get_statistics($tree_id = 'main')
  {
    $stats = array();    $tables = array(
      'persons' => 'People',
      'families' => 'Families',
      'children' => 'Children',
      'events' => 'Events',
      'sources' => 'Sources',
      'citations' => 'Citations',
      'media' => 'Media Items',
      'albums' => 'Albums',
      'xnotes' => 'Research Notes',
      'associations' => 'Associations',
      'mostwanted' => 'Research Items'
    );

    foreach ($tables as $table => $label) {
      $table_name = $this->get_table_name($table);
      $count = $this->wpdb->get_var(
        $this->wpdb->prepare(
          "SELECT COUNT(*) FROM $table_name WHERE tree_id = %s",
          $tree_id
        )
      );
      $stats[$table] = array(
        'label' => $label,
        'count' => (int) $count
      );
    }    return $stats;
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
   * Associations table - relationships between people
   */
  private function create_associations_table()
  {
    $table_name = $this->get_table_name('associations');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            person1_id int(11) NOT NULL,
            person2_id int(11) NOT NULL,
            relationship varchar(255) NOT NULL,
            description text,
            source_citation varchar(500) DEFAULT NULL,
            notes text,
            private tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY person1_id (person1_id),
            KEY person2_id (person2_id),
            KEY relationship (relationship)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Addresses table - physical addresses for people and places
   */
  private function create_addresses_table()
  {
    $table_name = $this->get_table_name('addresses');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            person_id int(11) DEFAULT NULL,
            repository_id int(11) DEFAULT NULL,
            address_type varchar(50) DEFAULT 'residence',
            address_line1 varchar(255) DEFAULT NULL,
            address_line2 varchar(255) DEFAULT NULL,
            city varchar(255) DEFAULT NULL,
            state varchar(255) DEFAULT NULL,
            postal_code varchar(50) DEFAULT NULL,
            country varchar(255) DEFAULT NULL,
            date_from varchar(100) DEFAULT NULL,
            date_to varchar(100) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            notes text,
            private tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY person_id (person_id),
            KEY repository_id (repository_id),
            KEY address_type (address_type),
            KEY city (city),
            KEY state (state),
            KEY country (country)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Cemeteries table - burial places and cemetery information
   */
  private function create_cemeteries_table()
  {
    $table_name = $this->get_table_name('cemeteries');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            cemetery_name varchar(500) NOT NULL,
            address_line1 varchar(255) DEFAULT NULL,
            address_line2 varchar(255) DEFAULT NULL,
            city varchar(255) DEFAULT NULL,
            state varchar(255) DEFAULT NULL,
            postal_code varchar(50) DEFAULT NULL,
            country varchar(255) DEFAULT NULL,
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            website varchar(500) DEFAULT NULL,
            cemetery_type varchar(100) DEFAULT NULL,
            established_date varchar(100) DEFAULT NULL,
            notes text,
            private tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY cemetery_name (cemetery_name),
            KEY city (city),
            KEY state (state),
            KEY country (country),
            KEY coordinates (latitude, longitude),
            FULLTEXT KEY search_cemetery (cemetery_name, city, state, country)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Albums table - photo albums and galleries
   */
  private function create_albums_table()
  {
    $table_name = $this->get_table_name('albums');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            album_name varchar(255) NOT NULL,
            description text,
            album_type varchar(50) DEFAULT 'general',
            cover_media_id int(11) DEFAULT NULL,
            sort_order int(11) DEFAULT 0,
            private tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY album_type (album_type),
            KEY sort_order (sort_order),
            KEY private (private),
            FULLTEXT KEY search_album (album_name, description)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Album links table - connect media to albums
   */
  private function create_albumlinks_table()
  {
    $table_name = $this->get_table_name('albumlinks');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            album_id int(11) NOT NULL,
            media_id int(11) NOT NULL,
            sort_order int(11) DEFAULT 0,
            caption text,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_album_media (album_id, media_id),
            KEY tree_id (tree_id),
            KEY album_id (album_id),
            KEY media_id (media_id),
            KEY sort_order (sort_order)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Extended notes table - additional note system
   */
  private function create_xnotes_table()
  {
    $table_name = $this->get_table_name('xnotes');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            note_title varchar(500) DEFAULT NULL,
            note_text longtext NOT NULL,
            note_category varchar(100) DEFAULT 'general',
            keywords varchar(500) DEFAULT NULL,
            private tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY note_category (note_category),
            KEY private (private),
            FULLTEXT KEY search_xnotes (note_title, note_text, keywords)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Note links table - connect extended notes to entities
   */
  private function create_notelinks_table()
  {
    $table_name = $this->get_table_name('notelinks');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            xnote_id int(11) NOT NULL,
            person_id int(11) DEFAULT NULL,
            family_id int(11) DEFAULT NULL,
            source_id int(11) DEFAULT NULL,
            event_id int(11) DEFAULT NULL,
            link_type varchar(50) DEFAULT 'general',
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY xnote_id (xnote_id),
            KEY person_id (person_id),
            KEY family_id (family_id),
            KEY source_id (source_id),
            KEY event_id (event_id),
            KEY link_type (link_type)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Most wanted table - research tracking
   */
  private function create_mostwanted_table()
  {
    $table_name = $this->get_table_name('mostwanted');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            person_id int(11) DEFAULT NULL,
            wanted_type varchar(100) NOT NULL,
            description text NOT NULL,
            priority enum('low','medium','high','urgent') DEFAULT 'medium',
            status enum('open','in_progress','resolved','closed') DEFAULT 'open',
            researcher_name varchar(255) DEFAULT NULL,
            researcher_email varchar(255) DEFAULT NULL,
            notes text,
            private tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY person_id (person_id),
            KEY wanted_type (wanted_type),
            KEY priority (priority),
            KEY status (status),
            FULLTEXT KEY search_wanted (description, researcher_name)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Media types table - organize media by type
   */
  private function create_mediatypes_table()
  {
    $table_name = $this->get_table_name('mediatypes');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            type_name varchar(100) NOT NULL,
            description varchar(255) DEFAULT NULL,
            file_extensions varchar(255) DEFAULT NULL,
            icon_class varchar(100) DEFAULT NULL,
            sort_order int(11) DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY type_name (type_name),
            KEY sort_order (sort_order),
            KEY active (active)
        ) {$this->charset_collate};";

    dbDelta($sql);

    // Insert default media types
    $this->insert_default_mediatypes();
  }

  /**
   * States/provinces table - geographic regions
   */
  private function create_states_table()
  {
    $table_name = $this->get_table_name('states');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            state_name varchar(255) NOT NULL,
            state_code varchar(10) DEFAULT NULL,
            country_code varchar(3) DEFAULT 'USA',
            sort_order int(11) DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY state_name (state_name),
            KEY state_code (state_code),
            KEY country_code (country_code),
            KEY sort_order (sort_order)
        ) {$this->charset_collate};";

    dbDelta($sql);

    // Insert default US states
    $this->insert_default_states();
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
   * Insert default media types
   */
  private function insert_default_mediatypes()
  {
    $table_name = $this->get_table_name('mediatypes');
    
    $default_types = array(
      array('Photo', 'Digital photographs and images', 'jpg,jpeg,png,gif,bmp,tiff', 'fas fa-image', 1),
      array('Document', 'Text documents and PDFs', 'pdf,doc,docx,txt,rtf', 'fas fa-file-alt', 2),
      array('Audio', 'Sound recordings and audio files', 'mp3,wav,m4a,wma,aac', 'fas fa-volume-up', 3),
      array('Video', 'Video recordings and movies', 'mp4,avi,mov,wmv,mkv', 'fas fa-video', 4),
      array('Certificate', 'Birth, death, marriage certificates', 'pdf,jpg,png,tiff', 'fas fa-certificate', 5),
      array('Newspaper', 'Newspaper clippings and articles', 'pdf,jpg,png,tiff', 'fas fa-newspaper', 6),
      array('Map', 'Maps and geographic documents', 'pdf,jpg,png,tiff', 'fas fa-map', 7),
      array('Other', 'Other types of media', '*', 'fas fa-file', 8)
    );

    foreach ($default_types as $type) {
      $this->wpdb->replace(
        $table_name,
        array(
          'type_name' => $type[0],
          'description' => $type[1], 
          'file_extensions' => $type[2],
          'icon_class' => $type[3],
          'sort_order' => $type[4],
          'active' => 1
        )
      );
    }
  }

  /**
   * Insert default US states
   */
  private function insert_default_states()
  {
    $table_name = $this->get_table_name('states');
    
    $states = array(
      array('Alabama', 'AL', 'USA', 1),
      array('Alaska', 'AK', 'USA', 2),
      array('Arizona', 'AZ', 'USA', 3),
      array('Arkansas', 'AR', 'USA', 4),
      array('California', 'CA', 'USA', 5),
      array('Colorado', 'CO', 'USA', 6),
      array('Connecticut', 'CT', 'USA', 7),
      array('Delaware', 'DE', 'USA', 8),
      array('Florida', 'FL', 'USA', 9),
      array('Georgia', 'GA', 'USA', 10),
      // Add more states as needed
      array('Hawaii', 'HI', 'USA', 11),
      array('Idaho', 'ID', 'USA', 12),
      array('Illinois', 'IL', 'USA', 13),
      array('Indiana', 'IN', 'USA', 14),
      array('Iowa', 'IA', 'USA', 15),
      array('Kansas', 'KS', 'USA', 16),
      array('Kentucky', 'KY', 'USA', 17),
      array('Louisiana', 'LA', 'USA', 18),
      array('Maine', 'ME', 'USA', 19),
      array('Maryland', 'MD', 'USA', 20),
      array('Massachusetts', 'MA', 'USA', 21),
      array('Michigan', 'MI', 'USA', 22),
      array('Minnesota', 'MN', 'USA', 23),
      array('Mississippi', 'MS', 'USA', 24),
      array('Missouri', 'MO', 'USA', 25),
      array('Montana', 'MT', 'USA', 26),
      array('Nebraska', 'NE', 'USA', 27),
      array('Nevada', 'NV', 'USA', 28),
      array('New Hampshire', 'NH', 'USA', 29),
      array('New Jersey', 'NJ', 'USA', 30),
      array('New Mexico', 'NM', 'USA', 31),
      array('New York', 'NY', 'USA', 32),
      array('North Carolina', 'NC', 'USA', 33),
      array('North Dakota', 'ND', 'USA', 34),
      array('Ohio', 'OH', 'USA', 35),
      array('Oklahoma', 'OK', 'USA', 36),
      array('Oregon', 'OR', 'USA', 37),
      array('Pennsylvania', 'PA', 'USA', 38),
      array('Rhode Island', 'RI', 'USA', 39),
      array('South Carolina', 'SC', 'USA', 40),
      array('South Dakota', 'SD', 'USA', 41),
      array('Tennessee', 'TN', 'USA', 42),
      array('Texas', 'TX', 'USA', 43),
      array('Utah', 'UT', 'USA', 44),
      array('Vermont', 'VT', 'USA', 45),
      array('Virginia', 'VA', 'USA', 46),
      array('Washington', 'WA', 'USA', 47),
      array('West Virginia', 'WV', 'USA', 48),
      array('Wisconsin', 'WI', 'USA', 49),
      array('Wyoming', 'WY', 'USA', 50)
    );

    foreach ($states as $state) {
      $this->wpdb->replace(
        $table_name,
        array(
          'state_name' => $state[0],
          'state_code' => $state[1], 
          'country_code' => $state[2],
          'sort_order' => $state[3],
          'active' => 1
        )
      );
    }
  }

  /**
   * Album to entities table - flexible album organization
   */
  private function create_album2entities_table()
  {
    $table_name = $this->get_table_name('album2entities');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            album_id int(11) NOT NULL,
            entity_type enum('person','family','source','media','event') NOT NULL,
            entity_id varchar(100) NOT NULL,
            event_id varchar(10) DEFAULT NULL,
            sort_order float DEFAULT 0,
            notes text,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_album_entity (tree_id, album_id, entity_type, entity_id),
            KEY album_id (album_id),
            KEY entity_type (entity_type),
            KEY entity_id (entity_id),
            KEY sort_order (sort_order)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Branches table - family tree branch organization
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
            branch_type varchar(50) DEFAULT 'paternal',
            color_code varchar(7) DEFAULT '#0073aa',
            active tinyint(1) DEFAULT 1,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY root_person_id (root_person_id),
            KEY branch_type (branch_type),
            KEY active (active),
            FULLTEXT KEY search_branch (branch_name, description)
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
            relationship_type varchar(50) DEFAULT 'member',
            generation int(11) DEFAULT 0,
            notes text,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_branch_person (branch_id, person_id),
            KEY tree_id (tree_id),
            KEY branch_id (branch_id),
            KEY person_id (person_id),
            KEY relationship_type (relationship_type),
            KEY generation (generation)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Countries table - international country lookup
   */
  private function create_countries_table()
  {
    $table_name = $this->get_table_name('countries');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            country_name varchar(255) NOT NULL,
            country_code char(2) NOT NULL,
            country_code3 char(3) DEFAULT NULL,
            numeric_code int(11) DEFAULT NULL,
            phone_code varchar(10) DEFAULT NULL,
            capital varchar(255) DEFAULT NULL,
            currency varchar(3) DEFAULT NULL,
            languages varchar(255) DEFAULT NULL,
            sort_order int(11) DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY country_code (country_code),
            UNIQUE KEY country_code3 (country_code3),
            KEY country_name (country_name),
            KEY sort_order (sort_order),
            KEY active (active)
        ) {$this->charset_collate};";

    dbDelta($sql);

    // Insert default countries
    $this->insert_default_countries();
  }

  /**
   * DNA groups table - DNA testing group management
   */
  private function create_dna_groups_table()
  {
    $table_name = $this->get_table_name('dna_groups');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            group_name varchar(255) NOT NULL,
            description text,
            haplogroup varchar(50) DEFAULT NULL,
            dna_type enum('autosomal','mitochondrial','y-chromosome','x-chromosome') DEFAULT 'autosomal',
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY dna_type (dna_type),
            KEY haplogroup (haplogroup),
            FULLTEXT KEY search_group (group_name, description)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * DNA links table - connect DNA tests to people
   */
  private function create_dna_links_table()
  {
    $table_name = $this->get_table_name('dna_links');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            dna_test_id int(11) NOT NULL,
            person_id int(11) NOT NULL,
            relationship_type varchar(50) DEFAULT 'tested',
            match_confidence enum('very_high','high','moderate','low','speculative') DEFAULT 'moderate',
            shared_cm decimal(8,2) DEFAULT NULL,
            notes text,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY dna_test_id (dna_test_id),
            KEY person_id (person_id),
            KEY relationship_type (relationship_type),
            KEY match_confidence (match_confidence)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * DNA tests table - DNA test records
   */
  private function create_dna_tests_table()
  {
    $table_name = $this->get_table_name('dna_tests');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            test_name varchar(255) NOT NULL,
            testing_company varchar(100) DEFAULT NULL,
            test_type enum('autosomal','mitochondrial','y-chromosome','x-chromosome','whole_genome') DEFAULT 'autosomal',
            kit_number varchar(100) DEFAULT NULL,
            test_date date DEFAULT NULL,
            tester_name varchar(255) DEFAULT NULL,
            tester_email varchar(255) DEFAULT NULL,
            haplogroup varchar(50) DEFAULT NULL,
            raw_data_file varchar(500) DEFAULT NULL,
            results_summary text,
            notes text,
            private tinyint(1) DEFAULT 1,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY testing_company (testing_company),
            KEY test_type (test_type),
            KEY kit_number (kit_number),
            KEY test_date (test_date),
            KEY haplogroup (haplogroup),
            FULLTEXT KEY search_test (test_name, tester_name, kit_number)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }
}
