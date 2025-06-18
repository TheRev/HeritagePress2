<?php

/**
 * Detailed trace of individual parsing loop
 */

// Load WordPress properly
require_once('c:/MAMP/htdocs/HeritagePress2/wp-config.php');

// Load the enhanced parser
require_once('includes/gedcom/class-hp-enhanced-gedcom-parser.php');

$gedcom_file = 'C:\\MAMP\\htdocs\\HeritagePress2\\gedcom_test_files\\FTM_lyle_2025-06-17.ged';

echo "=== DETAILED INDIVIDUAL PARSING TRACE ===\n";

// Create a custom parser class that exposes the parsing details
class TracingParser extends HP_Enhanced_GEDCOM_Parser
{
  public function trace_parse_individual($person_id)
  {
    global $wpdb;

    $info = $this->init_individual();
    $events = array();
    $prev_level = 1;

    $this->line_info = $this->get_line();
    echo "Starting individual parse for $person_id\n";
    echo "First line: Level {$this->line_info['level']}, Tag: {$this->line_info['tag']}, Rest: {$this->line_info['rest']}\n";

    $loop_count = 0;
    while ($this->line_info['tag'] && $this->line_info['level'] >= $prev_level) {
      $loop_count++;
      echo "\n=== LOOP $loop_count ===\n";
      echo "Current line: Level {$this->line_info['level']}, Tag: {$this->line_info['tag']}, Rest: {$this->line_info['rest']}\n";

      if ($loop_count > 20) {
        echo "Breaking loop - too many iterations\n";
        break;
      }

      if ($this->line_info['level'] == $prev_level) {
        $tag = $this->line_info['tag'];
        echo "Processing tag: $tag at level {$this->line_info['level']}\n";

        switch ($tag) {
          case 'NAME':
            echo "  -> Parsing NAME\n";
            $this->parse_name($info);
            break;

          case 'SEX':
            echo "  -> Parsing SEX\n";
            $info['sex'] = strtoupper(trim($this->line_info['rest']));
            $this->line_info = $this->get_line();
            break;

          case 'BIRT':
            echo "  -> Parsing BIRT event\n";
            $birth_info = $this->parse_event($prev_level);
            $info['birthdate'] = $birth_info['date'];
            $info['birthplace'] = $birth_info['place'];
            $this->save_event($person_id, 'BIRT', $birth_info);
            echo "  -> BIRT event saved\n";
            break;

          case 'DEAT':
            echo "  -> Parsing DEAT event\n";
            $death_info = $this->parse_event($prev_level);
            $info['deathdate'] = $death_info['date'];
            $info['deathplace'] = $death_info['place'];
            $this->save_event($person_id, 'DEAT', $death_info);
            echo "  -> DEAT event saved\n";
            break;

          case 'BURI':
            echo "  -> Parsing BURI event\n";
            $burial_info = $this->parse_event($prev_level);
            $info['burialdate'] = $burial_info['date'];
            $info['burialplace'] = $burial_info['place'];
            $this->save_event($person_id, 'BURI', $burial_info);
            echo "  -> BURI event saved\n";
            break;

          case 'RESI':
            echo "  -> Parsing RESI event\n";
            $residence_info = $this->parse_event($prev_level);
            $this->save_event($person_id, 'RESI', $residence_info);
            echo "  -> RESI event saved\n";
            break;

          case 'EVEN':
          case 'EDUC':
          case 'OCCU':
            echo "  -> Parsing $tag event\n";
            $event_info = $this->parse_event($prev_level);
            $this->save_event($person_id, $tag, $event_info);
            echo "  -> $tag event saved\n";
            break;

          default:
            echo "  -> Skipping unknown tag: $tag\n";
            $this->line_info = $this->get_line();
            break;
        }

        echo "After processing $tag, next line: Level {$this->line_info['level']}, Tag: {$this->line_info['tag']}\n";
      } else {
        echo "Level mismatch: current {$this->line_info['level']}, expected >= $prev_level\n";
        break;
      }
    }

    echo "\nExited loop after $loop_count iterations\n";
    echo "Final line: Level {$this->line_info['level']}, Tag: {$this->line_info['tag']}\n";

    // Save individual to database
    $this->save_individual($person_id, $info);
    return $info;
  }
}

// Clear events table first
global $wpdb;
$wpdb->delete($wpdb->prefix . 'hp_events', array('gedcom' => 'trace_test'));

// Create parser
$parser = new TracingParser($gedcom_file, 'trace_test', array(
  'del' => 'yes',
  'allevents' => 'yes'
));

// Use reflection to access private methods
$reflection = new ReflectionClass($parser);

// Access the file handle
$file_handle_prop = $reflection->getProperty('file_handle');
$file_handle_prop->setAccessible(true);
$file_handle = $file_handle_prop->getValue($parser);

// Access get_line method
$get_line_method = $reflection->getMethod('get_line');
$get_line_method->setAccessible(true);

// Access line_info property
$line_info_prop = $reflection->getProperty('line_info');
$line_info_prop->setAccessible(true);

// Find the individual record
$line_count = 0;
do {
  $line_info = $get_line_method->invoke($parser);
  $line_info_prop->setValue($parser, $line_info);
  $line_count++;

  if ($line_count > 100) break; // Safety limit

} while (!($line_info['level'] == 0 && preg_match('/^@I114@/', $line_info['tag'])));

if ($line_info && preg_match('/^@I114@/', $line_info['tag'])) {
  echo "Found individual I114 at line $line_count\n";

  // Parse the individual
  $info = $parser->trace_parse_individual('I114');

  // Check how many events were saved
  $events_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}hp_events WHERE gedcom = %s AND persfamID = %s",
    'trace_test',
    'I114'
  ));

  echo "\nEvents saved for I114: $events_count\n";
} else {
  echo "Individual I114 not found!\n";
}

fclose($file_handle);
echo "=== TRACE COMPLETED ===\n";
