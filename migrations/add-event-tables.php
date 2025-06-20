<?php

/**
 * Migration: Add Event Tables
 *
 * Creates the database tables needed for event management:
 * - hp_events: Main events table
 * - hp_eventtypes: Event type definitions
 * - hp_addresses: Address information for events
 *
 * Based on genealogy events, eventtypes, and addresses tables
 */

if (!defined('ABSPATH')) {
  exit;
}

function hp_migrate_add_event_tables()
{
  global $wpdb;

  $charset_collate = $wpdb->get_charset_collate();

  // Events table - stores individual event records
  $events_table = $wpdb->prefix . 'hp_events';
  $events_sql = "CREATE TABLE IF NOT EXISTS $events_table (
    eventID int(11) NOT NULL AUTO_INCREMENT,
    eventtypeID varchar(25) NOT NULL DEFAULT '',
    persfamID varchar(20) NOT NULL DEFAULT '',
    eventdate varchar(20) NOT NULL DEFAULT '',
    eventdatetr varchar(20) NOT NULL DEFAULT '',
    eventplace varchar(255) NOT NULL DEFAULT '',
    age varchar(12) NOT NULL DEFAULT '',
    agency varchar(200) NOT NULL DEFAULT '',
    cause varchar(200) NOT NULL DEFAULT '',
    addressID int(11) DEFAULT NULL,
    info text,
    gedcom varchar(20) NOT NULL DEFAULT '',
    parenttag varchar(10) NOT NULL DEFAULT '',
    PRIMARY KEY (eventID),
    KEY persfamID (persfamID),
    KEY gedcom (gedcom),
    KEY eventtypeID (eventtypeID),
    KEY eventdate (eventdate),
    KEY eventplace (eventplace(50))
  ) $charset_collate;";

  // Event types table - defines available event types
  $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';
  $eventtypes_sql = "CREATE TABLE IF NOT EXISTS $eventtypes_table (
    eventtypeID varchar(25) NOT NULL DEFAULT '',
    tag varchar(10) NOT NULL DEFAULT '',
    display varchar(100) NOT NULL DEFAULT '',
    keep tinyint(1) NOT NULL DEFAULT 1,
    type enum('I','F','S') NOT NULL DEFAULT 'I',
    PRIMARY KEY (eventtypeID),
    KEY tag (tag),
    KEY type (type),
    KEY keep (keep)
  ) $charset_collate;";

  // Addresses table - stores address information for events
  $addresses_table = $wpdb->prefix . 'hp_addresses';
  $addresses_sql = "CREATE TABLE IF NOT EXISTS $addresses_table (
    addressID int(11) NOT NULL AUTO_INCREMENT,
    address1 varchar(100) NOT NULL DEFAULT '',
    address2 varchar(100) NOT NULL DEFAULT '',
    city varchar(50) NOT NULL DEFAULT '',
    state varchar(50) NOT NULL DEFAULT '',
    zip varchar(20) NOT NULL DEFAULT '',
    country varchar(50) NOT NULL DEFAULT '',
    gedcom varchar(20) NOT NULL DEFAULT '',
    phone varchar(30) NOT NULL DEFAULT '',
    email varchar(100) NOT NULL DEFAULT '',
    www varchar(255) NOT NULL DEFAULT '',
    PRIMARY KEY (addressID),
    KEY gedcom (gedcom),
    KEY city (city),
    KEY state (state),
    KEY country (country)
  ) $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

  // Create tables
  dbDelta($events_sql);
  dbDelta($eventtypes_sql);
  dbDelta($addresses_sql);

  // Check if we need to populate event types
  $event_type_count = $wpdb->get_var("SELECT COUNT(*) FROM $eventtypes_table");

  if ($event_type_count == 0) {
    hp_populate_default_event_types();
  }

  return array(
    'events' => $wpdb->get_var("SHOW TABLES LIKE '$events_table'") === $events_table,
    'eventtypes' => $wpdb->get_var("SHOW TABLES LIKE '$eventtypes_table'") === $eventtypes_table,
    'addresses' => $wpdb->get_var("SHOW TABLES LIKE '$addresses_table'") === $addresses_table
  );
}

/**
 * Populate default event types (similar to standard event types)
 */
