<?php

/**
 * Report Controller
 * Handles custom report creation, management, and execution
 */

if (!defined('ABSPATH')) {
  exit;
}

class HP_Report_Controller
{
  private $wpdb;
  private $reports_table;
  private $people_table;
  private $families_table;
  private $places_table;
  private $sources_table;

  public function __construct()
  {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->reports_table = $wpdb->prefix . 'hp_reports';
    $this->people_table = $wpdb->prefix . 'hp_people';
    $this->families_table = $wpdb->prefix . 'hp_families';
    $this->places_table = $wpdb->prefix . 'hp_places';
    $this->sources_table = $wpdb->prefix . 'hp_sources';
  }

  /**
   * Handle form submissions
   */
  public function handle_form_submission()
  {
    if (!current_user_can('edit_genealogy')) {
      return;
    }

    // Handle report creation
    if (isset($_POST['create_report']) && wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_report_create')) {
      $this->create_report();
    }

    // Handle report update
    if (isset($_POST['update_report']) && wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_report_update')) {
      $this->update_report();
    }

    // Handle report deletion
    if (isset($_POST['delete_report']) && wp_verify_nonce($_POST['_wpnonce'], 'heritagepress_report_delete')) {
      $this->delete_report();
    }
  }

  /**
   * Display main reports page
   */
  public function display_page()
  {
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
    $report_id = isset($_GET['reportID']) ? intval($_GET['reportID']) : 0;

    switch ($action) {
      case 'add':
        $this->display_add_form();
        break;
      case 'edit':
        $this->display_edit_form($report_id);
        break;
      case 'run':
        $this->display_run_report($report_id);
        break;
      default:
        $this->display_list();
        break;
    }
  }

