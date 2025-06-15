<?php

/**
 * GEDCOM Import AJAX Callbacks
 *
 * AJAX functions for the GEDCOM import process
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * AJAX callback to handle the GEDCOM file upload
 */
function hp_ajax_upload_gedcom()
{
  // Check nonce
  check_ajax_referer('heritagepress_gedcom_upload', 'gedcom_upload_nonce');

  // Check if user has permission
  if (!current_user_can('manage_options')) {
    wp_send_json_error(__('You do not have permission to import GEDCOM files.', 'heritagepress'));
    return;
  }

  // Check if file was uploaded
  if (!isset($_FILES['gedcom_file']) || $_FILES['gedcom_file']['error'] !== UPLOAD_ERR_OK) {
    wp_send_json_error(__('No file was uploaded or there was an upload error.', 'heritagepress'));
    return;
  }

  // Get the file extension
  $file_extension = strtolower(pathinfo($_FILES['gedcom_file']['name'], PATHINFO_EXTENSION));
  if (!in_array($file_extension, array('ged', 'gedcom'))) {
    wp_send_json_error(__('Invalid file type. Only .ged or .gedcom files are allowed.', 'heritagepress'));
    return;
  }

  // Create uploads directory if it doesn't exist
  $upload_dir = wp_upload_dir();
  $gedcom_dir = $upload_dir['basedir'] . '/heritagepress/gedcom';

  if (!file_exists($gedcom_dir)) {
    wp_mkdir_p($gedcom_dir);
  }

  // Generate unique filename
  $filename = uniqid('gedcom_') . '.ged';
  $file_path = $gedcom_dir . '/' . $filename;

  // Move uploaded file to destination
  if (!move_uploaded_file($_FILES['gedcom_file']['tmp_name'], $file_path)) {
    wp_send_json_error(__('Failed to save the uploaded file.', 'heritagepress'));
    return;
  }

  // Get tree information
  $tree_destination = isset($_POST['tree_destination']) ? sanitize_text_field($_POST['tree_destination']) : 'new';
  $tree_id = '';
  $tree_name = '';

  if ($tree_destination === 'new') {
    $tree_id = isset($_POST['tree_id']) ? sanitize_text_field($_POST['tree_id']) : 'main';
    $tree_name = isset($_POST['tree_name']) ? sanitize_text_field($_POST['tree_name']) : __('My Family Tree', 'heritagepress');
  } else {
    $tree_id = isset($_POST['existing_tree_id']) ? sanitize_text_field($_POST['existing_tree_id']) : '';
    $tree = hp_get_tree_by_id($tree_id);
    $tree_name = $tree ? $tree['name'] : '';
  }

  // Get character set
  $character_set = isset($_POST['character_set']) ? sanitize_text_field($_POST['character_set']) : 'auto';

  // Store upload data in session
  $_SESSION['hp_gedcom_upload'] = array(
    'file_path' => $file_path,
    'original_name' => sanitize_text_field($_FILES['gedcom_file']['name']),
    'tree_destination' => $tree_destination,
    'tree_id' => $tree_id,
    'tree_name' => $tree_name,
    'character_set' => $character_set,
    'timestamp' => time()
  );

  // Redirect to validation step
  wp_send_json_success(array(
    'redirect' => admin_url('admin.php?page=heritagepress&section=import-export&tab=gedcom-import&step=validate')
  ));
}
add_action('wp_ajax_hp_upload_gedcom', 'hp_ajax_upload_gedcom');

/**
 * AJAX callback to validate the GEDCOM file
 */
function hp_ajax_validate_gedcom()
{
  // Check nonce
  check_ajax_referer('heritagepress_gedcom_validate', 'gedcom_validate_nonce');

  // Check if user has permission
  if (!current_user_can('manage_options')) {
    wp_send_json_error(__('You do not have permission to validate GEDCOM files.', 'heritagepress'));
    return;
  }

  // Get file path
  $file_path = isset($_POST['file_path']) ? sanitize_text_field($_POST['file_path']) : '';
  if (empty($file_path) || !file_exists($file_path)) {
    wp_send_json_error(__('Invalid file path or file does not exist.', 'heritagepress'));
    return;
  }

  // Create validator instance
  $validator = new HP_GEDCOM_Validator($file_path);

  // Validate the file
  $validation_results = $validator->validate();

  // Store validation results in session
  $_SESSION['hp_gedcom_validation'] = $validation_results;

  // Redirect to the configuration step
  wp_send_json_success(array(
    'redirect' => admin_url('admin.php?page=heritagepress&section=import-export&tab=gedcom-import&step=validate')
  ));
}
add_action('wp_ajax_hp_validate_gedcom', 'hp_ajax_validate_gedcom');

