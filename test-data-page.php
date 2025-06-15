<?php

/**
 * Quick Test Data Insertion for HeritagePress People Section
 * Add this as a temporary admin page to insert test data
 */

// Add this to the admin class temporarily
add_action('admin_menu', 'hp_add_test_data_page');
add_action('admin_init', 'hp_handle_test_data_insertion');

function hp_add_test_data_page()
{
  add_submenu_page(
    'heritagepress',
    'Insert Test Data',
    'Test Data',
    'manage_options',
    'heritagepress-test-data',
    'hp_test_data_page'
  );
}

function hp_handle_test_data_insertion()
{
  if (isset($_POST['insert_test_data']) && wp_verify_nonce($_POST['_wpnonce'], 'insert_test_data')) {
    global $wpdb;

    $people_table = $wpdb->prefix . 'hp_people';

    // Check if table exists, create if needed
    if ($wpdb->get_var("SHOW TABLES LIKE '$people_table'") != $people_table) {
      $sql = "CREATE TABLE $people_table (
                ID int(11) NOT NULL AUTO_INCREMENT,
                personID varchar(20) NOT NULL,
                gedcom varchar(50) NOT NULL DEFAULT 'main',
                firstname varchar(100) DEFAULT NULL,
                lastname varchar(100) DEFAULT NULL,
                lnprefix varchar(50) DEFAULT NULL,
                prefix varchar(20) DEFAULT NULL,
                suffix varchar(20) DEFAULT NULL,
                nickname varchar(50) DEFAULT NULL,
                nameorder varchar(20) DEFAULT 'western',
                sex varchar(1) DEFAULT NULL,
                birthdate varchar(50) DEFAULT NULL,
                birthplace varchar(255) DEFAULT NULL,
                deathdate varchar(50) DEFAULT NULL,
                deathplace varchar(255) DEFAULT NULL,
                living tinyint(1) DEFAULT 0,
                private tinyint(1) DEFAULT 0,
                changedate datetime DEFAULT CURRENT_TIMESTAMP,
                changedby varchar(50) DEFAULT NULL,
                PRIMARY KEY (ID),
                UNIQUE KEY person_tree (personID, gedcom),
                KEY name_index (lastname, firstname)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
    }

    // Clear existing test data
    $wpdb->delete($people_table, array('gedcom' => 'test_tree'));

    // Sample people data
    $sample_people = array(
      array(
        'personID' => 'I1',
        'gedcom' => 'test_tree',
        'firstname' => 'Robert Eugene',
        'lastname' => 'Williams',
        'sex' => 'M',
        'birthdate' => '2 OCT 1822',
        'birthplace' => 'Weston, Madison, Connecticut',
        'deathdate' => '14 APR 1905',
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
        'birthdate' => 'BEF 1828',
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
        'birthdate' => '11 JUN 1861',
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
        'birthdate' => '15 JUL 1850',
        'birthplace' => 'New York, New York',
        'deathdate' => '3 MAR 1920',
        'deathplace' => 'Brooklyn, New York',
        'living' => 0,
        'private' => 0,
        'changedate' => current_time('mysql'),
        'changedby' => 'test_import'
      ),
      array(
        'personID' => 'I5',
        'gedcom' => 'test_tree',
        'firstname' => 'Emily Rose',
        'lastname' => 'Davis',
        'sex' => 'F',
        'birthdate' => '10 MAY 1990',
        'birthplace' => 'Seattle, Washington',
        'living' => 1,
        'private' => 1,
        'changedate' => current_time('mysql'),
        'changedby' => 'test_import'
      )
    );

    $inserted = 0;
    foreach ($sample_people as $person) {
      if ($wpdb->insert($people_table, $person)) {
        $inserted++;
      }
    }

    if ($inserted > 0) {
      add_settings_error('hp_test_data', 'success', "Successfully inserted $inserted test people into 'test_tree'", 'success');
    } else {
      add_settings_error('hp_test_data', 'error', 'Failed to insert test data', 'error');
    }

    // Redirect to prevent resubmission
    wp_redirect(admin_url('admin.php?page=heritagepress-test-data&success=1'));
    exit;
  }
}

function hp_test_data_page()
{
  global $wpdb;

  $people_table = $wpdb->prefix . 'hp_people';
  $current_count = 0;

  if ($wpdb->get_var("SHOW TABLES LIKE '$people_table'") == $people_table) {
    $current_count = $wpdb->get_var("SELECT COUNT(*) FROM $people_table WHERE gedcom = 'test_tree'");
  }

?>
  <div class="wrap">
    <h1>HeritagePress Test Data</h1>

    <?php settings_errors('hp_test_data'); ?>

    <?php if (isset($_GET['success'])): ?>
      <div class="notice notice-success is-dismissible">
        <p><strong>Test data inserted successfully!</strong> You can now test the People section.</p>
        <p><a href="<?php echo admin_url('admin.php?page=heritagepress-people'); ?>" class="button button-primary">Go to People Section</a></p>
      </div>
    <?php endif; ?>

    <div class="card">
      <h2>Current Status</h2>
      <p><strong>Test Tree People Count:</strong> <?php echo $current_count; ?></p>
      <p><strong>People Table:</strong> <?php echo ($wpdb->get_var("SHOW TABLES LIKE '$people_table'") == $people_table) ? '✅ Exists' : '❌ Missing'; ?></p>
    </div>

    <div class="card">
      <h2>Insert Test Data</h2>
      <p>This will insert 5 sample people into the 'test_tree' for testing the People section.</p>

      <form method="post" action="">
        <?php wp_nonce_field('insert_test_data'); ?>
        <p>
          <input type="submit" name="insert_test_data" class="button button-primary" value="Insert Test Data"
            onclick="return confirm('This will replace any existing test_tree data. Continue?')">
        </p>
      </form>
    </div>

    <?php if ($current_count > 0): ?>
      <div class="card">
        <h2>Test Data Preview</h2>
        <?php
        $samples = $wpdb->get_results("SELECT personID, firstname, lastname, sex, birthdate, living FROM $people_table WHERE gedcom = 'test_tree' ORDER BY personID LIMIT 10");
        if ($samples): ?>
          <table class="wp-list-table widefat fixed striped">
            <thead>
              <tr>
                <th>Person ID</th>
                <th>Name</th>
                <th>Sex</th>
                <th>Birth Date</th>
                <th>Living</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($samples as $person): ?>
                <tr>
                  <td><?php echo esc_html($person->personID); ?></td>
                  <td><?php echo esc_html($person->firstname . ' ' . $person->lastname); ?></td>
                  <td><?php echo esc_html($person->sex); ?></td>
                  <td><?php echo esc_html($person->birthdate); ?></td>
                  <td><?php echo $person->living ? 'Yes' : 'No'; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <h2>Next Steps</h2>
      <p>After inserting test data, you can:</p>
      <ul>
        <li><a href="<?php echo admin_url('admin.php?page=heritagepress-people'); ?>">Browse people in the People section</a></li>
        <li>Test searching and filtering</li>
        <li>Try adding/editing people</li>
        <li>Test reports and utilities</li>
      </ul>
    </div>
  </div>
<?php
}
?>