  /**
   * Display reports list
   */
  private function display_list()
  {
    // Get search parameters
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $active_only = isset($_GET['active_only']) ? intval($_GET['active_only']) : 0;

    // Build query
    $where_conditions = array();
    $query_params = array();

    if (!empty($search)) {
      $where_conditions[] = "(reportname LIKE %s OR reportdesc LIKE %s)";
      $query_params[] = '%' . $this->wpdb->esc_like($search) . '%';
      $query_params[] = '%' . $this->wpdb->esc_like($search) . '%';
    }

    if ($active_only) {
      $where_conditions[] = "active = 1";
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    $query = "SELECT reportID, reportname, reportdesc, ranking, active
                  FROM {$this->reports_table}
                  {$where_clause}
                  ORDER BY ranking, reportname";

    $reports = $this->wpdb->get_results(
      $query_params ? $this->wpdb->prepare($query, $query_params) : $query,
      ARRAY_A
    );

    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/reports-main.php';
  }

  /**
   * Display add report form
   */
  private function display_add_form()
  {
    $report_data = array(
      'reportID' => 0,
      'reportname' => '',
      'reportdesc' => '',
      'ranking' => 1,
      'active' => 0,
      'display' => '',
      'criteria' => '',
      'orderby' => '',
      'sqlselect' => ''
    );

    $field_definitions = $this->get_field_definitions();
    $operator_definitions = $this->get_operator_definitions();

    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/reports-add.php';
  }

  /**
   * Display edit report form
   */
  private function display_edit_form($report_id)
  {
    if (!$report_id) {
      wp_die(__('Invalid report ID', 'heritagepress'));
    }

    $report_data = $this->wpdb->get_row(
      $this->wpdb->prepare(
        "SELECT * FROM {$this->reports_table} WHERE reportID = %d",
        $report_id
      ),
      ARRAY_A
    );

    if (!$report_data) {
      wp_die(__('Report not found', 'heritagepress'));
    }

    $field_definitions = $this->get_field_definitions();
    $operator_definitions = $this->get_operator_definitions();

    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/reports-add.php';
  }

  /**
   * Display run report
   */
  private function display_run_report($report_id)
  {
    if (!$report_id) {
      wp_die(__('Invalid report ID', 'heritagepress'));
    }

    $report = $this->wpdb->get_row(
      $this->wpdb->prepare(
        "SELECT * FROM {$this->reports_table} WHERE reportID = %d AND active = 1",
        $report_id
      ),
      ARRAY_A
    );

    if (!$report) {
      wp_die(__('Report not found or inactive', 'heritagepress'));
    }

    $results = $this->execute_report($report);
    include HERITAGEPRESS_PLUGIN_DIR . 'admin/views/reports-run.php';
  }

  /**
   * Create new report
   */
  private function create_report()
  {
    $reportname = sanitize_text_field($_POST['reportname']);
    $reportdesc = sanitize_textarea_field($_POST['reportdesc']);
    $ranking = intval($_POST['ranking']);
    $active = isset($_POST['active']) ? intval($_POST['active']) : 0;
    $display = sanitize_textarea_field($_POST['display']);
    $criteria = sanitize_textarea_field($_POST['criteria']);
    $orderby = sanitize_textarea_field($_POST['orderby']);
    $sqlselect = sanitize_textarea_field($_POST['sqlselect']);

    if (empty($reportname)) {
      add_settings_error('heritagepress_reports', 'missing_name', __('Report name is required', 'heritagepress'), 'error');
      return;
    }

    $result = $this->wpdb->insert(
      $this->reports_table,
      array(
        'reportname' => $reportname,
        'reportdesc' => $reportdesc,
        'ranking' => $ranking,
        'active' => $active,
        'display' => $display,
        'criteria' => $criteria,
        'orderby' => $orderby,
        'sqlselect' => $sqlselect
      ),
      array('%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s')
    );

    if ($result) {
      $report_id = $this->wpdb->insert_id;

      // Log the activity
      $this->log_activity('add_report', "Added new report: $reportname (ID: $report_id)");

      // Redirect based on submit button
      if (isset($_POST['save_and_edit'])) {
        wp_redirect(admin_url("admin.php?page=heritagepress-reports&action=edit&reportID=$report_id"));
      } else {
        wp_redirect(admin_url('admin.php?page=heritagepress-reports&message=created'));
      }
      exit;
    } else {
      add_settings_error('heritagepress_reports', 'create_failed', __('Failed to create report', 'heritagepress'), 'error');
    }
  }

  /**
   * Update existing report
   */
  private function update_report()
  {
    $report_id = intval($_POST['reportID']);
    $reportname = sanitize_text_field($_POST['reportname']);
    $reportdesc = sanitize_textarea_field($_POST['reportdesc']);
    $ranking = intval($_POST['ranking']);
    $active = isset($_POST['active']) ? intval($_POST['active']) : 0;
    $display = sanitize_textarea_field($_POST['display']);
    $criteria = sanitize_textarea_field($_POST['criteria']);
    $orderby = sanitize_textarea_field($_POST['orderby']);
    $sqlselect = sanitize_textarea_field($_POST['sqlselect']);

    if (!$report_id || empty($reportname)) {
      add_settings_error('heritagepress_reports', 'invalid_data', __('Invalid report data', 'heritagepress'), 'error');
      return;
    }

    $result = $this->wpdb->update(
      $this->reports_table,
      array(
        'reportname' => $reportname,
        'reportdesc' => $reportdesc,
        'ranking' => $ranking,
        'active' => $active,
        'display' => $display,
        'criteria' => $criteria,
        'orderby' => $orderby,
        'sqlselect' => $sqlselect
      ),
      array('reportID' => $report_id),
      array('%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s'),
      array('%d')
    );

    if ($result !== false) {
      $this->log_activity('update_report', "Updated report: $reportname (ID: $report_id)");
      wp_redirect(admin_url("admin.php?page=heritagepress-reports&action=edit&reportID=$report_id&message=updated"));
      exit;
    } else {
      add_settings_error('heritagepress_reports', 'update_failed', __('Failed to update report', 'heritagepress'), 'error');
    }
  }

  /**
   * Delete report
   */
  private function delete_report()
  {
    $report_id = intval($_POST['reportID']);

    if (!$report_id) {
      add_settings_error('heritagepress_reports', 'invalid_id', __('Invalid report ID', 'heritagepress'), 'error');
      return;
    }

    // Get report name for logging
    $report_name = $this->wpdb->get_var(
      $this->wpdb->prepare(
        "SELECT reportname FROM {$this->reports_table} WHERE reportID = %d",
        $report_id
      )
    );

    $result = $this->wpdb->delete(
      $this->reports_table,
      array('reportID' => $report_id),
      array('%d')
    );

    if ($result) {
      $this->log_activity('delete_report', "Deleted report: $report_name (ID: $report_id)");
      wp_redirect(admin_url('admin.php?page=heritagepress-reports&message=deleted'));
      exit;
    } else {
      add_settings_error('heritagepress_reports', 'delete_failed', __('Failed to delete report', 'heritagepress'), 'error');
    }
  }

  /**
   * Execute a report and return results
   */
  private function execute_report($report)
  {
    // If custom SQL is provided, use it
    if (!empty($report['sqlselect'])) {
      // Basic SQL injection protection
      $allowed_keywords = array('SELECT', 'FROM', 'WHERE', 'ORDER', 'BY', 'LIMIT', 'JOIN', 'LEFT', 'RIGHT', 'INNER', 'OUTER');
      $sql = trim($report['sqlselect']);

      // Very basic validation - in production you'd want more robust protection
      if (!preg_match('/^SELECT/i', $sql)) {
        return array('error' => __('Only SELECT statements are allowed', 'heritagepress'));
      }

      try {
        return $this->wpdb->get_results($sql, ARRAY_A);
      } catch (Exception $e) {
        return array('error' => __('SQL Error: ', 'heritagepress') . $e->getMessage());
      }
    }

    // Build query from display fields, criteria, and order
    return $this->build_and_execute_query($report);
  }

  /**
   * Build and execute query from report definition
   */
  private function build_and_execute_query($report)
  {
    // This is a simplified implementation
    // In the full version, you'd parse the display, criteria, and orderby fields
    // to build the appropriate SQL query

    $query = "SELECT personID, firstname, lastname, birthdate, deathdate
                  FROM {$this->people_table}
                  ORDER BY lastname, firstname
                  LIMIT 100";

    return $this->wpdb->get_results($query, ARRAY_A);
  }

  /**
   * Get field definitions for report builder
   */
  private function get_field_definitions()
  {
    return array(
      'people' => array(
        'personID' => __('Person ID', 'heritagepress'),
        'firstname' => __('First Name', 'heritagepress'),
        'lastname' => __('Last Name', 'heritagepress'),
        'fullname' => __('Full Name', 'heritagepress'),
        'birthdate' => __('Birth Date', 'heritagepress'),
        'birthplace' => __('Birth Place', 'heritagepress'),
        'deathdate' => __('Death Date', 'heritagepress'),
        'deathplace' => __('Death Place', 'heritagepress'),
        'sex' => __('Sex', 'heritagepress'),
        'living' => __('Living', 'heritagepress'),
        'private' => __('Private', 'heritagepress'),
        'gedcom' => __('Tree', 'heritagepress')
      ),
      'families' => array(
        'familyID' => __('Family ID', 'heritagepress'),
        'husband' => __('Husband', 'heritagepress'),
        'wife' => __('Wife', 'heritagepress'),
        'marrdate' => __('Marriage Date', 'heritagepress'),
        'marrplace' => __('Marriage Place', 'heritagepress'),
        'divdate' => __('Divorce Date', 'heritagepress'),
        'divplace' => __('Divorce Place', 'heritagepress')
      ),
      'places' => array(
        'placeID' => __('Place ID', 'heritagepress'),
        'place' => __('Place Name', 'heritagepress'),
        'city' => __('City', 'heritagepress'),
        'county' => __('County', 'heritagepress'),
        'state' => __('State', 'heritagepress'),
        'country' => __('Country', 'heritagepress')
      )
    );
  }

  /**
   * Get operator definitions
   */
  private function get_operator_definitions()
  {
    return array(
      'eq' => '=',
      'neq' => '!=',
      'gt' => '>',
      'gte' => '>=',
      'lt' => '<',
      'lte' => '<=',
      'contains' => __('Contains', 'heritagepress'),
      'starts_with' => __('Starts With', 'heritagepress'),
      'ends_with' => __('Ends With', 'heritagepress'),
      'is_null' => __('Is Empty', 'heritagepress'),
      'is_not_null' => __('Is Not Empty', 'heritagepress'),
      'and' => __('AND', 'heritagepress'),
      'or' => __('OR', 'heritagepress')
    );
  }

  /**
   * Log activity
   */
  private function log_activity($action, $description)
  {
    // You could integrate with WordPress logging or a custom log table
    error_log("HeritagePress Reports: $action - $description");
  }

  /**
   * Get reports for dropdown/selection
   */
  public function get_reports_list($active_only = true)
  {
    $where_clause = $active_only ? 'WHERE active = 1' : '';

    return $this->wpdb->get_results(
      "SELECT reportID, reportname, reportdesc
             FROM {$this->reports_table}
             {$where_clause}
             ORDER BY ranking, reportname",
      ARRAY_A
    );
  }

  /**
   * Check if reports table exists and create if needed
   */
  public function ensure_table_exists()
  {
    $table_name = $this->reports_table;

    if ($this->wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $charset_collate = $this->wpdb->get_charset_collate();

      $sql = "CREATE TABLE $table_name (
                reportID int(11) NOT NULL AUTO_INCREMENT,
                reportname varchar(80) NOT NULL,
                reportdesc text NOT NULL,
                ranking int(11) NOT NULL DEFAULT 1,
                display text NOT NULL,
                criteria text NOT NULL,
                orderby text NOT NULL,
                sqlselect text NOT NULL,
                active tinyint(4) NOT NULL DEFAULT 0,
                PRIMARY KEY (reportID),
                KEY reportname (reportname),
                KEY ranking (ranking)
            ) $charset_collate;";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
    }
  }
}
