<?php

/**
 * HeritagePress GEDCOM Media Record Handler
 *
 * Handles processing of GEDCOM Media Object (OBJE) records
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_GEDCOM_Media extends HP_GEDCOM_Record_Base
{
  /**
   * WordPress uploads directory information
   *
   * @var array
   */
  private $wp_uploads;

  /**
   * Media structure information
   *
   * @var array
   */
  private $media_structure;

  /**
   * Constructor
   *
   * @param array $media_structure Media structure information
   */
  public function __construct($media_structure = array())
  {
    parent::__construct();

    // Get WordPress uploads directory
    $this->wp_uploads = wp_upload_dir();

    // Set media structure
    $this->media_structure = !empty($media_structure) ? $media_structure : array(
      'base_folder' => 'media',
      'path_pattern' => ''
    );
  }

  /**
   * Set media structure information
   *
   * @param array $media_structure Media structure information
   */
  public function set_media_structure($media_structure)
  {
    $this->media_structure = $media_structure;
  }

  /**
   * Process a media record
   *
   * @param array $record Record data
   * @return string|false Media ID or false on failure
   */
  public function process($record)
  {
    // Check if this is an OBJE record
    if (!isset($record['type']) || $record['type'] !== 'OBJE') {
      return false;
    }

    // Get the ID
    $gedcom_id = isset($record['id']) ? $record['id'] : '';
    if (empty($gedcom_id)) {
      return false;
    }

    // Check if already processed
    if (in_array($gedcom_id, $this->processed_ids)) {
      return $gedcom_id;
    }

    // Extract media data
    $media_data = $this->extract_media_data($record);

    // Handle the actual file
    $this->handle_media_file($media_data);

    // Insert into database
    $result = $this->insert_media($media_data);

    if ($result) {
      $this->processed_ids[] = $gedcom_id;

      // Process notes
      $this->process_notes($gedcom_id, $record);

      return $gedcom_id;
    }

    return false;
  }

  /**
   * Extract media data from GEDCOM record
   *
   * @param array $record Record data
   * @return array Media data
   */
  private function extract_media_data($record)
  {
    $media_data = array(
      'gedcom_id' => isset($record['id']) ? $record['id'] : '',
      'tree_id' => $this->tree_id,
      'title' => '',
      'file_path' => '',
      'file_name' => '',
      'form' => '', // File format
      'type' => '', // Media type (photo, document, etc.)
      'wp_attachment_id' => 0,
      'import_timestamp' => time(),
    );

    // Extract media details
    if (!empty($record['children'])) {
      foreach ($record['children'] as $child) {
        if (!isset($child['tag'])) {
          continue;
        }

        switch ($child['tag']) {
          case 'TITL':
            $media_data['title'] = isset($child['value']) ? $child['value'] : '';
            break;

          case 'FILE':
            if (!empty($child['value'])) {
              $media_data['file_path'] = $child['value'];
              $media_data['file_name'] = basename($child['value']);
            }
            break;

          case 'FORM':
            $media_data['form'] = isset($child['value']) ? $child['value'] : '';
            break;

          case 'TYPE':
            $media_data['type'] = isset($child['value']) ? $child['value'] : '';
            break;
        }
      }
    }

    // If no title, use filename
    if (empty($media_data['title']) && !empty($media_data['file_name'])) {
      $media_data['title'] = $media_data['file_name'];
    }

    return $media_data;
  }

  /**
   * Handle the media file (copy to WordPress uploads)
   *
   * @param array $media_data Media data
   */
  private function handle_media_file(&$media_data)
  {
    if (empty($media_data['file_path'])) {
      return;
    }

    // Determine source path based on program type
    $source_path = $this->resolve_media_path($media_data['file_path']);
    if (empty($source_path) || !file_exists($source_path)) {
      return;
    }

    // Create target directory if it doesn't exist
    $target_dir = $this->wp_uploads['basedir'] . '/heritagepress/' . $this->tree_id . '/media';
    if (!file_exists($target_dir)) {
      wp_mkdir_p($target_dir);
    }

    // Generate unique filename
    $file_info = pathinfo($media_data['file_name']);
    $file_ext = !empty($file_info['extension']) ? '.' . $file_info['extension'] : '';
    $file_base = !empty($file_info['filename']) ? $file_info['filename'] : 'media';
    $target_file = $target_dir . '/' . sanitize_file_name($file_base . $file_ext);

    // Ensure filename is unique
    $counter = 0;
    while (file_exists($target_file)) {
      $counter++;
      $target_file = $target_dir . '/' . sanitize_file_name($file_base . '-' . $counter . $file_ext);
    }

    // Copy the file
    if (copy($source_path, $target_file)) {
      // Store the relative path
      $media_data['wp_media_path'] = str_replace($this->wp_uploads['basedir'] . '/', '', $target_file);

      // Create WordPress attachment
      $attachment_id = $this->create_wp_attachment($target_file, $media_data['title']);
      if ($attachment_id) {
        $media_data['wp_attachment_id'] = $attachment_id;
      }
    }
  }

  /**
   * Create WordPress attachment for media file
   *
   * @param string $file_path Full path to file
   * @param string $title Title for attachment
   * @return int Attachment ID
   */
  private function create_wp_attachment($file_path, $title)
  {
    // Get the file type
    $file_type = wp_check_filetype(basename($file_path));

    // Prepare attachment data
    $attachment = array(
      'post_mime_type' => $file_type['type'],
      'post_title' => $title,
      'post_content' => '',
      'post_status' => 'inherit'
    );

    // Insert attachment into WordPress media library
    $attach_id = wp_insert_attachment($attachment, $file_path);

    // Generate attachment metadata
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
    wp_update_attachment_metadata($attach_id, $attach_data);

    return $attach_id;
  }

  /**
   * Resolve media file path based on program-specific patterns
   *
   * @param string $gedcom_path Path from GEDCOM
   * @return string Resolved absolute path
   */
  private function resolve_media_path($gedcom_path)
  {
    // Base media folder (from import options)
    $base_folder = !empty($this->media_structure['base_folder']) ? $this->media_structure['base_folder'] : 'media';

    // Try direct path first
    if (file_exists($gedcom_path)) {
      return $gedcom_path;
    }

    // Try relative to the base media folder
    $base_path = rtrim($base_folder, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $gedcom_path);
    if (file_exists($base_path)) {
      return $base_path;
    }

    // Try to apply program-specific path resolution
    $path_pattern = !empty($this->media_structure['path_pattern']) ? $this->media_structure['path_pattern'] : '';

    if (!empty($path_pattern)) {
      $resolved_path = str_replace('{MEDIA_PATH}', $gedcom_path, $path_pattern);
      if (file_exists($resolved_path)) {
        return $resolved_path;
      }
    }

    // Try Family Tree Maker pattern (if detected)
    $ftm_path = rtrim($base_folder, '/\\') . DIRECTORY_SEPARATOR . 'Files' . DIRECTORY_SEPARATOR . basename($gedcom_path);
    if (file_exists($ftm_path)) {
      return $ftm_path;
    }

    // Try RootsMagic pattern (if detected)
    $rm_path = rtrim($base_folder, '/\\') . DIRECTORY_SEPARATOR . 'Media' . DIRECTORY_SEPARATOR . basename($gedcom_path);
    if (file_exists($rm_path)) {
      return $rm_path;
    }

    // Last resort - just look for the file by name in the base folder
    $basename = basename($gedcom_path);
    $potential_paths = glob($base_folder . '/*/' . $basename);
    if (!empty($potential_paths) && file_exists($potential_paths[0])) {
      return $potential_paths[0];
    }

    return '';
  }

  /**
   * Insert media record into database
   *
   * @param array $media_data Media data
   * @return string|false Media ID or false on failure
   */
  private function insert_media($media_data)
  {
    // Ensure we have a table name with proper prefix
    $media_table = $this->db->prefix . 'hp_media';

    // Check if media already exists
    $existing_media = $this->db->get_row(
      $this->db->prepare(
        "SELECT * FROM $media_table WHERE gedcom_id = %s AND tree_id = %s",
        $media_data['gedcom_id'],
        $media_data['tree_id']
      )
    );

    if ($existing_media) {
      // Update existing record
      $this->db->update(
        $media_table,
        $media_data,
        array(
          'gedcom_id' => $media_data['gedcom_id'],
          'tree_id' => $media_data['tree_id']
        )
      );

      return $media_data['gedcom_id'];
    } else {
      // Insert new record
      $result = $this->db->insert($media_table, $media_data);

      if ($result) {
        return $media_data['gedcom_id'];
      }
    }

    return false;
  }

  /**
   * Process notes associated with media
   *
   * @param string $media_id Media ID
   * @param array $record Media record
   */
  private function process_notes($media_id, $record)
  {
    if (empty($record['children'])) {
      return;
    }

    $notes_table = $this->db->prefix . 'hp_xnotes';

    foreach ($record['children'] as $child) {
      if (isset($child['tag']) && $child['tag'] === 'NOTE' && !empty($child['value'])) {
        $this->db->insert(
          $notes_table,
          array(
            'entity_id' => $media_id,
            'entity_type' => 'media',
            'tree_id' => $this->tree_id,
            'note_text' => $child['value']
          )
        );
      }
    }
  }
}