/**
 * AJAX callback to save GEDCOM import configuration
 */
function hp_ajax_save_gedcom_config()
{
  // Check nonce
  check_ajax_referer('heritagepress_gedcom_config', 'gedcom_config_nonce');

  // Check if user has permission
  if (!current_user_can('manage_options')) {
    wp_send_json_error(__('You do not have permission to configure GEDCOM import.', 'heritagepress'));
    return;
  }

  // Get config data from form
  $config_data = array(
    'import_type' => isset($_POST['import_type']) ? sanitize_text_field($_POST['import_type']) : 'all',
    'import_individuals' => isset($_POST['import_individuals']),
    'import_families' => isset($_POST['import_families']),
    'import_sources' => isset($_POST['import_sources']),
    'import_repositories' => isset($_POST['import_repositories']),
    'import_notes' => isset($_POST['import_notes']),
    'import_media' => isset($_POST['import_media']),
    'import_places' => isset($_POST['import_places']),
    'import_events' => isset($_POST['import_events']),
    'import_custom_events' => isset($_POST['import_custom_events']),
    'living_option' => isset($_POST['living_option']) ? sanitize_text_field($_POST['living_option']) : 'import_partial',
    'apply_privacy' => isset($_POST['apply_privacy']),
    'years_death' => isset($_POST['years_death']) ? intval($_POST['years_death']) : 5,
    'years_birth' => isset($_POST['years_birth']) ? intval($_POST['years_birth']) : 110,
    'private_notes' => isset($_POST['private_notes']),
    'private_media' => isset($_POST['private_media']),
    'private_sources' => isset($_POST['private_sources'])
  );

  // Store config data in session
  $_SESSION['hp_gedcom_config'] = $config_data;

  // Redirect to people step
  wp_send_json_success(array(
    'redirect' => admin_url('admin.php?page=heritagepress&section=import-export&tab=gedcom-import&step=people')
  ));
}
add_action('wp_ajax_hp_save_gedcom_config', 'hp_ajax_save_gedcom_config');

/**
 * AJAX callback to save people settings
 */
