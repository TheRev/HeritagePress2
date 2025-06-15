<?php

/**
 * Test script to verify column width styling
 * Navigate to: /wp-content/plugins/heritagepress/test-column-widths.php
 */

// Basic WordPress bootstrap - minimal version for testing
define('WP_USE_THEMES', false);
require_once(dirname(dirname(dirname(__DIR__))) . '/wp-load.php');

// Verify this is the right path
if (!function_exists('wp_head')) {
  echo "Error: Could not load WordPress. Check the path in this script.";
  exit;
}

// Get some test data
global $wpdb;
$table_name = $wpdb->prefix . 'hp_people';

$test_people = $wpdb->get_results($wpdb->prepare("
    SELECT personid, firstname, lastname, birthdate, deathdate
    FROM {$table_name}
    ORDER BY personid
    LIMIT 10
"));

?>
<!DOCTYPE html>
<html>

<head>
  <title>Column Width Test - HeritagePress</title>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
      margin: 20px;
    }

    h1 {
      color: #1d2327;
    }

    .test-section {
      margin: 30px 0;
      padding: 20px;
      border: 1px solid #c3c4c7;
      background: #f6f7f7;
    }

    /* Load the CSS from our plugin */
    <?php
    $css_file = __DIR__ . '/includes/template/People/people.css';
    if (file_exists($css_file)) {
      echo file_get_contents($css_file);
    }
    ?>

    /* Test styles */
    .debug-info {
      background: #fff;
      padding: 10px;
      margin: 10px 0;
      border-left: 4px solid #00a0d2;
    }

    .test-table {
      margin: 20px 0;
    }
  </style>
</head>

<body>
  <h1>Column Width Test - HeritagePress</h1>

  <div class="debug-info">
    <h3>CSS Test Information</h3>
    <p><strong>Purpose:</strong> Verify that the Person ID column is sized to fit its content (not equal width)</p>
    <p><strong>Expected:</strong> Person ID column should be narrow, only as wide as the longest ID number</p>
    <p><strong>Test Data:</strong> <?php echo count($test_people); ?> people loaded from database</p>
  </div>

  <div class="test-section">
    <h2>Test Table with People Data</h2>
    <table class="wp-list-table widefat fixed striped people-table test-table">
      <thead>
        <tr>
          <td id="cb" class="manage-column column-cb check-column">
            <input type="checkbox" />
          </td>
          <th scope="col" class="manage-column column-personid sortable">
            <span>Person ID</span>
          </th>
          <th scope="col" class="manage-column column-photo">Photo</th>
          <th scope="col" class="manage-column column-name sortable">
            <span>Name</span>
          </th>
          <th scope="col" class="manage-column column-birth sortable">
            <span>Birth</span>
          </th>
          <th scope="col" class="manage-column column-death sortable">
            <span>Death</span>
          </th>
          <th scope="col" class="manage-column column-changed sortable">
            <span>Last Changed</span>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php if ($test_people): ?>
          <?php foreach ($test_people as $person): ?>
            <tr>
              <th scope="row" class="check-column">
                <input type="checkbox" value="<?php echo htmlspecialchars($person->personid); ?>" />
              </th>
              <td class="column-personid">
                <strong><?php echo htmlspecialchars($person->personid); ?></strong>
              </td>
              <td class="column-photo">
                <div class="person-photo-placeholder">
                  <span class="dashicons dashicons-admin-users"></span>
                </div>
              </td>
              <td class="column-name">
                <div class="person-name">
                  <strong><?php echo htmlspecialchars($person->firstname . ' ' . $person->lastname); ?></strong>
                </div>
              </td>
              <td class="column-birth">
                <?php echo htmlspecialchars($person->birthdate ?: 'Unknown'); ?>
              </td>
              <td class="column-death">
                <?php echo htmlspecialchars($person->deathdate ?: 'Unknown'); ?>
              </td>
              <td class="column-changed">
                2024-01-15 10:30:00
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7">No test data available. Please check your database connection.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="debug-info">
    <h3>CSS Debug Information</h3>
    <p><strong>Table classes:</strong> wp-list-table widefat fixed striped people-table</p>
    <p><strong>Key CSS rules applied:</strong></p>
    <ul>
      <li><code>.people-table { table-layout: auto !important; }</code> - Overrides WordPress fixed layout</li>
      <li><code>.people-table .column-personid { width: 1% !important; min-width: max-content !important; }</code> - Forces content-based sizing</li>
    </ul>
  </div>

  <div class="test-section">
    <h2>Inspection Instructions</h2>
    <ol>
      <li>Right-click on the Person ID column header and select "Inspect Element"</li>
      <li>Look for the <code>.column-personid</code> class in the CSS panel</li>
      <li>Verify that <code>table-layout: auto !important</code> is applied to <code>.people-table</code></li>
      <li>Verify that the Person ID column is narrower than other columns</li>
      <li>Test by changing the browser width - the Person ID column should remain content-based</li>
    </ol>
  </div>

</body>

</html>
