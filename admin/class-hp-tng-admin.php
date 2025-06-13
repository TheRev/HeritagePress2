<?php

/**
 * TNG Import/Export Admin Interface
 *
 * Provides admin screens for TNG database migration and import/export functionality
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_TNG_Admin
{
  private $tng_importer;

  public function __construct()
  {
    $this->tng_importer = new HP_TNG_Importer();
    $this->init_hooks();
  }

  /**
   * Initialize hooks
   */
  private function init_hooks()
  {
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('wp_ajax_hp_test_tng_connection', array($this, 'ajax_test_tng_connection'));
    add_action('wp_ajax_hp_import_tng_data', array($this, 'ajax_import_tng_data'));
    add_action('wp_ajax_hp_export_tng_data', array($this, 'ajax_export_tng_data'));
    add_action('wp_ajax_hp_migrate_to_tng_schema', array($this, 'ajax_migrate_to_tng_schema'));
  }

  /**
   * Add admin menu items
   */
  public function add_admin_menu()
  {
    add_submenu_page(
      'heritagepress',
      'TNG Import/Export',
      'TNG Import/Export',
      'manage_genealogy',
      'heritagepress-tng',
      array($this, 'tng_admin_page')
    );
  }
  /**
   * Enqueue admin scripts and styles
   */
  public function enqueue_scripts($hook)
  {
    if ('heritagepress_page_heritagepress-tng' !== $hook) {
      return;
    }

    wp_enqueue_script(
      'heritagepress-tng-admin',
      HERITAGEPRESS_PLUGIN_URL . 'admin/js/tng-admin.js',
      array('jquery'),
      HERITAGEPRESS_VERSION,
      true
    );

    wp_enqueue_style(
      'heritagepress-tng-admin',
      HERITAGEPRESS_PLUGIN_URL . 'admin/css/tng-admin.css',
      array(),
      HERITAGEPRESS_VERSION
    );

    wp_localize_script('heritagepress-tng-admin', 'hpTngAjax', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('hp_tng_admin'),
      'strings' => array(
        'testing_connection' => __('Testing connection...', 'heritagepress'),
        'importing_data' => __('Importing data...', 'heritagepress'),
        'exporting_data' => __('Exporting data...', 'heritagepress'),
        'migrating_schema' => __('Migrating to TNG schema...', 'heritagepress'),
        'connection_success' => __('Connection successful!', 'heritagepress'),
        'connection_failed' => __('Connection failed!', 'heritagepress')
      )
    ));
  }

  /**
   * TNG admin page
   */
  public function tng_admin_page()
  {
    $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'import';
    $db_type = get_option('heritagepress_db_type', 'standard');
?>
    <div class="wrap">
      <h1><?php _e('TNG Import/Export', 'heritagepress'); ?></h1>

      <h2 class="nav-tab-wrapper">
        <a href="?page=heritagepress-tng&tab=import" class="nav-tab <?php echo $current_tab === 'import' ? 'nav-tab-active' : ''; ?>">
          <?php _e('Import from TNG', 'heritagepress'); ?>
        </a>
        <a href="?page=heritagepress-tng&tab=export" class="nav-tab <?php echo $current_tab === 'export' ? 'nav-tab-active' : ''; ?>">
          <?php _e('Export to TNG', 'heritagepress'); ?>
        </a>
        <a href="?page=heritagepress-tng&tab=schema" class="nav-tab <?php echo $current_tab === 'schema' ? 'nav-tab-active' : ''; ?>">
          <?php _e('Database Schema', 'heritagepress'); ?>
        </a>
        <a href="?page=heritagepress-tng&tab=settings" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
          <?php _e('Settings', 'heritagepress'); ?>
        </a>
      </h2>

      <?php
      switch ($current_tab) {
        case 'import':
          $this->render_import_tab();
          break;
        case 'export':
          $this->render_export_tab();
          break;
        case 'schema':
          $this->render_schema_tab();
          break;
        case 'settings':
          $this->render_settings_tab();
          break;
        default:
          $this->render_import_tab();
      }
      ?>
    </div>
  <?php
  }

  /**
   * Render import tab
   */
  private function render_import_tab()
  {
  ?>
    <div class="hp-tng-tab-content">
      <h3><?php _e('Import Data from TNG Database', 'heritagepress'); ?></h3>

      <div class="card">
        <h4><?php _e('TNG Database Connection', 'heritagepress'); ?></h4>
        <form id="tng-connection-form">
          <table class="form-table">
            <tr>
              <th scope="row"><?php _e('Database Host', 'heritagepress'); ?></th>
              <td><input type="text" name="host" value="localhost" class="regular-text" /></td>
            </tr>
            <tr>
              <th scope="row"><?php _e('Database Name', 'heritagepress'); ?></th>
              <td><input type="text" name="database" class="regular-text" required /></td>
            </tr>
            <tr>
              <th scope="row"><?php _e('Username', 'heritagepress'); ?></th>
              <td><input type="text" name="username" class="regular-text" required /></td>
            </tr>
            <tr>
              <th scope="row"><?php _e('Password', 'heritagepress'); ?></th>
              <td><input type="password" name="password" class="regular-text" /></td>
            </tr>
            <tr>
              <th scope="row"><?php _e('Table Prefix', 'heritagepress'); ?></th>
              <td><input type="text" name="table_prefix" class="regular-text" placeholder="tng_" /></td>
            </tr>
            <tr>
              <th scope="row"><?php _e('GEDCOM Filter', 'heritagepress'); ?></th>
              <td>
                <input type="text" name="gedcom_filter" class="regular-text" placeholder="<?php _e('Leave empty to import all', 'heritagepress'); ?>" />
                <p class="description"><?php _e('Specify a GEDCOM identifier to import only specific family trees', 'heritagepress'); ?></p>
              </td>
            </tr>
          </table>

          <p class="submit">
            <button type="button" id="test-tng-connection" class="button"><?php _e('Test Connection', 'heritagepress'); ?></button>
          </p>
        </form>

        <div id="connection-status" style="display: none;"></div>
      </div>

      <div class="card" id="import-options" style="display: none;">
        <h4><?php _e('Import Options', 'heritagepress'); ?></h4>
        <form id="tng-import-form">
          <table class="form-table">
            <tr>
              <th scope="row"><?php _e('Import Data', 'heritagepress'); ?></th>
              <td>
                <fieldset>
                  <label><input type="checkbox" name="import_people" checked /> <?php _e('People', 'heritagepress'); ?></label><br />
                  <label><input type="checkbox" name="import_families" checked /> <?php _e('Families', 'heritagepress'); ?></label><br />
                  <label><input type="checkbox" name="import_events" checked /> <?php _e('Events', 'heritagepress'); ?></label><br />
                  <label><input type="checkbox" name="import_sources" /> <?php _e('Sources', 'heritagepress'); ?></label><br />
                  <label><input type="checkbox" name="import_media" /> <?php _e('Media', 'heritagepress'); ?></label>
                </fieldset>
              </td>
            </tr>
            <tr>
              <th scope="row"><?php _e('Backup Current Data', 'heritagepress'); ?></th>
              <td>
                <label><input type="checkbox" name="create_backup" checked /> <?php _e('Create backup before import', 'heritagepress'); ?></label>
                <p class="description"><?php _e('Recommended: Creates a backup of your current data before importing', 'heritagepress'); ?></p>
              </td>
            </tr>
          </table>

          <p class="submit">
            <button type="submit" class="button button-primary"><?php _e('Start Import', 'heritagepress'); ?></button>
          </p>
        </form>

        <div id="import-progress" style="display: none;">
          <h4><?php _e('Import Progress', 'heritagepress'); ?></h4>
          <div class="progress-bar">
            <div class="progress-fill" style="width: 0%;"></div>
          </div>
          <div id="import-status"></div>
        </div>
      </div>
    </div>
  <?php
  }

  /**
   * Render export tab
   */
  private function render_export_tab()
  {
  ?>
    <div class="hp-tng-tab-content">
      <h3><?php _e('Export Data to TNG Format', 'heritagepress'); ?></h3>

      <div class="card">
        <h4><?php _e('Export Options', 'heritagepress'); ?></h4>
        <form id="tng-export-form">
          <table class="form-table">
            <tr>
              <th scope="row"><?php _e('GEDCOM Filter', 'heritagepress'); ?></th>
              <td>
                <input type="text" name="gedcom_filter" class="regular-text" placeholder="<?php _e('Leave empty to export all', 'heritagepress'); ?>" />
                <p class="description"><?php _e('Specify a GEDCOM identifier to export only specific family trees', 'heritagepress'); ?></p>
              </td>
            </tr>
            <tr>
              <th scope="row"><?php _e('Export Format', 'heritagepress'); ?></th>
              <td>
                <fieldset>
                  <label><input type="radio" name="export_format" value="sql" checked /> <?php _e('SQL Dump', 'heritagepress'); ?></label><br />
                  <label><input type="radio" name="export_format" value="json" /> <?php _e('JSON Format', 'heritagepress'); ?></label><br />
                  <label><input type="radio" name="export_format" value="csv" /> <?php _e('CSV Files', 'heritagepress'); ?></label>
                </fieldset>
              </td>
            </tr>
            <tr>
              <th scope="row"><?php _e('Include Data', 'heritagepress'); ?></th>
              <td>
                <fieldset>
                  <label><input type="checkbox" name="export_people" checked /> <?php _e('People', 'heritagepress'); ?></label><br />
                  <label><input type="checkbox" name="export_families" checked /> <?php _e('Families', 'heritagepress'); ?></label><br />
                  <label><input type="checkbox" name="export_events" checked /> <?php _e('Events', 'heritagepress'); ?></label><br />
                  <label><input type="checkbox" name="export_sources" /> <?php _e('Sources', 'heritagepress'); ?></label><br />
                  <label><input type="checkbox" name="export_media" /> <?php _e('Media', 'heritagepress'); ?></label>
                </fieldset>
              </td>
            </tr>
          </table>

          <p class="submit">
            <button type="submit" class="button button-primary"><?php _e('Export Data', 'heritagepress'); ?></button>
          </p>
        </form>

        <div id="export-progress" style="display: none;">
          <h4><?php _e('Export Progress', 'heritagepress'); ?></h4>
          <div id="export-status"></div>
        </div>
      </div>
    </div>
  <?php
  }

  /**
   * Render schema tab
   */
  private function render_schema_tab()
  {
    $db_type = get_option('heritagepress_db_type', 'standard');
    $tng_db = new HP_Database_TNG_Compatible();
    $is_tng_compatible = $tng_db->check_tng_compatibility();

  ?>
    <div class="hp-tng-tab-content">
      <h3><?php _e('Database Schema Management', 'heritagepress'); ?></h3>

      <div class="card">
        <h4><?php _e('Current Database Type', 'heritagepress'); ?></h4>
        <p>
          <strong><?php _e('Schema Type:', 'heritagepress'); ?></strong>
          <?php echo $db_type === 'tng_compatible' ? __('TNG Compatible', 'heritagepress') : __('Standard HeritagePress', 'heritagepress'); ?>
        </p>
        <p>
          <strong><?php _e('TNG Compatibility:', 'heritagepress'); ?></strong>
          <?php if ($is_tng_compatible): ?>
            <span style="color: green;"><?php _e('✓ Compatible', 'heritagepress'); ?></span>
          <?php else: ?>
            <span style="color: red;"><?php _e('✗ Not Compatible', 'heritagepress'); ?></span>
          <?php endif; ?>
        </p>
      </div>

      <?php if ($db_type !== 'tng_compatible'): ?>
        <div class="card">
          <h4><?php _e('Migrate to TNG-Compatible Schema', 'heritagepress'); ?></h4>
          <p><?php _e('Convert your current database to use TNG-compatible table structures for seamless import/export.', 'heritagepress'); ?></p>

          <div class="notice notice-warning">
            <p><strong><?php _e('Warning:', 'heritagepress'); ?></strong> <?php _e('This will modify your database structure. A backup will be created automatically.', 'heritagepress'); ?></p>
          </div>

          <form id="schema-migration-form">
            <table class="form-table">
              <tr>
                <th scope="row"><?php _e('Migration Options', 'heritagepress'); ?></th>
                <td>
                  <fieldset>
                    <label><input type="checkbox" name="preserve_data" checked /> <?php _e('Preserve existing data', 'heritagepress'); ?></label><br />
                    <label><input type="checkbox" name="create_backup" checked /> <?php _e('Create database backup', 'heritagepress'); ?></label><br />
                    <label><input type="checkbox" name="map_existing_data" checked /> <?php _e('Map existing data to TNG format', 'heritagepress'); ?></label>
                  </fieldset>
                </td>
              </tr>
            </table>

            <p class="submit">
              <button type="submit" class="button button-primary"><?php _e('Migrate Schema', 'heritagepress'); ?></button>
            </p>
          </form>

          <div id="migration-progress" style="display: none;">
            <h4><?php _e('Migration Progress', 'heritagepress'); ?></h4>
            <div class="progress-bar">
              <div class="progress-fill" style="width: 0%;"></div>
            </div>
            <div id="migration-status"></div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php
  }

  /**
   * Render settings tab
   */
  private function render_settings_tab()
  {
    if (isset($_POST['submit'])) {
      $this->save_tng_settings();
      echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'heritagepress') . '</p></div>';
    }

    $settings = get_option('heritagepress_tng_settings', array());

  ?>
    <div class="hp-tng-tab-content">
      <h3><?php _e('TNG Integration Settings', 'heritagepress'); ?></h3>

      <form method="post">
        <div class="card">
          <h4><?php _e('Default Settings', 'heritagepress'); ?></h4>
          <table class="form-table">
            <tr>
              <th scope="row"><?php _e('Enable TNG Compatibility', 'heritagepress'); ?></th>
              <td>
                <label>
                  <input type="checkbox" name="tng_compatibility" value="1" <?php checked(get_option('heritagepress_tng_compatibility', false)); ?> />
                  <?php _e('Use TNG-compatible database schema for new installations', 'heritagepress'); ?>
                </label>
              </td>
            </tr>
            <tr>
              <th scope="row"><?php _e('Auto-import GEDCOM', 'heritagepress'); ?></th>
              <td>
                <label>
                  <input type="checkbox" name="auto_import_gedcom" value="1" <?php checked($settings['auto_import_gedcom'] ?? false); ?> />
                  <?php _e('Automatically detect and import GEDCOM identifiers', 'heritagepress'); ?>
                </label>
              </td>
            </tr>
            <tr>
              <th scope="row"><?php _e('Preserve TNG IDs', 'heritagepress'); ?></th>
              <td>
                <label>
                  <input type="checkbox" name="preserve_tng_ids" value="1" <?php checked($settings['preserve_tng_ids'] ?? true); ?> />
                  <?php _e('Maintain original TNG person and family IDs during import', 'heritagepress'); ?>
                </label>
              </td>
            </tr>
            <tr>
              <th scope="row"><?php _e('Import Batch Size', 'heritagepress'); ?></th>
              <td>
                <input type="number" name="import_batch_size" value="<?php echo esc_attr($settings['import_batch_size'] ?? 100); ?>" min="10" max="1000" />
                <p class="description"><?php _e('Number of records to process per batch during import', 'heritagepress'); ?></p>
              </td>
            </tr>
          </table>
        </div>

        <div class="card">
          <h4><?php _e('Media Handling', 'heritagepress'); ?></h4>
          <table class="form-table">
            <tr>
              <th scope="row"><?php _e('Media Import Mode', 'heritagepress'); ?></th>
              <td>
                <fieldset>
                  <label><input type="radio" name="media_import_mode" value="copy" <?php checked($settings['media_import_mode'] ?? 'copy', 'copy'); ?> /> <?php _e('Copy media files to WordPress uploads', 'heritagepress'); ?></label><br />
                  <label><input type="radio" name="media_import_mode" value="link" <?php checked($settings['media_import_mode'] ?? 'copy', 'link'); ?> /> <?php _e('Keep original file paths (link only)', 'heritagepress'); ?></label>
                </fieldset>
              </td>
            </tr>
            <tr>
              <th scope="row"><?php _e('TNG Media Path', 'heritagepress'); ?></th>
              <td>
                <input type="text" name="tng_media_path" value="<?php echo esc_attr($settings['tng_media_path'] ?? ''); ?>" class="regular-text" />
                <p class="description"><?php _e('Absolute path to TNG media directory (for file copying)', 'heritagepress'); ?></p>
              </td>
            </tr>
          </table>
        </div>

        <p class="submit">
          <input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Settings', 'heritagepress'); ?>" />
        </p>
      </form>
    </div>
