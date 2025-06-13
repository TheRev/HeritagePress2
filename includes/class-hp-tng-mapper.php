<?php

/**
 * TNG to HeritagePress Data Mapping Layer
 *
 * Handles conversion between TNG data formats and HeritagePress/WordPress integration
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_TNG_Mapper
{
  private $wpdb;

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
  }

  /**
   * Map TNG person record to HeritagePress format
   */
  public function map_tng_person_to_hp($tng_person)
  {
    return [
      // Core identification
      'ID' => $tng_person['ID'] ?? null,
      'gedcom' => $tng_person['gedcom'] ?? '',
      'personID' => $tng_person['personID'] ?? '',

      // Names (TNG format)
      'firstname' => $tng_person['firstname'] ?? '',
      'lastname' => $tng_person['lastname'] ?? '',
      'lnprefix' => $tng_person['lnprefix'] ?? '',
      'nickname' => $tng_person['nickname'] ?? '',
      'title' => $tng_person['title'] ?? '',
      'prefix' => $tng_person['prefix'] ?? '',
      'suffix' => $tng_person['suffix'] ?? '',

      // Vital information
      'sex' => $tng_person['sex'] ?? '',
      'birthdate' => $tng_person['birthdate'] ?? '',
      'birthdatetr' => $tng_person['birthdatetr'] ?? null,
      'birthplace' => $tng_person['birthplace'] ?? '',
      'deathdate' => $tng_person['deathdate'] ?? '',
      'deathdatetr' => $tng_person['deathdatetr'] ?? null,
      'deathplace' => $tng_person['deathplace'] ?? '',

      // Alternative birth information
      'altbirthtype' => $tng_person['altbirthtype'] ?? '',
      'altbirthdate' => $tng_person['altbirthdate'] ?? '',
      'altbirthdatetr' => $tng_person['altbirthdatetr'] ?? null,
      'altbirthplace' => $tng_person['altbirthplace'] ?? '',

      // Burial information
      'burialdate' => $tng_person['burialdate'] ?? '',
      'burialdatetr' => $tng_person['burialdatetr'] ?? null,
      'burialplace' => $tng_person['burialplace'] ?? '',
      'burialtype' => $tng_person['burialtype'] ?? 0,

      // Religious events
      'baptdate' => $tng_person['baptdate'] ?? '',
      'baptdatetr' => $tng_person['baptdatetr'] ?? null,
      'baptplace' => $tng_person['baptplace'] ?? '',
      'confdate' => $tng_person['confdate'] ?? '',
      'confdatetr' => $tng_person['confdatetr'] ?? null,
      'confplace' => $tng_person['confplace'] ?? '',
      'initdate' => $tng_person['initdate'] ?? '',
      'initdatetr' => $tng_person['initdatetr'] ?? null,
      'initplace' => $tng_person['initplace'] ?? '',
      'endldate' => $tng_person['endldate'] ?? '',
      'endldatetr' => $tng_person['endldatetr'] ?? null,
      'endlplace' => $tng_person['endlplace'] ?? '',

      // Metadata
      'changedate' => $tng_person['changedate'] ?? date('Y-m-d H:i:s'),
      'nameorder' => $tng_person['nameorder'] ?? 0,
      'famc' => $tng_person['famc'] ?? '',
      'metaphone' => $tng_person['metaphone'] ?? '',
      'living' => $tng_person['living'] ?? 0,
      'private' => $tng_person['private'] ?? 0,
      'branch' => $tng_person['branch'] ?? '',
      'changedby' => $tng_person['changedby'] ?? '',
      'edituser' => $tng_person['edituser'] ?? '',
      'edittime' => $tng_person['edittime'] ?? 0,
    ];
  }

  /**
   * Map TNG family record to HeritagePress format
   */
  public function map_tng_family_to_hp($tng_family)
  {
    return [
      'ID' => $tng_family['ID'] ?? null,
      'gedcom' => $tng_family['gedcom'] ?? '',
      'familyID' => $tng_family['familyID'] ?? '',
      'husband' => $tng_family['husband'] ?? '',
      'wife' => $tng_family['wife'] ?? '',
      'marrdate' => $tng_family['marrdate'] ?? '',
      'marrdatetr' => $tng_family['marrdatetr'] ?? null,
      'marrplace' => $tng_family['marrplace'] ?? '',
      'marrtype' => $tng_family['marrtype'] ?? '',
      'divdate' => $tng_family['divdate'] ?? '',
      'divdatetr' => $tng_family['divdatetr'] ?? null,
      'divplace' => $tng_family['divplace'] ?? '',
      'status' => $tng_family['status'] ?? '',
      'sealdate' => $tng_family['sealdate'] ?? '',
      'sealdatetr' => $tng_family['sealdatetr'] ?? null,
      'sealplace' => $tng_family['sealplace'] ?? '',
      'husborder' => $tng_family['husborder'] ?? 0,
      'wifeorder' => $tng_family['wifeorder'] ?? 0,
      'changedate' => $tng_family['changedate'] ?? date('Y-m-d H:i:s'),
      'living' => $tng_family['living'] ?? 0,
      'private' => $tng_family['private'] ?? 0,
      'branch' => $tng_family['branch'] ?? '',
      'changedby' => $tng_family['changedby'] ?? '',
      'edituser' => $tng_family['edituser'] ?? '',
      'edittime' => $tng_family['edittime'] ?? 0,
    ];
  }

  /**
   * Map TNG child record to HeritagePress format
   */
  public function map_tng_child_to_hp($tng_child)
  {
    return [
      'ID' => $tng_child['ID'] ?? null,
      'gedcom' => $tng_child['gedcom'] ?? '',
      'familyID' => $tng_child['familyID'] ?? '',
      'personID' => $tng_child['personID'] ?? '',
      'frel' => $tng_child['frel'] ?? '',
      'mrel' => $tng_child['mrel'] ?? '',
      'sealdate' => $tng_child['sealdate'] ?? '',
      'sealdatetr' => $tng_child['sealdatetr'] ?? null,
      'sealplace' => $tng_child['sealplace'] ?? '',
      'haskids' => $tng_child['haskids'] ?? 0,
      'ordernum' => $tng_child['ordernum'] ?? 0,
      'parentorder' => $tng_child['parentorder'] ?? 0,
    ];
  }

  /**
   * Map TNG event record to HeritagePress format
   */
  public function map_tng_event_to_hp($tng_event)
  {
    return [
      'eventID' => $tng_event['eventID'] ?? null,
      'gedcom' => $tng_event['gedcom'] ?? '',
      'persfamID' => $tng_event['persfamID'] ?? '',
      'eventtypeID' => $tng_event['eventtypeID'] ?? 0,
      'eventdate' => $tng_event['eventdate'] ?? '',
      'eventdatetr' => $tng_event['eventdatetr'] ?? null,
      'eventplace' => $tng_event['eventplace'] ?? '',
      'age' => $tng_event['age'] ?? '',
      'agency' => $tng_event['agency'] ?? '',
      'cause' => $tng_event['cause'] ?? '',
      'addressID' => $tng_event['addressID'] ?? '',
      'parenttag' => $tng_event['parenttag'] ?? '',
      'info' => $tng_event['info'] ?? '',
    ];
  }

  /**
   * Map TNG source record to HeritagePress format
   */
  public function map_tng_source_to_hp($tng_source)
  {
    return [
      'sourceID' => $tng_source['sourceID'] ?? '',
      'gedcom' => $tng_source['gedcom'] ?? '',
      'title' => $tng_source['title'] ?? '',
      'shorttitle' => $tng_source['shorttitle'] ?? '',
      'author' => $tng_source['author'] ?? '',
      'publisher' => $tng_source['publisher'] ?? '',
      'date' => $tng_source['date'] ?? '',
      'callnum' => $tng_source['callnum'] ?? '',
      'actualtext' => $tng_source['actualtext'] ?? '',
      'comments' => $tng_source['comments'] ?? '',
      'usenote' => $tng_source['usenote'] ?? 0,
      'repo' => $tng_source['repo'] ?? '',
      'publnote' => $tng_source['publnote'] ?? '',
      'volume' => $tng_source['volume'] ?? '',
      'page' => $tng_source['page'] ?? '',
      'film' => $tng_source['film'] ?? '',
    ];
  }

  /**
   * Map TNG citation record to HeritagePress format
   */
  public function map_tng_citation_to_hp($tng_citation)
  {
    return [
      'citationID' => $tng_citation['citationID'] ?? null,
      'gedcom' => $tng_citation['gedcom'] ?? '',
      'persfamID' => $tng_citation['persfamID'] ?? '',
      'eventID' => $tng_citation['eventID'] ?? '',
      'sourceID' => $tng_citation['sourceID'] ?? '',
      'ordernum' => $tng_citation['ordernum'] ?? 0,
      'description' => $tng_citation['description'] ?? '',
      'citedate' => $tng_citation['citedate'] ?? '',
      'citedatetr' => $tng_citation['citedatetr'] ?? null,
      'citetext' => $tng_citation['citetext'] ?? '',
      'page' => $tng_citation['page'] ?? '',
      'quay' => $tng_citation['quay'] ?? '',
      'note' => $tng_citation['note'] ?? '',
    ];
  }

  /**
   * Map TNG media record to HeritagePress format
   */
  public function map_tng_media_to_hp($tng_media)
  {
    return [
      'mediaID' => $tng_media['mediaID'] ?? null,
      'gedcom' => $tng_media['gedcom'] ?? '',
      'path' => $tng_media['path'] ?? '',
      'description' => $tng_media['description'] ?? '',
      'notes' => $tng_media['notes'] ?? '',
      'mediatypeID' => $tng_media['mediatypeID'] ?? 0,
      'usedate' => $tng_media['usedate'] ?? '',
      'usedatetr' => $tng_media['usedatetr'] ?? null,
      'places' => $tng_media['places'] ?? '',
      'changedate' => $tng_media['changedate'] ?? date('Y-m-d H:i:s'),
    ];
  }

  /**
   * Map WordPress user permissions to TNG format
   */
  public function map_wp_user_to_tng_permissions($wp_user_id, $gedcom = 'main')
  {
    $user = get_userdata($wp_user_id);
    if (!$user) {
      return null;
    }

    // Map WordPress capabilities to TNG permissions
    $role = $user->roles[0] ?? 'subscriber';

    $permissions = [
      'userID' => $wp_user_id,
      'description' => $user->display_name,
      'username' => $user->user_login,
      'password' => '', // WordPress handles passwords
      'password_type' => 'wordpress',
      'gedcom' => $gedcom,
      'mygedcom' => $gedcom,
      'personID' => '',
      'role' => $this->map_wp_role_to_tng($role),
      'allow_edit' => current_user_can('edit_posts') ? 1 : 0,
      'allow_add' => current_user_can('publish_posts') ? 1 : 0,
      'tentative_edit' => 0,
      'allow_delete' => current_user_can('delete_posts') ? 1 : 0,
      'allow_lds' => current_user_can('manage_options') ? 1 : 0,
      'allow_ged' => current_user_can('export') ? 1 : 0,
      'allow_pdf' => 1,
      'allow_living' => current_user_can('read_private_posts') ? 1 : 0,
      'allow_private' => current_user_can('read_private_posts') ? 1 : 0,
      'allow_private_notes' => current_user_can('read_private_posts') ? 1 : 0,
      'allow_private_media' => current_user_can('read_private_posts') ? 1 : 0,
      'allow_profile' => 1,
      'branch' => '',
      'realname' => $user->display_name,
      'phone' => get_user_meta($wp_user_id, 'phone', true) ?: '',
      'email' => $user->user_email,
      'address' => get_user_meta($wp_user_id, 'address', true) ?: '',
      'city' => get_user_meta($wp_user_id, 'city', true) ?: '',
      'state' => get_user_meta($wp_user_id, 'state', true) ?: '',
      'zip' => get_user_meta($wp_user_id, 'zip', true) ?: '',
      'country' => get_user_meta($wp_user_id, 'country', true) ?: '',
      'website' => $user->user_url,
      'languageID' => 1, // Default to English
      'lastlogin' => get_user_meta($wp_user_id, 'last_login', true) ?: date('Y-m-d H:i:s'),
      'disabled' => 0,
      'dt_registered' => $user->user_registered,
      'dt_activated' => $user->user_registered,
      'dt_consented' => $user->user_registered,
      'no_email' => 0,
      'notes' => get_user_meta($wp_user_id, 'description', true) ?: '',
      'reset_pwd_code' => '',
    ];

    return $permissions;
  }

  /**
   * Map WordPress role to TNG role
   */
  private function map_wp_role_to_tng($wp_role)
  {
    $role_mapping = [
      'administrator' => 'admin',
      'editor' => 'editor',
      'author' => 'contributor',
      'contributor' => 'member',
      'subscriber' => 'guest',
    ];

    return $role_mapping[$wp_role] ?? 'guest';
  }

  /**
   * Convert GEDCOM date to TNG format
   */
  public function convert_gedcom_date_to_tng($gedcom_date)
  {
    // Handle various GEDCOM date formats
    if (empty($gedcom_date)) {
      return ['date' => '', 'datetr' => null];
    }

    // Remove qualifiers like ABT, EST, CAL, etc.
    $clean_date = preg_replace('/^(ABT|EST|CAL|AFT|BEF|BET|FROM|TO)\s+/i', '', $gedcom_date);

    // Convert to MySQL date format if possible
    $datetr = null;
    if (preg_match('/(\d{1,2})\s+(\w{3})\s+(\d{4})/', $clean_date, $matches)) {
      $day = $matches[1];
      $month = $this->convert_month_name_to_number($matches[2]);
      $year = $matches[3];

      if ($month) {
        $datetr = sprintf('%04d-%02d-%02d', $year, $month, $day);
      }
    } elseif (preg_match('/(\w{3})\s+(\d{4})/', $clean_date, $matches)) {
      $month = $this->convert_month_name_to_number($matches[1]);
      $year = $matches[2];

      if ($month) {
        $datetr = sprintf('%04d-%02d-01', $year, $month);
      }
    } elseif (preg_match('/(\d{4})/', $clean_date, $matches)) {
      $year = $matches[1];
      $datetr = sprintf('%04d-01-01', $year);
    }

    return [
      'date' => $gedcom_date,
      'datetr' => $datetr
    ];
  }

  /**
   * Convert month name to number
   */
  private function convert_month_name_to_number($month_name)
  {
    $months = [
      'JAN' => 1,
      'FEB' => 2,
      'MAR' => 3,
      'APR' => 4,
      'MAY' => 5,
      'JUN' => 6,
      'JUL' => 7,
      'AUG' => 8,
      'SEP' => 9,
      'OCT' => 10,
      'NOV' => 11,
      'DEC' => 12
    ];

    return $months[strtoupper($month_name)] ?? null;
  }

  /**
   * Generate TNG-compatible personID from name and birth info
   */
  public function generate_tng_person_id($firstname, $lastname, $birthdate = '')
  {
    // TNG typically uses format like I123 or similar
    // For compatibility, we'll generate based on name hash
    $name_hash = substr(md5($firstname . $lastname . $birthdate), 0, 8);
    return 'I' . strtoupper($name_hash);
  }

  /**
   * Generate TNG-compatible familyID
   */
  public function generate_tng_family_id($husband_id, $wife_id)
  {
    $family_hash = substr(md5($husband_id . $wife_id), 0, 8);
    return 'F' . strtoupper($family_hash);
  }

  /**
   * Import TNG person record into HeritagePress
   */
  public function import_tng_person($tng_person_data)
  {
    $mapped_data = $this->map_tng_person_to_hp($tng_person_data);

    $table_name = $this->wpdb->prefix . 'hp_people';

    $result = $this->wpdb->insert($table_name, $mapped_data);

    if ($result === false) {
      return new WP_Error('db_insert_error', 'Failed to insert person record', $this->wpdb->last_error);
    }

    return $this->wpdb->insert_id;
  }

  /**
   * Import TNG family record into HeritagePress
   */
  public function import_tng_family($tng_family_data)
  {
    $mapped_data = $this->map_tng_family_to_hp($tng_family_data);

    $table_name = $this->wpdb->prefix . 'hp_families';

    $result = $this->wpdb->insert($table_name, $mapped_data);

    if ($result === false) {
      return new WP_Error('db_insert_error', 'Failed to insert family record', $this->wpdb->last_error);
    }

    return $this->wpdb->insert_id;
  }

  /**
   * Export HeritagePress person to TNG format
   */
  public function export_hp_person_to_tng($person_id)
  {
    $table_name = $this->wpdb->prefix . 'hp_people';

    $person = $this->wpdb->get_row(
      $this->wpdb->prepare("SELECT * FROM $table_name WHERE ID = %d", $person_id),
      ARRAY_A
    );

    if (!$person) {
      return new WP_Error('person_not_found', 'Person not found');
    }

    return $this->map_tng_person_to_hp($person);
  }

  /**
   * Validate TNG data structure
   */
  public function validate_tng_person_data($data)
  {
    $required_fields = ['gedcom', 'personID', 'firstname', 'lastname'];
    $errors = [];

    foreach ($required_fields as $field) {
      if (empty($data[$field])) {
        $errors[] = "Missing required field: $field";
      }
    }

    // Validate date formats
    if (!empty($data['birthdatetr']) && !$this->is_valid_mysql_date($data['birthdatetr'])) {
      $errors[] = "Invalid birth date format: {$data['birthdatetr']}";
    }

    if (!empty($data['deathdatetr']) && !$this->is_valid_mysql_date($data['deathdatetr'])) {
      $errors[] = "Invalid death date format: {$data['deathdatetr']}";
    }

    return empty($errors) ? true : $errors;
  }

  /**
   * Check if date is valid MySQL date format
   */
  private function is_valid_mysql_date($date)
  {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
  }
}
