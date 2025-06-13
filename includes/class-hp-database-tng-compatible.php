<?php

/**
 * HeritagePress TNG-Compatible Database Management
 *
 * Rebuilds database tables to match TNG structure exactly for true compatibility
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Database_TNG_Compatible
{
  /**
   * @var wpdb
   */
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
   * Create all TNG-compatible tables
   */
  public function create_tables()
  {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $this->create_people_table();
    $this->create_families_table();
    $this->create_children_table();
    $this->create_events_table();
    $this->create_eventtypes_table();
    $this->create_sources_table();
    $this->create_citations_table();
    $this->create_repositories_table();
    $this->create_media_table();
    $this->create_medialinks_table();
    $this->create_mediatypes_table();
    $this->create_albums_table();
    $this->create_albumlinks_table();
    $this->create_album2entities_table();
    $this->create_image_tags_table();
    $this->create_places_table();
    $this->create_address_table();
    $this->create_countries_table();
    $this->create_states_table();
    $this->create_cemeteries_table();
    $this->create_assoc_table();
    $this->create_branches_table();
    $this->create_branchlinks_table();
    $this->create_xnotes_table();
    $this->create_notelinks_table();
    $this->create_mostwanted_table();
    $this->create_trees_table();
    $this->create_users_table();
    $this->create_languages_table();
    $this->create_reports_table();
    $this->create_templates_table();
    $this->create_temp_events_table();
    $this->create_tlevents_table();
    $this->create_saveimport_table();
    $this->create_dna_tests_table();
    $this->create_dna_links_table();
    $this->create_dna_groups_table();

    // WordPress-specific enhancement tables
    $this->create_user_permissions_table();
    $this->create_import_log_table();
  }

  /**
   * TNG-Compatible People Table
   * Matches TNG people table structure exactly
   */
  private function create_people_table()
  {
    $table_name = $this->get_table_name('people');

    $sql = "CREATE TABLE $table_name (
            ID int(11) NOT NULL AUTO_INCREMENT,
            gedcom varchar(20) NOT NULL,
            personID varchar(22) NOT NULL,
            lnprefix varchar(25) NOT NULL,
            lastname varchar(127) NOT NULL,
            firstname varchar(127) NOT NULL,
            birthdate varchar(50) NOT NULL,
            birthdatetr date NOT NULL,
            sex varchar(25) NOT NULL,
            birthplace text NOT NULL,
            deathdate varchar(50) NOT NULL,
            deathdatetr date NOT NULL,
            deathplace text NOT NULL,
            altbirthtype varchar(5) NOT NULL,
            altbirthdate varchar(50) NOT NULL,
            altbirthdatetr date NOT NULL,
            altbirthplace text NOT NULL,
            burialdate varchar(50) NOT NULL,
            burialdatetr date NOT NULL,
            burialplace text NOT NULL,
            burialtype tinyint NOT NULL,
            baptdate varchar(50) NOT NULL,
            baptdatetr date NOT NULL,
            baptplace text NOT NULL,
            confdate varchar(50) NOT NULL,
            confdatetr date NOT NULL,
            confplace text NOT NULL,
            initdate varchar(50) NOT NULL,
            initdatetr date NOT NULL,
            initplace text NOT NULL,
            endldate varchar(50) NOT NULL,
            endldatetr date NOT NULL,
            endlplace text NOT NULL,
            changedate datetime NOT NULL,
            nickname text NOT NULL,
            title tinytext NOT NULL,
            prefix tinytext NOT NULL,
            suffix tinytext NOT NULL,
            nameorder tinyint NOT NULL,
            famc varchar(22) NOT NULL,
            metaphone varchar(15) NOT NULL,
            living tinyint NOT NULL,
            private tinyint NOT NULL,
            branch varchar(512) NOT NULL,
            changedby varchar(100) NOT NULL,
            edituser varchar(100) NOT NULL,
            edittime int NOT NULL,
            PRIMARY KEY (ID),
            UNIQUE KEY gedpers (gedcom, personID),
            KEY lastname (lastname, firstname),
            KEY firstname (firstname),
            KEY gedlast (gedcom, lastname, firstname),
            KEY gedfirst (gedcom, firstname),
            KEY birthplace (birthplace(20)),
            KEY altbirthplace (altbirthplace(20)),
            KEY deathplace (deathplace(20)),
            KEY burialplace (burialplace(20)),
            KEY baptplace (baptplace(20)),
            KEY confplace (confplace(20)),
            KEY initplace (initplace(20)),
            KEY endlplace (endlplace(20)),
            KEY changedate (changedate)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Families Table
   */
  private function create_families_table()
  {
    $table_name = $this->get_table_name('families');

    $sql = "CREATE TABLE $table_name (
            ID int(11) NOT NULL AUTO_INCREMENT,
            gedcom varchar(20) NOT NULL,
            familyID varchar(22) NOT NULL,
            husband varchar(22) NOT NULL,
            wife varchar(22) NOT NULL,
            marrdate varchar(50) NOT NULL,
            marrdatetr date NOT NULL,
            marrplace text NOT NULL,
            marrtype varchar(90) NOT NULL,
            divdate varchar(50) NOT NULL,
            divdatetr date NOT NULL,
            divplace text NOT NULL,
            status varchar(20) NOT NULL,
            sealdate varchar(50) NOT NULL,
            sealdatetr date NOT NULL,
            sealplace text NOT NULL,
            husborder tinyint NOT NULL,
            wifeorder tinyint NOT NULL,
            changedate datetime NOT NULL,
            living tinyint NOT NULL,
            private tinyint NOT NULL,
            branch varchar(512) NOT NULL,
            changedby varchar(100) NOT NULL,
            edituser varchar(100) NOT NULL,
            edittime int NOT NULL,
            PRIMARY KEY (ID),
            UNIQUE KEY familyID (gedcom, familyID),
            KEY husband (gedcom, husband),
            KEY wife (gedcom, wife),
            KEY marrplace (marrplace(20)),
            KEY divplace (divplace(20)),
            KEY changedate (changedate)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Children Table
   */
  private function create_children_table()
  {
    $table_name = $this->get_table_name('children');

    $sql = "CREATE TABLE $table_name (
            ID int(11) NOT NULL AUTO_INCREMENT,
            gedcom varchar(20) NOT NULL,
            familyID varchar(22) NOT NULL,
            personID varchar(22) NOT NULL,
            frel varchar(20) NOT NULL,
            mrel varchar(20) NOT NULL,
            sealdate varchar(50) NOT NULL,
            sealdatetr date NOT NULL,
            sealplace text NOT NULL,
            haskids tinyint NOT NULL,
            ordernum smallint NOT NULL,
            parentorder tinyint NOT NULL,
            PRIMARY KEY (ID),
            UNIQUE KEY familyID (gedcom, familyID, personID),
            KEY personID (gedcom, personID)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Events Table
   */
  private function create_events_table()
  {
    $table_name = $this->get_table_name('events');

    $sql = "CREATE TABLE $table_name (
            eventID int(11) NOT NULL AUTO_INCREMENT,
            gedcom varchar(20) NOT NULL,
            persfamID varchar(22) NOT NULL,
            eventtypeID int NOT NULL,
            eventdate varchar(50) NOT NULL,
            eventdatetr date NOT NULL,
            eventplace text NOT NULL,
            age varchar(12) NOT NULL,
            agency varchar(120) NOT NULL,
            cause varchar(90) NOT NULL,
            addressID varchar(10) NOT NULL,
            parenttag varchar(10) NOT NULL,
            info text NOT NULL,
            PRIMARY KEY (eventID),
            KEY persfamID (gedcom, persfamID),
            KEY eventplace (gedcom, eventplace(20))
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Event Types Table
   */
  private function create_eventtypes_table()
  {
    $table_name = $this->get_table_name('eventtypes');

    $sql = "CREATE TABLE $table_name (
            eventtypeID int(11) NOT NULL AUTO_INCREMENT,
            tag varchar(10) NOT NULL,
            description varchar(90) NOT NULL,
            display text NOT NULL,
            keep tinyint NOT NULL,
            collapse tinyint NOT NULL,
            ordernum smallint NOT NULL,
            ldsevent tinyint NOT NULL,
            type char(1) NOT NULL,
            PRIMARY KEY (eventtypeID),
            UNIQUE KEY typetagdesc (type, tag, description),
            KEY ordernum (ordernum)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Continue with other TNG tables...
   * Adding key tables for immediate compatibility
   */

  /**
   * WordPress-specific enhancement table for user permissions
   */
  private function create_user_permissions_table()
  {
    $table_name = $this->get_table_name('user_permissions');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            wp_user_id bigint(20) unsigned NOT NULL,
            gedcom varchar(20) NOT NULL,
            permission_level enum('none','view','edit','admin') DEFAULT 'none',
            can_view_private tinyint(1) DEFAULT 0,
            can_view_living tinyint(1) DEFAULT 0,
            can_edit_people tinyint(1) DEFAULT 0,
            can_edit_families tinyint(1) DEFAULT 0,
            can_import_gedcom tinyint(1) DEFAULT 0,
            can_export_gedcom tinyint(1) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY wp_user_gedcom (wp_user_id, gedcom),
            KEY gedcom (gedcom),
            KEY permission_level (permission_level),
            FOREIGN KEY (wp_user_id) REFERENCES {$this->wpdb->users}(ID) ON DELETE CASCADE
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Import logging for GEDCOM and TNG imports
   */
  private function create_import_log_table()
  {
    $table_name = $this->get_table_name('import_log');

    $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            import_type enum('gedcom','tng','manual') NOT NULL,
            filename varchar(255) DEFAULT NULL,
            gedcom varchar(20) NOT NULL,
            wp_user_id bigint(20) unsigned NOT NULL,
            status enum('started','in_progress','completed','failed') DEFAULT 'started',
            people_imported int DEFAULT 0,
            families_imported int DEFAULT 0,
            events_imported int DEFAULT 0,
            sources_imported int DEFAULT 0,
            media_imported int DEFAULT 0,
            errors_count int DEFAULT 0,
            warnings_count int DEFAULT 0,
            error_messages text,
            started_date datetime DEFAULT CURRENT_TIMESTAMP,
            completed_date datetime DEFAULT NULL,
            file_size bigint DEFAULT NULL,
            processing_time_seconds int DEFAULT NULL,
            PRIMARY KEY (id),
            KEY gedcom (gedcom),
            KEY wp_user_id (wp_user_id),
            KEY status (status),
            KEY started_date (started_date),
            FOREIGN KEY (wp_user_id) REFERENCES {$this->wpdb->users}(ID) ON DELETE CASCADE
        ) {$this->charset_collate};";

    dbDelta($sql);
  }
  /**
   * TNG-Compatible Sources Table
   */
  private function create_sources_table()
  {
    $table_name = $this->get_table_name('sources');

    $sql = "CREATE TABLE $table_name (
            sourceID varchar(22) NOT NULL,
            gedcom varchar(20) NOT NULL,
            title text NOT NULL,
            shorttitle varchar(255) NOT NULL,
            author varchar(255) NOT NULL,
            publisher varchar(255) NOT NULL,
            date varchar(50) NOT NULL,
            callnum varchar(255) NOT NULL,
            actualtext text NOT NULL,
            comments text NOT NULL,
            usenote tinyint NOT NULL,
            repo varchar(22) NOT NULL,
            publnote text NOT NULL,
            volume varchar(255) NOT NULL,
            page varchar(255) NOT NULL,
            film varchar(255) NOT NULL,
            PRIMARY KEY (gedcom, sourceID),
            KEY title (title(20))
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Citations Table
   */
  private function create_citations_table()
  {
    $table_name = $this->get_table_name('citations');

    $sql = "CREATE TABLE $table_name (
            citationID int(11) NOT NULL AUTO_INCREMENT,
            gedcom varchar(20) NOT NULL,
            persfamID varchar(22) NOT NULL,
            eventID varchar(10) NOT NULL,
            sourceID varchar(22) NOT NULL,
            ordernum float NOT NULL,
            description text NOT NULL,
            citedate varchar(50) NOT NULL,
            citedatetr date NOT NULL,
            citetext text NOT NULL,
            page text NOT NULL,
            quay varchar(2) NOT NULL,
            note text NOT NULL,
            PRIMARY KEY (citationID),
            KEY persfamID (gedcom, persfamID),
            KEY sourceID (gedcom, sourceID)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Repositories Table
   */
  private function create_repositories_table()
  {
    $table_name = $this->get_table_name('repositories');

    $sql = "CREATE TABLE $table_name (
            repoID varchar(22) NOT NULL,
            gedcom varchar(20) NOT NULL,
            reponame varchar(255) NOT NULL,
            addr1 varchar(60) NOT NULL,
            addr2 varchar(60) NOT NULL,
            city varchar(60) NOT NULL,
            state varchar(60) NOT NULL,
            zip varchar(10) NOT NULL,
            country varchar(60) NOT NULL,
            phone varchar(25) NOT NULL,
            email varchar(80) NOT NULL,
            www varchar(100) NOT NULL,
            note text NOT NULL,
            PRIMARY KEY (gedcom, repoID),
            KEY reponame (reponame)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Media Table
   */
  private function create_media_table()
  {
    $table_name = $this->get_table_name('media');

    $sql = "CREATE TABLE $table_name (
            mediaID int(11) NOT NULL AUTO_INCREMENT,
            gedcom varchar(20) NOT NULL,
            path varchar(255) NOT NULL,
            description text NOT NULL,
            notes text NOT NULL,
            mediatypeID int NOT NULL,
            usedate varchar(50) NOT NULL,
            usedatetr date NOT NULL,
            places text NOT NULL,
            changedate datetime NOT NULL,
            PRIMARY KEY (mediaID),
            KEY path (path)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Media Links Table
   */
  private function create_medialinks_table()
  {
    $table_name = $this->get_table_name('medialinks');

    $sql = "CREATE TABLE $table_name (
            medialinkID int(11) NOT NULL AUTO_INCREMENT,
            gedcom varchar(20) NOT NULL,
            mediaID int NOT NULL,
            personID varchar(22) NOT NULL,
            familyID varchar(22) NOT NULL,
            eventID varchar(10) NOT NULL,
            ordernum float NOT NULL,
            PRIMARY KEY (medialinkID),
            KEY mediaID (mediaID),
            KEY personID (gedcom, personID),
            KEY familyID (gedcom, familyID)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Media Types Table
   */
  private function create_mediatypes_table()
  {
    $table_name = $this->get_table_name('mediatypes');

    $sql = "CREATE TABLE $table_name (
            mediatypeID int(11) NOT NULL AUTO_INCREMENT,
            extensions varchar(50) NOT NULL,
            typename varchar(50) NOT NULL,
            tnmaxw int NOT NULL,
            tnmaxh int NOT NULL,
            dispheight int NOT NULL,
            PRIMARY KEY (mediatypeID),
            KEY typename (typename)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Albums Table
   */
  private function create_albums_table()
  {
    $table_name = $this->get_table_name('albums');

    $sql = "CREATE TABLE $table_name (
            albumID int(11) NOT NULL AUTO_INCREMENT,
            albumname varchar(100) NOT NULL,
            description text DEFAULT NULL,
            alwayson tinyint DEFAULT NULL,
            keywords text DEFAULT NULL,
            active tinyint NOT NULL,
            PRIMARY KEY (albumID),
            KEY albumname (albumname)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Album Links Table
   */
  private function create_albumlinks_table()
  {
    $table_name = $this->get_table_name('albumlinks');

    $sql = "CREATE TABLE $table_name (
            albumlinkID int(11) NOT NULL AUTO_INCREMENT,
            albumID int NOT NULL,
            mediaID int NOT NULL,
            ordernum int DEFAULT NULL,
            defphoto varchar(1) NOT NULL,
            PRIMARY KEY (albumlinkID),
            KEY albumID (albumID, ordernum)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Album to Entities Table
   */
  private function create_album2entities_table()
  {
    $table_name = $this->get_table_name('album2entities');

    $sql = "CREATE TABLE $table_name (
            alinkID int(11) NOT NULL AUTO_INCREMENT,
            gedcom varchar(20) NOT NULL,
            linktype char(1) NOT NULL,
            entityID varchar(100) NOT NULL,
            eventID varchar(10) NOT NULL,
            albumID int NOT NULL,
            ordernum float NOT NULL,
            PRIMARY KEY (alinkID),
            UNIQUE KEY alinkID (gedcom, entityID, albumID),
            KEY entityID (gedcom, entityID, ordernum)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Image Tags Table
   */
  private function create_image_tags_table()
  {
    $table_name = $this->get_table_name('image_tags');

    $sql = "CREATE TABLE $table_name (
            tagID int(11) NOT NULL AUTO_INCREMENT,
            gedcom varchar(20) NOT NULL,
            mediaID int NOT NULL,
            personID varchar(22) NOT NULL,
            x1 smallint NOT NULL,
            y1 smallint NOT NULL,
            x2 smallint NOT NULL,
            y2 smallint NOT NULL,
            PRIMARY KEY (tagID),
            KEY mediaID (mediaID),
            KEY personID (gedcom, personID)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Places Table
   */
  private function create_places_table()
  {
    $table_name = $this->get_table_name('places');

    $sql = "CREATE TABLE $table_name (
            place varchar(248) NOT NULL,
            latitude varchar(22) DEFAULT NULL,
            longitude varchar(22) DEFAULT NULL,
            zoom tinyint DEFAULT NULL,
            notes text DEFAULT NULL,
            PRIMARY KEY (place),
            KEY latitude (latitude),
            KEY longitude (longitude)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Address Table
   */
  private function create_address_table()
  {
    $table_name = $this->get_table_name('address');

    $sql = "CREATE TABLE $table_name (
            addressID int(11) NOT NULL AUTO_INCREMENT,
            address1 varchar(64) NOT NULL,
            address2 varchar(64) NOT NULL,
            city varchar(64) NOT NULL,
            state varchar(64) NOT NULL,
            zip varchar(10) NOT NULL,
            country varchar(64) NOT NULL,
            www varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(30) NOT NULL,
            gedcom varchar(20) NOT NULL,
            PRIMARY KEY (addressID),
            KEY address (gedcom, country, state, city)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Countries Table
   */
  private function create_countries_table()
  {
    $table_name = $this->get_table_name('countries');

    $sql = "CREATE TABLE $table_name (
            ID int(11) NOT NULL AUTO_INCREMENT,
            country varchar(50) NOT NULL,
            PRIMARY KEY (ID),
            KEY country (country)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible States Table
   */
  private function create_states_table()
  {
    $table_name = $this->get_table_name('states');

    $sql = "CREATE TABLE $table_name (
            ID int(11) NOT NULL AUTO_INCREMENT,
            state varchar(50) NOT NULL,
            country varchar(50) NOT NULL,
            PRIMARY KEY (ID),
            KEY state (state),
            KEY country (country)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Cemeteries Table
   */
  private function create_cemeteries_table()
  {
    $table_name = $this->get_table_name('cemeteries');

    $sql = "CREATE TABLE $table_name (
            cemeteryID int(11) NOT NULL AUTO_INCREMENT,
            cemname varchar(100) NOT NULL,
            address varchar(100) NOT NULL,
            city varchar(64) NOT NULL,
            state varchar(64) NOT NULL,
            zip varchar(10) NOT NULL,
            country varchar(64) NOT NULL,
            longitude varchar(22) DEFAULT NULL,
            latitude varchar(22) DEFAULT NULL,
            zoom tinyint DEFAULT NULL,
            notes text DEFAULT NULL,
            place varchar(248) NOT NULL,
            PRIMARY KEY (cemeteryID),
            KEY cemname (cemname),
            KEY place (place)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Association Table
   */
  private function create_assoc_table()
  {
    $table_name = $this->get_table_name('assoc');

    $sql = "CREATE TABLE $table_name (
            assocID int(11) NOT NULL AUTO_INCREMENT,
            gedcom varchar(20) NOT NULL,
            personID varchar(22) NOT NULL,
            associateID varchar(22) NOT NULL,
            relationship varchar(100) NOT NULL,
            note text NOT NULL,
            ordernum smallint NOT NULL,
            PRIMARY KEY (assocID),
            KEY personID (gedcom, personID),
            KEY associateID (gedcom, associateID)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Branches Table
   */
  private function create_branches_table()
  {
    $table_name = $this->get_table_name('branches');

    $sql = "CREATE TABLE $table_name (
            branchID varchar(22) NOT NULL,
            description varchar(255) NOT NULL,
            PRIMARY KEY (branchID),
            KEY description (description)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Branch Links Table
   */
  private function create_branchlinks_table()
  {
    $table_name = $this->get_table_name('branchlinks');

    $sql = "CREATE TABLE $table_name (
            brlinkID int(11) NOT NULL AUTO_INCREMENT,
            gedcom varchar(20) NOT NULL,
            personID varchar(22) NOT NULL,
            branchID varchar(22) NOT NULL,
            PRIMARY KEY (brlinkID),
            UNIQUE KEY gedpers (gedcom, personID, branchID),
            KEY branchID (branchID)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Extended Notes Table
   */
  private function create_xnotes_table()
  {
    $table_name = $this->get_table_name('xnotes');

    $sql = "CREATE TABLE $table_name (
            xnoteID varchar(22) NOT NULL,
            gedcom varchar(20) NOT NULL,
            note longtext NOT NULL,
            PRIMARY KEY (gedcom, xnoteID)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Note Links Table
   */
  private function create_notelinks_table()
  {
    $table_name = $this->get_table_name('notelinks');

    $sql = "CREATE TABLE $table_name (
            notelinkID int(11) NOT NULL AUTO_INCREMENT,
            gedcom varchar(20) NOT NULL,
            xnoteID varchar(22) NOT NULL,
            persfamID varchar(22) NOT NULL,
            eventID varchar(10) NOT NULL,
            ordernum float NOT NULL,
            PRIMARY KEY (notelinkID),
            KEY xnoteID (gedcom, xnoteID),
            KEY persfamID (gedcom, persfamID)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Most Wanted Table
   */
  private function create_mostwanted_table()
  {
    $table_name = $this->get_table_name('mostwanted');

    $sql = "CREATE TABLE $table_name (
            mostwantedID int(11) NOT NULL AUTO_INCREMENT,
            gedcom varchar(20) NOT NULL,
            personID varchar(22) NOT NULL,
            surname varchar(127) NOT NULL,
            givenname varchar(127) NOT NULL,
            birthdate varchar(50) NOT NULL,
            birthplace varchar(255) NOT NULL,
            deathdate varchar(50) NOT NULL,
            deathplace varchar(255) NOT NULL,
            comments text NOT NULL,
            submitter varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            submdate date NOT NULL,
            PRIMARY KEY (mostwantedID),
            KEY personID (gedcom, personID),
            KEY surname (surname)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Trees Table
   */
  private function create_trees_table()
  {
    $table_name = $this->get_table_name('trees');

    $sql = "CREATE TABLE $table_name (
            treeID int(11) NOT NULL AUTO_INCREMENT,
            treename varchar(50) NOT NULL,
            description text NOT NULL,
            owner varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            rootpersonID varchar(22) NOT NULL,
            gedcom varchar(20) NOT NULL,
            PRIMARY KEY (treeID),
            UNIQUE KEY gedcom (gedcom),
            KEY treename (treename)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Users Table (separate from WordPress users)
   */
  private function create_users_table()
  {
    $table_name = $this->get_table_name('tng_users');

    $sql = "CREATE TABLE $table_name (
            userID int(11) NOT NULL AUTO_INCREMENT,
            username varchar(30) NOT NULL,
            password varchar(255) NOT NULL,
            realname varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            gedcom varchar(500) NOT NULL,
            personID varchar(22) NOT NULL,
            allow_living varchar(15) NOT NULL,
            allow_private varchar(15) NOT NULL,
            allow_edit varchar(15) NOT NULL,
            allow_delete varchar(15) NOT NULL,
            lastlogin datetime NOT NULL,
            PRIMARY KEY (userID),
            UNIQUE KEY username (username),
            KEY email (email)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Languages Table
   */
  private function create_languages_table()
  {
    $table_name = $this->get_table_name('languages');

    $sql = "CREATE TABLE $table_name (
            language varchar(25) NOT NULL,
            labl varchar(255) NOT NULL,
            PRIMARY KEY (language, labl)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Reports Table
   */
  private function create_reports_table()
  {
    $table_name = $this->get_table_name('reports');

    $sql = "CREATE TABLE $table_name (
            reportID int(11) NOT NULL AUTO_INCREMENT,
            reportname varchar(100) NOT NULL,
            reportdesc text NOT NULL,
            reportfile varchar(100) NOT NULL,
            PRIMARY KEY (reportID),
            KEY reportname (reportname)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Templates Table
   */
  private function create_templates_table()
  {
    $table_name = $this->get_table_name('templates');

    $sql = "CREATE TABLE $table_name (
            template varchar(50) NOT NULL,
            PRIMARY KEY (template)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Temporary Events Table
   */
  private function create_temp_events_table()
  {
    $table_name = $this->get_table_name('temp_events');

    $sql = "CREATE TABLE $table_name (
            eventID int(11) NOT NULL AUTO_INCREMENT,
            gedcom varchar(20) NOT NULL,
            persfamID varchar(22) NOT NULL,
            eventtypeID int NOT NULL,
            eventdate varchar(50) NOT NULL,
            eventdatetr date NOT NULL,
            eventplace text NOT NULL,
            PRIMARY KEY (eventID),
            KEY persfamID (gedcom, persfamID)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Timeline Events Table
   */
  private function create_tlevents_table()
  {
    $table_name = $this->get_table_name('tlevents');

    $sql = "CREATE TABLE $table_name (
            tleventID int(11) NOT NULL AUTO_INCREMENT,
            tlevent varchar(255) NOT NULL,
            eventdate varchar(50) NOT NULL,
            eventdatetr date NOT NULL,
            eventplace varchar(255) NOT NULL,
            note text NOT NULL,
            PRIMARY KEY (tleventID),
            KEY eventdate (eventdatetr)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible Save Import Table
   */
  private function create_saveimport_table()
  {
    $table_name = $this->get_table_name('saveimport');

    $sql = "CREATE TABLE $table_name (
            importID int(11) NOT NULL AUTO_INCREMENT,
            filename varchar(255) NOT NULL,
            gedcom varchar(20) NOT NULL,
            importdate datetime NOT NULL,
            status enum('pending','processing','completed','failed') NOT NULL,
            PRIMARY KEY (importID),
            KEY gedcom (gedcom),
            KEY status (status)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible DNA Tests Table
   */
  private function create_dna_tests_table()
  {
    $table_name = $this->get_table_name('dna_tests');

    $sql = "CREATE TABLE $table_name (
            testID int(11) NOT NULL AUTO_INCREMENT,
            gedcom varchar(20) NOT NULL,
            personID varchar(22) NOT NULL,
            testtype varchar(50) NOT NULL,
            company varchar(100) NOT NULL,
            testdate date NOT NULL,
            results text NOT NULL,
            PRIMARY KEY (testID),
            KEY personID (gedcom, personID),
            KEY testtype (testtype)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible DNA Links Table
   */
  private function create_dna_links_table()
  {
    $table_name = $this->get_table_name('dna_links');

    $sql = "CREATE TABLE $table_name (
            linkID int(11) NOT NULL AUTO_INCREMENT,
            testID1 int NOT NULL,
            testID2 int NOT NULL,
            relationship varchar(100) NOT NULL,
            confidence varchar(20) NOT NULL,
            notes text NOT NULL,
            PRIMARY KEY (linkID),
            KEY testID1 (testID1),
            KEY testID2 (testID2)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * TNG-Compatible DNA Groups Table
   */
  private function create_dna_groups_table()
  {
    $table_name = $this->get_table_name('dna_groups');

    $sql = "CREATE TABLE $table_name (
            groupID int(11) NOT NULL AUTO_INCREMENT,
            groupname varchar(100) NOT NULL,
            description text NOT NULL,
            admin_personID varchar(22) NOT NULL,
            PRIMARY KEY (groupID),
            KEY groupname (groupname)
        ) {$this->charset_collate};";

    dbDelta($sql);
  }

  /**
   * Drop existing tables (use with caution!)
   */
  public function drop_existing_tables()
  {
    $tables = [
      'people',
      'families',
      'children',
      'events',
      'eventtypes',
      'sources',
      'citations',
      'repositories',
      'media',
      'medialinks',
      'mediatypes',
      'albums',
      'albumlinks',
      'album2entities',
      'image_tags',
      'places',
      'address',
      'countries',
      'states',
      'cemeteries',
      'assoc',
      'branches',
      'branchlinks',
      'xnotes',
      'notelinks',
      'mostwanted',
      'trees',
      'users',
      'languages',
      'reports',
      'templates',
      'temp_events',
      'tlevents',
      'saveimport',
      'dna_tests',
      'dna_links',
      'dna_groups',
      'user_permissions',
      'import_log'
    ];

    foreach ($tables as $table) {
      $table_name = $this->get_table_name($table);
      $this->wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
  }

  /**
   * Check if tables exist and are TNG-compatible
   */
  public function check_tng_compatibility()
  {
    $people_table = $this->get_table_name('people');

    // Check if table exists and has TNG structure
    $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '$people_table'");

    if (!$table_exists) {
      return false;
    }

    // Check for key TNG fields
    $columns = $this->wpdb->get_results("SHOW COLUMNS FROM $people_table");
    $tng_fields = ['personID', 'gedcom', 'firstname', 'lastname', 'birthdate', 'deathdate'];

    $found_fields = array_column($columns, 'Field');

    foreach ($tng_fields as $field) {
      if (!in_array($field, $found_fields)) {
        return false;
      }
    }

    return true;
  }
}
