<?php

/**
 * HeritagePress Geographic & Places Database
 *
 * Handles places, addresses, geography, and location tables
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Database_Geography
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
   * Create all geographic and location tables
   */
  public function create_tables()
  {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $this->create_places_table();
    $this->create_addresses_table();
    $this->create_countries_table();
    $this->create_states_table();
    $this->create_cemeteries_table();
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
            www varchar(500) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            date_from varchar(100) DEFAULT NULL,
            date_to varchar(100) DEFAULT NULL,
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
   * Countries table - country lookup data
   */
  private function create_countries_table()
  {
    $table_name = $this->get_table_name('countries');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            country_name varchar(255) NOT NULL,
            country_code varchar(3) NOT NULL,
            iso_code varchar(3) DEFAULT NULL,
            continent varchar(100) DEFAULT NULL,
            sort_order int(11) DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY country_code (country_code),
            KEY country_name (country_name),
            KEY continent (continent),
            KEY sort_order (sort_order),
            KEY active (active)
        ) {$this->charset_collate};";

    dbDelta($sql);

    // Insert default countries
    $this->insert_default_countries();
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
   * Insert default countries
   */
  private function insert_default_countries()
  {
    $table_name = $this->get_table_name('countries');
    
    $countries = array(
      array('United States', 'USA', 'US', 'North America', 1),
      array('Canada', 'CAN', 'CA', 'North America', 2),
      array('United Kingdom', 'GBR', 'GB', 'Europe', 3),
      array('Australia', 'AUS', 'AU', 'Oceania', 4),
      array('Germany', 'DEU', 'DE', 'Europe', 5),
      array('France', 'FRA', 'FR', 'Europe', 6),
      array('Italy', 'ITA', 'IT', 'Europe', 7),
      array('Ireland', 'IRL', 'IE', 'Europe', 8),
      array('Scotland', 'SCO', 'SC', 'Europe', 9),
      array('Wales', 'WAL', 'WA', 'Europe', 10),
      array('Mexico', 'MEX', 'MX', 'North America', 11),
      array('Spain', 'ESP', 'ES', 'Europe', 12),
      array('Portugal', 'PRT', 'PT', 'Europe', 13),
      array('Netherlands', 'NLD', 'NL', 'Europe', 14),
      array('Belgium', 'BEL', 'BE', 'Europe', 15),
      array('Switzerland', 'CHE', 'CH', 'Europe', 16),
      array('Austria', 'AUT', 'AT', 'Europe', 17),
      array('Poland', 'POL', 'PL', 'Europe', 18),
      array('Czech Republic', 'CZE', 'CZ', 'Europe', 19),
      array('Hungary', 'HUN', 'HU', 'Europe', 20)
    );

    foreach ($countries as $country) {
      $this->wpdb->replace(
        $table_name,
        array(
          'country_name' => $country[0],
          'country_code' => $country[1],
          'iso_code' => $country[2],
          'continent' => $country[3],
          'sort_order' => $country[4],
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
   * Drop geographic tables
   */
  public function drop_tables()
  {
    $tables = array(
      'places', 'addresses', 'countries', 'states', 'cemeteries'
    );

    foreach ($tables as $table) {
      $table_name = $this->get_table_name($table);
      $this->wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
  }

  /**
   * Get table statistics for geography tables
   */
  public function get_table_stats()
  {
    $stats = array();
    $tables = array(
      'places', 'addresses', 'countries', 'states', 'cemeteries'
    );

    foreach ($tables as $table) {
      $table_name = $this->get_table_name($table);
      $count = $this->wpdb->get_var("SELECT COUNT(*) FROM $table_name");
      $stats[$table] = (int)$count;
    }

    return $stats;
  }

}
