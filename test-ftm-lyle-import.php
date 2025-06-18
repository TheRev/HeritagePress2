<?php

/**
 * Comprehensive test for importing FTM_lyle_2025-06-17.ged
 * Tests all components: header, individuals, families, sources, repositories, media
 */

require_once 'heritagepress.php';
require_once 'includes/gedcom/class-hp-gedcom-importer.php';
require_once 'includes/gedcom/class-hp-enhanced-gedcom-parser.php';

echo "<h1>FTM Lyle GEDCOM Import Test</h1>\n";
echo "<h2>Testing FTM_lyle_2025-06-17.ged Import</h2>\n";

// Database setup - simulate WordPress wpdb
if (!class_exists('wpdb')) {
  class wpdb
  {
    private $connection;

    public function __construct($user, $pass, $db, $host)
    {
      $this->connection = new mysqli($host, $user, $pass, $db);
    }

    public function get_var($query)
    {
      $result = $this->connection->query($query);
      if ($result && $row = $result->fetch_row()) {
        return $row[0];
      }
      return null;
    }

    public function get_row($query)
    {
      $result = $this->connection->query($query);
      if ($result && $row = $result->fetch_object()) {
        return $row;
      }
      return null;
    }

    public function get_results($query)
    {
      $result = $this->connection->query($query);
      $rows = [];
      if ($result) {
        while ($row = $result->fetch_object()) {
          $rows[] = $row;
        }
      }
      return $rows;
    }

    public function query($query)
    {
      return $this->connection->query($query);
    }
  }
}

global $wpdb;
$wpdb = new wpdb('root', '', 'heritagepress2', 'localhost');

// GEDCOM file path
$gedcom_file = 'C:\MAMP\htdocs\HeritagePress2\gedcom_test_files\FTM_lyle_2025-06-17.ged';

if (!file_exists($gedcom_file)) {
  echo "<p><strong>ERROR:</strong> GEDCOM file not found: " . htmlspecialchars($gedcom_file) . "</p>\n";
  exit;
}

echo "<h3>File Information</h3>\n";
echo "<ul>\n";
echo "<li>File: " . htmlspecialchars($gedcom_file) . "</li>\n";
echo "<li>Size: " . number_format(filesize($gedcom_file)) . " bytes</li>\n";
echo "<li>Lines: " . count(file($gedcom_file)) . "</li>\n";
echo "</ul>\n";

// Test import options systematically
$import_options = [
  'default' => [],
  'all_events' => ['allevents' => true],
  'events_only' => ['eventsonly' => true],
  'uppercase_surnames' => ['ucaselast' => true],
  'import_media' => ['importmedia' => true],
  'import_coordinates' => ['importlatlong' => true],
  'overwrite_mode' => ['del' => true],
  'comprehensive' => [
    'allevents' => true,
    'importmedia' => true,
    'importlatlong' => true,
    'ucaselast' => true
  ]
];

