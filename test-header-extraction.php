<?php

/**
 * Test header extraction from FTM GEDCOM with enhanced parser
 */

echo "=== Enhanced Header Extraction Test ===\n";

// Create a simple header parser to test our enhancements
class SimpleHeaderParser
{
  private $file_handle;
  private $line_info = array();
  private $header_info = array();
  private $submitters = array();

  public function __construct($file_path)
  {
    $this->file_handle = fopen($file_path, 'r');
  }

  public function parse_header()
  {
    $this->header_info = array(
      'source_program' => '',
      'source_name' => '',
      'source_version' => '',
      'source_corporation' => '',
      'source_address' => '',
      'source_phone' => '',
      'destination' => '',
      'submitter' => '',
      'gedcom_version' => '',
      'gedcom_form' => '',
      'character_set' => '',
      'date' => '',
      'time' => '',
      'filename' => '',
      'copyright' => ''
    );

    // Read until we hit the first non-header record
    while (($line = fgets($this->file_handle)) !== false) {
      $line = rtrim($line);
      if (empty($line)) continue;

      $this->line_info = $this->parse_line($line);

      // Stop when we hit a non-header record
      if ($this->line_info['level'] == 0 && $this->line_info['tag'] != 'HEAD') {
        // Check if this is a submitter record
        if ($this->line_info['tag'] == 'SUBM') {
          $this->parse_submitter_record();
        }
        break;
      }

      if ($this->line_info['level'] == 1) {
        $this->parse_header_tag();
      }
    }

    return array(
      'header' => $this->header_info,
      'submitters' => $this->submitters
    );
  }

  private function parse_line($line)
  {
    // Remove BOM if present
    $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);