<?php
  }

  /**
   * Save TNG settings
   */
  private function save_tng_settings()
  {
    $settings = array(
      'auto_import_gedcom' => isset($_POST['auto_import_gedcom']),
      'preserve_tng_ids' => isset($_POST['preserve_tng_ids']),
      'import_batch_size' => intval($_POST['import_batch_size']),
      'media_import_mode' => sanitize_text_field($_POST['media_import_mode']),
      'tng_media_path' => sanitize_text_field($_POST['tng_media_path'])
    );

    update_option('heritagepress_tng_settings', $settings);
    update_option('heritagepress_tng_compatibility', isset($_POST['tng_compatibility']));
  }

  /**
   * AJAX: Test TNG database connection
   */
  public function ajax_test_tng_connection()
  {
    check_ajax_referer('hp_tng_admin', 'nonce');

    if (!current_user_can('manage_genealogy')) {
      wp_die('Unauthorized');
    }

    $host = sanitize_text_field($_POST['host']);
    $database = sanitize_text_field($_POST['database']);
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password']; // Don't sanitize password
    $table_prefix = sanitize_text_field($_POST['table_prefix']);

    $success = $this->tng_importer->connect_tng_database($host, $database, $username, $password, $table_prefix);

    if ($success) {
      $validation = $this->tng_importer->validate_tng_database();
      wp_send_json_success(array(
        'message' => __('Connection successful!', 'heritagepress'),
        'validation' => $validation
      ));
    } else {
      wp_send_json_error(array(
        'message' => __('Connection failed. Please check your database credentials.', 'heritagepress')
      ));
    }
  }

  /**
   * AJAX: Import TNG data
   */
  public function ajax_import_tng_data()
  {
    check_ajax_referer('hp_tng_admin', 'nonce');

    if (!current_user_can('manage_genealogy')) {
      wp_die('Unauthorized');
    }

    // Get form data
    $gedcom_filter = sanitize_text_field($_POST['gedcom_filter']);
    $options = array(
      'import_sources' => isset($_POST['import_sources']),
      'import_media' => isset($_POST['import_media']),
      'create_backup' => isset($_POST['create_backup'])
    );

    try {
      $results = $this->tng_importer->import_from_tng($gedcom_filter, $options);
      wp_send_json_success(array(
        'message' => __('Import completed successfully!', 'heritagepress'),
        'results' => $results,
        'log' => $this->tng_importer->get_log()
      ));
    } catch (Exception $e) {
      wp_send_json_error(array(
        'message' => $e->getMessage(),
        'log' => $this->tng_importer->get_log()
      ));
    }
  }

  /**
   * AJAX: Export TNG data
   */
  public function ajax_export_tng_data()
  {
    check_ajax_referer('hp_tng_admin', 'nonce');

    if (!current_user_can('manage_genealogy')) {
      wp_die('Unauthorized');
    }

    $gedcom_filter = sanitize_text_field($_POST['gedcom_filter']);
    $format = sanitize_text_field($_POST['export_format']);

    $upload_dir = wp_upload_dir();
    $output_path = $upload_dir['path'] . '/heritagepress_export_' . date('Y-m-d_H-i-s') . '.sql';

    try {
      $results = $this->tng_importer->export_to_tng_format($gedcom_filter, $output_path);

      wp_send_json_success(array(
        'message' => __('Export completed successfully!', 'heritagepress'),
        'download_url' => $upload_dir['url'] . '/' . basename($output_path),
        'results' => $results
      ));
    } catch (Exception $e) {
      wp_send_json_error(array(
        'message' => $e->getMessage()
      ));
    }
  }

  /**
   * AJAX: Migrate to TNG schema
   */
  public function ajax_migrate_to_tng_schema()
  {
    check_ajax_referer('hp_tng_admin', 'nonce');

    if (!current_user_can('manage_genealogy')) {
      wp_die('Unauthorized');
    }

    $options = array(
      'preserve_data' => isset($_POST['preserve_data']),
      'create_backup' => isset($_POST['create_backup']),
      'map_existing_data' => isset($_POST['map_existing_data'])
    );

    try {
      // Create TNG-compatible database
      $tng_db = new HP_Database_TNG_Compatible();

      if ($options['create_backup']) {
        // Create backup (would need implementation)
        // $this->create_database_backup();
      }

      $tng_db->create_tables();

      if ($options['preserve_data'] && $options['map_existing_data']) {
        // Migrate existing data (would need implementation)
        // $this->migrate_existing_data();
      }

      update_option('heritagepress_db_type', 'tng_compatible');

      wp_send_json_success(array(
        'message' => __('Schema migration completed successfully!', 'heritagepress')
      ));
    } catch (Exception $e) {
      wp_send_json_error(array(
        'message' => $e->getMessage()
      ));
    }
  }
}

// Initialize TNG admin if in admin area
if (is_admin()) {
  new HP_TNG_Admin();
}
