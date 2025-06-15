<?php

/**
 * Test Script to Import Sample GEDCOM File
 * This script will import the sample GEDCOM file to populate the database for testing
 */

// Only run this from WordPress admin or CLI
if (!defined('ABSPATH')) {
  // For direct access, load WordPress
  require_once(dirname(__FILE__) . '/../../../wp-config.php');
}

// Ensure user has permission
if (!current_user_can('manage_genealogy')) {
  wp_die('Permission denied. You must be an administrator to run this script.');
}

// Load the GEDCOM importer
require_once HERITAGEPRESS_PLUGIN_DIR . 'includes/gedcom/class-hp-modular-gedcom-importer.php';

/**
 * Import the sample GEDCOM file
 */
function import_sample_gedcom()
{
  $gedcom_file = 'C:\MAMP\htdocs\HeritagePress2\gedcom_test_files\sample-from-5.5.1-standard.ged';

  // Check if file exists
  if (!file_exists($gedcom_file)) {
    return array(
      'success' => false,
      'message' => 'GEDCOM file not found: ' . $gedcom_file
    );
  }

  // Set up import options
  $options = array(
    'overwrite_existing' => true,
    'import_media' => false, // Skip media for test data
    'privacy_year_threshold' => 100,
    'batch_size' => 50
  );

  try {
    // Create importer instance
    $importer = new HP_Modular_GEDCOM_Importer($gedcom_file, 'test_tree', $options);

    // Run the import
    $result = $importer->import();

    if ($result['success']) {
      return array(
        'success' => true,
        'message' => 'GEDCOM imported successfully!',
        'stats' => $result['stats'],
        'tree_id' => 'test_tree'
      );
    } else {
      return array(
        'success' => false,
        'message' => 'Import failed: ' . $result['message'],
        'errors' => isset($result['errors']) ? $result['errors'] : array()
      );
    }
  } catch (Exception $e) {
    return array(
      'success' => false,
      'message' => 'Import failed with exception: ' . $e->getMessage()
    );
  }
}

/**
 * Alternative: Simple direct database insertion for testing
 */
function insert_sample_people_directly()
{
  global $wpdb;

  $people_table = $wpdb->prefix . 'hp_people';

  // Check if table exists
  if ($wpdb->get_var("SHOW TABLES LIKE '$people_table'") != $people_table) {
    return array(
      'success' => false,
      'message' => 'People table does not exist. Please create HeritagePress tables first.'
    );
  }

  // Sample people data based on the GEDCOM file
  $sample_people = array(
    array(
      'personID' => 'I1',
      'gedcom' => 'test_tree',
      'firstname' => 'Robert Eugene',
      'lastname' => 'Williams',
      'sex' => 'M',
      'birthdate' => '1822-10-02',
      'birthplace' => 'Weston, Madison, Connecticut',
      'deathdate' => '1905-04-14',
      'deathplace' => 'Stamford, Fairfield, CT',
      'living' => 0,
      'private' => 0,
      'changedate' => current_time('mysql'),
      'changedby' => 'test_import'
    ),
    array(
      'personID' => 'I2',
      'gedcom' => 'test_tree',
      'firstname' => 'Mary Ann',
      'lastname' => 'Wilson',
      'sex' => 'F',
      'birthdate' => '1827-01-01', // BEF 1828
      'birthplace' => 'Connecticut',
      'living' => 0,
      'private' => 0,
      'changedate' => current_time('mysql'),
      'changedby' => 'test_import'
    ),
    array(
      'personID' => 'I3',
      'gedcom' => 'test_tree',
      'firstname' => 'Joe',
      'lastname' => 'Williams',
      'sex' => 'M',
      'birthdate' => '1861-06-11',
      'birthplace' => 'Idaho Falls, Bonneville, Idaho',
      'living' => 0,
      'private' => 0,
      'changedate' => current_time('mysql'),
      'changedby' => 'test_import'
    ),
    array(
      'personID' => 'I4',
      'gedcom' => 'test_tree',
      'firstname' => 'John',
      'lastname' => 'Smith',
      'sex' => 'M',
      'birthdate' => '1850-07-15',
      'birthplace' => 'New York, NY',
      'living' => 0,
      'private' => 0,
      'changedate' => current_time('mysql'),
      'changedby' => 'test_import'
    ),
    array(
      'personID' => 'I5',
      'gedcom' => 'test_tree',
      'firstname' => 'Sarah',
      'lastname' => 'Johnson',
      'sex' => 'F',
      'birthdate' => '1855-03-22',
      'birthplace' => 'Boston, MA',
      'living' => 0,
      'private' => 0,
      'changedate' => current_time('mysql'),
      'changedby' => 'test_import'
    ),
    array(
      'personID' => 'I6',
      'gedcom' => 'test_tree',
      'firstname' => 'Emily',
      'lastname' => 'Davis',
      'sex' => 'F',
      'birthdate' => '1990-05-10',
      'birthplace' => 'Seattle, WA',
      'living' => 1, // Living person for testing
      'private' => 1,
      'changedate' => current_time('mysql'),
      'changedby' => 'test_import'
    )
  );

  $inserted = 0;
  $errors = array();

  foreach ($sample_people as $person) {
    // Check if person already exists
    $existing = $wpdb->get_var($wpdb->prepare(
      "SELECT ID FROM $people_table WHERE personID = %s AND gedcom = %s",
      $person['personID'],
      $person['gedcom']
    ));

    if ($existing) {
      // Update existing person
      $result = $wpdb->update(
        $people_table,
        $person,
        array('ID' => $existing)
      );
    } else {
      // Insert new person
      $result = $wpdb->insert($people_table, $person);
    }

    if ($result !== false) {
      $inserted++;
    } else {
      $errors[] = "Failed to insert/update person: " . $person['firstname'] . ' ' . $person['lastname'];
    }
  }

  return array(
    'success' => $inserted > 0,
    'message' => "Inserted/updated $inserted people in test_tree",
    'stats' => array(
      'people_processed' => count($sample_people),
      'people_inserted' => $inserted,
      'errors' => $errors
    ),
    'tree_id' => 'test_tree'
  );
}