function hp_populate_default_event_types()
{
  global $wpdb;

  $eventtypes_table = $wpdb->prefix . 'hp_eventtypes';

  // Standard person event types (type = 'I')
  $person_events = array(
    array('eventtypeID' => 'BIRT', 'tag' => 'BIRT', 'display' => 'Birth', 'type' => 'I'),
    array('eventtypeID' => 'CHR', 'tag' => 'CHR', 'display' => 'Christening', 'type' => 'I'),
    array('eventtypeID' => 'BAPM', 'tag' => 'BAPM', 'display' => 'Baptism', 'type' => 'I'),
    array('eventtypeID' => 'CONF', 'tag' => 'CONF', 'display' => 'Confirmation', 'type' => 'I'),
    array('eventtypeID' => 'BARM', 'tag' => 'BARM', 'display' => 'Bar Mitzvah', 'type' => 'I'),
    array('eventtypeID' => 'BASM', 'tag' => 'BASM', 'display' => 'Bas Mitzvah', 'type' => 'I'),
    array('eventtypeID' => 'BLES', 'tag' => 'BLES', 'display' => 'Blessing', 'type' => 'I'),
    array('eventtypeID' => 'ADOP', 'tag' => 'ADOP', 'display' => 'Adoption', 'type' => 'I'),
    array('eventtypeID' => 'EDUC', 'tag' => 'EDUC', 'display' => 'Education', 'type' => 'I'),
    array('eventtypeID' => 'GRAD', 'tag' => 'GRAD', 'display' => 'Graduation', 'type' => 'I'),
    array('eventtypeID' => 'OCCU', 'tag' => 'OCCU', 'display' => 'Occupation', 'type' => 'I'),
    array('eventtypeID' => 'RESI', 'tag' => 'RESI', 'display' => 'Residence', 'type' => 'I'),
    array('eventtypeID' => 'RETI', 'tag' => 'RETI', 'display' => 'Retirement', 'type' => 'I'),
    array('eventtypeID' => 'EMIG', 'tag' => 'EMIG', 'display' => 'Emigration', 'type' => 'I'),
    array('eventtypeID' => 'IMMI', 'tag' => 'IMMI', 'display' => 'Immigration', 'type' => 'I'),
    array('eventtypeID' => 'NATU', 'tag' => 'NATU', 'display' => 'Naturalization', 'type' => 'I'),
    array('eventtypeID' => 'CENS', 'tag' => 'CENS', 'display' => 'Census', 'type' => 'I'),
    array('eventtypeID' => 'PROB', 'tag' => 'PROB', 'display' => 'Probate', 'type' => 'I'),
    array('eventtypeID' => 'WILL', 'tag' => 'WILL', 'display' => 'Will', 'type' => 'I'),
    array('eventtypeID' => 'DEAT', 'tag' => 'DEAT', 'display' => 'Death', 'type' => 'I'),
    array('eventtypeID' => 'BURI', 'tag' => 'BURI', 'display' => 'Burial', 'type' => 'I'),
    array('eventtypeID' => 'CREM', 'tag' => 'CREM', 'display' => 'Cremation', 'type' => 'I'),
    array('eventtypeID' => 'EVEN', 'tag' => 'EVEN', 'display' => 'Event', 'type' => 'I'),

    // Military events
    array('eventtypeID' => 'MILI', 'tag' => 'MILI', 'display' => 'Military Service', 'type' => 'I'),

    // Religious events
    array('eventtypeID' => 'FCOM', 'tag' => 'FCOM', 'display' => 'First Communion', 'type' => 'I'),
    array('eventtypeID' => 'ORDN', 'tag' => 'ORDN', 'display' => 'Ordination', 'type' => 'I'),

    // Medical events
    array('eventtypeID' => 'DSCR', 'tag' => 'DSCR', 'display' => 'Physical Description', 'type' => 'I'),

    // LDS ordinances
    array('eventtypeID' => 'BAPL', 'tag' => 'BAPL', 'display' => 'LDS Baptism', 'type' => 'I'),
    array('eventtypeID' => 'CONL', 'tag' => 'CONL', 'display' => 'LDS Confirmation', 'type' => 'I'),
    array('eventtypeID' => 'ENDL', 'tag' => 'ENDL', 'display' => 'LDS Endowment', 'type' => 'I'),
    array('eventtypeID' => 'SLGC', 'tag' => 'SLGC', 'display' => 'LDS Sealing to Child', 'type' => 'I')
  );

  // Standard family event types (type = 'F')
  $family_events = array(
    array('eventtypeID' => 'MARR', 'tag' => 'MARR', 'display' => 'Marriage', 'type' => 'F'),
    array('eventtypeID' => 'MARB', 'tag' => 'MARB', 'display' => 'Marriage Banns', 'type' => 'F'),
    array('eventtypeID' => 'MARC', 'tag' => 'MARC', 'display' => 'Marriage Contract', 'type' => 'F'),
    array('eventtypeID' => 'MARL', 'tag' => 'MARL', 'display' => 'Marriage License', 'type' => 'F'),
    array('eventtypeID' => 'MARS', 'tag' => 'MARS', 'display' => 'Marriage Settlement', 'type' => 'F'),
    array('eventtypeID' => 'ENGA', 'tag' => 'ENGA', 'display' => 'Engagement', 'type' => 'F'),
    array('eventtypeID' => 'DIV', 'tag' => 'DIV', 'display' => 'Divorce', 'type' => 'F'),
    array('eventtypeID' => 'DIVF', 'tag' => 'DIVF', 'display' => 'Divorce Filed', 'type' => 'F'),
    array('eventtypeID' => 'ANUL', 'tag' => 'ANUL', 'display' => 'Annulment', 'type' => 'F'),
    array('eventtypeID' => 'CENS', 'tag' => 'CENS', 'display' => 'Census', 'type' => 'F'),
    array('eventtypeID' => 'EVEN', 'tag' => 'EVEN', 'display' => 'Event', 'type' => 'F'),

    // LDS family ordinances
    array('eventtypeID' => 'SLGS', 'tag' => 'SLGS', 'display' => 'LDS Sealing to Spouse', 'type' => 'F')
  );

  // Source event types (type = 'S')
  $source_events = array(
    array('eventtypeID' => 'EVEN', 'tag' => 'EVEN', 'display' => 'Event', 'type' => 'S')
  );

  // Insert all event types
  foreach (array_merge($person_events, $family_events, $source_events) as $event) {
    $wpdb->insert(
      $eventtypes_table,
      array(
        'eventtypeID' => $event['eventtypeID'],
        'tag' => $event['tag'],
        'display' => $event['display'],
        'keep' => 1,
        'type' => $event['type']
      ),
      array('%s', '%s', '%s', '%d', '%s')
    );
  }
}

/**
 * Drop event tables (for cleanup)
 */
function hp_migrate_drop_event_tables()
{
  global $wpdb;

  $tables = array(
    $wpdb->prefix . 'hp_events',
    $wpdb->prefix . 'hp_eventtypes',
    $wpdb->prefix . 'hp_addresses'
  );

  foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
  }

  return true;
}
