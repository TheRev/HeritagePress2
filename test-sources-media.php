<?php
define('ABSPATH', 'c:/MAMP/htdocs/HeritagePress2/');
require_once 'c:/MAMP/htdocs/HeritagePress2/wp-config.php';
require_once 'includes/gedcom/class-hp-enhanced-gedcom-parser.php';

// Test with the real GEDCOM file
$gedcom_file = 'C:\MAMP\htdocs\HeritagePress2\gedcom_test_files\FTM_lyle_2025-06-17.ged';
$tree_id = 'test';

echo "=== Testing Enhanced Parser with Sources and Media ===\n";

$import_options = array(
  'del' => 'append',
  'allevents' => 'yes',
  'eventsonly' => '',
  'ucaselast' => 0,
  'norecalc' => 0,
  'neweronly' => 0,
  'importmedia' => 1,
  'importlatlong' => 1,
  'offsetchoice' => 'auto',
  'useroffset' => 0,
  'branch' => ''
);

try {
  $parser = new HP_Enhanced_GEDCOM_Parser($gedcom_file, $tree_id, $import_options);
  echo "Parser created successfully\n";

  $result = $parser->parse();

  if ($result['success']) {
    echo "\n=== Import Results ===\n";
    echo "Individuals: " . $result['stats']['individuals'] . "\n";
    echo "Families: " . $result['stats']['families'] . "\n";
    echo "Sources: " . $result['stats']['sources'] . "\n";
    echo "Media: " . $result['stats']['media'] . "\n";
    echo "Notes: " . $result['stats']['notes'] . "\n";
    echo "Events: " . $result['stats']['events'] . "\n";

    if (!empty($result['warnings'])) {
      echo "\nWarnings:\n";
      foreach ($result['warnings'] as $warning) {
        echo "- $warning\n";
      }
    }
  } else {
    echo "Error: " . $result['error'] . "\n";
  }
} catch (Exception $e) {
  echo "Exception: " . $e->getMessage() . "\n";
}