    if (preg_match('/^(\d+)\s+(@[^@]+@\s+)?(\S+)(\s+(.*))?$/', $line, $matches)) {
      return array(
        'level' => (int)$matches[1],
        'id' => isset($matches[2]) ? trim($matches[2]) : '',
        'tag' => $matches[3],
        'rest' => isset($matches[5]) ? $matches[5] : ''
      );
    }
    return array('level' => -1, 'id' => '', 'tag' => '', 'rest' => '');
  }

  private function parse_header_tag()
  {
    $tag = $this->line_info['tag'];
    $value = trim($this->line_info['rest']);

    switch ($tag) {
      case 'SOUR':
        $this->header_info['source_program'] = $value;
        $this->parse_source_details();
        break;
      case 'DEST':
        $this->header_info['destination'] = $value;
        break;
      case 'SUBM':
        $this->header_info['submitter'] = $value;
        break;
      case 'GEDC':
        $this->parse_gedcom_details();
        break;
      case 'CHAR':
        $this->header_info['character_set'] = $value;
        break;
      case 'DATE':
        $this->header_info['date'] = $value;
        break;
      case 'FILE':
        $this->header_info['filename'] = $value;
        break;
    }
  }

  private function parse_source_details()
  {
    while (($line = fgets($this->file_handle)) !== false) {
      $line = rtrim($line);
      if (empty($line)) continue;

      $info = $this->parse_line($line);
      if ($info['level'] != 2) {
        // Put the line back (simulate)
        fseek($this->file_handle, -strlen($line) - 1, SEEK_CUR);
        break;
      }

      switch ($info['tag']) {
        case 'VERS':
          $this->header_info['source_version'] = trim($info['rest']);
          break;
        case 'NAME':
          $this->header_info['source_name'] = trim($info['rest']);
          break;
        case 'CORP':
          $this->header_info['source_corporation'] = trim($info['rest']);
          $this->parse_corporation_details();
          break;
      }
    }
  }

  private function parse_corporation_details()
  {
    while (($line = fgets($this->file_handle)) !== false) {
      $line = rtrim($line);
      if (empty($line)) continue;

      $info = $this->parse_line($line);
      if ($info['level'] != 3) {
        fseek($this->file_handle, -strlen($line) - 1, SEEK_CUR);
        break;
      }

      switch ($info['tag']) {
        case 'ADDR':
          $this->header_info['source_address'] = trim($info['rest']);
          $this->parse_address_continuation();
          break;
        case 'PHON':
          $this->header_info['source_phone'] = trim($info['rest']);
          break;
      }
    }
  }

  private function parse_address_continuation()
  {
    while (($line = fgets($this->file_handle)) !== false) {
      $line = rtrim($line);
      if (empty($line)) continue;

      $info = $this->parse_line($line);
      if ($info['level'] != 4) {
        fseek($this->file_handle, -strlen($line) - 1, SEEK_CUR);
        break;
      }

      if ($info['tag'] == 'CONT') {
        $this->header_info['source_address'] .= "\n" . trim($info['rest']);
      } else if ($info['tag'] == 'CONC') {
        $this->header_info['source_address'] .= " " . trim($info['rest']);
      }
    }
  }

  private function parse_gedcom_details()
  {
    while (($line = fgets($this->file_handle)) !== false) {
      $line = rtrim($line);
      if (empty($line)) continue;

      $info = $this->parse_line($line);
      if ($info['level'] != 2) {
        fseek($this->file_handle, -strlen($line) - 1, SEEK_CUR);
        break;
      }

      switch ($info['tag']) {
        case 'VERS':
          $this->header_info['gedcom_version'] = trim($info['rest']);
          break;
        case 'FORM':
          $this->header_info['gedcom_form'] = trim($info['rest']);
          break;
      }
    }
  }

  private function parse_submitter_record()
  {
    $submitter_id = $this->line_info['id'];
    $submitter_data = array(
      'submitter_id' => $submitter_id,
      'name' => '',
      'address' => '',
      'email' => '',
      'phone' => ''
    );

    while (($line = fgets($this->file_handle)) !== false) {
      $line = rtrim($line);
      if (empty($line)) continue;

      $info = $this->parse_line($line);
      if ($info['level'] == 0) {
        fseek($this->file_handle, -strlen($line) - 1, SEEK_CUR);
        break;
      }

      if ($info['level'] == 1) {
        switch ($info['tag']) {
          case 'NAME':
            $submitter_data['name'] = trim($info['rest']);
            break;
          case 'ADDR':
            $submitter_data['address'] = trim($info['rest']);
            $this->parse_submitter_address($submitter_data);
            break;
          case 'EMAIL':
            $submitter_data['email'] = trim($info['rest']);
            break;
          case 'PHON':
            $submitter_data['phone'] = trim($info['rest']);
            break;
        }
      }
    }

    $this->submitters[$submitter_id] = $submitter_data;
  }

  private function parse_submitter_address(&$submitter_data)
  {
    while (($line = fgets($this->file_handle)) !== false) {
      $line = rtrim($line);
      if (empty($line)) continue;

      $info = $this->parse_line($line);
      if ($info['level'] != 2) {
        fseek($this->file_handle, -strlen($line) - 1, SEEK_CUR);
        break;
      }

      if ($info['tag'] == 'CONT') {
        $submitter_data['address'] .= "\n" . trim($info['rest']);
      } else if ($info['tag'] == 'CONC') {
        $submitter_data['address'] .= " " . trim($info['rest']);
      }
    }
  }

  public function close()
  {
    if ($this->file_handle) {
      fclose($this->file_handle);
    }
  }
}

// Test with the FTM file
$gedcom_file = '../../../gedcom_test_files/FTM_lyle_2025-06-17.ged';

if (!file_exists($gedcom_file)) {
  echo "ERROR: File not found: $gedcom_file\n";
  exit(1);
}

echo "Testing file: $gedcom_file\n";
echo "File size: " . filesize($gedcom_file) . " bytes\n\n";

$parser = new SimpleHeaderParser($gedcom_file);
$result = $parser->parse_header();
$parser->close();

echo "=== GEDCOM HEADER INFORMATION ===\n";
foreach ($result['header'] as $key => $value) {
  if (!empty($value)) {
    $label = ucwords(str_replace('_', ' ', $key));
    if (strpos($value, "\n") !== false) {
      echo sprintf("%-20s: %s\n", $label, str_replace("\n", "\n" . str_repeat(" ", 22), $value));
    } else {
      echo sprintf("%-20s: %s\n", $label, $value);
    }
  }
}

echo "\n=== SUBMITTER INFORMATION ===\n";
foreach ($result['submitters'] as $submitter_id => $submitter) {
  echo "Submitter ID: $submitter_id\n";
  foreach ($submitter as $key => $value) {
    if (!empty($value) && $key != 'submitter_id') {
      $label = ucwords(str_replace('_', ' ', $key));
      if (strpos($value, "\n") !== false) {
        echo sprintf("  %-15s: %s\n", $label, str_replace("\n", "\n" . str_repeat(" ", 18), $value));
      } else {
        echo sprintf("  %-15s: %s\n", $label, $value);
      }
    }
  }
  echo "\n";
}

echo "Test complete.\n";
