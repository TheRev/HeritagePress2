<?php

/**
 * HeritagePress Sources & Documentation Database
 *
 * Handles sources, citations, repositories, and research tables
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Database_Sources
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
   * Create all sources and documentation tables
   */
  public function create_tables()
  {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $this->create_sources_table();
    $this->create_citations_table();
    $this->create_repositories_table();
    $this->create_notes_table();
    $this->create_xnotes_table();
    $this->create_notelinks_table();
    $this->create_mostwanted_table();
    $this->create_associations_table();
    $this->create_reports_table();
    $this->create_templates_table();
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
   * Reports table - generated reports
   */
  private function create_reports_table()
  {
    $table_name = $this->get_table_name('reports');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            report_name varchar(255) NOT NULL,
            report_type varchar(100) NOT NULL,
            description text,
            parameters text,
            output_format varchar(50) DEFAULT 'html',
            file_path varchar(500) DEFAULT NULL,
            is_public tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY report_type (report_type),
            KEY created_by (created_by),
            KEY is_public (is_public)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Templates table - customizable templates
   */
  private function create_templates_table()
  {
    $table_name = $this->get_table_name('templates');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            template_name varchar(255) NOT NULL,
            template_type varchar(100) NOT NULL,
            description text,
            template_content longtext NOT NULL,
            is_default tinyint(1) DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            modified_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY template_type (template_type),
            KEY is_default (is_default),
            KEY active (active)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Drop sources and documentation tables
   */
  public function drop_tables()
  {
    $tables = array(
      'sources',
      'citations',
      'repositories',
      'notes',
      'xnotes',
      'notelinks',
      'mostwanted',
      'associations',
      'reports',
      'templates'
    );

    foreach ($tables as $table) {
      $table_name = $this->get_table_name($table);
      $this->wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
  }

  /**
   * Get table statistics for sources/research tables
   */
  public function get_table_stats()
  {
    $stats = array();
    $tables = array(
      'sources',
      'citations',
      'repositories',
      'notes',
      'xnotes',
      'notelinks',
      'mostwanted',
      'associations',
      'reports',
      'templates'
    );

    foreach ($tables as $table) {
      $table_name = $this->get_table_name($table);
      $count = $this->wpdb->get_var("SELECT COUNT(*) FROM $table_name");
      $stats[$table] = (int)$count;
    }

    return $stats;
  }
}
