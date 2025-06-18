<?php

/**
 * Debug individual parsing step by step
 */

require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

// Simple line-by-line GEDCOM reader to debug what's happening
$gedcom_file = 'C:\\MAMP\\htdocs\\HeritagePress2\\gedcom_test_files\\FTM_lyle_2025-06-17.ged';

echo "=== GEDCOM INDIVIDUAL PARSING DEBUG ===\n\n";

$lines = file($gedcom_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$in_individual = false;
$individual_id = '';
$line_count = 0;

foreach ($lines as $line_num => $line) {
  $line_count++;

  // Parse line
  if (preg_match('/^(\d+)\s+(\S+)\s*(.*)$/', $line, $matches)) {
    $level = $matches[1];
    $tag = $matches[2];
    $value = isset($matches[3]) ? $matches[3] : '';

    // Look for individual records
    if ($level == 0 && preg_match('/^@(I\d+)@$/', $tag, $id_matches)) {
      if ($value == 'INDI') {
        $in_individual = true;
        $individual_id = $id_matches[1];
        echo "Found individual: $individual_id at line $line_count\n";
        echo "Following individual events:\n";
      } else {
        $in_individual = false;
      }
    }

    // Track events within individual
    if ($in_individual && $level == 1) {
      echo "  Level $level: $tag = $value\n";

      if (in_array($tag, ['BIRT', 'DEAT', 'BURI', 'RESI', 'EDUC', 'EVEN'])) {
        echo "    >>> EVENT FOUND: $tag <<<\n";

        // Look for date and place on next lines
        for ($i = $line_num + 1; $i < count($lines) && $i < $line_num + 10; $i++) {
          if (preg_match('/^(\d+)\s+(\S+)\s*(.*)$/', $lines[$i], $sub_matches)) {
            $sub_level = $sub_matches[1];
            $sub_tag = $sub_matches[2];
            $sub_value = isset($sub_matches[3]) ? $sub_matches[3] : '';

            if ($sub_level <= 1) break; // Next main event

            if ($sub_level == 2 && in_array($sub_tag, ['DATE', 'PLAC'])) {
              echo "      $sub_tag: $sub_value\n";
            }
          }
        }
      }
    }

    // Stop after finding the first individual to avoid too much output
    if ($in_individual && $level == 0 && $tag != "@$individual_id@") {
      break;
    }
  }
}

echo "\n=== DEBUG COMPLETED ===\n";