function hp_ajax_save_gedcom_people()
{
  // Check nonce
  check_ajax_referer('heritagepress_gedcom_people', 'gedcom_people_nonce');

  // Check if user has permission
  if (!current_user_can('manage_options')) {
    wp_send_json_error(__('You do not have permission to configure GEDCOM import.', 'heritagepress'));
    return;
  }

  // Get people data from form
  $people_data = array(
    'name_format' => isset($_POST['name_format']) ? sanitize_text_field($_POST['name_format']) : 'surname_first',
    'capitalize_surnames' => isset($_POST['capitalize_surnames']),
    'extract_nicknames' => isset($_POST['extract_nicknames']),
    'import_alternate_names' => isset($_POST['import_alternate_names']),
    'import_name_prefixes' => isset($_POST['import_name_prefixes']),
    'import_name_suffixes' => isset($_POST['import_name_suffixes']),
    'import_birth' => isset($_POST['import_birth']),
    'import_christening' => isset($_POST['import_christening']),
    'import_death' => isset($_POST['import_death']),
    'import_burial' => isset($_POST['import_burial']),
    'import_occupation' => isset($_POST['import_occupation']),
    'import_education' => isset($_POST['import_education']),
    'import_residence' => isset($_POST['import_residence']),
    'import_religion' => isset($_POST['import_religion']),
    'import_military' => isset($_POST['import_military']),
    'import_medical' => isset($_POST['import_medical']),
    'import_physical' => isset($_POST['import_physical']),
    'import_immigration' => isset($_POST['import_immigration']),
    'merge_same_type_events' => isset($_POST['merge_same_type_events']),
    'standardize_event_names' => isset($_POST['standardize_event_names']),
    'import_marriage' => isset($_POST['import_marriage']),
    'import_divorce' => isset($_POST['import_divorce']),
    'import_engagement' => isset($_POST['import_engagement']),
    'import_family_events' => isset($_POST['import_family_events']),
    'import_parent_relationships' => isset($_POST['import_parent_relationships']),
    'import_spouse_relationships' => isset($_POST['import_spouse_relationships']),
    'import_sibling_relationships' => isset($_POST['import_sibling_relationships']),
    'import_step_relationships' => isset($_POST['import_step_relationships']),
    'import_adoption' => isset($_POST['import_adoption']),
    'import_relationship_notes' => isset($_POST['import_relationship_notes']),
    'merge_individuals' => isset($_POST['merge_individuals']),
    'link_existing_sources' => isset($_POST['link_existing_sources']),
    'id_handling' => isset($_POST['id_handling']) ? sanitize_text_field($_POST['id_handling']) : 'preserve',
    'id_prefix' => isset($_POST['id_prefix']) ? sanitize_text_field($_POST['id_prefix']) : 'HP_'
  );

  // Store people data in session
  $_SESSION['hp_gedcom_people'] = $people_data;

  // Redirect to media step
  wp_send_json_success(array(
    'redirect' => admin_url('admin.php?page=heritagepress&section=import-export&tab=gedcom-import&step=media')
  ));
}
add_action('wp_ajax_hp_save_gedcom_people', 'hp_ajax_save_gedcom_people');

/**
 * AJAX callback to save media settings
 */
function hp_ajax_save_gedcom_media()
{
  // Check nonce
  check_ajax_referer('heritagepress_gedcom_media', 'gedcom_media_nonce');

  // Check if user has permission
  if (!current_user_can('manage_options')) {
    wp_send_json_error(__('You do not have permission to configure GEDCOM import.', 'heritagepress'));
    return;
  }

  // Get media data from form
  $media_data = array(
    'import_media' => isset($_POST['import_media']) ? sanitize_text_field($_POST['import_media']) : 'all',
    'media_source' => isset($_POST['media_source']) ? sanitize_text_field($_POST['media_source']) : 'local',
    'local_media_path' => isset($_POST['local_media_path']) ? sanitize_text_field($_POST['local_media_path']) : '',
    'media_destination' => isset($_POST['media_destination']) ? sanitize_text_field($_POST['media_destination']) : '',
    'media_folder_structure' => isset($_POST['media_folder_structure']) ? sanitize_text_field($_POST['media_folder_structure']) : 'preserve',
    'file_handling' => isset($_POST['file_handling']) ? sanitize_text_field($_POST['file_handling']) : 'copy',
    'import_images' => isset($_POST['import_images']),
    'import_documents' => isset($_POST['import_documents']),
    'import_audio' => isset($_POST['import_audio']),
    'import_video' => isset($_POST['import_video']),
    'resize_images' => isset($_POST['resize_images']),
    'max_image_size' => isset($_POST['max_image_size']) ? intval($_POST['max_image_size']) : 1200,
    'generate_thumbnails' => isset($_POST['generate_thumbnails']),
    'extract_metadata' => isset($_POST['extract_metadata'])
  );

  // Store media data in session
  $_SESSION['hp_gedcom_media'] = $media_data;

  // Redirect to places step
  wp_send_json_success(array(
    'redirect' => admin_url('admin.php?page=heritagepress&section=import-export&tab=gedcom-import&step=places')
  ));
}
add_action('wp_ajax_hp_save_gedcom_media', 'hp_ajax_save_gedcom_media');

/**
 * AJAX callback to save places settings
 */
