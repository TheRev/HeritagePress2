<?php

/**
 * HeritagePress Media & Albums Database
 *
 * Handles media, albums, and multimedia content tables
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Database_Media
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
   * Create all media and album tables
   */
  public function create_tables()
  {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $this->create_media_table();
    $this->create_medialinks_table();
    $this->create_mediatypes_table();
    $this->create_albums_table();
    $this->create_albumlinks_table();
    $this->create_album2entities_table();
    $this->create_image_tags_table();
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
            is_cover tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_album_media (album_id, media_id),
            KEY tree_id (tree_id),
            KEY album_id (album_id),
            KEY media_id (media_id),
            KEY sort_order (sort_order),
            KEY is_cover (is_cover)
        ) {$this->charset_collate};";

    dbDelta($sql);
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
            entity_type varchar(50) NOT NULL,
            entity_id int(11) NOT NULL,
            event_id int(11) DEFAULT NULL,
            sort_order float DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_album_entity (tree_id, album_id, entity_type, entity_id),
            KEY tree_id (tree_id),
            KEY album_id (album_id),
            KEY entity_type (entity_type),
            KEY entity_id (entity_id),
            KEY sort_order (sort_order)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Image tags table - photo tagging system
   */
  private function create_image_tags_table()
  {
    $table_name = $this->get_table_name('image_tags');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            tree_id varchar(50) DEFAULT 'main',
            media_id int(11) NOT NULL,
            person_id int(11) DEFAULT NULL,
            tag_name varchar(255) DEFAULT NULL,
            x_coordinate int(11) DEFAULT NULL,
            y_coordinate int(11) DEFAULT NULL,
            width int(11) DEFAULT NULL,
            height int(11) DEFAULT NULL,
            description text,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            created_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tree_id (tree_id),
            KEY media_id (media_id),
            KEY person_id (person_id),
            KEY tag_name (tag_name)
        ) {$this->charset_collate};";

    dbDelta($sql);
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
   * Drop media and album tables
   */
  public function drop_tables()
  {
    $tables = array(
      'media', 'medialinks', 'mediatypes', 'albums', 
      'albumlinks', 'album2entities', 'image_tags'
    );

    foreach ($tables as $table) {
      $table_name = $this->get_table_name($table);
      $this->wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
  }

  /**
   * Get table statistics for media tables
   */
  public function get_table_stats()
  {
    $stats = array();
    $tables = array(
      'media', 'medialinks', 'mediatypes', 'albums', 
      'albumlinks', 'album2entities', 'image_tags'
    );

    foreach ($tables as $table) {
      $table_name = $this->get_table_name($table);
      $count = $this->wpdb->get_var("SELECT COUNT(*) FROM $table_name");
      $stats[$table] = (int)$count;
    }

    return $stats;
  }

}
