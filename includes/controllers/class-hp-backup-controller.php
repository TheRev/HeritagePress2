<?php

/**
 * HeritagePress Backup Controller
 *
 * Manages database backup operations for genealogy data
 *
 * @package    HeritagePress
 * @subpackage Controllers
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Backup_Controller
{

  /**
   * Table prefix
   *
   * @var string
   */
  private $table_prefix;

  /**
   * Default backup directory path
   *
   * @var string
   */
  private $backup_dir;

  /**
   * Chunk size for processing large tables
   *
   * @var int
   */
  private $chunk_size = 10000;

  /**
   * Constructor
   */
  public function __construct()
  {
    global $wpdb;
    $this->table_prefix = $wpdb->prefix . 'hp_';

    // Create backup directory if it doesn't exist
    $upload_dir = wp_upload_dir();
    $this->backup_dir = $upload_dir['basedir'] . '/heritagepress-backups';

    if (!file_exists($this->backup_dir)) {
      wp_mkdir_p($this->backup_dir);

      // Create an index.php file to prevent directory listing
      $index_file = $this->backup_dir . '/index.php';
      if (!file_exists($index_file)) {
        file_put_contents($index_file, "<?php\n// Silence is golden.");
      }

      // Create .htaccess for additional security
      $htaccess_file = $this->backup_dir . '/.htaccess';
      if (!file_exists($htaccess_file)) {
        file_put_contents($htaccess_file, "# Prevent directory listing\nOptions -Indexes\n\n# Prevent direct access to files\n<FilesMatch \"\\.(sql|bak)\$\">\nOrder Allow,Deny\nDeny from all\n</FilesMatch>");
      }
    }
  }

  /**
   * Get list of all genealogy tables
   *
   * @return array List of table names without prefix
   */
  public function get_genealogy_tables()
  {
    return [
      'addresses',
      'albumlinks',
      'albums',
      'associations',
      'branches',
      'branchlinks',
      'cemeteries',
      'children',
      'citations',
      'countries',
      'dna_groups',
      'dna_links',
      'dna_tests',
      'events',
      'eventtypes',
      'families',
      'image_tags',
      'languages',
      'media',
      'medialinks',
      'mediatypes',
      'mostwanted',
      'notelinks',
      'people',
      'places',
      'reports',
      'repositories',
      'sources',
      'states',
      'templates',
      'timeevents',
      'trees',
      'xnotes',
    ];
  }

  /**
   * Create backup of a single table
   *
   * @param string $table Table name without prefix
   * @param bool $include_sql Use SQL format (true) or CSV format (false)
   * @param bool $include_create Include CREATE TABLE statement
   * @param bool $include_drop Include DROP TABLE statement
   * @return array Result information including status and message
   */
  public function backup_table($table, $include_sql = true, $include_create = true, $include_drop = true)
  {
    global $wpdb;

    $full_table_name = $this->table_prefix . $table;
    $extension = $include_sql ? 'sql' : 'bak';
    $filename = $this->backup_dir . '/' . $table . '.' . $extension;

    // Delete existing backup file if it exists
    if (file_exists($filename)) {
      unlink($filename);
    }

    $handle = @fopen($filename, 'w');
    if (!$handle) {
      return [
        'success' => false,
        'message' => __('Cannot open file for writing', 'heritagepress') . ': ' . $filename,
      ];
    }

    // For SQL format backups
    if ($include_sql) {
      // Add DROP TABLE statement if requested
      if ($include_drop) {
        fwrite($handle, "DROP TABLE IF EXISTS `$full_table_name`;\n");
      }

      // Add CREATE TABLE statement if requested
      if ($include_create) {
        $create_table_query = $wpdb->get_results("SHOW CREATE TABLE `$full_table_name`", ARRAY_N);
        if (!empty($create_table_query[0][1])) {
          fwrite($handle, $create_table_query[0][1] . ";\n\n");
        }
      }
    }

    // Get column information
    $columns = $wpdb->get_results("DESCRIBE `$full_table_name`");
    $column_names = [];
    $column_types = [];

    foreach ($columns as $column) {
      $column_names[] = $column->Field;

      // Determine field type category for proper handling
      if (strpos($column->Type, 'int') !== false) {
        $column_types[$column->Field] = 'int';
      } elseif (strpos($column->Type, 'datetime') !== false || strpos($column->Type, 'timestamp') !== false) {
        $column_types[$column->Field] = 'datetime';
      } else {
        $column_types[$column->Field] = 'string';
      }
    }

    // For non-SQL format backups, write header row
    if (!$include_sql) {
      fwrite($handle, '"' . implode('","', $column_names) . '"' . "\n");
    }

    // Process data in chunks
    $offset = 0;
    $more_rows = true;
    $first_row = true;

    while ($more_rows) {
      $query = $wpdb->prepare("SELECT * FROM `$full_table_name` LIMIT %d, %d", $offset, $this->chunk_size);
      $rows = $wpdb->get_results($query, ARRAY_A);

      $row_count = count($rows);
      $more_rows = ($row_count == $this->chunk_size);

      if ($row_count > 0) {
        if ($include_sql && $first_row) {
          fwrite($handle, "INSERT INTO `$full_table_name` (`" . implode('`, `', $column_names) . "`) VALUES\n");
          $first_row = false;
        }

        foreach ($rows as $i => $row) {
          $values = [];

          foreach ($column_names as $column) {
            // Handle NULL values
            if (is_null($row[$column])) {
              if ($include_sql) {
                $values[] = 'NULL';
              } else {
                $values[] = '';
              }
              continue;
            }

            // Format based on column type
            if ($column_types[$column] === 'int') {
              // For integers, ensure non-empty values
              $value = $row[$column] === '' ? '0' : $row[$column];
              $values[] = $include_sql ? $value : $value;
            } elseif ($column_types[$column] === 'datetime') {
              // For datetime fields, ensure valid dates
              $value = $row[$column] === '' ? '0000-00-00 00:00:00' : $row[$column];
              $values[] = $include_sql ? "'$value'" : $value;
            } else {
              // For strings, properly escape
              $value = str_replace("\r", "\\r", str_replace("\n", "\\n", addslashes($row[$column])));
              $values[] = $include_sql ? "'$value'" : $value;
            }
          }

          if ($include_sql) {
            fwrite($handle, ($i === 0 ? '' : ",\n") . "(" . implode(", ", $values) . ")");
          } else {
            fwrite($handle, '"' . implode('","', $row) . '"' . "\n");
          }
        }

        if (!$include_sql) {
          fwrite($handle, "\n");
        }
      }

      $offset += $this->chunk_size;
    }

    // Close SQL statement
    if ($include_sql && !$first_row) {
      fwrite($handle, ";\n");
    }

    fclose($handle);

    // Get file size and time
    $file_size = filesize($filename);
    $file_time = filemtime($filename);

    return [
      'success' => true,
      'message' => __('Backup completed successfully', 'heritagepress'),
      'filename' => basename($filename),
      'filepath' => $filename,
      'filesize' => $this->format_file_size($file_size),
      'timestamp' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $file_time),
      'download_url' => admin_url('admin-ajax.php?action=hp_download_backup&file=' . basename($filename) . '&_wpnonce=' . wp_create_nonce('hp_download_backup'))
    ];
  }

  /**
   * Create backup of the database structure (all tables)
   *
   * @return array Result information
   */
  public function backup_structure()
  {
    global $wpdb;

    $filename = $this->backup_dir . '/heritagepress_tablestructure.sql';

    // Delete existing backup file if it exists
    if (file_exists($filename)) {
      unlink($filename);
    }

    $handle = @fopen($filename, 'w');
    if (!$handle) {
      return [
        'success' => false,
        'message' => __('Cannot open file for writing', 'heritagepress') . ': ' . $filename,
      ];
    }

    $tables = $this->get_genealogy_tables();

    foreach ($tables as $table) {
      $full_table_name = $this->table_prefix . $table;

      // Add DROP TABLE statement
      fwrite($handle, "DROP TABLE IF EXISTS `$full_table_name`;\n");

      // Add CREATE TABLE statement
      $create_table_query = $wpdb->get_results("SHOW CREATE TABLE `$full_table_name`", ARRAY_N);
      if (!empty($create_table_query[0][1])) {
        fwrite($handle, $create_table_query[0][1] . ";\n\n");
      }
    }

    fclose($handle);

    // Get file size and time
    $file_size = filesize($filename);
    $file_time = filemtime($filename);

    return [
      'success' => true,
      'message' => __('Table structure backup completed successfully', 'heritagepress'),
      'filename' => basename($filename),
      'filepath' => $filename,
      'filesize' => $this->format_file_size($file_size),
      'timestamp' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $file_time),
      'download_url' => admin_url('admin-ajax.php?action=hp_download_backup&file=' . basename($filename) . '&_wpnonce=' . wp_create_nonce('hp_download_backup'))
    ];
  }

  /**
   * Delete a backup file
   *
   * @param string $table Table name or 'structure'
   * @return array Result information
   */
  public function delete_backup($table)
  {
    $sql_file = $this->backup_dir . '/' . $table . '.sql';
    $bak_file = $this->backup_dir . '/' . $table . '.bak';

    $deleted = false;

    if (file_exists($sql_file)) {
      unlink($sql_file);
      $deleted = true;
    }

    if (file_exists($bak_file)) {
      unlink($bak_file);
      $deleted = true;
    }

    if ($deleted) {
      return [
        'success' => true,
        'message' => __('Backup deleted successfully', 'heritagepress')
      ];
    } else {
      return [
        'success' => false,
        'message' => __('No backup file found to delete', 'heritagepress')
      ];
    }
  }

  /**
   * Get backup file information
   *
   * @param string $table Table name
   * @return array File information
   */
  public function get_backup_info($table)
  {
    $sql_file = $this->backup_dir . '/' . $table . '.sql';
    $bak_file = $this->backup_dir . '/' . $table . '.bak';

    $file_path = file_exists($sql_file) ? $sql_file : (file_exists($bak_file) ? $bak_file : null);

    if (!$file_path) {
      return [
        'exists' => false,
      ];
    }

    $file_size = filesize($file_path);
    $file_time = filemtime($file_path);
    $extension = pathinfo($file_path, PATHINFO_EXTENSION);

    return [
      'exists' => true,
      'filename' => basename($file_path),
      'filepath' => $file_path,
      'extension' => $extension,
      'filesize' => $this->format_file_size($file_size),
      'timestamp' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $file_time),
      'download_url' => admin_url('admin-ajax.php?action=hp_download_backup&file=' . basename($file_path) . '&_wpnonce=' . wp_create_nonce('hp_download_backup'))
    ];
  }

  /**
   * Get list of backup files
   *
   * @return array List of backup files with metadata
   */
  public function get_backup_files()
  {
    $backup_dir = $this->get_backup_dir();
    $files = array();

    if (is_dir($backup_dir)) {
      $dir_contents = scandir($backup_dir);

      foreach ($dir_contents as $file) {
        // Skip directories and non-backup files
        if (is_dir($backup_dir . '/' . $file) || !preg_match('/\.sql$|\.bak$/i', $file)) {
          continue;
        }

        $file_path = $backup_dir . '/' . $file;
        $size = filesize($file_path);
        $date = filemtime($file_path);

        $files[] = array(
          'name' => $file,
          'path' => $file_path,
          'size' => $size,
          'formatted_size' => $this->format_file_size($size),
          'date' => $date,
          'formatted_date' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $date)
        );
      }

      // Sort by date, newest first
      usort($files, function ($a, $b) {
        return $b['date'] - $a['date'];
      });
    }

    return $files;
  }

  /**
   * Get list of structure files
   *
   * @return array List of structure files with metadata
   */
  public function get_structure_files()
  {
    $backup_dir = $this->get_backup_dir();
    $files = array();

    if (is_dir($backup_dir)) {
      $dir_contents = scandir($backup_dir);

      foreach ($dir_contents as $file) {
        // Skip directories and non-structure files
        if (is_dir($backup_dir . '/' . $file) || !preg_match('/structure.*\.sql$|structure.*\.bak$/i', $file)) {
          continue;
        }

        $file_path = $backup_dir . '/' . $file;
        $size = filesize($file_path);
        $date = filemtime($file_path);

        $files[] = array(
          'name' => $file,
          'path' => $file_path,
          'size' => $size,
          'formatted_size' => $this->format_file_size($size),
          'date' => $date,
          'formatted_date' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $date)
        );
      }

      // Sort by date, newest first
      usort($files, function ($a, $b) {
        return $b['date'] - $a['date'];
      });
    }

    return $files;
  }

  /**
   * Get list of genealogy tables
   *
   * @return array Array of table names
   */
  public function get_tables()
  {
    return $this->get_genealogy_tables();
  }

  /**
   * Format file size in human-readable format
   *
   * @param int $size File size in bytes
   * @return string Formatted file size
   */
  private function format_file_size($size)
  {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $power = $size > 0 ? floor(log($size, 1024)) : 0;
    return sprintf('%.2f %s', $size / pow(1024, $power), $units[$power]);
  }
}