function hp_ajax_save_gedcom_places()
{
  // Check nonce
  check_ajax_referer('heritagepress_gedcom_places', 'gedcom_places_nonce');

  // Check if user has permission
  if (!current_user_can('manage_options')) {
    wp_send_json_error(__('You do not have permission to configure GEDCOM import.', 'heritagepress'));
    return;
  }

  // Get places data from form
  $places_data = array(
    'place_handling' => isset($_POST['place_handling']) ? sanitize_text_field($_POST['place_handling']) : 'exact',
    'place_format' => isset($_POST['place_format']) ? sanitize_text_field($_POST['place_format']) : 'original',
    'place_separator' => isset($_POST['place_separator']) ? sanitize_text_field($_POST['place_separator']) : 'comma',
    'extract_place_hierarchy' => isset($_POST['extract_place_hierarchy']),
    'index_places' => isset($_POST['index_places']),
    'geocode_places' => isset($_POST['geocode_places']),
    'geocode_service' => isset($_POST['geocode_service']) ? sanitize_text_field($_POST['geocode_service']) : 'nominatim',
    'google_maps_api_key' => isset($_POST['google_maps_api_key']) ? sanitize_text_field($_POST['google_maps_api_key']) : '',
    'geocode_priority' => isset($_POST['geocode_priority']) ? sanitize_text_field($_POST['geocode_priority']) : 'important',
    'default_map_type' => isset($_POST['default_map_type']) ? sanitize_text_field($_POST['default_map_type']) : 'road',
    'show_place_markers' => isset($_POST['show_place_markers']),
    'cluster_markers' => isset($_POST['cluster_markers'])
  );

  // Store places data in session
  $_SESSION['hp_gedcom_places'] = $places_data;

  // Redirect to process step
  wp_send_json_success(array(
    'redirect' => admin_url('admin.php?page=heritagepress&section=import-export&tab=gedcom-import&step=process')
  ));
}
add_action('wp_ajax_hp_save_gedcom_places', 'hp_ajax_save_gedcom_places');

/**
 * AJAX callback to process the GEDCOM import
 */
function hp_ajax_process_gedcom_import()
{
  // Check nonce
  check_ajax_referer('heritagepress_gedcom_import', 'gedcom_import_nonce');

  // Check if user has permission
  if (!current_user_can('manage_options')) {
    wp_send_json_error(__('You do not have permission to import GEDCOM files.', 'heritagepress'));
    return;
  }

  // Get file path
  $file_path = isset($_POST['file_path']) ? sanitize_text_field($_POST['file_path']) : '';
  if (empty($file_path) || !file_exists($file_path)) {
    wp_send_json_error(__('Invalid file path or file does not exist.', 'heritagepress'));
    return;
  }

  // Get all saved settings from session
  $upload_data = isset($_SESSION['hp_gedcom_upload']) ? $_SESSION['hp_gedcom_upload'] : array();
  $validation_results = isset($_SESSION['hp_gedcom_validation']) ? $_SESSION['hp_gedcom_validation'] : array();
  $config_data = isset($_SESSION['hp_gedcom_config']) ? $_SESSION['hp_gedcom_config'] : array();
  $people_data = isset($_SESSION['hp_gedcom_people']) ? $_SESSION['hp_gedcom_people'] : array();
  $media_data = isset($_SESSION['hp_gedcom_media']) ? $_SESSION['hp_gedcom_media'] : array();
  $places_data = isset($_SESSION['hp_gedcom_places']) ? $_SESSION['hp_gedcom_places'] : array();

  // Generate import key for tracking progress
  $import_key = md5($file_path . time());

  // Start the import process as a background task
  wp_schedule_single_event(time(), 'hp_process_gedcom_import_bg', array(
    'import_key' => $import_key,
    'file_path' => $file_path,
    'upload_data' => $upload_data,
    'validation_results' => $validation_results,
    'config_data' => $config_data,
    'people_data' => $people_data,
    'media_data' => $media_data,
    'places_data' => $places_data
  ));

  // Save initial progress data
  set_transient('hp_gedcom_import_progress_' . $import_key, array(
    'started' => time(),
    'percent' => 0,
    'status' => __('Starting import...', 'heritagepress'),
    'current_operation' => __('Initializing import process', 'heritagepress'),
    'processed_count' => 0,
    'total_count' => isset($validation_results['stats']) ? array_sum($validation_results['stats']) : 0,
    'log_entries' => array(
      __('Import process started', 'heritagepress')
    ),
    'complete' => false
  ), 60 * 60 * 2); // Keep for 2 hours

  // Return import key
  wp_send_json_success(array(
    'import_key' => $import_key
  ));
}
add_action('wp_ajax_hp_process_gedcom_import', 'hp_ajax_process_gedcom_import');

