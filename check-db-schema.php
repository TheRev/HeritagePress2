<?php
// Check database schema and create missing tables
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

global $wpdb;

echo "=== DATABASE SCHEMA CHECK ===\n";

// List of all HeritagePress tables with their purposes
$hp_tables = [
  'hp_people' => 'Individual records',
  'hp_families' => 'Family relationships',
  'hp_sources' => 'Source citations',
  'hp_media' => 'Media objects',
  'hp_notes' => 'Notes and comments',
  'hp_events' => 'Events and facts',
  'hp_eventtypes' => 'Event type definitions',
  'hp_repositories' => 'Repository records',
  'hp_citations' => 'Citation links',
  'hp_xnotes' => 'Extended notes',
  'hp_places' => 'Place records'
];

echo "Checking for existing HeritagePress tables:\n";

$existing_tables = [];
$missing_tables = [];

foreach ($hp_tables as $table => $description) {
  $full_table = $wpdb->prefix . $table;
  $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table'");

  if ($exists) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table");
    echo "✓ $table ($description) - $count records\n";
    $existing_tables[] = $table;
  } else {
    echo "✗ $table ($description) - MISSING\n";
    $missing_tables[] = $table;
  }
}

if (!empty($missing_tables)) {
  echo "\nCreating missing tables:\n";
  // Create hp_notes table if missing
  if (in_array('hp_notes', $missing_tables)) {
    $sql = "CREATE TABLE {$wpdb->prefix}hp_notes (
            noteID varchar(22) NOT NULL,
            gedcom varchar(20) NOT NULL DEFAULT '',
            note longtext,
            nn varchar(255) DEFAULT NULL,
            PRIMARY KEY (noteID, gedcom),
            KEY gedcom (gedcom),
            KEY nn (nn)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

    $result = $wpdb->query($sql);
    if ($result !== false) {
      echo "✓ Created hp_notes table\n";
    } else {
      echo "✗ Failed to create hp_notes table\n";
    }
  }

  // Create hp_citations table if missing
  if (in_array('hp_citations', $missing_tables)) {
    $sql = "CREATE TABLE {$wpdb->prefix}hp_citations (
            citationID varchar(255) NOT NULL,
            gedcom varchar(255) NOT NULL DEFAULT '',
            sourceID varchar(255) DEFAULT NULL,
            page varchar(255) DEFAULT NULL,
            quay varchar(10) DEFAULT NULL,
            quality varchar(10) DEFAULT NULL,
            PRIMARY KEY (citationID, gedcom),
            KEY gedcom (gedcom),
            KEY sourceID (sourceID)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

    $result = $wpdb->query($sql);
    if ($result !== false) {
      echo "✓ Created hp_citations table\n";
    } else {
      echo "✗ Failed to create hp_citations table\n";
    }
  }

  // Create hp_xnotes table if missing
  if (in_array('hp_xnotes', $missing_tables)) {
    $sql = "CREATE TABLE {$wpdb->prefix}hp_xnotes (
            ID int(11) NOT NULL AUTO_INCREMENT,
            persfamID varchar(255) NOT NULL,
            gedcom varchar(255) NOT NULL DEFAULT '',
            eventID varchar(255) DEFAULT NULL,
            note longtext,
            secret varchar(10) DEFAULT NULL,
            PRIMARY KEY (ID),
            KEY persfamID (persfamID),
            KEY gedcom (gedcom)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

    $result = $wpdb->query($sql);
    if ($result !== false) {
      echo "✓ Created hp_xnotes table\n";
    } else {
      echo "✗ Failed to create hp_xnotes table\n";
    }
  }
  // Create hp_places table if missing
  if (in_array('hp_places', $missing_tables)) {
    $sql = "CREATE TABLE {$wpdb->prefix}hp_places (
            placeID int(11) NOT NULL AUTO_INCREMENT,
            gedcom varchar(255) NOT NULL DEFAULT '',
            place varchar(255) NOT NULL,
            latitude decimal(10,7) DEFAULT NULL,
            longitude decimal(10,7) DEFAULT NULL,
            notes text,
            PRIMARY KEY (placeID),
            KEY gedcom (gedcom),
            KEY place (place)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

    $result = $wpdb->query($sql);
    if ($result !== false) {
      echo "✓ Created hp_places table\n";
    } else {
      echo "✗ Failed to create hp_places table\n";
    }
  }

  // Create hp_eventtypes table if missing
  if (in_array('hp_eventtypes', $missing_tables)) {
    $sql = "CREATE TABLE {$wpdb->prefix}hp_eventtypes (
            eventtypeID int(11) NOT NULL AUTO_INCREMENT,
            eventtype varchar(10) NOT NULL,
            type varchar(1) NOT NULL DEFAULT 'I',
            display varchar(100) NOT NULL,
            keep varchar(1) NOT NULL DEFAULT 'Y',
            listorder int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (eventtypeID),
            UNIQUE KEY eventtype (eventtype)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

    $result = $wpdb->query($sql);
    if ($result !== false) {
      echo "✓ Created hp_eventtypes table\n";

      // Insert default GEDCOM event types
      $default_events = [
        // Individual events
        ['BIRT', 'I', 'Birth', 'Y', 1],
        ['DEAT', 'I', 'Death', 'Y', 2],
        ['BAPM', 'I', 'Baptism', 'Y', 3],
        ['BURI', 'I', 'Burial', 'Y', 4],
        ['CHR', 'I', 'Christening', 'Y', 5],
        ['CONF', 'I', 'Confirmation', 'Y', 6],
        ['EMIG', 'I', 'Emigration', 'Y', 7],
        ['IMMI', 'I', 'Immigration', 'Y', 8],
        ['NATU', 'I', 'Naturalization', 'Y', 9],
        ['OCCU', 'I', 'Occupation', 'Y', 10],
        ['RESI', 'I', 'Residence', 'Y', 11],
        ['GRAD', 'I', 'Graduation', 'Y', 12],
        ['RETI', 'I', 'Retirement', 'Y', 13],
        ['CENS', 'I', 'Census', 'Y', 14],
        ['WILL', 'I', 'Will', 'Y', 15],
        ['PROB', 'I', 'Probate', 'Y', 16],
        ['EDUC', 'I', 'Education', 'Y', 17],
        ['RELI', 'I', 'Religion', 'Y', 18],

        // Family events
        ['MARR', 'F', 'Marriage', 'Y', 20],
        ['DIV', 'F', 'Divorce', 'Y', 21],
        ['ENGA', 'F', 'Engagement', 'Y', 22],
        ['MARS', 'F', 'Marriage Settlement', 'Y', 23],
        ['MARL', 'F', 'Marriage License', 'Y', 24],
        ['MARB', 'F', 'Marriage Banns', 'Y', 25],
        ['ANUL', 'F', 'Annulment', 'Y', 26],
        ['CENS', 'F', 'Census', 'Y', 27],
        ['DIVF', 'F', 'Divorce Filed', 'Y', 28],
        ['EVEN', 'I', 'Event', 'Y', 30],
        ['EVEN', 'F', 'Event', 'Y', 31]
      ];

      foreach ($default_events as $event) {
        $wpdb->insert(
          $wpdb->prefix . 'hp_eventtypes',
          [
            'eventtype' => $event[0],
            'type' => $event[1],
            'display' => $event[2],
            'keep' => $event[3],
            'listorder' => $event[4]
          ]
        );
      }
      echo "✓ Populated hp_eventtypes with default GEDCOM events\n";
    } else {
      echo "✗ Failed to create hp_eventtypes table\n";
    }
  }
}

echo "\n=== SCHEMA CHECK COMPLETED ===\n";

if (empty($missing_tables)) {
  echo "All required tables exist!\n";
} else {
  echo "Missing tables have been created.\n";
}

// Show table structures for verification
echo "\nTable structures:\n";
foreach ($existing_tables as $table) {
  $full_table = $wpdb->prefix . $table;
  echo "\n$table structure:\n";
  $structure = $wpdb->get_results("DESCRIBE $full_table");
  foreach ($structure as $column) {
    echo "  {$column->Field}: {$column->Type}\n";
  }
}