foreach ($import_options as $test_name => $options) {
  echo "<h3>Testing: " . ucwords(str_replace('_', ' ', $test_name)) . "</h3>\n";

  // Clear existing data for clean test
  if (isset($options['del']) && $options['del']) {
    echo "<p>Clearing existing data...</p>\n";
    $wpdb->query("DELETE FROM hp_people WHERE 1=1");
    $wpdb->query("DELETE FROM hp_families WHERE 1=1");
    $wpdb->query("DELETE FROM hp_sources WHERE 1=1");
    $wpdb->query("DELETE FROM hp_repositories WHERE 1=1");
    $wpdb->query("DELETE FROM hp_media WHERE 1=1");
    $wpdb->query("DELETE FROM hp_events WHERE 1=1");
    $wpdb->query("DELETE FROM hp_citations WHERE 1=1");
  }

  echo "<p>Running import with options: " . json_encode($options) . "</p>\n";

  try {
    // Initialize importer with file, tree, and options
    $importer = new HP_GEDCOM_Importer_Controller($gedcom_file, 'lyle_test', $options);
    $result = $importer->import();

    if ($result['success']) {
      echo "<p><strong>✓ Import successful!</strong></p>\n";

      // Count imported records
      $counts = [
        'individuals' => $wpdb->get_var("SELECT COUNT(*) FROM hp_people"),
        'families' => $wpdb->get_var("SELECT COUNT(*) FROM hp_families"),
        'sources' => $wpdb->get_var("SELECT COUNT(*) FROM hp_sources"),
        'repositories' => $wpdb->get_var("SELECT COUNT(*) FROM hp_repositories"),
        'media' => $wpdb->get_var("SELECT COUNT(*) FROM hp_media"),
        'events' => $wpdb->get_var("SELECT COUNT(*) FROM hp_events"),
        'citations' => $wpdb->get_var("SELECT COUNT(*) FROM hp_citations")
      ];

      echo "<h4>Import Statistics</h4>\n";
      echo "<ul>\n";
      foreach ($counts as $type => $count) {
        echo "<li>" . ucwords($type) . ": " . number_format($count) . "</li>\n";
      }
      echo "</ul>\n";

      // Detailed verification for comprehensive test
      if ($test_name === 'comprehensive') {
        echo "<h4>Detailed Verification</h4>\n";

        // Check Lyle Van Volkenburg
        $person = $wpdb->get_row("SELECT * FROM hp_people WHERE personid = 'I114'");
        if ($person) {
          echo "<p><strong>✓ Person I114 (Lyle Van Volkenburg) found:</strong></p>\n";
          echo "<ul>\n";
          echo "<li>Name: " . htmlspecialchars($person->firstname . ' ' . $person->lastname) . "</li>\n";
          echo "<li>Birth Date: " . htmlspecialchars($person->birthdatetr ?? 'N/A') . "</li>\n";
          echo "<li>Birth Place: " . htmlspecialchars($person->birthplace ?? 'N/A') . "</li>\n";
          echo "<li>Death Date: " . htmlspecialchars($person->deathdatetr ?? 'N/A') . "</li>\n";
          echo "<li>Death Place: " . htmlspecialchars($person->deathplace ?? 'N/A') . "</li>\n";
          echo "</ul>\n";

          // Check coordinates if imported
          if (isset($options['importlatlong']) && $options['importlatlong']) {
            echo "<p><strong>Coordinates:</strong></p>\n";
            echo "<ul>\n";
            echo "<li>Birth Latitude: " . ($person->birthlat ?? 'N/A') . "</li>\n";
            echo "<li>Birth Longitude: " . ($person->birthlong ?? 'N/A') . "</li>\n";
            echo "<li>Death Latitude: " . ($person->deathlat ?? 'N/A') . "</li>\n";
            echo "<li>Death Longitude: " . ($person->deathlong ?? 'N/A') . "</li>\n";
            echo "</ul>\n";
          }
        } else {
          echo "<p><strong>✗ Person I114 not found!</strong></p>\n";
        }

        // Check family F31
        $family = $wpdb->get_row("SELECT * FROM hp_families WHERE familyid = 'F31'");
        if ($family) {
          echo "<p><strong>✓ Family F31 found:</strong></p>\n";
          echo "<ul>\n";
          echo "<li>Husband: " . htmlspecialchars($family->husband ?? 'N/A') . "</li>\n";
          echo "<li>Marriage Date: " . htmlspecialchars($family->marrdatetr ?? 'N/A') . "</li>\n";
          echo "<li>Marriage Place: " . htmlspecialchars($family->marrplace ?? 'N/A') . "</li>\n";
          echo "</ul>\n";
        } else {
          echo "<p><strong>✗ Family F31 not found!</strong></p>\n";
        }

        // Check sources
        $source_count = $wpdb->get_var("SELECT COUNT(*) FROM hp_sources");
        echo "<p><strong>Sources imported:</strong> " . $source_count . "</p>\n";

        $sample_sources = $wpdb->get_results("SELECT sourceid, title FROM hp_sources LIMIT 5");
        if ($sample_sources) {
          echo "<ul>\n";
          foreach ($sample_sources as $source) {
            echo "<li>" . htmlspecialchars($source->sourceid) . ": " . htmlspecialchars($source->title) . "</li>\n";
          }
          echo "</ul>\n";
        }

        // Check repositories
        $repo_count = $wpdb->get_var("SELECT COUNT(*) FROM hp_repositories");
        echo "<p><strong>Repositories imported:</strong> " . $repo_count . "</p>\n";

        $repositories = $wpdb->get_results("SELECT repoid, reponame FROM hp_repositories");
        if ($repositories) {
          echo "<ul>\n";
          foreach ($repositories as $repo) {
            echo "<li>" . htmlspecialchars($repo->repoid) . ": " . htmlspecialchars($repo->reponame) . "</li>\n";
          }
          echo "</ul>\n";
        }

        // Check media objects if imported
        if (isset($options['importmedia']) && $options['importmedia']) {
          $media_count = $wpdb->get_var("SELECT COUNT(*) FROM hp_media");
          echo "<p><strong>Media objects imported:</strong> " . $media_count . "</p>\n";

          $sample_media = $wpdb->get_results("SELECT mediaid, description, path FROM hp_media LIMIT 5");
          if ($sample_media) {
            echo "<ul>\n";
            foreach ($sample_media as $media) {
              echo "<li>" . htmlspecialchars($media->mediaid) . ": " . htmlspecialchars($media->description) . " (" . htmlspecialchars($media->path) . ")</li>\n";
            }
            echo "</ul>\n";
          }
        }

        // Check events if imported
        if (isset($options['allevents']) && $options['allevents']) {
          $event_count = $wpdb->get_var("SELECT COUNT(*) FROM hp_events");
          echo "<p><strong>Events imported:</strong> " . $event_count . "</p>\n";

          $sample_events = $wpdb->get_results("
                        SELECT personid, eventtype, eventdate, eventplace
                        FROM hp_events
                        WHERE personid = 'I114'
                        LIMIT 10
                    ");
          if ($sample_events) {
            echo "<p><strong>Sample events for I114:</strong></p>\n";
            echo "<ul>\n";
            foreach ($sample_events as $event) {
              echo "<li>" . htmlspecialchars($event->eventtype) . ": " .
                htmlspecialchars($event->eventdate) . " at " .
                htmlspecialchars($event->eventplace) . "</li>\n";
            }
            echo "</ul>\n";
          }
        }

        // Check citations
        $citation_count = $wpdb->get_var("SELECT COUNT(*) FROM hp_citations");
        echo "<p><strong>Citations imported:</strong> " . $citation_count . "</p>\n";

        $sample_citations = $wpdb->get_results("
                    SELECT sourceID, description, page
                    FROM hp_citations
                    LIMIT 5
                ");
        if ($sample_citations) {
          echo "<ul>\n";
          foreach ($sample_citations as $citation) {
            echo "<li>Source " . htmlspecialchars($citation->sourceID) . ": " .
              htmlspecialchars($citation->description) . " (Page: " .
              htmlspecialchars($citation->page) . ")</li>\n";
          }
          echo "</ul>\n";
        }
        // Display header info if available
        if (isset($result['stats']['header_info']) && !empty($result['stats']['header_info'])) {
          $header_info = $result['stats']['header_info'];
          echo "<h4>GEDCOM Header Information</h4>\n";
          echo "<ul>\n";
          foreach ($header_info as $key => $value) {
            if (!empty($value)) {
              echo "<li>" . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . ": " . htmlspecialchars($value) . "</li>\n";
            }
          }
          echo "</ul>\n";
        }
      }
    } else {
      echo "<p><strong>✗ Import failed:</strong> " . htmlspecialchars($result['message']) . "</p>\n";
    }
  } catch (Exception $e) {
    echo "<p><strong>✗ Import error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
  }

  echo "<hr>\n";
}

echo "<h3>Test Complete</h3>\n";
echo "<p>FTM Lyle GEDCOM import testing finished.</p>\n";