/**
 * AJAX callback to check import progress
 */
function hp_ajax_check_import_progress()
{
  // Check nonce
  check_ajax_referer('hp_check_import_progress', 'nonce');

  // Check if user has permission
  if (!current_user_can('manage_options')) {
    wp_send_json_error(__('You do not have permission to check import progress.', 'heritagepress'));
    return;
  }

  // Get import key
  $import_key = isset($_POST['import_key']) ? sanitize_text_field($_POST['import_key']) : '';
  if (empty($import_key)) {
    wp_send_json_error(__('Invalid import key.', 'heritagepress'));
    return;
  }

  // Get progress data
  $progress = get_transient('hp_gedcom_import_progress_' . $import_key);
  if (false === $progress) {
    wp_send_json_error(__('Import progress not found. The import may have failed or the progress data expired.', 'heritagepress'));
    return;
  }

  // Return progress data
  wp_send_json_success($progress);
}
add_action('wp_ajax_hp_check_import_progress', 'hp_ajax_check_import_progress');

/**
 * Background process to handle the GEDCOM import
 */
function hp_process_gedcom_import_bg($args)
{
  $import_key = $args['import_key'];
  $file_path = $args['file_path'];
  $upload_data = $args['upload_data'];
  $validation_results = $args['validation_results'];
  $config_data = $args['config_data'];
  $people_data = $args['people_data'];
  $media_data = $args['media_data'];
  $places_data = $args['places_data'];

  // Update progress to indicate we're starting
  $progress = get_transient('hp_gedcom_import_progress_' . $import_key);
  $progress['status'] = __('Processing GEDCOM file...', 'heritagepress');
  $progress['current_operation'] = __('Loading GEDCOM parser', 'heritagepress');
  $progress['percent'] = 5;
  $progress['log_entries'][] = __('GEDCOM parser initialized', 'heritagepress');
  set_transient('hp_gedcom_import_progress_' . $import_key, $progress, 60 * 60 * 2);

  // Create GEDCOM importer instance
  $importer = new HP_GEDCOM_Importer($file_path);

  // Configure the importer with all settings
  $importer->configure(array_merge(
    $config_data,
    $people_data,
    $media_data,
    $places_data,
    array(
      'tree_id' => $upload_data['tree_id'],
      'tree_name' => $upload_data['tree_name'],
      'character_set' => $upload_data['character_set'],
      'import_key' => $import_key
    )
  ));

  // Set up progress callback
  $importer->set_progress_callback(function ($status, $percent, $operation, $count, $log_entry = null) use ($import_key) {
    $progress = get_transient('hp_gedcom_import_progress_' . $import_key);
    $progress['status'] = $status;
    $progress['current_operation'] = $operation;
    $progress['percent'] = $percent;
    $progress['processed_count'] = $count;

    if ($log_entry) {
      $progress['log_entries'][] = $log_entry;
    }

    set_transient('hp_gedcom_import_progress_' . $import_key, $progress, 60 * 60 * 2);
  });

  // Run the import
  $results = $importer->import();

  // Update progress to indicate we're done
  $progress = get_transient('hp_gedcom_import_progress_' . $import_key);
  $progress['status'] = __('Import complete!', 'heritagepress');
  $progress['current_operation'] = __('Finalizing import', 'heritagepress');
  $progress['percent'] = 100;
  $progress['complete'] = true;
  $progress['log_entries'][] = __('Import process completed', 'heritagepress');
  set_transient('hp_gedcom_import_progress_' . $import_key, $progress, 60 * 60 * 2);

  // Store import results
  set_transient('hp_gedcom_import_results_' . $import_key, $results, 60 * 60 * 24); // Keep for 24 hours
}
add_action('hp_process_gedcom_import_bg', 'hp_process_gedcom_import_bg');