// Check if this is being run directly
if (isset($_GET['action']) && $_GET['action'] === 'import_sample') {
  // Verify nonce for security
  if (!wp_verify_nonce($_GET['_wpnonce'], 'import_sample_gedcom')) {
    wp_die('Security check failed');
  }

  echo '<div style="padding: 20px; font-family: Arial, sans-serif;">';
  echo '<h2>HeritagePress Sample GEDCOM Import</h2>';

  // Try the modular importer first
  echo '<h3>Attempting GEDCOM Import...</h3>';
  $result = import_sample_gedcom();

  if (!$result['success']) {
    // Fall back to direct database insertion
    echo '<p style="color: orange;">GEDCOM import failed: ' . esc_html($result['message']) . '</p>';
    echo '<h3>Falling back to direct database insertion...</h3>';
    $result = insert_sample_people_directly();
  }

  if ($result['success']) {
    echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; border-radius: 4px;">';
    echo '<h4>✅ Success!</h4>';
    echo '<p>' . esc_html($result['message']) . '</p>';

    if (isset($result['stats'])) {
      echo '<h5>Import Statistics:</h5>';
      echo '<ul>';
      foreach ($result['stats'] as $key => $value) {
        if (is_array($value)) {
          echo '<li>' . esc_html(ucwords(str_replace('_', ' ', $key))) . ': ' . count($value) . ' items</li>';
        } else {
          echo '<li>' . esc_html(ucwords(str_replace('_', ' ', $key))) . ': ' . esc_html($value) . '</li>';
        }
      }
      echo '</ul>';
    }

    echo '<p><strong>You can now test the People section with tree ID: ' . esc_html($result['tree_id']) . '</strong></p>';
    echo '</div>';
  } else {
    echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; border-radius: 4px;">';
    echo '<h4>❌ Import Failed</h4>';
    echo '<p>' . esc_html($result['message']) . '</p>';

    if (isset($result['errors']) && !empty($result['errors'])) {
      echo '<h5>Errors:</h5>';
      echo '<ul>';
      foreach ($result['errors'] as $error) {
        echo '<li>' . esc_html($error) . '</li>';
      }
      echo '</ul>';
    }
    echo '</div>';
  }

  echo '<p><a href="' . admin_url('admin.php?page=heritagepress-people') . '">→ Go to People Section</a></p>';
  echo '</div>';
  exit;
}

// If not run directly, just define the functions for use elsewhere
?>

<!DOCTYPE html>
<html>

<head>
  <title>HeritagePress Sample Data Import</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      max-width: 800px;
      margin: 50px auto;
      padding: 20px;
    }

    .button {
      background: #0073aa;
      color: white;
      padding: 10px 20px;
      text-decoration: none;
      border-radius: 4px;
      display: inline-block;
      margin: 10px 0;
    }

    .button:hover {
      background: #005a87;
    }

    .info {
      background: #e7f3ff;
      border: 1px solid #b6d7ff;
      padding: 15px;
      border-radius: 4px;
      margin: 20px 0;
    }
  </style>
</head>

<body>
  <h1>HeritagePress Sample Data Import</h1>

  <div class="info">
    <h3>About This Import</h3>
    <p>This script will import sample people data into your HeritagePress database for testing the People section.</p>
    <p><strong>GEDCOM File:</strong> <code>C:\MAMP\htdocs\HeritagePress2\gedcom_test_files\sample-from-5.5.1-standard.ged</code></p>
    <p><strong>Tree ID:</strong> test_tree</p>
  </div>

  <h3>Import Options</h3>

  <p>
    <a href="?action=import_sample&_wpnonce=<?php echo wp_create_nonce('import_sample_gedcom'); ?>" class="button">
      Import Sample GEDCOM Data
    </a>
  </p>

  <p>This will create sample people records that you can use to test the People section functionality.</p>

  <h3>After Import</h3>
  <p>Once the import is complete, you can:</p>
  <ul>
    <li>Go to <strong>HeritagePress → People</strong> to browse the imported people</li>
    <li>Test the search functionality</li>
    <li>Try adding/editing people</li>
    <li>Generate reports</li>
    <li>Run utilities</li>
  </ul>

  <p><a href="<?php echo admin_url('admin.php?page=heritagepress-people'); ?>">→ Go to People Section</a></p>
</body>

</html>
