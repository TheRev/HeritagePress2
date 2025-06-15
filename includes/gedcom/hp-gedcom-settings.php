<?php

/**
 * GEDCOM Import Default Settings Functions
 *
 * Functions to provide default settings for GEDCOM import based on the source program
 */

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Get default import settings based on the source program
 *
 * @param string $program_name The name of the genealogy program that created the GEDCOM
 * @return array Default import settings
 */
function hp_get_default_import_settings($program_name = '')
{
  $settings = array(
    'import_type' => 'all',
    'import_individuals' => true,
    'import_families' => true,
    'import_sources' => true,
    'import_repositories' => true,
    'import_notes' => true,
    'import_media' => true,
    'import_places' => true,
    'import_events' => true,
    'import_custom_events' => true,
    'living_option' => 'import_partial',
    'apply_privacy' => true,
    'years_death' => 5,
    'years_birth' => 110,
    'private_notes' => false,
    'private_media' => false,
    'private_sources' => false,
  );

  // Program-specific defaults
  switch (strtolower($program_name)) {
    case 'familysearch':
    case 'family search':
    case 'familysearch tree':
      $settings['import_custom_events'] = false;
      break;

    case 'family tree maker':
    case 'ftm':
      $settings['import_custom_events'] = true;
      break;

    case 'rootsmagic':
      $settings['import_notes'] = true;
      $settings['import_media'] = true;
      break;

    case 'ancestry':
    case 'ancestry.com':
      $settings['import_media'] = false; // Ancestry often includes broken media links
      break;

    case 'gramps':
      $settings['import_custom_events'] = true;
      break;

    case 'myheritage':
      $settings['import_media'] = false; // MyHeritage often includes non-accessible media links
      break;
  }

  return $settings;
}

/**
 * Get default people settings based on the source program
 *
 * @param string $program_name The name of the genealogy program that created the GEDCOM
 * @return array Default people settings
 */
function hp_get_default_people_settings($program_name = '')
{
  $settings = array(
    'name_format' => 'surname_first',
    'capitalize_surnames' => true,
    'extract_nicknames' => true,
    'import_alternate_names' => true,
    'import_name_prefixes' => true,
    'import_name_suffixes' => true,
    'import_birth' => true,
    'import_christening' => true,
    'import_death' => true,
    'import_burial' => true,
    'import_occupation' => true,
    'import_education' => true,
    'import_residence' => true,
    'import_religion' => true,
    'import_military' => true,
    'import_medical' => true,
    'import_physical' => true,
    'import_immigration' => true,
    'merge_same_type_events' => true,
    'standardize_event_names' => true,
    'import_marriage' => true,
    'import_divorce' => true,
    'import_engagement' => true,
    'import_family_events' => true,
    'import_parent_relationships' => true,
    'import_spouse_relationships' => true,
    'import_sibling_relationships' => true,
    'import_step_relationships' => true,
    'import_adoption' => true,
    'import_relationship_notes' => true,
    'merge_individuals' => true,
    'link_existing_sources' => true,
    'id_handling' => 'preserve',
    'id_prefix' => 'HP_',
  );

  // Program-specific defaults
  switch (strtolower($program_name)) {
    case 'family tree maker':
    case 'ftm':
      $settings['extract_nicknames'] = true;
      $settings['import_alternate_names'] = true;
      break;

    case 'rootsmagic':
      $settings['merge_same_type_events'] = false; // RootsMagic often has meaningful multiple events
      break;

    case 'ancestry':
    case 'ancestry.com':
      $settings['extract_nicknames'] = true;
      $settings['import_physical'] = false;
      break;

    case 'legacy':
    case 'legacy family tree':
      $settings['standardize_event_names'] = true;
      $settings['import_medical'] = false;
      break;

    case 'paf':
    case 'personal ancestral file':
      $settings['id_handling'] = 'generate'; // PAF IDs often conflict
      break;
  }

  return $settings;
}

/**
 * Get default media settings based on the source program
 *
 * @param string $program_name The name of the genealogy program that created the GEDCOM
 * @return array Default media settings
 */
function hp_get_default_media_settings($program_name = '')
{
  $settings = array(
    'import_media' => 'all',
    'media_source' => 'local',
    'local_media_path' => '',
    'media_destination' => '',
    'media_folder_structure' => 'preserve',
    'file_handling' => 'copy',
    'import_images' => true,
    'import_documents' => true,
    'import_audio' => true,
    'import_video' => true,
    'resize_images' => true,
    'max_image_size' => 1200,
    'generate_thumbnails' => true,
    'extract_metadata' => true,
  );

  // Program-specific defaults
  switch (strtolower($program_name)) {
    case 'family tree maker':
    case 'ftm':
      $settings['media_source'] = 'local';
      $settings['media_folder_structure'] = 'preserve';
      break;

    case 'rootsmagic':
      $settings['media_source'] = 'local';
      $settings['media_folder_structure'] = 'preserve';
      break;

    case 'ancestry':
    case 'ancestry.com':
      $settings['import_media'] = 'links'; // Ancestry media usually requires login
      $settings['media_source'] = 'url';
      break;

    case 'myheritage':
      $settings['import_media'] = 'links'; // MyHeritage media usually requires login
      $settings['media_source'] = 'url';
      break;

    case 'gramps':
      $settings['media_folder_structure'] = 'type';
      $settings['extract_metadata'] = true;
      break;
  }

  return $settings;
}

/**
 * Get default places settings based on the source program
 *
 * @param string $program_name The name of the genealogy program that created the GEDCOM
 * @return array Default places settings
 */
function hp_get_default_places_settings($program_name = '')
{
  $settings = array(
    'place_handling' => 'exact',
    'place_format' => 'original',
    'place_separator' => 'comma',
    'extract_place_hierarchy' => true,
    'index_places' => true,
    'geocode_places' => false,
    'geocode_service' => 'nominatim',
    'google_maps_api_key' => '',
    'geocode_priority' => 'important',
    'default_map_type' => 'road',
    'show_place_markers' => true,
    'cluster_markers' => true,
  );

  // Program-specific defaults
  switch (strtolower($program_name)) {
    case 'family tree maker':
    case 'ftm':
      $settings['place_handling'] = 'standardize';
      $settings['extract_place_hierarchy'] = true;
      break;

    case 'rootsmagic':
      $settings['place_handling'] = 'exact';
      $settings['extract_place_hierarchy'] = true;
      break;

    case 'ancestry':
    case 'ancestry.com':
      $settings['place_handling'] = 'standardize';
      $settings['place_format'] = 'smallest_first';
      break;

    case 'gramps':
      $settings['place_handling'] = 'standardize';
      $settings['place_format'] = 'largest_first';
      break;
  }

  return $settings;
}
