<?php

/**
 * TNG Data Import/Export Functionality
 *
 * Handles direct import from TNG databases and export to TNG format
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_TNG_Importer
{
  private $wpdb;
  private $mapper;
  private $tng_db;
  private $import_log = [];

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->mapper = new HP_TNG_Mapper();
  }

  /**
   * Connect to external TNG database for import
   */
  public function connect_tng_database($host, $database, $username, $password, $table_prefix = '')
  {
    try {
      $this->tng_db = new PDO(
        "mysql:host={$host};dbname={$database};charset=utf8",
        $username,
        $password,
        [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        ]
      );

      $this->log_message('success', "Connected to TNG database: {$database}");
      return true;
    } catch (PDOException $e) {
      $this->log_message('error', "Failed to connect to TNG database: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Import all data from connected TNG database
   */
  public function import_from_tng($gedcom_filter = '', $options = [])
  {
    if (!$this->tng_db) {
      throw new Exception('No TNG database connection established');
    }

    $import_stats = [
      'people' => 0,
      'families' => 0,
      'children' => 0,
      'events' => 0,
      'sources' => 0,
      'media' => 0,
      'citations' => 0,
      'errors' => 0
    ];

    // Start transaction for data integrity
    $this->wpdb->query('START TRANSACTION');

    try {
      // Import core genealogy data
      $import_stats['people'] = $this->import_people($gedcom_filter);
      $import_stats['families'] = $this->import_families($gedcom_filter);
      $import_stats['children'] = $this->import_children($gedcom_filter);
      $import_stats['events'] = $this->import_events($gedcom_filter);

      // Import supporting data
      if (!empty($options['import_sources'])) {
        $import_stats['sources'] = $this->import_sources($gedcom_filter);
        $import_stats['citations'] = $this->import_citations($gedcom_filter);
      }

      if (!empty($options['import_media'])) {
        $import_stats['media'] = $this->import_media($gedcom_filter);
      }

      // Commit transaction
      $this->wpdb->query('COMMIT');

      $this->log_message('success', 'TNG import completed successfully', $import_stats);
      return $import_stats;
    } catch (Exception $e) {
      // Rollback on error
      $this->wpdb->query('ROLLBACK');
      $this->log_message('error', 'TNG import failed: ' . $e->getMessage());
      throw $e;
    }
  }

  /**
   * Import people from TNG database
   */
  private function import_people($gedcom_filter = '')
  {
    $where_clause = $gedcom_filter ? "WHERE gedcom = :gedcom" : "";
    $sql = "SELECT * FROM people {$where_clause} ORDER BY ID";

    $stmt = $this->tng_db->prepare($sql);
    if ($gedcom_filter) {
      $stmt->bindParam(':gedcom', $gedcom_filter);
    }
    $stmt->execute();

    $count = 0;
    $table_name = $this->wpdb->prefix . 'hp_people';

    while ($person = $stmt->fetch()) {
      $mapped_person = $this->mapper->map_tng_person_to_hp($person);

      // Insert into HeritagePress people table
      $result = $this->wpdb->insert($table_name, $mapped_person);

      if ($result === false) {
        $this->log_message('error', "Failed to import person: {$person['personID']} - " . $this->wpdb->last_error);
      } else {
        $count++;
      }
    }

    return $count;
  }

  /**
   * Import families from TNG database
   */
  private function import_families($gedcom_filter = '')
  {
    $where_clause = $gedcom_filter ? "WHERE gedcom = :gedcom" : "";
    $sql = "SELECT * FROM families {$where_clause} ORDER BY ID";

    $stmt = $this->tng_db->prepare($sql);
    if ($gedcom_filter) {
      $stmt->bindParam(':gedcom', $gedcom_filter);
    }
    $stmt->execute();

    $count = 0;
    $table_name = $this->wpdb->prefix . 'hp_families';

    while ($family = $stmt->fetch()) {
      $mapped_family = $this->mapper->map_tng_family_to_hp($family);

      $result = $this->wpdb->insert($table_name, $mapped_family);

      if ($result === false) {
        $this->log_message('error', "Failed to import family: {$family['familyID']} - " . $this->wpdb->last_error);
      } else {
        $count++;
      }
    }

    return $count;
  }

  /**
   * Import children relationships from TNG database
   */
  private function import_children($gedcom_filter = '')
  {
    $where_clause = $gedcom_filter ? "WHERE gedcom = :gedcom" : "";
    $sql = "SELECT * FROM children {$where_clause} ORDER BY ID";

    $stmt = $this->tng_db->prepare($sql);
    if ($gedcom_filter) {
      $stmt->bindParam(':gedcom', $gedcom_filter);
    }
    $stmt->execute();

    $count = 0;
    $table_name = $this->wpdb->prefix . 'hp_children';

    while ($child = $stmt->fetch()) {
      $mapped_child = $this->mapper->map_tng_child_to_hp($child);

      $result = $this->wpdb->insert($table_name, $mapped_child);

      if ($result === false) {
        $this->log_message('error', "Failed to import child relationship: {$child['personID']} - " . $this->wpdb->last_error);
      } else {
        $count++;
      }
    }

    return $count;
  }

  /**
   * Import events from TNG database
   */
  private function import_events($gedcom_filter = '')
  {
    $where_clause = $gedcom_filter ? "WHERE gedcom = :gedcom" : "";
    $sql = "SELECT * FROM events {$where_clause} ORDER BY eventID";

    $stmt = $this->tng_db->prepare($sql);
    if ($gedcom_filter) {
      $stmt->bindParam(':gedcom', $gedcom_filter);
    }
    $stmt->execute();

    $count = 0;
    $table_name = $this->wpdb->prefix . 'hp_events';

    while ($event = $stmt->fetch()) {
      $mapped_event = $this->mapper->map_tng_event_to_hp($event);

      $result = $this->wpdb->insert($table_name, $mapped_event);

      if ($result === false) {
        $this->log_message('error', "Failed to import event: {$event['eventID']} - " . $this->wpdb->last_error);
      } else {
        $count++;
      }
    }

    return $count;
  }

  /**
   * Import sources from TNG database
   */
  private function import_sources($gedcom_filter = '')
  {
    $where_clause = $gedcom_filter ? "WHERE gedcom = :gedcom" : "";
    $sql = "SELECT * FROM sources {$where_clause}";

    $stmt = $this->tng_db->prepare($sql);
    if ($gedcom_filter) {
      $stmt->bindParam(':gedcom', $gedcom_filter);
    }
    $stmt->execute();

    $count = 0;
    $table_name = $this->wpdb->prefix . 'hp_sources';

    while ($source = $stmt->fetch()) {
      $mapped_source = $this->mapper->map_tng_source_to_hp($source);

      $result = $this->wpdb->insert($table_name, $mapped_source);

      if ($result === false) {
        $this->log_message('error', "Failed to import source: {$source['sourceID']} - " . $this->wpdb->last_error);
      } else {
        $count++;
      }
    }

    return $count;
  }

  /**
   * Import citations from TNG database
   */
  private function import_citations($gedcom_filter = '')
  {
    $where_clause = $gedcom_filter ? "WHERE gedcom = :gedcom" : "";
    $sql = "SELECT * FROM citations {$where_clause} ORDER BY citationID";

    $stmt = $this->tng_db->prepare($sql);
    if ($gedcom_filter) {
      $stmt->bindParam(':gedcom', $gedcom_filter);
    }
    $stmt->execute();

    $count = 0;
    $table_name = $this->wpdb->prefix . 'hp_citations';

    while ($citation = $stmt->fetch()) {
      $mapped_citation = $this->mapper->map_tng_citation_to_hp($citation);

      $result = $this->wpdb->insert($table_name, $mapped_citation);

      if ($result === false) {
        $this->log_message('error', "Failed to import citation: {$citation['citationID']} - " . $this->wpdb->last_error);
      } else {
        $count++;
      }
    }

    return $count;
  }

  /**
   * Import media from TNG database
   */
  private function import_media($gedcom_filter = '')
  {
    $where_clause = $gedcom_filter ? "WHERE gedcom = :gedcom" : "";
    $sql = "SELECT * FROM media {$where_clause} ORDER BY mediaID";

    $stmt = $this->tng_db->prepare($sql);
    if ($gedcom_filter) {
      $stmt->bindParam(':gedcom', $gedcom_filter);
    }
    $stmt->execute();

    $count = 0;
    $table_name = $this->wpdb->prefix . 'hp_media';

    while ($media = $stmt->fetch()) {
      $mapped_media = $this->mapper->map_tng_media_to_hp($media);

      $result = $this->wpdb->insert($table_name, $mapped_media);

      if ($result === false) {
        $this->log_message('error', "Failed to import media: {$media['mediaID']} - " . $this->wpdb->last_error);
      } else {
        $count++;
      }
    }

    return $count;
  }

  /**
   * Export HeritagePress data to TNG format
   */
  public function export_to_tng_format($gedcom = '', $output_path = '')
  {
    $export_data = [
      'people' => $this->export_people_data($gedcom),
      'families' => $this->export_families_data($gedcom),
      'children' => $this->export_children_data($gedcom),
      'events' => $this->export_events_data($gedcom),
      'sources' => $this->export_sources_data($gedcom),
      'citations' => $this->export_citations_data($gedcom)
    ];

    if ($output_path) {
      // Write to file as SQL dump
      $this->write_tng_sql_dump($export_data, $output_path);
    }

    return $export_data;
  }

  /**
   * Export people data in TNG format
   */
  private function export_people_data($gedcom = '')
  {
    $table_name = $this->wpdb->prefix . 'hp_people';
    $where_clause = $gedcom ? "WHERE gedcom = %s" : "";

    if ($gedcom) {
      $results = $this->wpdb->get_results(
        $this->wpdb->prepare("SELECT * FROM {$table_name} {$where_clause}", $gedcom),
        ARRAY_A
      );
    } else {
      $results = $this->wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A);
    }

    return $results;
  }

  /**
   * Export families data in TNG format
   */
  private function export_families_data($gedcom = '')
  {
    $table_name = $this->wpdb->prefix . 'hp_families';
    $where_clause = $gedcom ? "WHERE gedcom = %s" : "";

    if ($gedcom) {
      $results = $this->wpdb->get_results(
        $this->wpdb->prepare("SELECT * FROM {$table_name} {$where_clause}", $gedcom),
        ARRAY_A
      );
    } else {
      $results = $this->wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A);
    }

    return $results;
  }

  /**
   * Export children data in TNG format
   */
  private function export_children_data($gedcom = '')
  {
    $table_name = $this->wpdb->prefix . 'hp_children';
    $where_clause = $gedcom ? "WHERE gedcom = %s" : "";

    if ($gedcom) {
      $results = $this->wpdb->get_results(
        $this->wpdb->prepare("SELECT * FROM {$table_name} {$where_clause}", $gedcom),
        ARRAY_A
      );
    } else {
      $results = $this->wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A);
    }

    return $results;
  }

  /**
   * Export events data in TNG format
   */
  private function export_events_data($gedcom = '')
  {
    $table_name = $this->wpdb->prefix . 'hp_events';
    $where_clause = $gedcom ? "WHERE gedcom = %s" : "";

    if ($gedcom) {
      $results = $this->wpdb->get_results(
        $this->wpdb->prepare("SELECT * FROM {$table_name} {$where_clause}", $gedcom),
        ARRAY_A
      );
    } else {
      $results = $this->wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A);
    }

    return $results;
  }

  /**
   * Export sources data in TNG format
   */
  private function export_sources_data($gedcom = '')
  {
    $table_name = $this->wpdb->prefix . 'hp_sources';
    $where_clause = $gedcom ? "WHERE gedcom = %s" : "";

    if ($gedcom) {
      $results = $this->wpdb->get_results(
        $this->wpdb->prepare("SELECT * FROM {$table_name} {$where_clause}", $gedcom),
        ARRAY_A
      );
    } else {
      $results = $this->wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A);
    }

    return $results;
  }

  /**
   * Export citations data in TNG format
   */
  private function export_citations_data($gedcom = '')
  {
    $table_name = $this->wpdb->prefix . 'hp_citations';
    $where_clause = $gedcom ? "WHERE gedcom = %s" : "";

    if ($gedcom) {
      $results = $this->wpdb->get_results(
        $this->wpdb->prepare("SELECT * FROM {$table_name} {$where_clause}", $gedcom),
        ARRAY_A
      );
    } else {
      $results = $this->wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A);
    }

    return $results;
  }

  /**
   * Write export data as TNG-compatible SQL dump
   */
  private function write_tng_sql_dump($export_data, $output_path)
  {
    $sql_content = "-- HeritagePress to TNG Export\n";
    $sql_content .= "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($export_data as $table => $records) {
      if (empty($records)) continue;

      $sql_content .= "-- Table: {$table}\n";

      foreach ($records as $record) {
        $columns = array_keys($record);
        $values = array_map(function ($value) {
          return is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
        }, array_values($record));

        $sql_content .= "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
      }

      $sql_content .= "\n";
    }

    file_put_contents($output_path, $sql_content);
  }

  /**
   * Log import/export messages
   */
  private function log_message($type, $message, $data = null)
  {
    $log_entry = [
      'timestamp' => current_time('mysql'),
      'type' => $type,
      'message' => $message,
      'data' => $data
    ];

    $this->import_log[] = $log_entry;

    // Also log to WordPress debug log if enabled
    if (WP_DEBUG_LOG) {
      error_log("HeritagePress TNG Import [{$type}]: {$message}");
    }
  }

  /**
   * Get import/export log
   */
  public function get_log()
  {
    return $this->import_log;
  }

  /**
   * Clear import/export log
   */
  public function clear_log()
  {
    $this->import_log = [];
  }

  /**
   * Validate TNG database structure
   */
  public function validate_tng_database()
  {
    if (!$this->tng_db) {
      return ['valid' => false, 'error' => 'No database connection'];
    }

    $required_tables = ['people', 'families', 'children', 'events', 'eventtypes'];
    $validation_results = ['valid' => true, 'missing_tables' => [], 'issues' => []];

    foreach ($required_tables as $table) {
      try {
        $stmt = $this->tng_db->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() === 0) {
          $validation_results['missing_tables'][] = $table;
          $validation_results['valid'] = false;
        }
      } catch (PDOException $e) {
        $validation_results['issues'][] = "Error checking table {$table}: " . $e->getMessage();
        $validation_results['valid'] = false;
      }
    }

    return $validation_results;
  }
}
